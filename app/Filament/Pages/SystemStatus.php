<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Http;

class SystemStatus extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationLabel = 'Стан системи';
    protected static ?string $title = 'Стан системи';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.system-status';

    public static function canAccess(): bool
    {
        $tenant = app(\App\Services\TenantService::class);

        return $tenant->canSwitch()
            && $tenant->viewingTenantId() === \App\Services\TenantService::PRIMARY_USER_ID;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function snapshot(): array
    {
        try {
            $response = Http::timeout(3)->acceptJson()->get('http://127.0.0.1:8765/v1/snapshot');
            if ($response->successful() && is_array($response->json())) {
                return ['available' => true, 'data' => $response->json()];
            }

            return ['available' => false, 'error' => 'Gateway відповів HTTP ' . $response->status(), 'data' => []];
        } catch (\Throwable $error) {
            return ['available' => false, 'error' => 'Gateway недоступний', 'data' => []];
        }
    }

    public function bytes(int|float|null $value): string
    {
        $value = (float) ($value ?? 0);
        foreach (['B', 'KB', 'MB', 'GB', 'TB'] as $unit) {
            if ($value < 1024 || $unit === 'TB') {
                return number_format($value, $unit === 'B' ? 0 : 1) . ' ' . $unit;
            }
            $value /= 1024;
        }

        return '0 B';
    }
}
