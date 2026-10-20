<?php

namespace App\Filament\Widgets\Concerns;

use App\Filament\Widgets\ActiveBotsWidget;
use App\Filament\Widgets\JsonTradesWidget;
use App\Filament\Widgets\RecentTradesWidget;
use App\Filament\Widgets\StatsOverview;

trait RefreshesDashboard
{
    /**
     * Refresh the other dashboard widgets. The calling widget already re-renders after its own action;
     * a broadcast "dashboardRefresh" would render it a second time in an extra round trip.
     */
    protected function refreshOtherWidgets(): void
    {
        foreach ([StatsOverview::class, ActiveBotsWidget::class, JsonTradesWidget::class, RecentTradesWidget::class] as $widget) {
            if ($widget !== static::class) {
                $this->dispatch('dashboardRefresh')->to($widget);
            }
        }
    }
}
