<?php

namespace App\Filament\Widgets;

use App\Services\BotCommandService;
use App\Services\TenantService;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Livewire\Attributes\On;

class ActiveBotsWidget extends Widget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $pollingInterval = null;
    protected static string $view = 'filament.widgets.active-bots';

    public ?string $pollInterval = null; // null | '5' | '15'

    #[On('dashboardRefresh')]
    public function refresh(): void {}

    public function setPollInterval(?string $v): void
    {
        $this->pollInterval = in_array($v, ['5', '15'], true) ? $v : null;
        $this->dispatch('dashboardRefresh');
    }

    public function triggerRefresh(): void { $this->dispatch('dashboardRefresh'); }
    public function manualUpdate(): void   { $this->dispatch('dashboardRefresh'); }

    public function setActiveTenant(int $id): void
    {
        app(TenantService::class)->setViewing($id);
        $this->js('window.location.reload()');
    }

    public function closePosition(int $botId): void
    {
        if (! app(TenantService::class)->canControl()) return; // read-only guard
        app(BotCommandService::class)->closePosition($botId);
        Notification::make()
            ->title('Команда надіслана')
            ->body('Позиція буде закрита протягом кількох секунд.')
            ->success()
            ->send();
    }

    protected function getViewData(): array
    {
        $t = app(TenantService::class);
        return [
            'bots'         => $t->getActiveBots(),
            'canControl'   => $t->canControl(),
            'canSwitch'    => $t->canSwitch(),
            'activeTenant' => $t->viewingTenantId(),
            'tenants'      => $t->availableTenants(),
        ];
    }
}
