<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

/** Read-only bridge for the live Freqtrade dashboard data. */
class FreqtradeGatewayService
{
    /**
     * User-confirmed Bybit LIVE production history. Older Bybit LIVE rows were
     * test/configuration runs and must not be presented as trading performance.
     * New rows after the cutoff are allowed through for the running long/short
     * production bots.
     */
    private const BYBIT_LIVE_CONFIRMED_TRADE_IDS = [
        'nautilus:bybit:long:9b34c085293b:30cedf11-cb56-4b72-94e4-4f91a3d83699',
        'nautilus:bybit:long:ef45da5990f1:75594c6b-339b-4a97-9575-4e38497b9d74',
    ];

    private const BYBIT_LIVE_HISTORY_CUTOFF_UTC = '2026-09-18T03:52:04.212+00:00';

    private ?array $snapshotCache = null;

    public function snapshot(): array
    {
        if ($this->snapshotCache !== null) {
            return $this->snapshotCache;
        }

        try {
            $response = Http::timeout(3)->acceptJson()->get('http://127.0.0.1:8765/v1/snapshot');

            if ($response->successful() && is_array($response->json())) {
                $data = $response->json();
                $generatedAt = is_string($data['generated_at_utc'] ?? null)
                    ? $data['generated_at_utc']
                    : null;

                return $this->snapshotCache = [
                    'available' => true,
                    'data' => $data,
                    'generated_at_utc' => $generatedAt,
                    'age_seconds' => $this->ageSeconds($generatedAt),
                    'error' => null,
                ];
            }

            return $this->snapshotCache = ['available' => false, 'data' => [], 'generated_at_utc' => null, 'age_seconds' => null, 'error' => 'HTTP ' . $response->status()];
        } catch (\Throwable $error) {
            return $this->snapshotCache = ['available' => false, 'data' => [], 'generated_at_utc' => null, 'age_seconds' => null, 'error' => 'Gateway недоступний'];
        }
    }

