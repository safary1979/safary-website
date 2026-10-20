<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Arr;

class MarketPeriods extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';
    protected static ?string $navigationLabel = 'Market Periods';
    protected static ?string $title = 'Market Periods';
    protected static ?int $navigationSort = 34;
    protected static string $view = 'filament.pages.market-periods';

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

    public function getRegimePeriods(): array
    {
        $path = '/home/ubuntu/SafaryEngine/data/strategy_search/market_regime_periods_latest.json';

        if (! is_file($path)) {
            return [
                'available' => false,
                'generated_at' => '',
                'counts' => [],
                'sources' => [],
                'method' => [],
                'current_period' => [],
                'similar_periods' => [],
                'recent_periods' => [],
            ];
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data)) {
            return [
                'available' => false,
                'generated_at' => '',
                'counts' => [],
                'sources' => [],
                'method' => [],
                'current_period' => [],
                'similar_periods' => [],
                'recent_periods' => [],
            ];
        }

        return [
            'available' => true,
            'generated_at' => (string) Arr::get($data, 'generated_at_kyiv', ''),
            'counts' => is_array(Arr::get($data, 'counts')) ? Arr::get($data, 'counts') : [],
            'sources' => is_array(Arr::get($data, 'sources')) ? Arr::get($data, 'sources') : [],
            'method' => is_array(Arr::get($data, 'method')) ? Arr::get($data, 'method') : [],
            'current_period' => is_array(Arr::get($data, 'current_period')) ? Arr::get($data, 'current_period') : [],
            'similar_periods' => is_array(Arr::get($data, 'similar_periods')) ? array_slice(Arr::get($data, 'similar_periods'), 0, 20) : [],
            'recent_periods' => is_array(Arr::get($data, 'recent_periods')) ? array_slice(Arr::get($data, 'recent_periods'), 0, 50) : [],
        ];
    }
}
