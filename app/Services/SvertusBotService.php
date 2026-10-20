<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Read-only cards for the two live Bybit bots in /home/ubuntu/Svertus, shaped like the
 * Gateway "nautilus" bots so the dashboard renders them with the same card code.
 * Reads only status.json, state.json, events.jsonl, runtime.conf, mode.env and the portfolio file.
 */
class SvertusBotService
{
    private const ROOT = '/home/ubuntu/Svertus';
    private const BOTS = [
        ['dir' => 'p7_runtime', 'unit' => 'p7-runtime', 'pair' => 'SOL/USDT:USDT'],
        ['dir' => 'btc_ref_runtime', 'unit' => 'btc-ref-runtime', 'pair' => 'BTC/USDT:USDT'],
    ];
    private const STALE_STATUS_SECONDS = 180;
    private const EVENT_TAIL_BYTES = 262144;

    /** @return \stdClass[] */
    public function bots(): array
    {
        return array_map(fn (array $bot) => $this->load($bot), self::BOTS);
    }

    private function load(array $def): \stdClass
    {
        $dir = self::ROOT . '/' . $def['dir'];
        $conf = $this->parseEnvFile($dir . '/runtime.conf');
        $status = $this->readJson($dir . '/logs/status.json');
        $state = $this->readJson($dir . '/state.json');
        $portfolio = $this->readJson($dir . '/' . ($conf['PORTFOLIO'] ?? ''));
        $events = $this->readEvents($dir . '/logs/events.jsonl');

        $age = null;
        if (! empty($status['updated'])) {
            try {
                $age = max(0, Carbon::now('UTC')->timestamp - Carbon::parse($status['updated'], 'UTC')->timestamp);
            } catch (\Throwable) {
            }
        }
        $alive = $age !== null && $age <= self::STALE_STATUS_SECONDS;
        $dataOk = (bool) ($status['data_ok'] ?? false);

        $sides = array_values(array_unique(array_map(
            fn ($leg) => (string) ($leg['candidate']['side'] ?? ''),
            (array) ($portfolio['legs'] ?? [])
        )));
        $active = is_array($state['active'] ?? null) ? $state['active'] : null;
        $positionOpen = $active !== null && in_array($active['phase'] ?? '', ['open', 'exiting'], true);
        $direction = $positionOpen
            ? (string) $active['side']
            : (count($sides) === 1 && $sides[0] !== '' ? $sides[0] : 'long/short');

        $strategyId = $conf['STRATEGY_ID'] ?? $def['unit'];
        $leverage = (float) ($portfolio['venue_leverage'] ?? 1);
        // Gateway mark price for the pair (same source as the ByNau cards); the bot's 5-minute heartbeat price
        // is only a fallback for pairs the Gateway does not carry (BTC).
        $mark = $this->gatewayMarkPrice($def['pair']) ?? (float) ($events['heartbeat']['price'] ?? 0);

        $bot = new \stdClass();
        $bot->id = null;
        $bot->bot_ref = null;
        $bot->source = 'nautilus';
        $bot->control_id = '';
        $bot->slug = $def['unit'];
        $bot->display_name = $strategyId . ' · ' . strtok($def['pair'], '/');
        $bot->symbol = $def['pair'];
        $bot->pair = $def['pair'];
        $bot->exchange = 'bybit';
        $bot->market_exchange = 'bybit';
        $bot->execution_exchange = 'bybit';
        $bot->direction = $direction;
        $bot->timeframe = null;
        $bot->leverage = $leverage;
        $bot->runtime_error = $alive && ! $dataOk ? 'Проблема з даними ринку' : null;
        $bot->started_at_utc = $events['started_at'];
        $bot->warning_history = [];
        $bot->active_warnings = [];
        $bot->margin = null;
        $bot->dca = null;
        $bot->status = ! $alive ? 'stale' : ($dataOk ? 'running' : 'blocked');
        $bot->account = 'Svertus';
        $bot->run_mode = 'live';
        $bot->config_path = null;
        $bot->readonly = true;

        $stateObj = new \stdClass();
        $stateObj->mark_price = $mark;
        $stateObj->position_json = null;
        if ($positionOpen) {
            $avg = (float) ($active['average_price'] ?? 0);
            $qty = (float) ($active['quantity'] ?? 0);
            $sign = ($active['side'] ?? '') === 'short' ? -1 : 1;
            $notional = $avg * $qty;
            $pnl = $avg > 0 && $mark > 0 ? ($mark - $avg) * $qty * $sign : 0.0;
            $tp = (float) ($active['take_profit_price'] ?? 0);
            $sl = (float) ($active['stop_loss_price'] ?? 0);
            $stateObj->position_json = [
                'side_str' => (string) $active['side'],
                'avg_price' => $avg,
                'pnl_usdt' => $pnl,
                'pnl_pct' => $notional > 0 ? $pnl / ($notional / max(1.0, $leverage)) * 100 : 0.0,
                'notional' => $notional,
                'qty' => $qty,
                'opened_at' => null,
                'bars_held' => 0,
                'entry_signal' => ['candidate_id' => (string) ($active['leg'] ?? ''), 'strategy' => 'NautilusTrader'],
                'take_profit' => $tp,
                'take_profit_pct' => $avg > 0 && $tp > 0 ? abs($tp - $avg) / $avg * 100 : 0.0,
                'stop_loss' => $sl,
                'stop_loss_pct' => $avg > 0 && $sl > 0 ? abs($sl - $avg) / $avg * 100 : 0.0,
            ];
        }
        $bot->state = $stateObj;
        // Totals come from the bot's own book (state.json). Profitable trades are the recorded closed positions
        // (Nautilus log and EXIT_FILLED events); the book's remaining trades have no record and are the earlier
        // stop-loss trades (user, 10.10), so they count as losses.
        $closedTotal = (int) ($state['closed_trades'] ?? 0);
        $wins = count(array_filter($this->closedTrades($dir, $strategyId), fn ($t) => $t->pnl_usdt > 0));
        $bot->net_stats = [
            'pnl_usdt' => (float) ($state['realized_pnl_usdt'] ?? 0),
            'trades' => $closedTotal,
            'wins' => min($wins, $closedTotal),
            'losses' => max(0, $closedTotal - $wins),
        ];

        return $bot;
    }

