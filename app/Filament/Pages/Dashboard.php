<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getTitle(): string { return ''; }
    public function getHeading(): string { return ''; }

    public function getWidgets(): array
    {
        $t = app(\App\Services\TenantService::class);

        return [
            \App\Filament\Widgets\StatsOverview::class,
            \App\Filament\Widgets\ActiveBotsWidget::class,
            $t->viewingHasDb()
                ? \App\Filament\Widgets\RecentTradesWidget::class
                : \App\Filament\Widgets\JsonTradesWidget::class,
        ];
    }

    public function getView(): string
    {
        return 'filament.pages.dashboard';
    }
}
