<x-filament-panels::page>
    <div class="space-y-3">
        @if($this->canManageTables())
            <details class="rounded-lg border border-gray-200 bg-white px-3 py-2 dark:border-gray-700 dark:bg-gray-900">
                <summary class="cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-200">Додати таблицю XLSX / CSV</summary>
                <form wire:submit="importTable" class="mt-3 grid gap-3 border-t border-gray-200 pt-3 md:grid-cols-[minmax(12rem,1fr)_minmax(16rem,2fr)_auto] md:items-end dark:border-gray-700">
                    <label class="block">
                        <span class="mb-1 block text-xs text-gray-600 dark:text-gray-300">Назва</span>
                        <input wire:model="tableTitle" type="text" maxlength="180" placeholder="Автоматично з імені файлу"
                            class="w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800">
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs text-gray-600 dark:text-gray-300">Файл</span>
                        <input wire:model="upload" type="file" accept=".xlsx,.csv"
                            class="block w-full rounded-lg border border-gray-300 bg-white text-sm text-gray-700 file:mr-3 file:border-0 file:bg-gray-100 file:px-3 file:py-2 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:file:bg-gray-700">
                        @error('upload') <span class="mt-1 block text-sm text-danger-600">{{ $message }}</span> @enderror
                    </label>
                    <x-filament::button type="submit" icon="heroicon-o-arrow-up-tray">Додати</x-filament::button>
                    <p class="text-xs text-gray-500 dark:text-gray-400 md:col-span-3">Імпортується перший видимий аркуш книги Excel.</p>
                </form>
            </details>
        @endif

        @if($tables->isNotEmpty())
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs text-gray-500 dark:text-gray-400">Таблиці:</span>
                @foreach($tables as $table)
                    <div class="flex items-center overflow-hidden rounded-lg border {{ $selectedTable?->id === $table->id ? 'border-primary-500 bg-primary-50 dark:bg-primary-950' : 'border-gray-200 dark:border-gray-700' }}">
                        <button type="button" wire:click="selectTable({{ $table->id }})" class="px-2.5 py-1.5 text-left text-xs text-gray-800 dark:text-gray-100">
                            <span class="font-medium">{{ $table->title }}</span>
                            <span class="ml-1 text-gray-500 dark:text-gray-400">{{ number_format($table->row_count) }}</span>
                        </button>
                        @if($this->canManageTables())
                            <button type="button" wire:click="deleteTable({{ $table->id }})" wire:confirm="Видалити цю таблицю та всі її дані?"
                                title="Видалити таблицю" class="border-l border-gray-200 px-2 py-1.5 text-gray-500 hover:bg-danger-50 hover:text-danger-600 dark:border-gray-700 dark:hover:bg-danger-950">
                                <x-filament::icon icon="heroicon-o-trash" class="h-3.5 w-3.5" />
                            </button>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        @if($selectedTable)
            @php($rows = $selectedTable->rows_json ?? [])
            <section x-data="excelGrid(@js($selectedTable->column_headers ?? []), @js($rows))"
                class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="space-y-2 border-b border-gray-200 p-3 dark:border-gray-700">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $selectedTable->title }}</h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $selectedTable->source_filename ?: 'Додано на сайті' }}@if($selectedTable->sheet_name) · аркуш «{{ $selectedTable->sheet_name }}»@endif · {{ number_format($selectedTable->row_count) }} рядків
                            </p>
                        </div>
                        <button type="button" @click="clearFilters()" class="rounded border border-gray-300 px-2.5 py-1.5 text-xs text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800">
                            Скинути фільтри
                        </button>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <input x-model.debounce.200ms="search" type="search" aria-label="Пошук по всій таблиці" placeholder="Пошук по таблиці"
                            class="min-w-[13rem] flex-1 rounded-lg border-gray-300 px-2.5 py-1.5 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800">
                        <select x-model.number="rangeColumn" aria-label="Числовий стовпець" title="Числовий стовпець для фільтра за діапазоном"
                            class="min-w-[13rem] max-w-[20rem] rounded-lg border-gray-300 px-2.5 py-1.5 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800">
                            <template x-for="column in rangeColumns" :key="column.index">
                                <option :value="column.index" x-text="column.label"></option>
                            </template>
                        </select>
                        <div class="flex items-center gap-1" title="Оберіть стовпець і введіть одну або обидві межі — фільтр застосовується одразу.">
                            <input x-model="minimum" type="number" step="any" aria-label="Мінімальне значення" placeholder="Від"
                                class="w-24 rounded-lg border-gray-300 px-2.5 py-1.5 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800">
                            <span class="text-xs text-gray-400">—</span>
                            <input x-model="maximum" type="number" step="any" aria-label="Максимальне значення" placeholder="До"
                                class="w-24 rounded-lg border-gray-300 px-2.5 py-1.5 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800">
                        </div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">від/до застосовуються одразу</span>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-gray-500 dark:text-gray-400">
                        <span>Показано <span class="font-semibold text-gray-800 dark:text-gray-100" x-text="filteredRows.length"></span> із {{ number_format($selectedTable->row_count) }}</span>
                        <label class="flex items-center gap-2">Рядків на сторінці
                            <select x-model.number="pageSize" @change="page = 1" class="rounded-lg border-gray-300 py-1 text-xs dark:border-gray-600 dark:bg-gray-800">
                                <option value="25">25</option><option value="50">50</option><option value="100">100</option><option value="250">250</option><option value="0">Усі</option>
                            </select>
                        </label>
                    </div>
                </div>

                <div class="max-h-[70vh] overflow-auto">
                    <table class="min-w-full border-separate border-spacing-0 text-left text-xs">
                        <thead class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <template x-for="(column, index) in displayColumns" :key="'head-' + index">
                                    <th class="min-w-[7.5rem] max-w-[10rem] border-b border-r border-gray-200 px-1.5 py-1.5 align-top font-semibold text-gray-800 dark:border-gray-700 dark:text-gray-100">
                                        <div class="flex items-start justify-between gap-1">
                                            <span class="whitespace-pre-line break-words text-left leading-tight" :title="column.label" x-text="column.label"></span>
                                            <span class="flex shrink-0 gap-0.5">
                                                <button type="button" @click="sortBy(column.sortIndex, 'desc')" :aria-label="'Сортувати ' + column.label + ' за спаданням'" title="За спаданням"
                                                    :class="sortColumn === column.sortIndex && sortDirection === 'desc' ? 'bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-200' : 'text-gray-500 hover:bg-gray-200 dark:text-gray-300 dark:hover:bg-gray-700'"
                                                    class="rounded px-1 text-xs">↓</button>
                                                <button type="button" @click="sortBy(column.sortIndex, 'asc')" :aria-label="'Сортувати ' + column.label + ' за зростанням'" title="За зростанням"
                                                    :class="sortColumn === column.sortIndex && sortDirection === 'asc' ? 'bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-200' : 'text-gray-500 hover:bg-gray-200 dark:text-gray-300 dark:hover:bg-gray-700'"
                                                    class="rounded px-1 text-xs">↑</button>
                                            </span>
                                        </div>
                                    </th>
                                </template>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <template x-for="(row, rowIndex) in pageRows" :key="page + '-' + rowIndex">
                                <tr class="group">
                                    <template x-for="(column, colIndex) in displayColumns" :key="colIndex">
                                        <td class="whitespace-nowrap border-r border-gray-100 px-2 py-1.5 text-gray-700 dark:border-gray-800 dark:text-gray-200 group-hover:bg-gray-200 group-hover:text-gray-900 dark:group-hover:bg-gray-700 dark:group-hover:text-white"
                                            :class="column.numeric ? 'text-right tabular-nums' : 'text-left'"
                                            :title="column.kind === 'tp_sl_values' ? exactTpSl(rowIndex) : ''"
                                            x-text="row[colIndex]"></td>
                                    </template>
                                </tr>
                            </template>
                            <tr x-show="filteredRows.length === 0">
                                <td :colspan="displayColumns.length" class="px-4 py-8 text-center text-sm text-gray-500">За цими фільтрами рядків немає.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-between border-t border-gray-200 px-4 py-3 text-xs dark:border-gray-700">
                    <span class="text-gray-500 dark:text-gray-400">Сторінка <span x-text="page"></span> із <span x-text="pageCount"></span></span>
                    <div class="flex gap-2">
                        <button type="button" @click="page = Math.max(1, page - 1)" :disabled="page <= 1" class="rounded border border-gray-300 px-3 py-1.5 disabled:opacity-40 dark:border-gray-600">Назад</button>
                        <button type="button" @click="page = Math.min(pageCount, page + 1)" :disabled="page >= pageCount" class="rounded border border-gray-300 px-3 py-1.5 disabled:opacity-40 dark:border-gray-600">Далі</button>
                    </div>
                </div>
            </section>
        @endif
    </div>

    <script>
        window.excelGrid = (columns, rows) => ({
            columns,
            rows,
            search: '',
            rangeColumn: Math.max(0, columns.findIndex((column) => /повний період|full period/i.test(column))),
            minimum: '',
            maximum: '',
            sortColumn: Math.max(0, columns.findIndex((column) => /pnl повний період/i.test(column))),
            sortDirection: 'desc',
            page: 1,
            pageSize: 50,
            get indexes() {
                return {
                    trial: this.columns.indexOf('Optuna trial'),
                    trades: this.columns.indexOf('Угоди, повний період'),
                    tpValue: this.columns.indexOf('TP, % руху ціни'),
                    slValue: this.columns.indexOf('SL, % руху ціни'),
                    tpCount: this.columns.indexOf('TP, повний період'),
                    slCount: this.columns.indexOf('SL, повний період'),
                    archiveTp: this.columns.indexOf('Архів: TP'),
                    archiveSl: this.columns.indexOf('Архів: SL'),
                };
            },
            get displayColumns() {
                const ix = this.indexes;
                const hidden = new Set([ix.trial, ix.tpValue, ix.slValue]);
                const definitions = [];
                let tpSlInserted = false;

                for (let index = 0; index < this.columns.length; index++) {
                    if (index === ix.trades && !tpSlInserted) {
                        definitions.push({label: 'TP / SL', kind: 'tp_sl_values', indexes: [ix.tpValue, ix.slValue], sortIndex: ix.tpValue, numeric: true});
                        tpSlInserted = true;
                    }

                    if (hidden.has(index) || index < 0) continue;

                    if (index === ix.tpCount) {
                        definitions.push({label: 'TP / SL угоди', kind: 'tp_sl_counts', indexes: [ix.tpCount, ix.slCount], sortIndex: ix.tpCount, numeric: true});
                        continue;
                    }
                    if (index === ix.slCount) continue;
                    if (index === ix.trades) {
                        definitions.push({label: 'Угоди', kind: 'value', index, sortIndex: index, numeric: typeof this.rows[0]?.[index] === 'number'});
                        continue;
                    }
                    if (index === ix.archiveTp) {
                        definitions.push({label: 'Архів TP / SL', kind: 'archive_tp_sl_counts', indexes: [ix.archiveTp, ix.archiveSl], sortIndex: ix.archiveTp, numeric: true});
                        continue;
                    }
                    if (index === ix.archiveSl) continue;

                    definitions.push({
                        label: this.columnLabel(this.columns[index]),
                        kind: 'value', index, sortIndex: index,
                        numeric: typeof this.rows[0]?.[index] === 'number',
                    });
                }
                return definitions;
            },
            get rangeColumns() {
                return this.columns.map((header, index) => ({index, label: this.columnLabel(header)}))
                    .filter(({index}) => index !== this.indexes.trial && index !== this.indexes.tpValue && index !== this.indexes.slValue && typeof this.rows[0]?.[index] === 'number');
            },
            get filteredRows() {
                const query = this.search.trim().toLocaleLowerCase();
                const min = this.minimum === '' ? null : Number(this.minimum);
                const max = this.maximum === '' ? null : Number(this.maximum);

                return this.rows.filter((row) => {
                    if (query && !row.some((value) => String(value ?? '').toLocaleLowerCase().includes(query))) return false;

                    const rangeValue = Number(row[this.rangeColumn]);
                    if (min !== null && (!Number.isFinite(rangeValue) || rangeValue < min)) return false;
                    if (max !== null && (!Number.isFinite(rangeValue) || rangeValue > max)) return false;
                    return true;
                }).sort((left, right) => {
                    const a = left[this.sortColumn];
                    const b = right[this.sortColumn];
                    const an = Number(a);
                    const bn = Number(b);
                    const bothNumeric = a !== null && a !== '' && b !== null && b !== '' && Number.isFinite(an) && Number.isFinite(bn);
                    const comparison = bothNumeric ? an - bn : String(a ?? '').localeCompare(String(b ?? ''), 'uk', {numeric: true, sensitivity: 'base'});
                    return this.sortDirection === 'asc' ? comparison : -comparison;
                });
            },
            get pageCount() {
                return this.pageSize === 0 ? 1 : Math.max(1, Math.ceil(this.filteredRows.length / this.pageSize));
            },
            get pageRows() {
                this.page = Math.min(this.page, this.pageCount);
                const start = this.pageSize === 0 ? 0 : (this.page - 1) * this.pageSize;
                const source = this.pageSize === 0 ? this.filteredRows : this.filteredRows.slice(start, start + this.pageSize);
                return source.map((row) => this.displayColumns.map((column) => {
                    if (column.kind === 'tp_sl_values') return `TP ${this.format(row[column.indexes[0]], column.indexes[0])}% / SL ${this.format(row[column.indexes[1]], column.indexes[1])}%`;
                    if (column.kind === 'tp_sl_counts' || column.kind === 'archive_tp_sl_counts') return `${this.format(row[column.indexes[0]])} / ${this.format(row[column.indexes[1]])}`;
                    return this.format(row[column.index], column.index);
                }));
            },
            sortBy(index, direction) {
                this.sortColumn = index;
                this.sortDirection = direction;
                this.page = 1;
            },
            columnLabel(column) {
                const shortNames = {
                    'Ранг за загальним PnL': 'Ранг',
                    'PnL повний період, USDT': 'PnL All',
                    'PnL 2023, USDT': 'PnL 2023',
                    'PnL 2024, USDT': 'PnL 2024',
                    'PnL 2025, USDT': 'PnL 2025',
                    'PnL 2026 до 19.09, USDT': 'PnL 2026 до 19.09',
                    'Архів PnL 2023, USDT': 'Архів PnL 2023',
                    'Архів PnL 2024, USDT': 'Архів PnL 2024',
                    'Архів PnL 2025, USDT': 'Архів PnL 2025',
                    'Архів PnL 2026, USDT': 'Архів PnL 2026',
                    'Макс. DD балансу, %': 'DD балансу %',
                    'Макс. DD балансу, USDT': 'DD балансу USDT',
                    'DD з відкритою позицією, %': 'DD позиції %',
                    'Угоди, повний період': 'Угоди',
                    'Сигнали, повний період': 'Сигнали',
                    'Прибуткові угоди': 'Прибутк. угоди',
                    'Збиткові угоди': 'Збитк. угоди',
                    'Комісії, USDT': 'Комісії USDT',
                    'Funding net, USDT': 'Funding USDT',
                    'PnL архівний період, USDT': 'Архів PnL',
                    'Архів: макс. DD балансу, %': 'Архів DD балансу %',
                    'Архів: макс. DD балансу, USDT': 'Архів DD балансу USDT',
                    'Архів: DD з відкритою позицією, %': 'Архів DD позиції %',
                    'Архів: угоди': 'Архів угоди',
                    'Архів: комісії, USDT': 'Архів комісії',
                    'Архів: funding net, USDT': 'Архів funding',
                    'failure_depth_min_pct': 'Глибина пробою %',
                    'reclaim_min_pct': 'Повернення %',
                    'htf_filter': 'HTF фільтр',
                    'htf_tf': 'HTF TF',
                    'htf_period': 'HTF період',
                    'htf_fast': 'HTF fast',
                    'htf_slow': 'HTF slow',
                };
                return shortNames[column] ?? column;
            },
            format(value, columnIndex) {
                if (typeof value === 'number') {
                    const header = String(this.columns[columnIndex] ?? '');
                    const wholeNumber = header.includes('USDT') || /DD/i.test(header) || Number.isInteger(value);
                    return new Intl.NumberFormat('uk-UA', {maximumFractionDigits: wholeNumber ? 0 : 4}).format(value);
                }
                return value ?? '';
            },
            exactTpSl(rowIndex) {
                const source = this.pageSize === 0 ? this.filteredRows : this.filteredRows.slice((this.page - 1) * this.pageSize, this.page * this.pageSize);
                const row = source[rowIndex];
                if (!row) return '';
                return `TP ${row[this.indexes.tpValue]}% / SL ${row[this.indexes.slValue]}%`;
            },
            clearFilters() {
                this.search = '';
                this.minimum = '';
                this.maximum = '';
                this.page = 1;
            },
        });
    </script>
</x-filament-panels::page>