    /** Top dashboard cards for the Svertus view, in the shape of FreqtradeGatewayService::statsFromData(). */
    public function stats(array $bots): array
    {
        $closedPnl = 0.0;
        $closedTrades = 0;
        $wins = 0;
        $unrealized = 0.0;
        $active = 0;
        $inTrade = 0;
        foreach ($bots as $bot) {
            $closedPnl += $bot->net_stats['pnl_usdt'];
            $closedTrades += $bot->net_stats['trades'];
            $wins += $bot->net_stats['wins'];
            if (in_array($bot->status, ['running', 'blocked'], true)) {
                $active++;
            }
            if ($bot->state->position_json) {
                $inTrade++;
                $unrealized += (float) $bot->state->position_json['pnl_usdt'];
            }
        }
        $winRate = $closedTrades > 0 ? round($wins / $closedTrades * 100, 1) : 0;
        $signed = fn (float $v) => ($v >= 0 ? '+' : '') . number_format($v, 2) . ' USDT';

        return [
            ['label' => 'PnL закриті', 'value' => $signed($closedPnl), 'sub' => "{$closedTrades} угод", 'color' => $closedPnl >= 0 ? '#22c55e' : '#ef4444'],
            ['label' => 'PnL нереалізований', 'value' => $signed($unrealized), 'sub' => "{$inTrade} в угоді", 'color' => $unrealized >= 0 ? '#22c55e' : '#ef4444'],
            ['label' => 'Win Rate', 'value' => $winRate . '%', 'sub' => "{$wins}/{$closedTrades} прибуткових", 'color' => $winRate >= 50 ? '#22c55e' : '#f59e0b'],
            ['label' => 'Боти', 'value' => "{$active} active · {$inTrade} in trade", 'sub' => count($bots) . ' всього', 'color' => '#60a5fa'],
        ];
    }

