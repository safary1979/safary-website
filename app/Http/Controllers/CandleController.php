<?php

namespace App\Http\Controllers;

use App\Models\Trade;
use App\Models\Bot;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use PDO;

class CandleController extends Controller
{
    private const TF_MS = [
        '1m'  =>        60_000,
        '5m'  =>       300_000,
        '15m' =>       900_000,
        '1h'  =>     3_600_000,
        '4h'  =>    14_400_000,
        '1d'  =>    86_400_000,
    ];

    private const BACKTESTER_DB = '/home/ubuntu/SafaryEngine/data/backtester.db';

    // ── Candles ──────────────────────────────────────────────────────────────

    public function candles(Request $request): JsonResponse
    {
        $exchange = $request->input('exchange', 'okx');
        $symbol   = $request->input('symbol',   'HYPE/USDT:USDT');
        $tf       = $request->input('tf',        '1h');
        $from     = (int) $request->input('from', 0);      // ms unix
        $to       = (int) $request->input('to',   PHP_INT_MAX);

        if (! isset(self::TF_MS[$tf])) {
            return response()->json(['error' => 'Invalid timeframe'], 422);
        }

        if (! file_exists(self::BACKTESTER_DB)) {
            return response()->json(['error' => 'backtester.db not found'], 500);
        }

        $intervalMs = self::TF_MS[$tf];

        // Derive market_type from symbol (futures have ':' in symbol, e.g. HYPE/USDT:USDT)
        $marketType = str_contains($symbol, ':') ? 'linear' : 'spot';

        try {
            $pdo = new PDO('sqlite:' . self::BACKTESTER_DB);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Hard limit: never return more than 2000 candles in one response
            $maxMs = $intervalMs * 2000;
            if ($to - $from > $maxMs) {
                $from = $to - $maxMs;
            }

            $baseParams = [
                ':exchange'    => $exchange,
                ':market_type' => $marketType,
                ':symbol'      => $symbol,
                ':from'        => $from,
                ':to'          => $to,
            ];

            if ($tf === '1m') {
                // Direct query — no aggregation needed
                $sql = "
                    SELECT open_time AS time, open, high, low, close, volume
                    FROM candles
                    WHERE exchange    = :exchange
                      AND market_type = :market_type
                      AND symbol      = :symbol
                      AND timeframe   = '1m'
                      AND open_time BETWEEN :from AND :to
                    ORDER BY open_time
                ";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($baseParams);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                // Three-pass aggregation using SQLite's bare-column optimization.
                //
                // SQLite bare-column rule: works ONLY when there is EXACTLY ONE min()/max()
                // in the SELECT. Having both MIN(open_time) + MIN(low) in one query would
                // return 'open' from an arbitrary row — causing open ≠ prev_close gaps.
                //
                // Pass 1 (open):  SELECT bucket, MIN(open_time), open          — one MIN only
                // Pass 2 (close): SELECT bucket, MAX(open_time), close         — one MAX only
                // Pass 3 (hlv):   SELECT bucket, MAX(high), MIN(low), SUM(vol) — pure aggregates

                $params = array_merge([':interval' => $intervalMs], $baseParams);
                $where  = "exchange=:exchange AND market_type=:market_type AND symbol=:symbol
                           AND timeframe='1m' AND open_time BETWEEN :from AND :to";

                // Pass 1: OPEN — bare column from MIN(open_time) row
                $s1 = $pdo->prepare("
                    SELECT (open_time/:interval)*:interval AS bucket, MIN(open_time), open
                    FROM candles WHERE {$where}
                    GROUP BY bucket ORDER BY bucket
                ");
                $s1->execute($params);
                $openRows = $s1->fetchAll(PDO::FETCH_ASSOC);

                // Pass 2: CLOSE — bare column from MAX(open_time) row
                $s2 = $pdo->prepare("
                    SELECT (open_time/:interval)*:interval AS bucket, MAX(open_time), close
                    FROM candles WHERE {$where}
                    GROUP BY bucket
                ");
                $s2->execute($params);
                $closeMap = [];
                while ($r = $s2->fetch(PDO::FETCH_ASSOC)) {
                    $closeMap[(int)$r['bucket']] = (float)$r['close'];
                }

                // Pass 3: HIGH, LOW, VOLUME — pure aggregations, no bare column needed
                $s3 = $pdo->prepare("
                    SELECT (open_time/:interval)*:interval AS bucket,
                           MAX(high) AS high, MIN(low) AS low, SUM(volume) AS volume
                    FROM candles WHERE {$where}
                    GROUP BY bucket
                ");
                $s3->execute($params);
                $hlvMap = [];
                while ($r = $s3->fetch(PDO::FETCH_ASSOC)) {
                    $hlvMap[(int)$r['bucket']] = [
                        'high'   => (float)$r['high'],
                        'low'    => (float)$r['low'],
                        'volume' => (float)$r['volume'],
                    ];
                }

                // Merge all three passes
                $candles = [];
                foreach ($openRows as $r) {
                    $bucket  = (int)$r['bucket'];
                    $hlv     = $hlvMap[$bucket] ?? ['high' => (float)$r['open'], 'low' => (float)$r['open'], 'volume' => 0];
                    $candles[] = [
                        'time'   => $bucket / 1000,   // ms → seconds
                        'open'   => (float)$r['open'],
                        'high'   => $hlv['high'],
                        'low'    => $hlv['low'],
                        'close'  => $closeMap[$bucket] ?? (float)$r['open'],
                        'volume' => $hlv['volume'],
                    ];
                }

                return response()->json($candles);
            }

            // Convert 1m rows to lightweight-charts format (ms → seconds)
            $candles = array_map(fn($r) => [
                'time'   => (int) $r['time'] / 1000,
                'open'   => (float) $r['open'],
                'high'   => (float) $r['high'],
                'low'    => (float) $r['low'],
                'close'  => (float) $r['close'],
                'volume' => (float) $r['volume'],
            ], $rows);

            return response()->json($candles);

        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ── Trades (markers) ─────────────────────────────────────────────────────

    public function trades(Request $request): JsonResponse
    {
        $exchange = $request->input('exchange', 'okx');
        $symbol   = $request->input('symbol',   'HYPE/USDT:USDT');
        $from     = $request->input('from');
        $to       = $request->input('to');
        $botId    = $request->input('bot_id');   // optional: filter single bot

        $query = Trade::query()->whereNotNull('exit_time');

        if ($botId) {
            // Specific bot — exact filter
            $query->where('bot_id', (int) $botId);
        } else {
            // All bots for this exchange+symbol
            $pair       = preg_replace('/:.*$/', '', $symbol);   // "HYPE/USDT:USDT" → "HYPE/USDT"
            $symbolBase = explode('/', $pair)[0] ?? $pair;        // "HYPE"
            $botIds     = Bot::where('exchange', $exchange)
                ->where('symbol', $symbolBase)
                ->pluck('id')
                ->toArray();

            if (! empty($botIds)) {
                $query->whereIn('bot_id', $botIds);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        // $from/$to may be unix timestamps (int) or ISO strings
        if ($from) {
            $fromDt = is_numeric($from)
                ? \Carbon\Carbon::createFromTimestamp((int)$from)->toDateTimeString()
                : $from;
            $query->where('entry_time', '>=', $fromDt);
        }
        if ($to) {
            $toDt = is_numeric($to)
                ? \Carbon\Carbon::createFromTimestamp((int)$to)->toDateTimeString()
                : $to;
            $query->where('entry_time', '<=', $toDt);
        }

        $trades = $query->orderBy('entry_time')->get([
            'id', 'bot_id', 'side', 'entry_time', 'exit_time',
            'entry_price', 'exit_price', 'pnl_usdt', 'pnl_pct', 'exit_reason',
        ]);

        $result = $trades->map(function ($t) {
            $pnl    = (float) $t->pnl_usdt;
            $pnlPct = (float) $t->pnl_pct;
            $win    = $pnl >= 0;

            return [
                'id'          => $t->id,
                'side'        => $t->side,
                'entry_time'  => $t->entry_time  ? (int)(strtotime($t->entry_time))  : null,
                'exit_time'   => $t->exit_time   ? (int)(strtotime($t->exit_time))   : null,
                'entry_price' => (float) $t->entry_price,
                'exit_price'  => (float) $t->exit_price,
                'pnl_usdt'    => $pnl,
                'pnl_pct'     => $pnlPct,
                'exit_reason' => $t->exit_reason,
                'win'         => $win,
            ];
        });

        return response()->json($result);
    }
}
