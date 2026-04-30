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
        return [
            'trades' => app(TenantService::class)->getJsonTrades(),
        ];
    }
}