    /**
     * Closed positions of both bots for the "Останні угоди" table, newest first.
     *
     * @return \stdClass[]
     */
    public function trades(): array
    {
        $trades = [];
        foreach (self::BOTS as $def) {
            $dir = self::ROOT . '/' . $def['dir'];
            $conf = $this->parseEnvFile($dir . '/runtime.conf');
            array_push($trades, ...$this->closedTrades($dir, $conf['STRATEGY_ID'] ?? $def['unit']));
        }
        usort($trades, fn ($a, $b) => strcmp($b->exit_time, $a->exit_time));

        return array_slice($trades, 0, 100);
    }

    /**
     * Closed positions of one bot: the Nautilus node's PositionClosed records plus the bot's own EXIT_FILLED
     * events (the node log does not record every close), a close found in both counted once.
     *
     * @return \stdClass[]
     */
    private function closedTrades(string $dir, string $botName): array
    {
        $trades = $this->logClosedTrades($dir, $botName);
        $logged = array_map(fn ($t) => strtotime($t->exit_time), $trades);
        foreach ($this->eventClosedTrades($dir, $botName) as $trade) {
            $at = strtotime($trade->exit_time);
            if (! array_filter($logged, fn ($ts) => abs($ts - $at) <= 120)) {
                $trades[] = $trade;
            }
        }

        return $trades;
    }

