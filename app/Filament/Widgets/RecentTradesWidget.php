<?php

namespace App\Filament\Widgets;

use App\Models\Bot;
use App\Models\Trade;
use App\Services\TenantService;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Livewire\Attributes\On;

class RecentTradesWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Останні угоди';
    protected ?string $pollingInterval = null;
    private static ?array $scoreCache = null;

    #[On('dashboardRefresh')]
    public function refresh(): void {}

    public function table(Table $table): Table
    {
        $tenant = app(TenantService::class);

        if ($tenant->dashboardMode() === 'demo') {
            return $table
                ->query(Trade::query()->whereRaw('1 = 0'))
                ->columns([])
                ->emptyStateHeading('DEMO: дані недоступні')
                ->emptyStateDescription('Джерело DEMO для цього кабінету не підключене.');
        }

        if (! $tenant->viewingHasDb()) {
            // JSON tenant — empty query, trades shown via JsonTradesWidget
            return $table
                ->query(Trade::query()->whereRaw('1 = 0'))
                ->columns([])
                ->emptyStateHeading('')
                ->emptyStateDescription('');
        }

        $query = Trade::query()->orderByDesc('id');
        $query->whereIn('bot_id', $tenant->dashboardBotIds());
        if ($from = $tenant->getSessionStart()) {
            $query->whereRaw("datetime(exit_time) >= datetime(?)", [$from->toDateTimeString()]);
        }

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('bot_name')
                    ->label('Бот')
                    ->getStateUsing(fn (Trade $r) => static::displayBotName(optional(Bot::find($r->bot_id))->slug ?? "#{$r->bot_id}"))
                    ->searchable(query: function ($query, string $search) {
                        $ids = Bot::where('slug', 'like', "%{$search}%")->pluck('id');
                        $query->whereIn('bot_id', $ids);
                    }),
                Tables\Columns\TextColumn::make('pair')->label('Pair')
                    ->formatStateUsing(fn ($state) => strtoupper(explode('/', $state)[0] ?? $state)),
                Tables\Columns\TextColumn::make('side')->label('Side')->badge()
                    ->color(fn ($state) => $state === 'buy' ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('entry_time')->label('Вхід')->dateTime('m-d H:i')->sortable(),
                Tables\Columns\TextColumn::make('exit_time')->label('Вихід')->dateTime('m-d H:i')->sortable(),
                Tables\Columns\TextColumn::make('entry_price')->label('Ціна вхід')->numeric(4)->toggleable(),
                Tables\Columns\TextColumn::make('exit_price')->label('Ціна вихід')->numeric(4)->toggleable(),
                Tables\Columns\TextColumn::make('pnl_usdt')->label('PnL USDT')->numeric(2)->sortable()
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger')
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('Σ')->numeric(2)),
                Tables\Columns\TextColumn::make('trade_score')
                    ->label('Score')
                    ->getStateUsing(fn (Trade $r) => static::scoreLabel($r->id))
                    ->badge()
                    ->color(fn ($state) => is_numeric($state) && (float) $state >= 85 ? 'success' : (is_numeric($state) && (float) $state >= 70 ? 'warning' : 'gray')),
                Tables\Columns\TextColumn::make('price_move_pct')
                    ->label('Move %')
                    ->getStateUsing(fn (Trade $r) => static::priceMovePct($r))
                    ->numeric(2)
                    ->suffix(' %')
                    ->color(fn ($state) => (float) $state >= 0 ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('mfe_pct')
                    ->label('MFE %')
                    ->getStateUsing(fn (Trade $r) => static::pathMetric($r->id, 'mfe_pct'))
                    ->numeric(2)
                    ->suffix(' %')
                    ->color('success')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('mae_pct')
                    ->label('MAE %')
                    ->getStateUsing(fn (Trade $r) => static::pathMetric($r->id, 'mae_pct'))
                    ->numeric(2)
                    ->suffix(' %')
                    ->color('danger')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('exit_reason')->label('Причина')->badge()->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('bot_id')
                    ->label('Бот')
                    ->options(fn () => Bot::orderBy('slug')->pluck('slug', 'id')->toArray()),
                Tables\Filters\SelectFilter::make('period')
                    ->label('Період')
                    ->options(['today' => 'Сьогодні', 'week' => 'Тиждень', 'month' => 'Місяць'])
                    ->query(function ($query, array $data) {
                        $v = $data['value'] ?? null;
                        if ($v === 'today') $query->whereDate('exit_time', today());
                        if ($v === 'week')  $query->where('exit_time', '>=', now()->subWeek());
                        if ($v === 'month') $query->where('exit_time', '>=', now()->subMonth());
                    }),
                Tables\Filters\SelectFilter::make('result')
                    ->label('Результат')
                    ->options(['profit' => 'Прибуткові', 'loss' => 'Збиткові'])
                    ->query(function ($query, array $data) {
                        $v = $data['value'] ?? null;
                        if ($v === 'profit') $query->where('pnl_usdt', '>', 0);
                        if ($v === 'loss')   $query->where('pnl_usdt', '<', 0);
                    }),
            ])
            ->defaultSort('id', 'desc')
            ->paginated([20, 50, 100]);
    }

    private static function displayBotName(?string $slug): string
    {
        $slug = (string) $slug;
        if (preg_match('/^([A-Z0-9]+)_USDT_USDT_(long|short)/', $slug, $m)) {
            return "{$m[1]}_{$m[2]}";
        }
        if (preg_match('/^([A-Z0-9]+)_USDT_(long|short)/', $slug, $m)) {
            return "{$m[1]}_{$m[2]}";
        }

        return $slug;
    }

    private static function scoreLabel(int $tradeId): string
    {
        $score = static::scores()[$tradeId]['scores']['total_score'] ?? null;
        return is_numeric($score) ? number_format((float) $score, 1, '.', '') : '—';
    }

    private static function pathMetric(int $tradeId, string $key): ?float
    {
        $path = static::scores()[$tradeId]['path_context'] ?? static::scores()[$tradeId]['touch_context'] ?? [];
        $value = is_array($path) ? ($path[$key] ?? null) : null;

        return is_numeric($value) ? (float) $value : null;
    }

    private static function priceMovePct(Trade $trade): float
    {
        $entry = (float) $trade->entry_price;
        $exit = (float) $trade->exit_price;
        if ($entry <= 0 || $exit <= 0) {
            return 0.0;
        }

        $side = strtolower((string) $trade->side);
        if (in_array($side, ['short', 'sell'], true)) {
            return ($entry - $exit) / $entry * 100.0;
        }

        return ($exit - $entry) / $entry * 100.0;
    }

    private static function scores(): array
    {
        if (self::$scoreCache !== null) {
            return self::$scoreCache;
        }

        $path = '/home/ubuntu/SafaryEngine/data/strategy_search/live_trade_scores_latest.json';
        $data = is_file($path) ? json_decode((string) @file_get_contents($path), true) : [];
        $rows = is_array($data['trades'] ?? null) ? $data['trades'] : [];
        self::$scoreCache = [];
        foreach ($rows as $row) {
            if (is_array($row) && isset($row['trade_id'])) {
                self::$scoreCache[(int) $row['trade_id']] = $row;
            }
        }
        return self::$scoreCache;
    }
}
