<?php

namespace App\Filament\Widgets;

use App\Models\Trade;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentTradesWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Останні угоди';
    protected ?string $pollingInterval = '15s';

    public function table(Table $table): Table
    {
        return $table
            ->query(Trade::query()->orderByDesc('id')->limit(20))
            ->columns([
                Tables\Columns\TextColumn::make('id'),
                Tables\Columns\TextColumn::make('bot_id'),
                Tables\Columns\TextColumn::make('pair'),
                Tables\Columns\TextColumn::make('side')->badge()
                    ->color(fn ($state) => $state === 'buy' ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('entry_time')->dateTime('Y-m-d H:i'),
                Tables\Columns\TextColumn::make('exit_time')->dateTime('Y-m-d H:i'),
                Tables\Columns\TextColumn::make('entry_price')->numeric(4),
                Tables\Columns\TextColumn::make('exit_price')->numeric(4),
                Tables\Columns\TextColumn::make('pnl_usdt')->numeric(2)
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('pnl_pct')->numeric(2)->suffix(' %'),
                Tables\Columns\TextColumn::make('exit_reason')->badge(),
            ])
            ->paginated(false);
    }
}