    /**
     * EXIT_FILLED events of the bot's events.jsonl (entry time from the preceding ENTRY_FILLED); the reason is the
     * event's last word (TP, SL, or the exit kind).
     *
     * @return \stdClass[]
     */
    private function eventClosedTrades(string $dir, string $botName): array
    {
        $path = $dir . '/logs/events.jsonl';
        if (! is_file($path) || ! is_readable($path)) {
            return [];
        }

        return Cache::remember('svertus_exits:' . md5($path) . ':' . filesize($path) . ':' . filemtime($path), 600, function () use ($path, $botName) {
            $reasons = ['TP' => 'take_profit', 'SL' => 'stop_loss'];
            $out = [];
            $entryTs = null;
            foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                if (! str_contains($line, '_FILLED"')) {
                    continue;
                }
                $row = json_decode($line, true);
                if (is_array($row) && ($row['event'] ?? '') === 'ENTRY_FILLED') {
                    $entryTs = strtotime((string) $row['ts']);
                }
                if (! is_array($row) || ($row['event'] ?? '') !== 'EXIT_FILLED') {
                    continue;
                }
                $words = preg_split('/\s+/', trim((string) ($row['message'] ?? '')));
                $exitTs = strtotime((string) $row['ts']);
                $trade = new \stdClass();
                $trade->bot_name = $botName;
                $trade->side = strtolower($words[1] ?? '') === 'long' ? 'long' : 'short';
                $trade->entry_price = (float) ($row['entry'] ?? 0);
                $trade->exit_price = (float) ($row['exit'] ?? 0);
                $trade->pnl_usdt = (float) ($row['net_usdt'] ?? 0);
                $trade->entry_time = gmdate('Y-m-d H:i:s', $entryTs ?? $exitTs - (int) ($row['held_min'] ?? 0) * 60);
                $entryTs = null;
                $trade->exit_time = gmdate('Y-m-d H:i:s', $exitTs);
                $reason = (string) end($words);
                $trade->exit_reason = $reasons[$reason] ?? strtolower($reason);
                $out[] = $trade;
            }

            return $out;
        });
    }

    /**
     * Closed positions recorded by the Nautilus node (PositionClosed events), de-duplicated across runs.
     * The exit reason follows the closing order: a LIMIT fill is the exchange take-profit, a MARKET fill is the
     * bot's own exit (hold-time or formula) or a stop.
     *
     * @return \stdClass[]
     */
    private function logClosedTrades(string $dir, string $botName): array
    {
        $files = glob($dir . '/logs/nautilus/*.log') ?: [];
        $stamp = md5(implode('|', array_map(fn ($f) => $f . filesize($f) . filemtime($f), $files)));

        return Cache::remember('svertus_closed:' . md5($dir) . ':' . $stamp, 600, function () use ($files, $botName) {
            $orderTypes = [];
            $closed = [];
            foreach ($files as $file) {
                $handle = fopen($file, 'rb');
                if (! $handle) {
                    continue;
                }
                while (($line = fgets($handle)) !== false) {
                    if (str_contains($line, 'OrderFilled(')) {
                        if (preg_match('/client_order_id=([^,]+), .*?order_type=(\w+)/', $line, $m)) {
                            $orderTypes[$m[1]] = $m[2];
                        }
                    } elseif (str_contains($line, 'PositionClosed(')
                        && preg_match('/closing_order_id: Some\("([^"]+)"\).*?entry: (Buy|Sell).*?avg_px_open: ([\d.]+), avg_px_close: Some\(([\d.]+)\).*?realized_pnl: Some\(Money\((-?[\d.]+).*?ts_opened: UnixNanos\((\d+)\), ts_closed: Some\(UnixNanos\((\d+)\)/', $line, $m)) {
                        $closed[$m[1]] = $m;
                    }
                }
                fclose($handle);
            }

            $out = [];
            foreach ($closed as $closingOrder => $m) {
                $trade = new \stdClass();
                $trade->bot_name = $botName;
                $trade->side = $m[2] === 'Buy' ? 'long' : 'short';
                $trade->entry_price = (float) $m[3];
                $trade->exit_price = (float) $m[4];
                $trade->pnl_usdt = (float) $m[5];
                $trade->entry_time = gmdate('Y-m-d H:i:s', intdiv((int) $m[6], 1_000_000_000));
                $trade->exit_time = gmdate('Y-m-d H:i:s', intdiv((int) $m[7], 1_000_000_000));
                $trade->exit_reason = ($orderTypes[$closingOrder] ?? '') === 'LIMIT' ? 'take_profit (limit)' : 'market exit';
                $out[] = $trade;
            }

            return $out;
        });
    }

    private function gatewayMarkPrice(string $pair): ?float
    {
        $snapshot = app(FreqtradeGatewayService::class)->snapshot();
        foreach ((array) ($snapshot['data']['nautilus_bots'] ?? []) as $bot) {
            if (is_array($bot) && ($bot['pair'] ?? null) === $pair && (float) ($bot['mark_price'] ?? 0) > 0) {
                return (float) $bot['mark_price'];
            }
        }

        return null;
    }

    /** Last heartbeat price and the time of the last START (when the current run began). */
    private function readEvents(string $path): array
    {
        $out = ['heartbeat' => null, 'started_at' => null];
        if (! is_file($path) || ! is_readable($path)) {
            return $out;
        }
        $size = filesize($path);
        $handle = fopen($path, 'rb');
        if (! $handle) {
            return $out;
        }
        fseek($handle, max(0, $size - self::EVENT_TAIL_BYTES));
        $lines = explode("\n", (string) stream_get_contents($handle));
        fclose($handle);
        if ($size > self::EVENT_TAIL_BYTES) {
            array_shift($lines);
        }
        for ($i = count($lines) - 1; $i >= 0; $i--) {
            $row = json_decode($lines[$i], true);
            if (! is_array($row)) {
                continue;
            }
            if (($row['event'] ?? '') === 'HEARTBEAT') {
                $out['heartbeat'] ??= $row;
            } elseif (($row['event'] ?? '') === 'START') {
                $out['started_at'] = $row['ts'] ?? null;
                break;
            }
        }

        return $out;
    }

    private function parseEnvFile(string $path): array
    {
        $out = [];
        if (! is_file($path) || ! is_readable($path)) {
            return $out;
        }
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            if ($line[0] === '#' || ! str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $out[trim($key)] = trim($value, " \t\"'");
        }

        return $out;
    }

    private function readJson(string $path): array
    {
        if ($path === '' || ! is_file($path) || ! is_readable($path)) {
            return [];
        }
        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : [];
    }
}
