<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\RefreshesDashboard;
use App\Models\Trade;
use App\Services\BotCommandService;
use App\Services\NautilusControlService;
use App\Services\TenantService;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Livewire\Attributes\On;

class ActiveBotsWidget extends Widget
{
    use RefreshesDashboard;

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
        $this->refreshOtherWidgets();
    }

    public function triggerRefresh(): void { $this->refreshOtherWidgets(); }
    public function manualUpdate(): void   { $this->refreshOtherWidgets(); }

    public function setActiveTenant(int $id): void
    {
        app(TenantService::class)->setViewing($id);
        $this->js('window.location.reload()');
    }

    public function closePosition(int $botId): void
    {
        $tenant = app(TenantService::class);
        if (! $tenant->canControl() || $tenant->dashboardMode() !== 'live') return; // read-only guard
        app(BotCommandService::class)->closePosition($botId);
        Notification::make()
            ->title('Команда надіслана')
            ->body('Позиція буде закрита протягом кількох секунд.')
            ->success()
            ->send();
    }

    public function commandBot(int $botId, string $command): void
    {
        $tenant = app(TenantService::class);
        if (! $tenant->canControl() || $tenant->dashboardMode() !== 'live') return;

        $bot = \App\Models\Bot::find($botId);
        if (! $bot) {
            Notification::make()->title('Бота не знайдено')->danger()->send();
            return;
        }

        $service = app(BotCommandService::class);
        match ($command) {
            'pause' => $service->pause($botId),
            'resume' => $service->resume($botId),
            'stop' => $service->stop($botId),
            'start' => $service->issue($botId, 'start', [
                'config_path' => $bot->config_path,
                'account' => $bot->account,
            ]),
            'restart' => $service->restart($botId, $bot->config_path, $bot->account),
            default => null,
        };

        Notification::make()
            ->title("Команда «{$command}» надіслана")
            ->success()
            ->send();
    }

    public function commandNautilus(string $botId, string $command): void
    {
        $tenant = app(TenantService::class);
        if (! $tenant->canControl() || $tenant->dashboardMode() !== 'live') return;

        try {
            app(NautilusControlService::class)->issue($botId, $command);
            Notification::make()
                ->title("Команда «{$command}» надіслана")
                ->success()
                ->send();
        } catch (\Throwable $error) {
            Notification::make()
                ->title('Команду не виконано')
                ->body($error->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getViewData(): array
    {
        $t = app(TenantService::class);
        $source = $t->dashboardSourceService();
        $svertus = $source !== null;
        $bots = $svertus ? collect($source->bots()) : $t->getActiveBots();
        $mode = $svertus ? $t->dashboardSource() : $t->dashboardMode();
        $modeAvailable = $svertus ? true : $t->dashboardModeAvailable();

        return [
            'bots'         => $bots,
            'demoBots'     => collect(),
            'botNetStats'  => $svertus ? [] : $this->botNetStats($bots, $t),
            'canControl'   => $t->canControl() && $mode === 'live',
            'canSwitch'    => $t->canSwitch(),
            'activeTenant' => $t->viewingTenantId(),
            'tenants'      => $t->availableTenants(),
            'gatewayStatus' => $svertus ? null : $t->dashboardGatewayStatus(),
            'dashboardMode' => $mode,
            'modeAvailable' => $modeAvailable,
        ];
    }

    private function botNetStats($bots, TenantService $tenant): array
    {
        if ($tenant->dashboardMode() !== 'live' || ! $tenant->viewingHasDb()) {
            return [];
        }

        $sessionStart = $tenant->getSessionStart();
        $out = [];

        foreach ($bots as $bot) {
            if (! $bot->id) {
                continue;
            }

            $base = Trade::query()
                ->where('bot_id', $bot->id)
                ->whereNotNull('exit_time');

            if ($sessionStart) {
                $base->whereRaw('datetime(exit_time) >= datetime(?)', [$sessionStart->toDateTimeString()]);
            }

            $count = (clone $base)->count();
            $wins = (clone $base)->where('pnl_usdt', '>', 0)->count();
            $pnl = (float) (clone $base)->sum('pnl_usdt');

            $out[$bot->id] = [
                'pnl_usdt' => $pnl,
                'trades' => $count,
                'wins' => $wins,
                'losses' => max(0, $count - $wins),
            ];
        }

        return $out;
    }
}
