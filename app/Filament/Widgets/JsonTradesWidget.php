<?php

namespace App\Filament\Widgets;

use App\Services\TenantService;
use Filament\Widgets\Widget;
use Livewire\Attributes\On;

class JsonTradesWidget extends Widget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';
    protected static string $view = 'filament.widgets.json-trades';

    #[On('dashboardRefresh')]
    public function refresh(): void {}

    protected function getViewData(): array
    {
        $tenant = app(TenantService::class);
        if ($source = $tenant->dashboardSourceService()) {
            return [
                'trades' => collect($source->trades()),
                'dashboardMode' => $tenant->dashboardSource(),
                'modeAvailable' => true,
            ];
        }

        return [
            'trades' => $tenant->getJsonTrades(),
            'dashboardMode' => $tenant->dashboardMode(),
            'modeAvailable' => $tenant->dashboardModeAvailable(),
        ];
    }
}
