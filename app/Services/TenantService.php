<?php

namespace App\Services;

use App\Models\Bot;
use App\Models\BotState;
use App\Models\Setting;
use App\Models\Trade;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class TenantService
{
    public const PRIMARY_USER_ID = 1;

    private const TENANTS = [
        1 => [
            'mode'        => 'db',
            'name'        => 'Ivan',
            'configs_dir' => '/home/ubuntu/SafaryEngine/live/configs',
        ],
        2 => [
            'mode'      => 'json',
            'name'      => 'Olexandr',
            'bots_root' => '/home/ubuntu/InfinitumBots',   // each subdir = 1 bot
        ],
    ];

    /** Which tenant's data are we currently viewing (can be changed by any user). */
    public function viewingTenantId(): int
    {
        $authId = (int)(Auth::id() ?? 0);
        return (int) session('viewing_tenant', $authId);
    }

    private function viewingCfg(): array
    {
        return self::TENANTS[$this->viewingTenantId()] ?? self::TENANTS[1];
    }

    public function canSwitch(): bool
    {
        return Auth::check();
    }

    /** Only primary user viewing his own dashboard can send commands. */
    public function canControl(): bool
    {
        return (int)(Auth::id() ?? 0) === self::PRIMARY_USER_ID
            && $this->viewingTenantId() === self::PRIMARY_USER_ID;
    }

    /** Any authenticated user can start a new session for the currently viewed tenant. */
    public function canManageSession(): bool
    {
        return Auth::check();
    }

    public function setViewing(int $userId): void
    {
        if (! array_key_exists($userId, self::TENANTS)) return;
        session(['viewing_tenant' => $userId]);
    }

    // ── Session (live start date) ────────────────────────────────────────────

    public function getSessionStart(): ?Carbon
    {
        $val = Setting::get($this->viewingTenantId(), 'session_start');
        return $val ? Carbon::parse($val) : null;
    }

    public function newSession(): void
    {
        Setting::set($this->viewingTenantId(), 'session_start', now()->toDateTimeString());
    }

    public function clearSession(): void
    {
        Setting::set($this->viewingTenantId(), 'session_start', null);
    }

    public function availableTenants(): array
    {
        $out = [];
        foreach (self::TENANTS as $id => $cfg) $out[$id] = $cfg['name'];
        return $out;
    }

    public function tenantName(int $id): string
    {
        return self::TENANTS[$id]['name'] ?? "User #{$id}";
    }

    public function viewingHasDb(): bool
    {
        return ($this->viewingCfg()['mode'] ?? 'db') === 'db';
    }

    public function configsPath(): string
    {
        // For DB-mode tenants keep the legacy single path
        $cfg = $this->viewingCfg();
        if (isset($cfg['configs_dir'])) {
            return (string) $cfg['configs_dir'];
        }
        // For json-mode with bots_root, return the root itself (BotConfigs iterates per-bot dirs)
        return (string) ($cfg['bots_root'] ?? config('cabinet.bot_configs_path', ''));
    }

    // ── Bot-folder helpers (Olexandr / json-mode) ────────────────────────

    /** Root directory that contains one subdir per bot. */
    public function getBotsRoot(): ?string
    {
        $root = $this->viewingCfg()['bots_root'] ?? null;
        return $root ? rtrim((string) $root, '/') : null;
    }

    /** All subdirectories found under bots_root. Returns [name => fullPath]. */
    public function getAvailableBotDirs(): array
    {
        $root = $this->getBotsRoot();
        if (! $root || ! is_dir($root)) return [];

        $out = [];
        foreach (glob($root . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
            $out[basename($dir)] = $dir;
        }
        ksort($out);
        return $out;
    }

    /** Names of the subdirs the user chose to activate (empty = all). */
    public function getSelectedBotDirNames(): array
    {
        $raw = Setting::get($this->viewingTenantId(), 'selected_bot_dirs');
        if (! $raw) return [];
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /** Save selected dir names. */
    public function setSelectedBotDirNames(array $names): void
    {
        Setting::set($this->viewingTenantId(), 'selected_bot_dirs', json_encode(array_values($names)));
    }

    /**
     * Resolve the full paths of the selected (or all) bot directories.
     * Selected items are stored as relative paths from bots_root (any depth).
     * Returns [fullPath, ...].
     */
    public function resolvedBotDirs(): array
    {
        $root     = $this->getBotsRoot();
        $selected = $this->getSelectedBotDirNames();  // relative paths, e.g. "project/bot1"

        if (! $root) return [];

        if (empty($selected)) {
            // Nothing selected → use all top-level subdirs
            return array_values($this->getAvailableBotDirs());
        }

        $out = [];
        foreach ($selected as $relPath) {
            $full = $root . '/' . ltrim($relPath, '/');
            $real = realpath($full);
            // Security: must stay inside bots_root
            if ($real && is_dir($real) && str_starts_with($real, $root)) {
                $out[] = $real;
            }
        }
        return $out;
    }

    /** Directory basenames (fnmatch patterns) hidden from the folder picker tree. */
    private const DIR_TREE_IGNORE = [
        'cycle_*',        // Infinitum per-cycle snapshot dirs (hundreds of them)
        '__pycache__', 'venv', '.venv',
        'node_modules', '.git',
    ];

    /**
     * Build a recursive directory tree under bots_root (max $maxDepth levels).
     * Skips service/internal directories (cycle_*, __pycache__, etc.).
     * Returns nested array:
     *   [ 'name', 'relPath', 'fullPath', 'hasChildren', 'children' => [...] ]
     */
    public function buildDirTree(string $dir, string $relBase = '', int $depth = 0, int $maxDepth = 5): array
    {
        if ($depth > $maxDepth || ! is_dir($dir)) return [];

        $nodes = [];
        foreach (glob(rtrim($dir, '/') . '/*', GLOB_ONLYDIR) ?: [] as $subDir) {
            $name = basename($subDir);
            // Skip ignored patterns
            foreach (self::DIR_TREE_IGNORE as $pattern) {
                if (fnmatch($pattern, $name)) continue 2;
            }
            $relPath  = $relBase === '' ? $name : $relBase . '/' . $name;
            $children = $this->buildDirTree($subDir, $relPath, $depth + 1, $maxDepth);
            $nodes[]  = [
                'name'        => $name,
                'relPath'     => $relPath,
                'fullPath'    => $subDir,
                'hasChildren' => ! empty($children),
                'children'    => $children,
            ];
        }
        return $nodes;
    }

    // ── Bots ────────────────────────────────────────────────

    public function getActiveBots(): Collection
    {
        return $this->viewingHasDb() ? $this->loadDbActiveBots() : $this->loadJsonBots();
    }

    /** All bots (for the "Всі боти" list). */
    public function getAllBots(): Collection
    {
        return $this->viewingHasDb() ? Bot::with('state')->get() : $this->loadJsonBots();
    }

    private function loadDbActiveBots(): Collection
    {
        return Bot::query()
            ->with('state')
            ->where(function ($q) {
                $q->whereIn('status', ['running', 'paused'])
                  ->orWhereHas('state', fn ($s) => $s->whereNotNull('position_json')->where('position_json', '!=', ''));
            })
            ->get();
    }

    private function loadJsonBots(): Collection
    {
        $bots = [];

        foreach ($this->resolvedBotDirs() as $botDir) {
            // Search in the bot dir itself and in a logs/ subdir
            $searchDirs = array_filter([$botDir, $botDir . '/logs'], 'is_dir');

            // Collect candidate state files:
            //   • generic:  bot_state*.json
            //   • infinitum: demo_live_state.json / live_state.json
            $statePatterns = ['bot_state*.json', 'demo_live_state.json', 'live_state.json'];
            $stateFiles    = [];
            foreach ($searchDirs as $dir) {
                foreach ($statePatterns as $pattern) {
                    foreach (glob(rtrim($dir, '/') . '/' . $pattern) ?: [] as $f) {
                        $stateFiles[$f] = $dir;  // deduplicate
                    }
                }
            }

            foreach ($stateFiles as $file => $dir) {
                $raw = @file_get_contents($file);
                if (! $raw) continue;
                $data = json_decode($raw, true);
                if (! is_array($data)) continue;

                $baseName = basename($file);

                // ── Infinitum "demo_live_state.json" / "live_state.json" ──
                if (in_array($baseName, ['demo_live_state.json', 'live_state.json'])) {
                    $bot = $this->botFromInfinitumState($data, $botDir);
                } else {
                    // ── Legacy bot_state*.json ────────────────────────────
                    $slug = str_replace(['bot_state_', 'bot_state', '.json'], '', $baseName);
                    if ($slug === '') $slug = basename($botDir);
                    $bot  = $this->botFromLegacyState($data, $slug, $botDir);
                }

                if ($bot) $bots[] = $bot;
            }
        }

        return collect($bots);
    }

    /** Build a bot stdClass from an Infinitum "demo_live_state.json" file. */
    private function botFromInfinitumState(array $data, string $botDir): \stdClass
    {
        $slug      = basename($botDir);
        $lastCycle = $data['last_cycle'] ?? [];
        $lastOrder = $data['last_order_plan'] ?? [];

        // Position: bot is "in position" when open_since is non-empty
        $openSince   = $data['open_since'] ?? '';
        $inPosition  = $openSince !== '' && $openSince !== null;
        $positionJson = null;
        if ($inPosition) {
            $positionJson = json_encode([
                'side'       => $data['last_side'] ?? 'long',
                'entry_price'=> (float)($lastOrder['estimated_entry_price'] ?? 0),
                'qty'        => (float)($lastOrder['qty'] ?? 0),
                'pnl_usdt'   => 0.0,
                'notional'   => (float)($lastOrder['notional_usd'] ?? 0),
            ]);
        }

        $bot              = new \stdClass();
        $bot->id          = null;
        $bot->slug        = $slug;
        $bot->symbol      = 'HYPE/USDT';   // TODO: make configurable
        $bot->pair        = 'HYPE/USDT';
        $bot->exchange    = 'bybit';
        $bot->direction   = $data['last_side'] ?? 'long';
        $bot->timeframe   = null;
        $bot->leverage    = 1;
        $bot->status      = ($lastCycle['live_enabled'] ?? false) ? 'running' : 'stopped';
        $bot->account     = '—';
        $bot->config_path = null;
        $bot->readonly    = true;
        $bot->bot_dir     = $botDir;

        $state                 = new \stdClass();
        $state->position_json  = $positionJson;
        $state->mark_price     = 0.0;
        $state->equity         = (float)($lastCycle['balance'] ?? $data['daily_realized_pnl_usd'] ?? 0);
        $state->cycle_state    = ($lastCycle['status'] ?? '') . ':' . ($lastCycle['reason'] ?? '');
        $state->bars_processed = $lastCycle['iteration'] ?? null;
        $state->trades_count   = (int)($data['daily_trade_count'] ?? 0);
        $state->updated_at     = $lastCycle['timestamp'] ?? null;
        $bot->state            = $state;

        return $bot;
    }

    /** Build a bot stdClass from a legacy bot_state*.json file. */
    private function botFromLegacyState(array $data, string $slug, string $botDir): \stdClass
    {
        $bot              = new \stdClass();
        $bot->id          = null;
        $bot->slug        = $slug;
        $bot->symbol      = $data['symbol'] ?? $slug;
        $bot->pair        = $data['symbol'] ?? $slug;
        $bot->exchange    = $data['exchange'] ?? 'bybit';
        $bot->direction   = $data['direction'] ?? 'long';
        $bot->timeframe   = $data['timeframe'] ?? null;
        $bot->leverage    = (int)($data['leverage'] ?? 1);
        $bot->status      = ($data['running'] ?? false) ? 'running' : 'stopped';
        $bot->account     = '—';
        $bot->config_path = $data['config_path'] ?? null;
        $bot->readonly    = true;
        $bot->bot_dir     = $botDir;

        $state                 = new \stdClass();
        $state->position_json  = $data['position'] ?? null;
        $state->mark_price     = (float)($data['mark_price'] ?? 0);
        $state->equity         = (float)($data['equity'] ?? 0);
        $state->cycle_state    = $data['cycle_state'] ?? null;
        $state->bars_processed = $data['bars_processed'] ?? null;
        $state->trades_count   = $data['trades_count'] ?? 0;
        $state->updated_at     = $data['updated_at'] ?? null;
        $bot->state            = $state;

        return $bot;
    }

    // ── Stats ───────────────────────────────────────────────

    public function getStats(): array
    {
        if ($this->viewingHasDb()) return $this->dbStats();
        return $this->jsonStats();
    }

    private function dbStats(): array
    {
        $closed  = Trade::query()->whereNotNull('exit_time');
        if ($from = $this->getSessionStart()) {
            $closed->whereRaw("datetime(exit_time) >= datetime(?)", [$from->toDateTimeString()]);
        }
        $pnl     = (float) (clone $closed)->sum('pnl_usdt');
        $total   = (clone $closed)->count();
        $wins    = (clone $closed)->where('pnl_usdt', '>', 0)->count();
        $winRate = $total > 0 ? round($wins / $total * 100, 1) : 0;
        $running   = Bot::where('status', 'running')->count();
        $totalBots = Bot::count();
        $inPosition = BotState::whereNotNull('position_json')->count();
        $unrealizedPnl = 0.0;
        BotState::whereNotNull('position_json')->pluck('position_json')->each(function ($pos) use (&$unrealizedPnl) {
            if (is_string($pos)) $pos = json_decode($pos, true);
            if (is_array($pos)) $unrealizedPnl += (float)($pos['pnl_usdt'] ?? 0);
        });

        return [
            ['label' => 'PnL закриті',        'value' => ($pnl >= 0 ? '+' : '') . number_format($pnl, 2) . ' USDT',          'sub' => "{$total} угод",                                  'color' => $pnl >= 0 ? '#22c55e' : '#ef4444'],
            ['label' => 'PnL нереалізований', 'value' => ($unrealizedPnl >= 0 ? '+' : '') . number_format($unrealizedPnl, 2) . ' USDT', 'sub' => "{$inPosition} в угоді",                  'color' => $unrealizedPnl >= 0 ? '#22c55e' : '#ef4444'],
            ['label' => 'Win Rate',           'value' => $winRate . '%',                                                      'sub' => "{$wins}/{$total} прибуткових",                  'color' => $winRate >= 50 ? '#22c55e' : '#f59e0b'],
            ['label' => 'Боти',               'value' => "{$running} active · {$inPosition} in trade",                       'sub' => "{$totalBots} всього",                            'color' => '#60a5fa'],
        ];
    }

    private function jsonStats(): array
    {
        $bots = $this->loadJsonBots();

        $running      = $bots->filter(fn ($b) => $b->status === 'running')->count();
        $total        = $bots->count();
        $inPosition   = 0;
        $unrealized   = 0.0;
        $equity       = 0.0;
        $tradesCount  = 0;

        foreach ($bots as $b) {
            $pos = $b->state->position_json ?? null;
            if (is_string($pos)) $pos = json_decode($pos, true);
            if (is_array($pos)) {
                $inPosition++;
                $unrealized += (float)($pos['pnl_usdt'] ?? 0);
            }
            $equity      += (float)($b->state->equity ?? 0);
            $tradesCount += (int)($b->state->trades_count ?? 0);
        }

        // Closed trades from CSV (legacy only; Infinitum events have no PnL)
        $csvTrades   = $this->getJsonTrades()->where('source', '!=', 'infinitum_events');
        $closedPnl   = $csvTrades->sum('pnl_usdt');
        $closedTotal = $csvTrades->count();
        $closedWins  = $csvTrades->where('pnl_usdt', '>', 0)->count();
        $winRate     = $closedTotal > 0 ? round($closedWins / $closedTotal * 100, 1) : 0;

        return [
            ['label' => 'PnL закриті',        'value' => ($closedPnl >= 0 ? '+' : '') . number_format($closedPnl, 2) . ' USDT', 'sub' => "{$closedTotal} угод",                     'color' => $closedPnl >= 0 ? '#22c55e' : '#ef4444'],
            ['label' => 'PnL нереалізований', 'value' => ($unrealized >= 0 ? '+' : '') . number_format($unrealized, 2) . ' USDT', 'sub' => "{$inPosition} в угоді",                  'color' => $unrealized >= 0 ? '#22c55e' : '#ef4444'],
            ['label' => 'Win Rate',           'value' => $winRate . '%',                                                          'sub' => "{$closedWins}/{$closedTotal} прибуткових", 'color' => $winRate >= 50 ? '#22c55e' : '#f59e0b'],
            ['label' => 'Боти',               'value' => "{$running} active · {$inPosition} in trade",                           'sub' => "{$total} всього",                          'color' => '#60a5fa'],
        ];
    }

    // ── Trades ──────────────────────────────────────────────

    public function hasTradeHistory(): bool
    {
        if ($this->viewingHasDb()) return true;
        return count($this->getJsonTradeFiles()) > 0;
    }

    // ── JSON trade CSV reader ────────────────────────────

    /**
     * Returns ['legacy' => [...], 'infinitum' => [...]]
     * legacy   — trades*.csv  (standard format)
     * infinitum — demo_live_events.csv / live_events.csv  (Infinitum event log)
     */
    private function getJsonTradeFiles(): array
    {
        $legacy   = [];
        $infinitum = [];

        foreach ($this->resolvedBotDirs() as $botDir) {
            $searchDirs = array_filter([$botDir, $botDir . '/logs'], 'is_dir');
            foreach ($searchDirs as $dir) {
                foreach (glob(rtrim($dir, '/') . '/trades*.csv') ?: [] as $f) {
                    $legacy[] = $f;
                }
                foreach (['demo_live_events.csv', 'live_events.csv'] as $name) {
                    $f = rtrim($dir, '/') . '/' . $name;
                    if (file_exists($f)) $infinitum[] = $f;
                }
            }
        }

        return compact('legacy', 'infinitum');
    }

    public function getJsonTrades(): \Illuminate\Support\Collection
    {
        $files = $this->getJsonTradeFiles();
        $rows  = [];
        $id    = 1;
        $from  = $this->getSessionStart();

        // ── Legacy trades*.csv ────────────────────────────────────────────
        foreach ($files['legacy'] as $file) {
            $slug   = str_replace(['trades_', '.csv'], '', basename($file));
            $handle = @fopen($file, 'r');
            if (! $handle) continue;

            $headers = null;
            while (($line = fgetcsv($handle, 0, ",", "\"", "")) !== false) {
                if ($headers === null) { $headers = $line; continue; }
                $data = array_combine($headers, $line);

                $exitTime = $data['closed_at'] ?? null;
                if ($from && $exitTime && Carbon::parse($exitTime)->lt($from)) continue;

                $row = new \stdClass();
                $row->id          = $id++;
                $row->bot_name    = $slug;
                $row->pair        = explode('_', $slug)[0] ?? $slug;
                $row->side        = $data['side'] ?? '';
                $row->entry_time  = $data['opened_at'] ?? null;
                $row->exit_time   = $exitTime;
                $row->entry_price = (float)($data['avg_price']  ?? $data['entry_price'] ?? 0);
                $row->exit_price  = (float)($data['exit_price'] ?? 0);
                $row->pnl_usdt    = (float)($data['pnl_usdt']   ?? 0);
                $row->pnl_pct     = (float)($data['pnl_pct']    ?? 0);
                $row->exit_reason = $data['exit_reason'] ?? null;
                $rows[] = $row;
            }
            fclose($handle);
        }

        // ── Infinitum demo_live_events.csv / live_events.csv ─────────────
        // These are event logs. A "trade" = one ORDER_SENT row (entry only;
        // exit happens via exchange TP/SL, so exit_time/price will be null).
        foreach ($files['infinitum'] as $file) {
            $slug   = basename(dirname($file));   // use parent dir as bot name
            $handle = @fopen($file, 'r');
            if (! $handle) continue;

            $headers = null;
            while (($line = fgetcsv($handle, 0, ",", "\"", "")) !== false) {
                if ($headers === null) { $headers = $line; continue; }
                $data = array_combine($headers, $line);

                // Only ORDER_SENT events are actual trade entries
                if (($data['reason'] ?? '') !== 'ORDER_SENT') continue;

                $entryTime = $data['timestamp'] ?? null;
                if ($from && $entryTime && Carbon::parse($entryTime)->lt($from)) continue;

                // action_key: "open_short:short:small:tp60:sl8:h8:d0:c1:market"
                $actionKey = $data['action_key'] ?? '';
                $side      = $data['side'] ?? (str_contains($actionKey, 'short') ? 'short' : 'long');

                // Parse TP/SL from action_key  (tp60 = 60 bps, sl8 = 8 bps)
                $tpBps = 0; $slBps = 0;
                if (preg_match('/tp(\d+)/', $actionKey, $m)) $tpBps = (int)$m[1];
                if (preg_match('/sl(\d+)/', $actionKey, $m)) $slBps = (int)$m[1];

                $row = new \stdClass();
                $row->id          = $id++;
                $row->bot_name    = $slug;
                $row->pair        = 'HYPE/USDT';
                $row->side        = $side;
                $row->entry_time  = $entryTime;
                $row->exit_time   = null;           // not available in event log
                $row->entry_price = 0.0;            // not stored directly; take_profit & stop_loss columns give levels
                $row->exit_price  = 0.0;
                $row->pnl_usdt    = 0.0;
                $row->pnl_pct     = 0.0;
                $row->exit_reason = $tpBps ? "TP={$tpBps}bps SL={$slBps}bps" : null;
                // Extra fields useful for display
                $row->notional_usd   = (float)($data['notional_usd'] ?? 0);
                $row->qty            = (float)($data['qty'] ?? 0);
                $row->take_profit    = (float)($data['take_profit'] ?? 0);
                $row->stop_loss      = (float)($data['stop_loss']   ?? 0);
                $row->ml_score       = (float)($data['ml_score']    ?? 0);
                $row->source         = 'infinitum_events';
                $rows[] = $row;
            }
            fclose($handle);
        }

        // Sort newest first
        usort($rows, fn ($a, $b) => strcmp($b->entry_time ?? '', $a->entry_time ?? ''));
        foreach ($rows as $i => $r) $r->id = $i + 1;

        return collect($rows);
    }
}
