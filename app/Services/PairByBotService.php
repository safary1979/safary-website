<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * Read-only cards for the live pair PairBy (Long b7362bbc + Short c33e375a on one Bybit sub-account), shaped like the
 * Gateway "nautilus" bots so the dashboard renders them with the same card code. The only input is the bot's
 * world-readable site.json (nautilus_live/bybit_sol_pair/live_strategy.py::_write_site), rewritten every 2 s.
 */
class PairByBotService
{
    private const SITE_FILE = '/home/ubuntu/Freqtrade/nautilus_live/bybit_sol_pair/runtime/live-pairby/site.json';
    private const LEGS = [
        'long' => ['slug' => 'pairby-long', 'name' => 'PairBy SOL Long', 'identity' => 'b7362bbc'],
        'short' => ['slug' => 'pairby-short', 'name' => 'PairBy SOL Short', 'identity' => 'c33e375a'],
    ];
    private const PAIR = 'SOL/USDT:USDT';
    private const LEVERAGE = 4;
    private const STALE_SECONDS = 60;

    /** @return \stdClass[] */
    public function bots(): array
    {
        $site = $this->site();
        $alive = $this->age($site) !== null && $this->age($site) <= self::STALE_SECONDS;

        return array_map(fn (string $leg) => $this->load($leg, $site, $alive), array_keys(self::LEGS));
    }

    /** Top dashboard cards, same shape and rules as the Svertus view. */
    public function stats(array $bots): array
    {
        return app(SvertusBotService::class)->stats($bots);
    }

    /** @return \stdClass[] */
    public function trades(): array
    {
        return array_map(function (array $row) {
            $trade = new \stdClass();
            $trade->bot_name = self::LEGS[$row['side']]['name'] ?? (string) $row['bot_id'];
            $trade->side = (string) $row['side'];
            $trade->entry_price = (float) $row['entry_price'];
            $trade->exit_price = (float) $row['exit_price'];
            $trade->pnl_usdt = (float) $row['pnl_usdt'];
            $trade->entry_time = $this->sqlTime($row['entry_time'] ?? null);
            $trade->exit_time = $this->sqlTime($row['exit_time'] ?? null);
            $trade->exit_reason = (string) $row['exit_reason'];

            return $trade;
        }, array_values(array_filter((array) ($this->site()['trades'] ?? []), 'is_array')));
    }

    private function load(string $leg, array $site, bool $alive): \stdClass
    {
        $def = self::LEGS[$leg];
        $status = is_array($site['legs'][$leg] ?? null) ? $site['legs'][$leg] : [];
        $totals = is_array($site['totals'][$leg] ?? null) ? $site['totals'][$leg] : [];
        $lifecycle = (string) ($status['lifecycle'] ?? '');
        $blocker = (string) ($status['entry_blocker'] ?? '');

        $bot = new \stdClass();
        $bot->id = null;
        $bot->bot_ref = null;
        $bot->source = 'nautilus';
        $bot->control_id = '';
        $bot->slug = $def['slug'];
        $bot->display_name = $def['name'] . ' · ' . $def['identity'];
        $bot->symbol = self::PAIR;
        $bot->pair = self::PAIR;
        $bot->exchange = 'bybit';
        $bot->market_exchange = 'bybit';
        $bot->execution_exchange = 'bybit';
        $bot->direction = $leg;
        $bot->timeframe = '15m';
        $bot->leverage = (float) ($status['leverage'] ?? self::LEVERAGE);
        $bot->runtime_error = $blocker !== '' ? $blocker : null;
        $bot->started_at_utc = $site['started_at_utc'] ?? null;
        $bot->warning_history = array_values((array) ($status['warnings'] ?? []));
        $bot->active_warnings = array_values((array) ($status['active_warnings'] ?? []));
        $bot->margin = null;
        $bot->dca = null;
        $bot->status = match (true) {
            $site === [] || $lifecycle === 'stopped' => 'stopped',
            ! $alive => 'stale',
            $lifecycle === 'error' => 'blocked',
            $lifecycle === 'starting' => 'starting',
            default => 'running',
        };
        $bot->account = 'PairBy';
        $bot->run_mode = (string) ($site['mode'] ?? 'live');
        $bot->config_path = null;
        $bot->readonly = true;

        $stateObj = new \stdClass();
        $stateObj->mark_price = (float) ($status['mark_price'] ?? 0);
        $stateObj->position_json = null;
        $position = is_array($status['position'] ?? null) ? $status['position'] : null;
        if ($position !== null) {
            $stateObj->position_json = [
                'side_str' => $leg,
                'avg_price' => (float) $position['avg_price'],
                'pnl_usdt' => (float) ($position['pnl_usdt'] ?? 0),
                'pnl_pct' => (float) ($position['pnl_pct'] ?? 0),
                'notional' => (float) $position['notional'],
                'qty' => (float) $position['qty'],
                'opened_at' => $status['entry_time_utc'] ?? null,
                'bars_held' => 0,
                'entry_signal' => ['candidate_id' => $def['identity'], 'strategy' => 'NautilusTrader'],
                'take_profit' => (float) $position['take_profit'],
                'take_profit_pct' => (float) $position['take_profit_pct'],
                'stop_loss' => (float) $position['stop_loss'],
                'stop_loss_pct' => (float) $position['stop_loss_pct'],
            ];
        }
        $bot->state = $stateObj;
        $trades = (int) ($totals['trades'] ?? 0);
        $wins = (int) ($totals['wins'] ?? 0);
        $bot->net_stats = [
            'pnl_usdt' => (float) ($totals['pnl_usdt'] ?? 0),
            'trades' => $trades,
            'wins' => $wins,
            'losses' => max(0, $trades - $wins),
        ];

        return $bot;
    }

    private function age(array $site): ?int
    {
        try {
            return empty($site['generated_at_utc']) ? null
                : max(0, Carbon::now('UTC')->timestamp - Carbon::parse($site['generated_at_utc'])->timestamp);
        } catch (\Throwable) {
            return null;
        }
    }

    private function sqlTime(?string $iso): string
    {
        try {
            return $iso ? Carbon::parse($iso)->utc()->format('Y-m-d H:i:s') : '';
        } catch (\Throwable) {
            return '';
        }
    }

    private function site(): array
    {
        if (! is_file(self::SITE_FILE) || ! is_readable(self::SITE_FILE)) {
            return [];
        }
        $decoded = json_decode((string) file_get_contents(self::SITE_FILE), true);

        return is_array($decoded) && ($decoded['schema'] ?? null) === 'bybit_sol_pair_site_v1' ? $decoded : [];
    }
}
