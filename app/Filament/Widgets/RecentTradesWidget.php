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

    #[On('dashboardRefresh')]
    public function refresh(): void {}

    public function table(Table $table): Table
    {
        $tenant = app(TenantService::class);

        if (! $tenant->viewingHasDb()) {
            // JSON tenant — empty query, trades shown via JsonTradesWidget
            return $table
                ->query(Trade::query()->whereRaw('1 = 0'))
                ->columns([])
                ->emptyStateHeading('')
                ->emptyStateDescription('');
        }

        $query = Trade::query()->orderByDesc('id');
        if ($from = $tenant->getSessionStart()) {
            $query->whereRaw("datetime(exit_time) >= datetime(?)", [$from->toDateTimeString()]);
        }

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('bot_name')
                    ->label('Бот')
                    ->getStateUsing(fn (Trade $r) => optional(Bot::find($r->bot_id))->slug ?? "#{$r->bot_id}")
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
                Tables\Columns\TextColumn::make('pnl_pct')->label('PnL %')->numeric(2)->suffix(' %')->sortable(),
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
}