    /**
     * Convert the Gateway's neutral payload into the shape used by the
     * existing read-only active-bots card and its price-bar view.
     */
    public function liveBots(?array $result = null, ?string $exchange = null): array
    {
        $result ??= $this->snapshot();
        if (! $result['available']) {
            return [];
        }

        $data = $this->withoutPairByView($this->filterDataByExchange($result['data'], $exchange));
        $databases = is_array($data['trade_databases'] ?? null) ? $data['trade_databases'] : [];
        $docker = is_array($data['docker'] ?? null) ? $data['docker'] : [];
        $runningNames = array_values(array_filter((array) ($docker['running_names'] ?? []), 'is_string'));
        $dockerReadable = (bool) ($docker['available'] ?? false);
        $bots = [];

        foreach ($databases as $database) {
            if (! is_array($database) || ! ($database['live'] ?? false)) {
                continue;
            }

            $config = is_array($database['config'] ?? null) ? $database['config'] : [];
            if ($config === []) {
                continue;
            }

            $label = preg_replace('/^tradesv3\.|\.live\.sqlite$/', '', (string) ($database['name'] ?? ''));
            $container = 'freqtrade-live-' . $label;
            $positions = is_array($database['open_positions'] ?? null) ? $database['open_positions'] : [];
            $running = in_array($container, $runningNames, true);

            // If Docker is not readable by the Gateway, keep a card for a
            // bot with an open trade. A closed/stale DB is not shown as live.
            if ($dockerReadable && array_key_exists('running_names', $docker) && ! $running) {
                continue;
            }
            if (! $dockerReadable && $positions === []) {
                continue;
            }

            $position = $positions[0] ?? null;
            $pair = (string) ($position['pair'] ?? $config['pair'] ?? '—');
            $side = (string) ($position['side'] ?? (str_contains($label, 'short') ? 'short' : 'long'));
            $strategy = (string) ($position['strategy'] ?? '');
            $entry = (float) ($position['entry_price'] ?? 0);
            $mark = (float) ($position['mark_price'] ?? 0);
            $stopLoss = (float) ($position['stop_loss'] ?? 0);
            $takeProfit = (float) ($position['take_profit'] ?? 0);

            $bot = new \stdClass();
            $bot->id = null;
            $bot->bot_ref = 'freqtrade:' . $label;
            $bot->source = 'freqtrade';
            $bot->slug = (string) ($config['bot_name'] ?? $label);
            $bot->display_name = $bot->slug;
            $bot->symbol = $pair;
            $bot->pair = $pair;
            $bot->exchange = $this->canonicalMarketExchange((string) ($config['exchange'] ?? 'okx'));
            $bot->market_exchange = $bot->exchange;
            $bot->direction = $side;
            $bot->timeframe = $position['timeframe'] ?? null;
            $bot->leverage = (float) ($position['leverage'] ?? $config['leverage'] ?? 1);
            $bot->status = $running || ! $dockerReadable ? 'running' : 'stopped';
            $bot->account = 'Freqtrade';
            $bot->config_path = null;
            $bot->readonly = true;
            $bot->dca = null;

            $state = new \stdClass();
            $state->mark_price = $mark;
            $state->position_json = $position ? [
                'side_str' => $side,
                'avg_price' => $entry,
                'pnl_usdt' => (float) ($position['pnl_usdt'] ?? 0),
                'pnl_pct' => (float) ($position['pnl_pct'] ?? 0),
                'notional' => (float) ($position['stake_amount'] ?? 0),
                'qty' => (float) ($position['amount'] ?? 0),
                'opened_at' => $this->timestamp($position['open_date'] ?? null),
                'bars_held' => 0,
                'entry_signal' => ['strategy' => $strategy],
                'take_profit' => $takeProfit,
                'take_profit_pct' => (float) ($position['take_profit_pct'] ?? 0),
                'stop_loss' => $stopLoss,
                'stop_loss_pct' => $entry > 0 && $stopLoss > 0
                    ? abs(($stopLoss - $entry) / $entry * 100)
                    : 0,
            ] : null;
            $bot->state = $state;
            $bots[] = $bot;
        }

        foreach ((array) ($data['nautilus_bots'] ?? []) as $snapshot) {
            if (! is_array($snapshot) || ! is_string($snapshot['bot_id'] ?? null)) {
                continue;
            }

            $position = is_array($snapshot['position'] ?? null) ? $snapshot['position'] : null;
            $lifecycle = (string) ($snapshot['lifecycle'] ?? '');
            $positionIsCurrent = in_array($lifecycle, ['running', 'starting', 'blocked', 'paused'], true);
            $bot = new \stdClass();
            $bot->id = null;
            $bot->bot_ref = 'nautilus:' . (string) $snapshot['bot_id'];
            $bot->source = 'nautilus';
            $bot->control_id = (string) $snapshot['bot_id'];
            $bot->slug = (string) $snapshot['bot_id'];
            $bot->display_name = (string) ($snapshot['display_name'] ?? $bot->slug);
            $bot->symbol = (string) ($snapshot['pair'] ?? '—');
            $bot->pair = $bot->symbol;
            $bot->exchange = $this->canonicalMarketExchange((string) ($snapshot['market_exchange'] ?? $snapshot['exchange'] ?? 'okx'));
            $bot->market_exchange = $bot->exchange;
            $bot->execution_exchange = (string) ($snapshot['exchange'] ?? $bot->exchange);
            $bot->direction = (string) ($snapshot['direction'] ?? 'long');
            $bot->timeframe = $snapshot['timeframe'] ?? null;
            $bot->leverage = (float) ($snapshot['leverage'] ?? 1);
            $bot->runtime_error = is_string($snapshot['entry_blocker'] ?? null)
                ? trim((string) $snapshot['entry_blocker'])
                : null;
            $bot->started_at_utc = is_string($snapshot['started_at_utc'] ?? null)
                ? $snapshot['started_at_utc']
                : null;
            $bot->warning_history = $this->warningList(
                is_array($snapshot['warning_history'] ?? null)
                    ? $snapshot['warning_history']
                    : ($snapshot['warnings'] ?? [])
            );
            $bot->active_warnings = $this->warningList($snapshot['active_warnings'] ?? []);
            // Margin telemetry is produced by the bot's account-wide gate.
            // Keep it read-only and lossless; the website must not recalculate
            // or reinterpret exchange margin values.
            $bot->margin = is_array($snapshot['margin'] ?? null)
                ? $snapshot['margin']
                : null;
            $bot->dca = $this->normalizeDca($snapshot['dca'] ?? null);
            $bot->status = match ($lifecycle) {
                'running' => $bot->runtime_error ? 'blocked' : 'running',
                'paused' => 'paused',
                'stale' => 'stale',
                'stopped' => 'stopped',
                default => 'starting',
            };
            $bot->account = $bot->slug;
            $bot->run_mode = (string) ($snapshot['mode'] ?? 'live');
            $bot->config_path = null;
            $bot->readonly = false;

            $state = new \stdClass();
            $state->mark_price = (float) ($snapshot['mark_price'] ?? 0);
            $state->position_json = $position && $positionIsCurrent ? [
                'side_str' => (string) ($position['side'] ?? $bot->direction),
                'avg_price' => (float) ($position['avg_price'] ?? 0),
                'pnl_usdt' => (float) ($position['pnl_usdt'] ?? 0),
                'pnl_pct' => (float) ($position['pnl_pct'] ?? 0),
                'notional' => (float) ($position['notional'] ?? 0),
                'qty' => (float) ($position['qty'] ?? 0),
                'opened_at' => null,
                'bars_held' => 0,
                'entry_signal' => ['strategy' => 'NautilusTrader'],
                'take_profit' => (float) ($position['take_profit'] ?? 0),
                'take_profit_pct' => (float) ($position['take_profit_pct'] ?? 0),
                'stop_loss' => (float) ($position['stop_loss'] ?? 0),
                'stop_loss_pct' => (float) ($position['stop_loss_pct'] ?? 0),
            ] : null;
            $bot->state = $state;
            $bots[] = $bot;
        }

        return $bots;
    }

