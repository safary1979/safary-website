<?php

namespace App\Filament\Widgets;

use App\Models\Bot;
use App\Models\BotState;
use App\Models\Trade;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;
    protected static ?string $pollingInterval = '10s';

    protected function getStats(): array
    {
        try {
            $equity  = (float) BotState::sum('equity');
            $trades  = Trade::query();
            $closed  = (clone $trades)->whereNotNull('exit_time');
            $pnl     = (float) (clone $closed)->sum('pnl_usdt');
            $total   = (clone $closed)->count();
            $wins    = (clone $closed)->where('pnl_usdt', '>', 0)->count();
            $winRate = $total > 0 ? round($wins / $total * 100, 1) : 0;
            $running = Bot::where('status', 'running')->count();
            $totalBots = Bot::count();
        } catch (\Throwable $e) {
            return [
                Stat::make('Bot DB', 'не підключена')
                    ->description('Перевір BOT_DB_PATH у .env')
                    ->color('danger'),
            ];
        }

        return [
            Stat::make('Equity (сума)', number_format($equity, 2) . ' USDT')
                ->color('primary'),
            Stat::make('PnL (закриті)', number_format($pnl, 2) . ' USDT')
                ->color($pnl >= 0 ? 'success' : 'danger'),
            Stat::make('Win Rate', $winRate . '%')
                ->description("{$wins}/{$total} угод")
                ->color($winRate >= 50 ? 'success' : 'warning'),
            Stat::make('Активні боти', "{$running}/{$totalBots}")
                ->color('info'),
        ];
    }
}
