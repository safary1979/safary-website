<?php

namespace App\Filament\Pages;

use App\Models\ApiAccount;
use App\Models\Bot;
use App\Services\BotCommandService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class BotConfigs extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-folder-open';
    protected static ?string $navigationLabel = 'Конфіги ботів';
    protected static ?string $title = 'Конфіги ботів';
    protected static ?int $navigationSort = 20;
    protected static string $view = 'filament.pages.bot-configs';

    public static function canAccess(): bool
    {
        return app(\App\Services\TenantService::class)->canSwitch();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    protected function tenantConfigsPath(): string
    {
        $p = app(\App\Services\TenantService::class)->configsPath();
        return $p ?: (string) config('cabinet.bot_configs_path', '/home/ubuntu/SafaryEngine/live/configs');
    }

    /**
     * Returns the list of root directories to scan for config files.
     * For DB-mode: just [tenantConfigsPath()].
     * For json-mode (bots_root): the resolved per-bot dirs, each with a configs/ sub or root.
     */
    protected function getConfigRoots(): array
    {
        $svc = app(\App\Services\TenantService::class);

        if ($svc->getBotsRoot()) {
            // json-mode: resolved bot dirs → look for configs/ subdir or root
            $roots = [];
            foreach ($svc->resolvedBotDirs() as $botDir) {
                $configsSubDir = $botDir . '/configs';
                $roots[] = [
                    'label' => basename($botDir),
                    'root'  => is_dir($configsSubDir) ? $configsSubDir : $botDir,
                    'guard' => $botDir,   // path traversal guard: must stay inside bot dir
                ];
            }
            return $roots;
        }

        return [[
            'label' => basename($this->tenantConfigsPath()),
            'root'  => $this->tenantConfigsPath(),
            'guard' => $this->tenantConfigsPath(),
        ]];
    }

    public ?string $selectedPath = null;
    public ?string $content      = null;
    public string  $search       = '';

    /** Relative paths of bot dirs the user has ticked (json-mode, any depth). */
    public array $activeBotDirs  = [];

    /** Relative paths of dirs that are currently expanded in the tree. */
    public array $expandedDirs   = [];

    public function getSummary(): array
    {
        if (!$this->selectedPath || !is_file($this->selectedPath)) {
            return [];
        }
        $raw = file_get_contents($this->selectedPath) ?: '';
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return [];
        }
        $keys = [
            'symbol', 'pair', 'exchange', 'direction', 'timeframe',
            'leverage', 'margin_mode', 'position_sizing_mode', 'position_size_value',
            'initial_balance', 'reinvest_profits',
            'strategy_type', 'strategy_enabled',
            'entry_mode', 'cycle_entry_mode',
            'take_profit_pct', 'stop_loss_pct', 'stop_loss_enabled', 'trailing_stop_pct',
            'dca_enabled', 'dca_mode',
            'testnet', 'demo', 'phantom',
            'maker_fee_pct', 'taker_fee_pct',
            'max_daily_loss_pct', 'emergency_stop_loss_pct',
        ];
        $out = [];
        foreach ($keys as $k) {
            if (array_key_exists($k, $data)) {
                $v = $data[$k];
                if (is_bool($v)) $v = $v ? 'true' : 'false';
                if (is_array($v)) $v = '['.count($v).']';
                $out[$k] = (string) $v;
            }
        }
        return $out;
    }

    public function mount(): void
    {
        $this->form->fill();
        // Load previously saved selections
        $this->activeBotDirs = app(\App\Services\TenantService::class)->getSelectedBotDirNames();
    }

    // ── Bot-folder selector (json-mode only) ──────────────────────────────

    /** All subdirs available under bots_root → [name => path] (top-level only). */
    public function getAvailableBotDirs(): array
    {
        return app(\App\Services\TenantService::class)->getAvailableBotDirs();
    }

    /** Full recursive tree for the directory picker. */
    public function getDirTree(): array
    {
        $svc  = app(\App\Services\TenantService::class);
        $root = $svc->getBotsRoot();
        if (! $root || ! is_dir($root)) return [];
        return $svc->buildDirTree($root);
    }

    /** Expand or collapse a directory node in the tree. */
    public function toggleExpandDir(string $relPath): void
    {
        if (in_array($relPath, $this->expandedDirs, true)) {
            $this->expandedDirs = array_values(
                array_filter($this->expandedDirs, fn ($p) => $p !== $relPath)
            );
        } else {
            $this->expandedDirs[] = $relPath;
        }
    }

    /** Is this a json-mode tenant with a bots_root? */
    public function hasBotsDirPicker(): bool
    {
        return app(\App\Services\TenantService::class)->getBotsRoot() !== null;
    }

    /** Toggle a single dir name in/out of the active list and persist. */
    public function toggleBotDir(string $name): void
    {
        if (in_array($name, $this->activeBotDirs, true)) {
            $this->activeBotDirs = array_values(array_filter($this->activeBotDirs, fn ($n) => $n !== $name));
        } else {
            $this->activeBotDirs[] = $name;
        }
        app(\App\Services\TenantService::class)->setSelectedBotDirNames($this->activeBotDirs);
        Notification::make()->title('Папки збережено')->success()->send();
    }

    /** Collect all relPaths from a recursive tree (flat). */
    private function flattenDirTree(array $nodes): array
    {
        $out = [];
        foreach ($nodes as $node) {
            $out[] = $node['relPath'];
            if (! empty($node['children'])) {
                $out = array_merge($out, $this->flattenDirTree($node['children']));
            }
        }
        return $out;
    }

    /** Select all dirs in the full tree (every node at every depth). */
    public function selectAllBotDirs(): void
    {
        $this->activeBotDirs = $this->flattenDirTree($this->getDirTree());
        app(\App\Services\TenantService::class)->setSelectedBotDirNames($this->activeBotDirs);
    }

    /** Deselect all (= show all, since empty means "use all"). */
    public function clearBotDirs(): void
    {
        $this->activeBotDirs = [];
        app(\App\Services\TenantService::class)->setSelectedBotDirNames([]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Textarea::make('content')
                ->label('Вміст конфіга')
                ->rows(25)
                ->extraInputAttributes(['style' => 'font-family: monospace; font-size: 13px;']),
        ]);
    }

    public function getConfigTree(): array
    {
        $tree = [];

        foreach ($this->getConfigRoots() as $rootDef) {
            $root = rtrim($rootDef['root'], '/');
            if (! is_dir($root)) continue;

            // Files directly in this root
            $rootFiles = [];
            foreach (glob($root . '/*.{yaml,yml,json,toml}', GLOB_BRACE) ?: [] as $file) {
                $rootFiles[] = ['name' => basename($file), 'path' => $file];
            }
            if ($rootFiles) {
                $tree[] = ['name' => $rootDef['label'], 'path' => $root, 'files' => $rootFiles];
            }

            // Subdirectories (only for non-bots_root mode to avoid double-nesting)
            if (! app(\App\Services\TenantService::class)->getBotsRoot()) {
                foreach (glob($root . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
                    $files = [];
                    foreach (glob($dir . '/*.{yaml,yml,json,toml}', GLOB_BRACE) ?: [] as $file) {
                        $files[] = ['name' => basename($file), 'path' => $file];
                    }
                    $tree[] = ['name' => basename($dir), 'path' => $dir, 'files' => $files];
                }
            }
        }

        return $tree;
    }

    public function openFile(string $path): void
    {
        $real = realpath($path);
        if (! $real) {
            Notification::make()->title('Файл не знайдено')->danger()->send();
            return;
        }

        // Verify the file lives inside one of the allowed roots
        $allowed = false;
        foreach ($this->getConfigRoots() as $rootDef) {
            $guard = realpath($rootDef['guard']);
            if ($guard && str_starts_with($real, $guard)) { $allowed = true; break; }
        }
        if (! $allowed) {
            Notification::make()->title('Недозволений шлях')->danger()->send();
            return;
        }

        $this->selectedPath = $real;
        $this->content = file_get_contents($real) ?: '';
        $this->form->fill(['content' => $this->content]);
    }

    public function save(): void
    {
        if (!$this->selectedPath) {
            return;
        }
        if (! app(\App\Services\TenantService::class)->canControl()) {
            Notification::make()->title('Read-only')->warning()->send();
            return;
        }
        $data = $this->form->getState();
        file_put_contents($this->selectedPath, $data['content']);
        Notification::make()->title('Збережено')->success()->send();
    }

    public function launchBot(array $data): void
    {
        if (!$this->selectedPath) {
            Notification::make()->title('Оберіть конфіг')->warning()->send();
            return;
        }
        $account = $data['account'] ?? null;
        if (!$account) {
            Notification::make()->title('Оберіть акаунт')->warning()->send();
            return;
        }
        $bot = Bot::where('config_path', $this->selectedPath)->first();
        $botId = $bot?->id ?? 0;
        app(BotCommandService::class)->issue($botId, 'start', [
            'config_path' => $this->selectedPath,
            'account' => $account,
        ]);
        Notification::make()
            ->title("Команда start ({$account}) надіслана")
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        $canControl = app(\App\Services\TenantService::class)->canControl();
        return [
            Action::make('save')->label('Зберегти')->icon('heroicon-o-document-check')
                ->color('primary')->action('save')
                ->visible(fn () => $canControl && (bool) $this->selectedPath),
            Action::make('launch')->label('Запустити бота')->icon('heroicon-o-rocket-launch')
                ->color('success')
                ->visible(fn () => $canControl && (bool) $this->selectedPath)
                ->form([
                    Select::make('account')
                        ->label('API key / акаунт')
                        ->options(fn () => ApiAccount::orderBy('name')
                            ->pluck('name', 'name')->toArray())
                        ->required(),
                ])
                ->action(fn (array $data) => $this->launchBot($data)),
        ];
    }
}