    /**
     * Read-only DEMO bot cards.  They deliberately bypass live trade
     * databases so demo positions and telemetry cannot enter live statistics.
     */
    public function demoBots(?array $result = null, ?string $exchange = null): array
    {
        $result ??= $this->snapshot();
        if (! ($result['available'] ?? false)) {
            return [];
        }

        $demoData = $this->filterDataByExchange($this->demoData($result), $exchange);

        $bots = $this->liveBots([
            'available' => true,
            'data' => $demoData,
            'generated_at_utc' => $result['generated_at_utc'] ?? null,
            'age_seconds' => $result['age_seconds'] ?? null,
            'error' => null,
        ], $exchange);

        foreach ($bots as $bot) {
            $bot->readonly = true;
        }

        return $bots;
    }

    /** Return read-only statistics for the selected DEMO source. */
    public function demoStats(?array $result = null, ?string $exchange = null): ?array
    {
        $result ??= $this->snapshot();
        if (! ($result['available'] ?? false)) {
            return null;
        }

        return $this->statsFromData($this->filterDataByExchange($this->demoData($result), $exchange));
    }

    /**
     * Read-only chart context for Gateway bots.  This intentionally uses a
     * stable external reference instead of the retired Laravel bot database.
     */
    public function botContext(string $botRef): ?array
    {
        if (! preg_match('/^(nautilus|freqtrade):[A-Za-z0-9._-]+$/', $botRef)) {
            return null;
        }

        $result = $this->snapshot();
        if (! $result['available']) {
            return ['available' => false, 'error' => $result['error'] ?? 'Gateway недоступний'];
        }

        $data = $result['data'];
        if (str_starts_with($botRef, 'nautilus:')) {
            $botId = substr($botRef, strlen('nautilus:'));
            // Demo bots use the same stable nautilus:<id> reference as live
            // bots. Search the two Gateway collections separately so a chart
            // opened from the DEMO dashboard does not receive a false 404.
            foreach ([
                ['bots' => 'nautilus_bots', 'trades' => 'nautilus_closed_trades', 'mode' => 'live'],
                ['bots' => 'nautilus_demo_bots', 'trades' => 'nautilus_demo_closed_trades', 'mode' => 'demo'],
            ] as $collection) {
                foreach ((array) ($data[$collection['bots']] ?? []) as $snapshot) {
                    if (! is_array($snapshot) || (string) ($snapshot['bot_id'] ?? '') !== $botId) {
                        continue;
                    }

                    $position = is_array($snapshot['position'] ?? null) ? $snapshot['position'] : null;
                    $lifecycle = (string) ($snapshot['lifecycle'] ?? '');
                    $positionIsCurrent = in_array($lifecycle, ['running', 'starting', 'blocked', 'paused'], true);
                    $trades = array_values(array_filter((array) ($data[$collection['trades']] ?? []), fn ($trade) =>
                        is_array($trade) && (string) ($trade['bot_ref'] ?? '') === $botRef
                    ));

                    return [
                        'available' => true,
                        'bot_ref' => $botRef,
                        'source' => 'nautilus',
                        'mode' => $collection['mode'],
                        'lifecycle' => $lifecycle,
                        'market_exchange' => $this->canonicalMarketExchange((string) ($snapshot['market_exchange'] ?? $snapshot['exchange'] ?? 'okx')),
                        'position' => $positionIsCurrent ? $this->chartPosition($position) : null,
                        'closed_trades' => $this->chartTrades($trades),
                        'generated_at_utc' => $result['generated_at_utc'] ?? null,
                        'age_seconds' => $result['age_seconds'] ?? null,
                    ];
                }
            }
        }

        if (str_starts_with($botRef, 'freqtrade:')) {
            $label = substr($botRef, strlen('freqtrade:'));
            foreach ((array) ($data['trade_databases'] ?? []) as $database) {
                if (! is_array($database) || ! ($database['live'] ?? false)) {
                    continue;
                }
                $name = (string) ($database['name'] ?? '');
                $databaseLabel = preg_replace('/^tradesv3\.|\.live\.sqlite$/', '', $name);
                if ($databaseLabel !== $label) {
                    continue;
                }

                $config = is_array($database['config'] ?? null) ? $database['config'] : [];
                $position = is_array($database['open_positions'][0] ?? null) ? $database['open_positions'][0] : null;
                return [
                    'available' => true,
                    'bot_ref' => $botRef,
                    'source' => 'freqtrade',
                    'market_exchange' => $this->canonicalMarketExchange((string) ($config['exchange'] ?? 'okx')),
                    'position' => $this->chartPosition($position),
                    'closed_trades' => $this->chartTrades((array) ($database['closed_trades'] ?? [])),
                    'generated_at_utc' => $result['generated_at_utc'] ?? null,
                    'age_seconds' => $result['age_seconds'] ?? null,
                ];
            }
        }

        return null;
    }

