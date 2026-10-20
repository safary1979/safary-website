<?php

namespace App\Http\Controllers;

use App\Models\Trade;
use App\Models\Bot;
use App\Models\BotState;
use App\Services\FreqtradeGatewayService;
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

    private const MARKET_DATA_DB = '/home/ubuntu/website/storage/app/market-data/market_data.sqlite';
    private const LONG_LIVE_FEED = '/home/ubuntu/SafaryEngine/data/strategy_search/live_long_regime_feed_latest.json';
    private const SHORT_SIGNAL_FEED = '/home/ubuntu/SafaryEngine/data/strategy_search/edge_factory/executable_quality/hype_shadow_router_v2_signal_latest.json';
    private const LIVE_ENTRY_DECISION_PREVIEW = '/home/ubuntu/SafaryEngine/data/strategy_search/live_entry_decision_preview_latest.json';
    private const SIGNAL_EVENTS_DB = '/home/ubuntu/SafaryEngine/data/strategy_search/signal_events.db';
    private const CANDIDATE_SELECTION_REPORT = '/home/ubuntu/SafaryEngine/data/strategy_search/candidate_selection_report_latest.json';
    private const REVIEW_OVERLAY_CANDIDATES = [
        'direct_long_protected_cc5c1e93e9',
    ];
    private const CANDIDATE_NOTIONAL_USDT = 25.0;
    private const CANDIDATE_FEE_ROUNDTRIP_PCT = 0.10;

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

        if (! file_exists(self::MARKET_DATA_DB)) {
            return response()->json(['error' => 'market data is not initialized'], 503);
        }

        $intervalMs = self::TF_MS[$tf];

        // Derive market_type from symbol (futures have ':' in symbol, e.g. HYPE/USDT:USDT)
        $marketType = str_contains($symbol, ':') ? 'linear' : 'spot';

        try {
            $pdo = new PDO('sqlite:' . self::MARKET_DATA_DB);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // 1h keeps a longer history window than the other timeframes.
            $maxCandles = $tf === '1h' ? 5000 : 2000;
            $maxMs = $intervalMs * $maxCandles;
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

    private function legacyBotDataAvailable(): bool
    {
        return is_file((string) config('cabinet.bot_db_path'));
    }

    public function trades(Request $request): JsonResponse
    {
        // The market chart must remain usable when the former Safary live
        // dashboard is absent.  Trade overlays are optional presentation data.
        if (! $this->legacyBotDataAvailable()) {
            return response()->json([]);
        }

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
            'id', 'bot_id', 'exchange', 'pair', 'side', 'entry_time', 'exit_time',
            'entry_price', 'exit_price', 'pnl_usdt', 'pnl_pct', 'exit_reason',
        ]);

        $candlePdo = null;
        if (file_exists(self::MARKET_DATA_DB)) {
            try {
                $candlePdo = new PDO('sqlite:' . self::MARKET_DATA_DB);
                $candlePdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (\Throwable) {
                $candlePdo = null;
            }
        }

        $result = $trades->map(function ($t) use ($candlePdo) {
            $pnl    = (float) $t->pnl_usdt;
            $pnlPct = (float) $t->pnl_pct;
            $win    = $pnl >= 0;
            $exitTs = $t->exit_time ? (int)(strtotime($t->exit_time)) : null;
            $markerTs = $this->inferTradeTouchTime($candlePdo, $t) ?: $exitTs;

            return [
                'id'          => $t->id,
                'side'        => $t->side,
                'entry_time'  => $t->entry_time  ? (int)(strtotime($t->entry_time))  : null,
                'exit_time'   => $exitTs,
                'marker_time' => $markerTs,
                'entry_price' => (float) $t->entry_price,
                'exit_price'  => (float) $t->exit_price,
                'pnl_usdt'    => $pnl,
                'pnl_pct'     => $pnlPct,
                'price_move_pct' => $this->tradePriceMovePct($t),
                'exit_reason' => $t->exit_reason,
                'win'         => $win,
            ];
        });

        return response()->json($result);
    }

    public function position(Request $request): JsonResponse
    {
        if (! $this->legacyBotDataAvailable()) {
            return response()->json(['position' => null]);
        }

        $botId = (int) $request->input('bot_id', 0);

        if ($botId <= 0) {
            return response()->json(['error' => 'bot_id is required'], 422);
        }

        $bot = Bot::find($botId);
        if (! $bot) {
            return response()->json(['error' => 'Bot not found'], 404);
        }

        $state = BotState::find($botId);
        $position = $state?->position_json;
        if (is_string($position)) {
            $position = json_decode($position, true);
        }

        if (! is_array($position) || empty($position)) {
            return response()->json(['position' => null]);
        }

        $entry = (float) ($position['avg_price'] ?? $position['entry_price'] ?? 0);
        if ($entry <= 0) {
            return response()->json(['position' => null]);
        }

        $config = $this->loadBotConfig($bot->config_path ?? null);
        $side = strtolower((string) ($position['side_str'] ?? ''));

        $entryLane = $config['entry_conditions'][0]['lanes'][0] ?? null;
        $profileConfig = $entryLane && isset($config['entry_exit_profiles'][$entryLane]) && is_array($config['entry_exit_profiles'][$entryLane])
            ? $config['entry_exit_profiles'][$entryLane]
            : [];

        $tpPct = (float) ($profileConfig['take_profit_pct'] ?? $config['take_profit_pct'] ?? 0);
        $slPct = (float) ($profileConfig['stop_loss_pct'] ?? $config['stop_loss_pct'] ?? 0);
        $slEnabled = (bool) ($config['stop_loss_enabled'] ?? ($slPct > 0));

        $takeProfit = null;
        $stopLoss = null;

        if ($side === 'long') {
            if ($tpPct > 0) {
                $takeProfit = $entry * (1 + $tpPct / 100);
            }
            if ($slEnabled && $slPct > 0) {
                $stopLoss = $entry * (1 - $slPct / 100);
            }
        } elseif ($side === 'short') {
            if ($tpPct > 0) {
                $takeProfit = $entry * (1 - $tpPct / 100);
            }
            if ($slEnabled && $slPct > 0) {
                $stopLoss = $entry * (1 + $slPct / 100);
            }
        }

        return response()->json([
            'position' => [
                'bot_id' => $botId,
                'side' => $side,
                'entry_price' => $entry,
                'opened_at' => isset($position['opened_at']) ? (int) $position['opened_at'] : null,
                'opened_at_ms' => isset($position['opened_at_ms']) ? (int) $position['opened_at_ms'] : null,
                'qty' => (float) ($position['qty'] ?? 0),
                'pnl_usdt' => (float) ($position['pnl_usdt'] ?? 0),
                'pnl_pct' => (float) ($position['pnl_pct'] ?? 0),
                'take_profit' => $takeProfit,
                'stop_loss' => $stopLoss,
                'take_profit_pct' => $tpPct,
                'stop_loss_pct' => $slPct,
            ],
        ]);
    }

    /** Gateway-backed position and completed trades for a non-legacy bot. */
    public function botContext(Request $request): JsonResponse
    {
        $botRef = (string) $request->input('bot_ref', '');
        $context = app(FreqtradeGatewayService::class)->botContext($botRef);

        if ($context === null) {
            return response()->json(['error' => 'Bot context not found'], 404);
        }
        if (! ($context['available'] ?? false)) {
            return response()->json($context, 503);
        }

        return response()->json($context);
    }

    // ── Candidate backtest overlay (read-only chart markers) ───────────────

    public function candidateTrades(Request $request): JsonResponse
    {
        $exchange = $request->input('exchange', 'okx');
        $symbol = $request->input('symbol', 'HYPE/USDT:USDT');
        $candidateId = (string) $request->input('candidate', '');
        $options = $this->candidateOverlayOptions();

        if ($request->boolean('meta')) {
            return response()->json([
                'options' => array_map(fn ($option) => $this->publicCandidateOption($option), array_values($options)),
                'default' => $this->defaultCandidateOverlayId($options),
            ]);
        }

        if ($candidateId === '' || ! isset($options[$candidateId])) {
            return response()->json([
                'candidate' => null,
                'trades' => [],
                'options' => array_map(fn ($option) => $this->publicCandidateOption($option), array_values($options)),
            ]);
        }

        if (! file_exists(self::MARKET_DATA_DB)) {
            return response()->json(['error' => 'market data is not initialized'], 503);
        }

        $fromMs = $this->requestTimeMs($request->input('from'));
        $toMs = $this->requestTimeMs($request->input('to'));
        $option = $options[$candidateId];
        $entries = $this->candidateOverlayEntries($option);

        try {
            $pdo = new PDO('sqlite:' . self::MARKET_DATA_DB);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $trades = [];
            foreach ($entries as $entry) {
                $openMs = (int) ($entry['open_time'] ?? 0);
                if ($openMs <= 0) {
                    continue;
                }
                if ($fromMs && $openMs < $fromMs) {
                    continue;
                }
                if ($toMs && $openMs > $toMs) {
                    continue;
                }

                $trade = $this->simulateCandidateOverlayTrade($pdo, $exchange, $symbol, $option, $entry);
                if ($trade) {
                    $trades[] = $trade;
                }
            }

            usort($trades, fn ($a, $b) => ($a['entry_time'] ?? 0) <=> ($b['entry_time'] ?? 0));

            return response()->json([
                'candidate' => $this->publicCandidateOption($option),
                'count' => count($trades),
                'trades' => $trades,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function loadBotConfig(?string $path): array
    {
        if (! $path || ! is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) @file_get_contents($path), true);
        return is_array($decoded) ? $decoded : [];
    }

    private function candidateOverlayOptions(): array
    {
        $options = [];
        $liveIds = $this->liveOverlayCandidateIds();

        $longFeed = $this->readJsonFile(self::LONG_LIVE_FEED);
        $longIdx = 0;
        foreach (($longFeed['candidates'] ?? []) as $candidate) {
            if (! is_array($candidate)) {
                continue;
            }
            $id = (string) ($candidate['id'] ?? $candidate['candidate_id'] ?? '');
            if ($id === '') {
                continue;
            }
            if (! in_array($id, $liveIds['long'], true)) {
                continue;
            }
            $longIdx++;
            $liveTag = 'LV-L' . $longIdx;
            $profile = (string) ($candidate['profile'] ?? 'time_exit_120m');
            $counts = $candidate['counts'] ?? [];
            $recentCount = count($candidate['recent_potential_trades'] ?? []);
            $options[$id] = [
                'id' => $id,
                'live_tag' => $liveTag,
                'label' => $liveTag . ' · LONG · ' . $id,
                'side' => 'long',
                'profile' => $profile,
                'source' => 'long_live_feed',
                'role' => (string) ($candidate['role'] ?? ''),
                'available_count' => $recentCount ?: (int) ($counts['potential_trades'] ?? 0),
            ];
        }

        foreach ($this->reviewCandidateOverlayOptions() as $id => $option) {
            if (! isset($options[$id])) {
                $options[$id] = $option;
            }
        }

        $shortOptions = [
            'short_v9_frequency_shadow_001' => [
                'label' => 'Replay live-candidate short · frequency 001 · TP1/SL0.75',
                'component' => 'short_v9_frequency_shadow',
                'lane' => 'short_v9_frequency_shadow_001',
                'profile' => 'tp100_sl075_h90',
            ],
            'short_v9_frequency_shadow_002' => [
                'label' => 'Replay live-candidate short · frequency 002 · TP1/SL0.75',
                'component' => 'short_v9_frequency_shadow',
                'lane' => 'short_v9_frequency_shadow_002',
                'profile' => 'tp100_sl075_h90',
            ],
            'short_v9_only_time60' => [
                'label' => 'Replay live-candidate short · v9 only · time exit 60m',
                'component' => 'short_v9_only',
                'lane' => 'short_v9_rejection_refined_001',
                'profile' => 'time_exit_60m',
            ],
        ];

        $shortFeed = $this->readJsonFile(self::SHORT_SIGNAL_FEED);
        $shortSignals = is_array($shortFeed['signals'] ?? null) ? $shortFeed['signals'] : [];
        $shortIdx = 0;
        foreach ($shortOptions as $id => $meta) {
            if (! in_array($id, $liveIds['short'], true)) {
                continue;
            }
            $shortIdx++;
            $liveTag = 'LV-S' . $shortIdx;
            $count = 0;
            foreach ($shortSignals as $signal) {
                if (! is_array($signal)) {
                    continue;
                }
                if (($signal['component'] ?? '') === $meta['component'] && ($signal['lane'] ?? '') === $meta['lane']) {
                    $count++;
                }
            }
            $options[$id] = [
                'id' => $id,
                'live_tag' => $liveTag,
                'label' => $liveTag . ' · SHORT · ' . $meta['label'],
                'side' => 'short',
                'profile' => $meta['profile'],
                'source' => 'short_signal_feed',
                'component' => $meta['component'],
                'lane' => $meta['lane'],
                'available_count' => $count,
            ];
        }

        return $options;
    }

    private function reviewCandidateOverlayOptions(): array
    {
        $report = $this->readJsonFile(self::CANDIDATE_SELECTION_REPORT);
        $rows = [];
        foreach (['long', 'short'] as $side) {
            foreach (($report['top_non_live'][$side] ?? []) as $row) {
                if (is_array($row)) {
                    $rows[] = $row;
                }
            }
        }

        $options = [];
        $idxBySide = ['long' => 0, 'short' => 0];
        foreach ($rows as $row) {
            $id = (string) ($row['candidate_id'] ?? $row['normalized_candidate_id'] ?? '');
            if ($id === '' || ! in_array($id, self::REVIEW_OVERLAY_CANDIDATES, true)) {
                continue;
            }

            $side = strtolower((string) ($row['side'] ?? ''));
            if (! in_array($side, ['long', 'short'], true)) {
                continue;
            }

            $idxBySide[$side]++;
            $tag = ($side === 'long' ? 'RV-L' : 'RV-S') . $idxBySide[$side];
            $now = is_array($row['now_availability'] ?? null) ? $row['now_availability'] : [];
            $hours = 24;
            $count = (int) ($now['last_24h_potential_entries'] ?? 0);

            $options[$id] = [
                'id' => $id,
                'live_tag' => $tag,
                'label' => $tag . ' · REVIEW · ' . strtoupper($side) . ' · ' . $id,
                'side' => $side,
                'profile' => (string) ($row['profile'] ?? ''),
                'source' => 'signal_events_recent',
                'recent_hours' => $hours,
                'available_count' => $count,
            ];
        }

        return $options;
    }

    private function liveOverlayCandidateIds(): array
    {
        $data = $this->readJsonFile(self::LIVE_ENTRY_DECISION_PREVIEW);
        $out = ['long' => [], 'short' => []];

        foreach (($data['decisions'] ?? []) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $side = strtolower((string) ($row['side'] ?? ''));
            if (! in_array($side, ['long', 'short'], true)) {
                continue;
            }

            $event = is_array($row['event_driven'] ?? null) ? $row['event_driven'] : [];
            if (empty($event['available'])) {
                continue;
            }

            $id = (string) ($event['candidate_id'] ?? $row['candidate_id'] ?? $row['lane'] ?? '');
            if ($side === 'short' && str_contains($id, ':')) {
                $parts = explode(':', $id);
                $id = $parts[1] ?? $id;
            }
            if ($id !== '' && ! in_array($id, $out[$side], true)) {
                $out[$side][] = $id;
            }
        }

        return $out;
    }

    private function publicCandidateOption(array $option): array
    {
        return [
            'id' => (string) ($option['id'] ?? ''),
            'live_tag' => (string) ($option['live_tag'] ?? ''),
            'label' => (string) ($option['label'] ?? ($option['id'] ?? 'candidate')),
            'side' => (string) ($option['side'] ?? ''),
            'profile' => (string) ($option['profile'] ?? ''),
            'available_count' => (int) ($option['available_count'] ?? 0),
        ];
    }

    private function defaultCandidateOverlayId(array $options): ?string
    {
        foreach (['long_momentum_slope_rebound_time120_v1', 'long_mapped_pullback_tp150_v1', 'short_v9_frequency_shadow_001'] as $id) {
            if (isset($options[$id]) && (int) ($options[$id]['available_count'] ?? 0) > 0) {
                return $id;
            }
        }
        foreach ($options as $id => $option) {
            if ((int) ($option['available_count'] ?? 0) > 0) {
                return (string) $id;
            }
        }
        return null;
    }

    private function candidateOverlayEntries(array $option): array
    {
        if (($option['source'] ?? '') === 'long_live_feed') {
            $feed = $this->readJsonFile(self::LONG_LIVE_FEED);
            $id = (string) ($option['id'] ?? '');
            $rows = [];
            foreach (($feed['candidates'] ?? []) as $candidate) {
                if (! is_array($candidate)) {
                    continue;
                }
                $candidateId = (string) ($candidate['id'] ?? $candidate['candidate_id'] ?? '');
                if ($candidateId !== $id) {
                    continue;
                }
                foreach (($candidate['recent_potential_trades'] ?? []) as $row) {
                    if (is_array($row)) {
                        $rows[] = $row;
                    }
                }
            }
            if (! $rows) {
                foreach (($feed['recent_signals'] ?? []) as $row) {
                    if (is_array($row) && (string) ($row['candidate_id'] ?? $row['id'] ?? '') === $id) {
                        $rows[] = $row;
                    }
                }
            }
            return $this->dedupeCandidateEntries($rows);
        }

        if (($option['source'] ?? '') === 'short_signal_feed') {
            $feed = $this->readJsonFile(self::SHORT_SIGNAL_FEED);
            $component = (string) ($option['component'] ?? '');
            $lane = (string) ($option['lane'] ?? '');
            $rows = [];
            foreach (($feed['signals'] ?? []) as $row) {
                if (! is_array($row)) {
                    continue;
                }
                if ((string) ($row['component'] ?? '') === $component && (string) ($row['lane'] ?? '') === $lane) {
                    $rows[] = $row;
                }
            }
            return $this->dedupeCandidateEntries($rows);
        }

        if (($option['source'] ?? '') === 'signal_events_recent') {
            return $this->candidateOverlaySignalEventEntries($option);
        }

        return [];
    }

    private function candidateOverlaySignalEventEntries(array $option): array
    {
        if (! is_file(self::SIGNAL_EVENTS_DB)) {
            return [];
        }

        $id = (string) ($option['id'] ?? '');
        $side = strtolower((string) ($option['side'] ?? ''));
        $recentHours = max(1, (int) ($option['recent_hours'] ?? 24));
        if ($id === '') {
            return [];
        }

        try {
            $pdo = new PDO('sqlite:' . self::SIGNAL_EVENTS_DB);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $fromOpen = (int) (time() * 1000) - $recentHours * 60 * 60 * 1000;
            $stmt = $pdo->prepare("
                SELECT candidate_id, component, lane, profile, side, open_time, signal_price, quality_score, router_rank, payload_json
                FROM signal_events
                WHERE candidate_id = :candidate_id
                  AND (:side = '' OR side = :side)
                  AND open_time >= :from_open
                ORDER BY open_time
            ");
            $stmt->execute([
                ':candidate_id' => $id,
                ':side' => $side,
                ':from_open' => $fromOpen,
            ]);

            $rows = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $payload = json_decode((string) ($row['payload_json'] ?? ''), true);
                $payload = is_array($payload) ? $payload : [];
                $rows[] = [
                    'candidate_id' => (string) ($row['candidate_id'] ?? $id),
                    'component' => (string) ($row['component'] ?? ''),
                    'lane' => (string) ($row['lane'] ?? ''),
                    'profile' => (string) ($row['profile'] ?? ($option['profile'] ?? '')),
                    'side' => (string) ($row['side'] ?? ($option['side'] ?? '')),
                    'open_time' => (int) ($row['open_time'] ?? 0),
                    'signal_price' => isset($row['signal_price']) ? (float) $row['signal_price'] : null,
                    'quality_score' => isset($row['quality_score']) ? (float) $row['quality_score'] : null,
                    'router_rank' => isset($row['router_rank']) ? (int) $row['router_rank'] : null,
                    'entry_delay_bars' => (int) ($payload['entry_delay_bars'] ?? 0),
                ];
            }

            return $this->dedupeCandidateEntries($rows);
        } catch (\Throwable) {
            return [];
        }
    }

    private function dedupeCandidateEntries(array $rows): array
    {
        $seen = [];
        $out = [];
        foreach ($rows as $row) {
            $key = (string) ($row['open_time'] ?? '') . '|' . (string) ($row['candidate_id'] ?? $row['id'] ?? $row['lane'] ?? '');
            if ($key === '|' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $row;
        }
        usort($out, fn ($a, $b) => (int) ($a['open_time'] ?? 0) <=> (int) ($b['open_time'] ?? 0));
        return $out;
    }

    private function simulateCandidateOverlayTrade(PDO $pdo, string $exchange, string $symbol, array $option, array $entry): ?array
    {
        $side = strtolower((string) ($entry['side'] ?? $option['side'] ?? ''));
        if (! in_array($side, ['long', 'short'], true)) {
            return null;
        }

        $profile = (string) ($entry['profile'] ?? $entry['exit_policy'] ?? $option['profile'] ?? '');
        $spec = $this->candidateProfileSpec($profile);
        if (! $spec) {
            return null;
        }

        $openMs = (int) ($entry['open_time'] ?? 0);
        $delayBars = max(0, (int) ($entry['entry_delay_bars'] ?? 0));
        $entryMs = $openMs + $delayBars * 60_000;
        $hold = (int) $spec['hold'];
        $marketType = str_contains($symbol, ':') ? 'linear' : 'spot';

        $stmt = $pdo->prepare("
            SELECT open_time, open, high, low, close
            FROM candles
            WHERE exchange = :exchange
              AND market_type = :market_type
              AND symbol = :symbol
              AND timeframe = '1m'
              AND open_time BETWEEN :from_ms AND :to_ms
            ORDER BY open_time
        ");
        $stmt->execute([
            ':exchange' => $exchange,
            ':market_type' => $marketType,
            ':symbol' => $symbol,
            ':from_ms' => $entryMs,
            ':to_ms' => $entryMs + $hold * 60_000,
        ]);
        $candles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($candles) < 1) {
            return null;
        }

        $entryCandle = $candles[0];
        $entryPrice = (float) ($entryCandle['close'] ?? 0);
        if ($entryPrice <= 0) {
            return null;
        }

        $exitPrice = null;
        $exitMs = null;
        $reason = 'max_holding_bars';

        $tpPct = (float) ($spec['tp'] ?? 0);
        $slPct = (float) ($spec['sl'] ?? 0);

        if ($tpPct > 0 && $slPct > 0) {
            $tpPrice = $side === 'long'
                ? $entryPrice * (1 + $tpPct / 100)
                : $entryPrice * (1 - $tpPct / 100);
            $slPrice = $side === 'long'
                ? $entryPrice * (1 - $slPct / 100)
                : $entryPrice * (1 + $slPct / 100);

            $availableBars = count($candles) - 1;
            for ($i = 1; $i <= min($hold, $availableBars); $i++) {
                $candle = $candles[$i] ?? null;
                if (! $candle) {
                    break;
                }
                $high = (float) $candle['high'];
                $low = (float) $candle['low'];
                $tpHit = $side === 'long' ? $high >= $tpPrice : $low <= $tpPrice;
                $slHit = $side === 'long' ? $low <= $slPrice : $high >= $slPrice;

                if ($tpHit && ! $slHit) {
                    $exitPrice = $tpPrice;
                    $exitMs = (int) $candle['open_time'];
                    $reason = 'take_profit';
                    break;
                }
                if ($slHit) {
                    // Mirrors search label convention: same-bar TP/SL tie is treated as SL.
                    $exitPrice = $slPrice;
                    $exitMs = (int) $candle['open_time'];
                    $reason = 'stop_loss';
                    break;
                }
            }
        }

        if ($exitPrice === null || $exitMs === null) {
            if (count($candles) < $hold + 1) {
                return [
                    'id' => (string) ($option['id'] ?? 'candidate') . ':' . $entryMs,
                    'candidate_id' => (string) ($option['id'] ?? ''),
                    'side' => $side,
                    'entry_time' => (int) ($entryMs / 1000),
                    'exit_time' => null,
                    'marker_time' => null,
                    'entry_price' => round($entryPrice, 8),
                    'exit_price' => null,
                    'pnl_usdt' => 0.0,
                    'pnl_pct' => null,
                    'price_move_pct' => null,
                    'exit_reason' => 'open_or_incomplete',
                    'profile' => $profile,
                    'quality_score' => isset($entry['quality_score']) ? round((float) $entry['quality_score'], 6) : null,
                    'win' => null,
                ];
            }
            $exitCandle = $candles[$hold] ?? null;
            if (! $exitCandle) {
                return null;
            }
            $exitPrice = (float) $exitCandle['close'];
            $exitMs = (int) $exitCandle['open_time'];
            $reason = 'max_holding_bars';
        }

        $movePct = $side === 'long'
            ? ($exitPrice / $entryPrice - 1.0) * 100.0
            : (1.0 - $exitPrice / $entryPrice) * 100.0;
        $netPct = $movePct - self::CANDIDATE_FEE_ROUNDTRIP_PCT;

        return [
            'id' => (string) ($option['id'] ?? 'candidate') . ':' . $entryMs,
            'candidate_id' => (string) ($option['id'] ?? ''),
            'side' => $side,
            'entry_time' => (int) ($entryMs / 1000),
            'exit_time' => (int) ($exitMs / 1000),
            'marker_time' => (int) ($exitMs / 1000),
            'entry_price' => round($entryPrice, 8),
            'exit_price' => round($exitPrice, 8),
            'pnl_usdt' => round($netPct / 100.0 * self::CANDIDATE_NOTIONAL_USDT, 6),
            'pnl_pct' => round($netPct, 6),
            'price_move_pct' => round($movePct, 6),
            'exit_reason' => $reason,
            'profile' => $profile,
            'quality_score' => isset($entry['quality_score']) ? round((float) $entry['quality_score'], 6) : null,
            'win' => $netPct >= 0,
        ];
    }

    private function candidateProfileSpec(string $profile): ?array
    {
        if (preg_match('/time_exit_(\d+)(?:m|b)?/', $profile, $m)) {
            return ['tp' => 0.0, 'sl' => 0.0, 'hold' => max(1, (int) $m[1])];
        }
        if (preg_match('/tp(\d+)_sl(\d+)_h(\d+)/', $profile, $m)) {
            return [
                'tp' => ((float) ((int) $m[1])) / 100.0,
                'sl' => ((float) ((int) $m[2])) / 100.0,
                'hold' => max(1, (int) $m[3]),
            ];
        }
        return null;
    }

    private function requestTimeMs(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            $time = (int) $value;
            return $time < 100_000_000_000 ? $time * 1000 : $time;
        }
        $ts = strtotime((string) $value);
        return $ts ? $ts * 1000 : null;
    }

    private function readJsonFile(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }
        $decoded = json_decode((string) @file_get_contents($path), true);
        return is_array($decoded) ? $decoded : [];
    }

    private function inferTradeTouchTime(?PDO $pdo, Trade $trade): ?int
    {
        if (! $pdo || ! $trade->entry_time || ! $trade->exit_time || ! $trade->exit_price) {
            return null;
        }

        $reason = strtolower((string) $trade->exit_reason);
        if (! str_contains($reason, 'take_profit') && ! str_contains($reason, 'stop_loss')) {
            return null;
        }

        $side = strtolower((string) $trade->side);
        $entryMs = (int) (strtotime((string) $trade->entry_time) * 1000);
        $exitMs = (int) (strtotime((string) $trade->exit_time) * 1000);
        $exitPrice = (float) $trade->exit_price;
        $marketType = str_contains((string) $trade->pair, ':') ? 'linear' : 'spot';

        $touchSql = match (true) {
            str_contains($reason, 'take_profit') && $side === 'short' => 'low <= :price',
            str_contains($reason, 'take_profit') && $side === 'long' => 'high >= :price',
            str_contains($reason, 'stop_loss') && $side === 'short' => 'high >= :price',
            str_contains($reason, 'stop_loss') && $side === 'long' => 'low <= :price',
            default => '',
        };
        if ($touchSql === '') {
            return null;
        }

        try {
            $stmt = $pdo->prepare("
                SELECT open_time
                FROM candles
                WHERE exchange = :exchange
                  AND market_type = :market_type
                  AND symbol = :symbol
                  AND timeframe = '1m'
                  AND open_time BETWEEN :entry_ms AND :exit_ms
                  AND {$touchSql}
                ORDER BY open_time
                LIMIT 1
            ");
            $stmt->execute([
                ':exchange' => $trade->exchange,
                ':market_type' => $marketType,
                ':symbol' => $trade->pair,
                ':entry_ms' => $entryMs,
                ':exit_ms' => $exitMs,
                ':price' => $exitPrice,
            ]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? (int) ((int) $row['open_time'] / 1000) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function tradePriceMovePct(Trade $trade): float
    {
        $entry = (float) $trade->entry_price;
        $exit = (float) $trade->exit_price;
        if ($entry <= 0 || $exit <= 0) {
            return 0.0;
        }
        $move = ($exit - $entry) / $entry * 100.0;
        return strtolower((string) $trade->side) === 'short' ? -$move : $move;
    }
}
