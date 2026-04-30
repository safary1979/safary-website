<?php

namespace App\Filament\Pages;

use App\Services\TenantService;
use Filament\Pages\Page;

class AllBots extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationLabel = 'Всі боти';
    protected static ?string $title = 'Всі боти';
    protected static ?int $navigationSort = 10;
    protected static string $view = 'filament.pages.all-bots';

    public static function canAccess(): bool
    {
        $t = app(TenantService::class);
        // Only show for read-only JSON tenants; primary user has BotResource.
        return $t->canSwitch() && ! $t->canControl();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    protected function getViewData(): array
    {
        return [
            'bots' => app(TenantService::class)->getAllBots(),
        ];
    }
}
