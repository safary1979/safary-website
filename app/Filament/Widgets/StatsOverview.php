<?php

namespace App\Filament\Widgets;

use App\Services\TenantService;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Livewire\Attributes\On;

class StatsOverview extends Widget
{
    protected static ?int $sort = 1;
    protected static ?string $pollingInterval = null;
    protected int | string | array $columnSpan = 'full';
    protected static string $view = 'filament.widgets.stats-compact';

    #[On('dashboardRefresh')]
    public function refresh(): void {}

    public function newSession(): void
    {
        app(TenantService::class)->newSession();
        Notification::make()
            ->title('Нова сесія розпочата')
            ->body('Статистика та угоди фільтруються з ' . now()->format('d.m.Y H:i'))
            ->success()
            ->send();
        $this->dispatch('dashboardRefresh');
    }

    public function clearSession(): void
    {
        app(TenantService::class)->clearSession();
        Notification::make()
            ->title('Фільтр сесії знято')
            ->body('Показуються всі угоди за весь час')
            ->info()
            ->send();
        $this->dispatch('dashboardRefresh');
    }

    protected function getViewData(): array
    {
        $t = app(TenantService::class);
        try {
            return [
                'stats'          => $t->getStats(),
                'sessionStart'   => $t->getSessionStart(),
                'canManageSession' => $t->canManageSession(),
            ];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage(), 'sessionStart' => null, 'canManageSession' => false];
        }
    }
}