    private function canonicalMarketExchange(string $exchange): string
    {
        $exchange = strtolower(trim($exchange));
        if (str_contains($exchange, 'okx')) {
            return 'okx';
        }
        if (str_contains($exchange, 'bybit')) {
            return 'bybit';
        }

        return match ($exchange) {
            default => $exchange !== '' ? $exchange : 'okx',
        };
    }

    /** Keep the dashboard exchange selector isolated from raw Gateway data. */
    private function filterDataByExchange(array $data, ?string $exchange): array
    {
        $exchange = strtolower(trim((string) $exchange));
        if (! in_array($exchange, ['okx', 'bybit'], true)) {
            return $data;
        }

        $matches = function (mixed $value) use ($exchange): bool {
            $value = strtolower(trim((string) $value));
            if (str_contains($value, 'okx')) {
                $value = 'okx';
            } elseif (str_contains($value, 'bybit')) {
                $value = 'bybit';
            }

            return $value === $exchange;
        };

        $data['trade_databases'] = array_values(array_filter(
            is_array($data['trade_databases'] ?? null) ? $data['trade_databases'] : [],
            function ($database) use ($matches): bool {
                if (! is_array($database)) {
                    return false;
                }

                $config = is_array($database['config'] ?? null) ? $database['config'] : [];
                return $matches(
                    $database['market_exchange']
                        ?? $database['exchange']
                        ?? ($config['exchange'] ?? null)
                );
            },
        ));

        $allowedBotRefs = [];
        foreach (['nautilus_bots', 'nautilus_demo_bots'] as $key) {
            $bots = array_values(array_filter(
                is_array($data[$key] ?? null) ? $data[$key] : [],
                function ($bot) use ($matches, &$allowedBotRefs): bool {
                    if (! is_array($bot) || ! $matches($bot['market_exchange'] ?? $bot['exchange'] ?? null)) {
                        return false;
                    }

                    $botId = (string) ($bot['bot_id'] ?? '');
                    $allowedBotRefs[] = (string) ($bot['bot_ref'] ?? ($botId !== '' ? 'nautilus:' . $botId : ''));
                    return true;
                },
            ));
            $data[$key] = $bots;
        }

        foreach (['nautilus_closed_trades', 'nautilus_demo_closed_trades'] as $key) {
            $data[$key] = array_values(array_filter(
                is_array($data[$key] ?? null) ? $data[$key] : [],
                function ($trade) use ($matches, $allowedBotRefs): bool {
                    if (! is_array($trade)) {
                        return false;
                    }

                    $tradeExchange = $trade['market_exchange'] ?? $trade['exchange'] ?? null;
                    if ($tradeExchange !== null && $matches($tradeExchange)) {
                        return true;
                    }

                    return in_array((string) ($trade['bot_ref'] ?? ''), $allowedBotRefs, true);
                },
            ));
        }

        return $data;
    }

