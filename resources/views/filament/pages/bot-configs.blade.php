<x-filament-panels::page>
    @php
        $summary = $this->getSummary();
        $paramKeys = [
            'symbol', 'direction',
            'pair', 'timeframe',
            'exchange', 'leverage',
            'strategy_type', 'margin_mode',
            'take_profit_pct', 'stop_loss_pct',
            'trailing_stop_pct', 'dca_enabled',
            'position_sizing_mode', 'position_size_value',
            'initial_balance', 'reinvest_profits',
            'maker_fee_pct', 'taker_fee_pct',
            'testnet', 'demo',
            'max_daily_loss_pct', 'emergency_stop_loss_pct',
        ];
        $tree         = $this->getConfigTree();
        $hasDirPicker = $this->hasBotsDirPicker();
        $dirTree      = $hasDirPicker ? $this->getDirTree() : [];
    @endphp

    {{-- ── Bot-folder picker (json-mode / bots_root only) ──────────────── --}}
    @if ($hasDirPicker)
    <div class="fi-section rounded-xl bg-white dark:bg-gray-900 ring-1 ring-gray-950/5 dark:ring-white/10"
         style="padding:1rem; margin-bottom:1rem;">

        {{-- Header --}}
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:.6rem;">
            <div>
                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                    Папки ботів
                    <span class="text-xs font-normal text-gray-400 font-mono" style="margin-left:.35rem;">
                        ~/InfinitumBots
                    </span>
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400" style="margin-top:.15rem;">
                    Розкрийте дерево та відмітьте потрібні папки. Нічого не відмічено — показуються всі верхні.
                </p>
            </div>
            <div style="display:flex; gap:.5rem; align-items:center;">
                <button wire:click="selectAllBotDirs"
                        class="text-xs text-primary-600 dark:text-primary-400 hover:underline"
                        style="padding:.2rem .5rem;">
                    Всі ✓
                </button>
                <button wire:click="clearBotDirs"
                        class="text-xs text-gray-400 hover:text-gray-200 hover:underline"
                        style="padding:.2rem .5rem;">
                    Зняти
                </button>
            </div>
        </div>

        {{-- Tree --}}
        @if (empty($dirTree))
            <p class="text-sm text-gray-400">
                Папка
                <code class="text-xs bg-gray-100 dark:bg-gray-800 px-1 rounded">~/InfinitumBots</code>
                порожня або не існує.
            </p>
        @else
            <div style="max-height:260px; overflow-y:auto; padding:.25rem 0;
                        border:1px solid #1f2937; border-radius:.4rem; background:#0a0f1a;">
                @foreach ($dirTree as $node)
                    @include('filament.partials.dir-tree-node', [
                        'node'         => $node,
                        'depth'        => 0,
                        'expandedDirs' => $this->expandedDirs,
                        'activeDirs'   => $this->activeBotDirs,
                    ])
                @endforeach
            </div>
        @endif

        {{-- Status line --}}
        <p class="text-xs text-gray-400" style="margin-top:.45rem;">
            @if (count($this->activeBotDirs) > 0)
                Обрано: <strong class="text-gray-300">{{ count($this->activeBotDirs) }}</strong> папок
            @else
                Нічого не обрано — показуються всі верхні папки
            @endif
        </p>
    </div>
    @endif

    <div style="display:grid; grid-template-columns: 1fr 2fr; gap: 1rem;">
        {{-- LEFT: file list --}}
        <div class="fi-section rounded-xl bg-white dark:bg-gray-900 ring-1 ring-gray-950/5 dark:ring-white/10" style="padding:1rem;">
            <h3 class="text-sm font-semibold text-gray-950 dark:text-white" style="margin-bottom:.75rem;">Конфіги</h3>

            <input type="text" wire:model.live.debounce.200ms="search" placeholder="Пошук..."
                class="text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-white border border-gray-300 dark:border-gray-700 rounded"
                style="width:100%; padding:.25rem .5rem; margin-bottom:.75rem;" />

            @forelse ($tree as $folder)
                @php
                    $files = array_values(array_filter($folder['files'], fn($f) =>
                        $search === '' || stripos($f['name'], $search) !== false));
                @endphp
                @if ($files)
                    <div style="margin-bottom:.75rem;">
                        <div class="text-xs font-semibold text-primary-600 dark:text-primary-400" style="margin-bottom:.25rem;">
                            {{ $folder['name'] }} <span class="text-gray-400">({{ count($files) }})</span>
                        </div>
                        <ul style="display:flex; flex-direction:column; gap:.25rem;">
                            @foreach ($files as $file)
                                <li>
                                    <button type="button"
                                        wire:click="openFile(@js($file['path']))"
                                        class="text-sm text-left hover:bg-gray-100 dark:hover:bg-gray-800 {{ $selectedPath === $file['path'] ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 font-medium' : 'text-gray-700 dark:text-gray-300' }}"
                                        style="width:100%; padding:.25rem .5rem; border-radius:.25rem;">
                                        {{ $file['name'] }}
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @empty
                <p class="text-sm text-gray-500">
                    @if ($hasDirPicker && empty($dirTree))
                        Папка ~/InfinitumBots порожня.
                    @elseif ($hasDirPicker)
                        Конфігів не знайдено у вибраних папках.
                    @else
                        Папка порожня.
                    @endif
                </p>
            @endforelse
        </div>

        {{-- RIGHT: fixed 2-col params grid --}}
        <div class="fi-section rounded-xl bg-white dark:bg-gray-900 ring-1 ring-gray-950/5 dark:ring-white/10" style="padding:1rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:.75rem;">
                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Параметри</h3>
                @if ($selectedPath)
                    <span class="text-xs text-gray-500 font-mono" style="margin-left:.5rem; overflow:hidden; text-overflow:ellipsis;" title="{{ $selectedPath }}">
                        {{ basename($selectedPath) }}
                    </span>
                @else
                    <span class="text-xs text-gray-400">оберіть конфіг</span>
                @endif
            </div>
            <div style="display:grid; grid-template-columns: 1fr 1fr; column-gap:1.5rem; row-gap:.25rem;" class="text-sm">
                @foreach ($paramKeys as $k)
                    <div class="border-b border-gray-100 dark:border-gray-800"
                         style="display:flex; justify-content:space-between; gap:.5rem; padding:.25rem 0;">
                        <span class="text-gray-500 dark:text-gray-400">{{ $k }}</span>
                        <span class="text-gray-900 dark:text-white font-mono" style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="{{ $summary[$k] ?? '' }}">
                            {{ $summary[$k] ?? '—' }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- FULL-WIDTH: raw content editor, only when file selected --}}
    @if ($selectedPath)
        <div class="fi-section rounded-xl bg-white dark:bg-gray-900 ring-1 ring-gray-950/5 dark:ring-white/10" style="padding:1rem; margin-top:1rem;">
            <h3 class="text-sm font-semibold text-gray-950 dark:text-white" style="margin-bottom:.5rem;">Вміст</h3>
            <form wire:submit="save">
                {{ $this->form }}
            </form>
        </div>
    @endif
</x-filament-panels::page>
