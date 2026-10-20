<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\RefreshesDashboard;
use App\Services\TenantService;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Livewire\Attributes\On;

class StatsOverview extends Widget
{
    use RefreshesDashboard;

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
        $this->refreshOtherWidgets();
    }

    public function clearSession(): void
    {
        app(TenantService::class)->clearSession();
        Notification::make()
            ->title('Фільтр сесії знято')
            ->body('Показуються всі угоди за весь час')
            ->info()
            ->send();
        $this->refreshOtherWidgets();
    }

    public function setDashboardMode(string $mode): void
    {
        app(TenantService::class)->setDashboardMode($mode);
        $this->refreshOtherWidgets();
    }

    public function toggleSource(string $source): void
    {
        app(TenantService::class)->toggleDashboardSource($source);
        $this->refreshOtherWidgets();
    }

    public function setDashboardExchange(string $exchange): void
    {
        app(TenantService::class)->setDashboardExchange($exchange);
        $this->refreshOtherWidgets();
    }

    protected function getViewData(): array
    {
        $t = app(TenantService::class);
        try {
            return [
                'stats'          => $t->getStats(),
                'sessionStart'   => $t->getSessionStart(),
                'canManageSession' => $t->canManageSession(),
                'dashboardMode'  => $t->dashboardMode(),
                'dashboardExchange' => $t->dashboardExchange(),
                'modeAvailable'  => $t->dashboardModeAvailable(),
                'svertusAvailable' => $t->svertusAvailable(),
                'dashboardSource' => $t->dashboardSource(),
                'pairbyCount' => $t->svertusAvailable() ? $this->pairbyCount() : null,
            ];
        } catch (\Throwable $e) {
            return [
                'error' => $e->getMessage(),
                'sessionStart' => null,
                'canManageSession' => false,
                'dashboardMode' => 'live',
                'dashboardExchange' => 'okx',
                'modeAvailable' => false,
            ];
        }
    }

    /** «in a trade / bots» of the PairBy view, the same in-position rule as the bot cards. */
    private function pairbyCount(): string
    {
        $bots = app(\App\Services\PairByBotService::class)->bots();
        $inTrade = count(array_filter($bots, fn ($bot) => ! empty($bot->state?->position_json)));

        return $inTrade . '/' . count($bots);
    }
}