    /**
     * The main LIVE dashboard shows only the ByNau grid: the PairBy and ByCopy bots and their trades are shown in the
     * dashboard's PairBy view (PairByBotService), so they are left out here.
     */
    private function withoutPairByView(array $data): array
    {
        $other = fn (string $ref): bool => preg_match('/^(nautilus:)?(PairBy|ByCopy)/', $ref) === 1;
        $data['nautilus_bots'] = array_values(array_filter(
            is_array($data['nautilus_bots'] ?? null) ? $data['nautilus_bots'] : [],
            fn ($bot) => is_array($bot) && ! $other((string) ($bot['bot_id'] ?? '')),
        ));
        $data['nautilus_closed_trades'] = array_values(array_filter(
            is_array($data['nautilus_closed_trades'] ?? null) ? $data['nautilus_closed_trades'] : [],
            fn ($trade) => is_array($trade) && ! $other((string) ($trade['bot_ref'] ?? '')),
        ));

        return $data;
    }

    /** Keep confirmed Bybit LIVE history while allowing future production fills. */
    private function filterBybitLiveHistory(array $data): array
    {
        $filter = function (mixed $trade): bool {
            if (! is_array($trade)) {
                return false;
            }

            $id = (string) ($trade['id'] ?? '');
            if (in_array($id, self::BYBIT_LIVE_CONFIRMED_TRADE_IDS, true)) {
                return true;
            }

            $mode = strtolower((string) ($trade['mode'] ?? 'live'));
            $botId = (string) ($trade['bot_id'] ?? '');
            if ($mode !== 'live' || ! in_array($botId, ['ByNauSOLLong', 'ByNauSOLShort'], true)) {
                return false;
            }

            try {
                return Carbon::parse((string) ($trade['exit_time'] ?? ''), 'UTC')
                    ->greaterThanOrEqualTo(Carbon::parse(self::BYBIT_LIVE_HISTORY_CUTOFF_UTC, 'UTC'));
            } catch (\Throwable) {
                return false;
            }
        };

        $data['nautilus_closed_trades'] = array_values(array_filter(
            is_array($data['nautilus_closed_trades'] ?? null) ? $data['nautilus_closed_trades'] : [],
            $filter,
        ));

        foreach ((array) ($data['trade_databases'] ?? []) as $index => $database) {
            if (! is_array($database)) {
                continue;
            }

            $closed = array_values(array_filter(
                is_array($database['closed_trades'] ?? null) ? $database['closed_trades'] : [],
                $filter,
            ));
            $data['trade_databases'][$index]['closed_trades'] = $closed;
            $data['trade_databases'][$index]['closed_pnl_usdt'] = array_sum(array_map(
                static fn (array $trade): float => (float) ($trade['pnl_usdt'] ?? 0),
                $closed,
            ));
            $data['trade_databases'][$index]['closed_trade_count'] = count($closed);
            $data['trade_databases'][$index]['closed_wins'] = count(array_filter(
                $closed,
                static fn (array $trade): bool => (float) ($trade['pnl_usdt'] ?? 0) > 0,
            ));
        }

        return $data;
    }

