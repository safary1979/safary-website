<?php

namespace App\Filament\Widgets;

use App\Models\Bot;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ActiveBotsWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Активні боти';
    protected ?string $pollingInterval = '10s';

    public function table(Table $table): Table
    {
        return $table
            ->query(Bot::query()->whereIn('status', ['running', 'paused']))
            ->columns([
                Tables\Columns\TextColumn::make('slug')->searchable(),
                Tables\Columns\TextColumn::make('pair'),
                Tables\Columns\TextColumn::make('exchange')->badge(),
                Tables\Columns\TextColumn::make('direction')->badge(),
                Tables\Columns\TextColumn::make('timeframe'),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn ($state) => $state === 'running' ? 'success' : 'warning'),
                Tables\Columns\TextColumn::make('state.equity')->label('Equity')->numeric(2),
                Tables\Columns\TextColumn::make('state.mark_price')->label('Mark')->numeric(4),
                Tables\Columns\TextColumn::make('state.trades_count')->label('Trades'),
            ])
            ->paginated(false);
    }
}