    private function warningList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_slice(array_values(array_filter($value, fn ($item) => is_string($item))), -20);
    }

    /**
     * Keep the DCA view deliberately small and typed.  The Gateway already
     * strips venue identifiers; this second boundary prevents a malformed
     * snapshot from breaking the Blade card or turning into executable data.
     */
    private function normalizeDca(mixed $value): ?array
    {
        if (! is_array($value) || ($value['enabled'] ?? false) !== true || ! is_array($value['additions'] ?? null)) {
            return null;
        }

        $order = static function (mixed $row): ?array {
            if (! is_array($row)) {
                return null;
            }
            $price = is_numeric($row['price'] ?? null) ? (float) $row['price'] : null;
            $quantity = is_numeric($row['quantity'] ?? null) ? (float) $row['quantity'] : null;
            if ($price === null || $quantity === null || $price <= 0 || $quantity <= 0) {
                return null;
            }

            return [
                'label' => (string) ($row['label'] ?? ''),
                'role' => (string) ($row['role'] ?? ''),
                'tier' => isset($row['tier']) && is_numeric($row['tier']) ? (int) $row['tier'] : null,
                'price' => $price,
                'quantity' => $quantity,
                'filled_quantity' => is_numeric($row['filled_quantity'] ?? null) ? (float) $row['filled_quantity'] : 0.0,
                'status' => in_array(($row['status'] ?? null), ['filled', 'partial', 'pending', 'cancelled', 'rejected'], true)
                    ? (string) $row['status']
                    : 'pending',
                'venue_state' => (string) ($row['venue_state'] ?? ''),
                'phase' => (string) ($row['phase'] ?? ''),
                'reduce_only' => ($row['reduce_only'] ?? false) === true,
            ];
        };

        $additions = array_values(array_filter(array_map($order, $value['additions'])));
        if ($additions === []) {
            return null;
        }

        return [
            'enabled' => true,
            'max_additions' => max(0, (int) ($value['max_additions'] ?? count($additions))),
            'completed_additions' => max(0, (int) ($value['completed_additions'] ?? 0)),
            'initial' => $order($value['initial'] ?? null),
            'additions' => $additions,
            'take_profit' => $order($value['take_profit'] ?? null),
            'reference_price' => is_numeric($value['reference_price'] ?? null) ? (float) $value['reference_price'] : null,
            'phase' => (string) ($value['phase'] ?? ''),
            'strategy_signature' => (string) ($value['strategy_signature'] ?? ''),
        ];
    }

    private function ageSeconds(?string $timestamp): ?int
    {
        if (! $timestamp) {
            return null;
        }
        try {
            return (int) max(0, Carbon::parse($timestamp, 'UTC')->diffInSeconds(now('UTC')));
        } catch (\Throwable) {
            return null;
        }
    }

    private function chartPosition(?array $position): ?array
    {
        if (! $position) {
            return null;
        }

        $entry = (float) ($position['avg_price'] ?? $position['entry_price'] ?? 0);
        if ($entry <= 0) {
            return null;
        }

        return [
            'side' => (string) ($position['side'] ?? 'long'),
            'entry_price' => $entry,
            'opened_at' => $this->timestamp($position['opened_at'] ?? $position['open_date'] ?? null),
            'qty' => (float) ($position['qty'] ?? $position['amount'] ?? 0),
            'pnl_usdt' => (float) ($position['pnl_usdt'] ?? 0),
            'pnl_pct' => (float) ($position['pnl_pct'] ?? 0),
            'take_profit' => (float) ($position['take_profit'] ?? 0) ?: null,
            'stop_loss' => (float) ($position['stop_loss'] ?? 0) ?: null,
        ];
    }

    private function chartTrades(array $trades): array
    {
        return array_values(array_filter(array_map(function ($trade) {
            if (! is_array($trade)) {
                return null;
            }
            $entry = (float) ($trade['entry_price'] ?? 0);
            $exit = (float) ($trade['exit_price'] ?? 0);
            $side = (string) ($trade['side'] ?? 'long');
            $move = $entry > 0 && $exit > 0
                ? (($exit - $entry) / $entry * 100.0) * ($side === 'short' ? -1.0 : 1.0)
                : 0.0;

            return [
                'id' => (string) ($trade['id'] ?? ''),
                'side' => $side,
                'entry_time' => $this->timestamp($trade['entry_time'] ?? null),
                'exit_time' => $this->timestamp($trade['exit_time'] ?? null),
                'marker_time' => $this->timestamp($trade['exit_time'] ?? null),
                'entry_price' => (float) ($trade['entry_price'] ?? 0),
                'exit_price' => (float) ($trade['exit_price'] ?? 0),
                'pnl_usdt' => (float) ($trade['pnl_usdt'] ?? 0),
                'pnl_pct' => (float) ($trade['pnl_pct'] ?? 0),
                'price_move_pct' => $move,
                'exit_reason' => (string) ($trade['exit_reason'] ?? ''),
            ];
        }, $trades)));
    }

    /** Aggregate closed and open PnL for the live Freqtrade databases. */
    public function liveStats(?string $exchange = null): ?array
    {
        $result = $this->snapshot();
        if (! $result['available']) {
            return null;
        }

        $data = $this->withoutPairByView($this->filterDataByExchange($result['data'], $exchange));
        if (strtolower((string) $exchange) === 'bybit') {
            $data = $this->filterBybitLiveHistory($data);
        }

        return $this->statsFromData($data);
    }

    /** Aggregate closed and open PnL for a normalized Gateway payload. */
    private function statsFromData(array $data): array
    {

        $closedPnl = 0.0;
        $closedTrades = 0;
        $closedWins = 0;
        $openTrades = 0;
        $activeBots = 0;
        $unrealizedPnl = $this->unrealizedPnl($data);
        $docker = is_array($data['docker'] ?? null) ? $data['docker'] : [];
        $dockerReadable = (bool) ($docker['available'] ?? false);
        $runningNames = array_values(array_filter((array) ($docker['running_names'] ?? []), 'is_string'));

        foreach ((array) ($data['trade_databases'] ?? []) as $database) {
            if (! is_array($database) || ! ($database['live'] ?? false)) {
                continue;
            }

            $label = preg_replace('/^tradesv3\.|\.live\.sqlite$/', '', (string) ($database['name'] ?? ''));
            $container = 'freqtrade-live-' . $label;
            $positions = is_array($database['open_positions'] ?? null) ? $database['open_positions'] : [];
            $running = in_array($container, $runningNames, true);
            if (($dockerReadable && ! $running) || (! $dockerReadable && $positions === [])) {
                continue;
            }

            $activeBots++;
            $closedPnl += (float) ($database['closed_pnl_usdt'] ?? 0);
            $closedTrades += (int) ($database['closed_trade_count'] ?? 0);
            $closedWins += (int) ($database['closed_wins'] ?? 0);
            $openTrades += (int) ($database['open_trades'] ?? 0);
        }

        foreach ((array) ($data['nautilus_bots'] ?? []) as $bot) {
            if (! is_array($bot)) {
                continue;
            }
            $lifecycle = (string) ($bot['lifecycle'] ?? '');
            if (in_array($lifecycle, ['running', 'starting', 'blocked', 'paused'], true)) {
                $activeBots++;
            }
            if (in_array($lifecycle, ['running', 'starting', 'blocked', 'paused'], true) && is_array($bot['position'] ?? null)) {
                $openTrades++;
                $unrealizedPnl += (float) ($bot['position']['pnl_usdt'] ?? 0);
            }
        }

        foreach ((array) ($data['nautilus_closed_trades'] ?? []) as $trade) {
            if (! is_array($trade)) {
                continue;
            }
            $pnl = (float) ($trade['pnl_usdt'] ?? 0);
            $closedPnl += $pnl;
            $closedTrades++;
            if ($pnl > 0) {
                $closedWins++;
            }
        }

        $winRate = $closedTrades > 0 ? round($closedWins / $closedTrades * 100, 1) : 0;

        return [
            ['label' => 'PnL закриті', 'value' => ($closedPnl >= 0 ? '+' : '') . number_format($closedPnl, 2) . ' USDT', 'sub' => "{$closedTrades} угод", 'color' => $closedPnl >= 0 ? '#22c55e' : '#ef4444'],
            ['label' => 'PnL нереалізований', 'value' => ($unrealizedPnl >= 0 ? '+' : '') . number_format($unrealizedPnl, 2) . ' USDT', 'sub' => "{$openTrades} в угоді", 'color' => $unrealizedPnl >= 0 ? '#22c55e' : '#ef4444'],
            ['label' => 'Win Rate', 'value' => $winRate . '%', 'sub' => "{$closedWins}/{$closedTrades} прибуткових", 'color' => $winRate >= 50 ? '#22c55e' : '#f59e0b'],
            ['label' => 'Боти', 'value' => "{$activeBots} active · {$openTrades} in trade", 'sub' => "{$activeBots} всього", 'color' => '#60a5fa'],
        ];
    }

    /** Return recent closed trades in the shape expected by the dashboard table. */
    public function liveTrades(?string $exchange = null): ?array
    {
        $result = $this->snapshot();
        if (! $result['available']) {
            return null;
        }

        $data = $this->withoutPairByView($this->filterDataByExchange($result['data'], $exchange));
        if (strtolower((string) $exchange) === 'bybit') {
            $data = $this->filterBybitLiveHistory($data);
        }

        return $this->tradesFromData($data);
    }

    /** Return read-only closed trades for the selected DEMO source. */
    public function demoTrades(?array $result = null, ?string $exchange = null): ?array
    {
        $result ??= $this->snapshot();
        if (! ($result['available'] ?? false)) {
            return null;
        }

        return $this->tradesFromData($this->filterDataByExchange($this->demoData($result), $exchange));
    }

    /** Return closed trades from a normalized Gateway payload. */
    private function tradesFromData(array $data): array
    {

        $trades = [];
        foreach ((array) ($data['trade_databases'] ?? []) as $database) {
            if (! is_array($database) || ! ($database['live'] ?? false)) {
                continue;
            }
            $botName = (string) data_get($database, 'config.bot_name', $database['name'] ?? 'Freqtrade');
            foreach ((array) ($database['closed_trades'] ?? []) as $trade) {
                if (! is_array($trade)) {
                    continue;
                }
                $trade['bot_name'] = $botName;
                $trades[] = $trade;
            }
        }

        foreach ((array) ($data['nautilus_closed_trades'] ?? []) as $trade) {
            if (is_array($trade)) {
                $trades[] = $trade;
            }
        }

        usort($trades, fn (array $left, array $right): int => strcmp((string) ($right['exit_time'] ?? ''), (string) ($left['exit_time'] ?? '')));

        return array_slice($trades, 0, 100);
    }

    /** Normalize the Gateway's separate DEMO collections without exchange hard-coding. */
    private function demoData(array $result): array
    {
        $data = is_array($result['data'] ?? null) ? $result['data'] : [];
        $data['trade_databases'] = [];
        $data['nautilus_bots'] = array_values(array_filter(
            is_array($data['nautilus_demo_bots'] ?? null) ? $data['nautilus_demo_bots'] : [],
            'is_array',
        ));
        $data['nautilus_closed_trades'] = array_values(array_filter(
            is_array($data['nautilus_demo_closed_trades'] ?? null) ? $data['nautilus_demo_closed_trades'] : [],
            'is_array',
        ));

        return $data;
    }

    private function unrealizedPnl(array $data): float
    {
        $pnl = 0.0;
        foreach ((array) ($data['trade_databases'] ?? []) as $database) {
            if (! is_array($database) || ! ($database['live'] ?? false)) {
                continue;
            }
            foreach ((array) ($database['open_positions'] ?? []) as $position) {
                if (is_array($position)) {
                    $pnl += (float) ($position['pnl_usdt'] ?? 0);
                }
            }
        }

        return $pnl;
    }

    private function timestamp(mixed $value): ?int
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return (int) \Carbon\Carbon::parse($value, 'UTC')->timestamp;
        } catch (\Throwable) {
            return null;
        }
    }
}
