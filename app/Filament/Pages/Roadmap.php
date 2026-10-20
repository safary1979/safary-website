<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Arr;

class Roadmap extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static ?string $navigationLabel = 'Roadmap';
    protected static ?string $title = 'Roadmap';
    protected static ?int $navigationSort = 33;
    protected static string $view = 'filament.pages.roadmap';

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

    public function getRoadmapState(): array
    {
        $base = '/home/ubuntu/SafaryEngine/data/strategy_search/edge_factory/executable_quality';
        $strategyBase = '/home/ubuntu/SafaryEngine/data/strategy_search';
        $paperBase = $strategyBase . '/paper_shadow_router_v2';
        $longGapBase = $strategyBase . '/edge_factory/long_gap_reality';
        $eventFamilyBase = $strategyBase . '/edge_factory/event_family_scan';
        $prospectivePath = $this->latestFile($base . '/shadow_router_v2_prospective/*_latest.json');
        $quarantinePath = $this->latestFile($base . '/shadow_router_v2_quarantine/hype_shadow_router_v2_quarantine_20*_latest.json');
        $eventFamilyPath = $this->latestFile($eventFamilyBase . '/hype_event_lane_family_scan*_report.json');

        return [
            'codex_live_plan' => $this->readText('/home/ubuntu/SafaryEngine/CODEX_SHADOW_ROUTER_LIVE_ROADMAP_20260506.md'),
            'project_roadmap' => $this->readJson($strategyBase . '/project_roadmap_latest.json'),
            'background' => $this->readJson($strategyBase . '/background_health_latest.json'),
            'readiness' => $this->readJson($base . '/hype_shadow_router_v2_readiness_latest.json'),
            'candidate_sets' => $this->readJson($base . '/hype_shadow_router_v2_candidate_sets_latest.json'),
            'live_now' => $this->readJson($base . '/hype_shadow_router_v2_live_now_latest.json'),
            'signal_chain' => $this->readJson($base . '/hype_shadow_router_v2_signal_chain_audit_latest.json'),
            'component_promotion' => $this->readJson($base . '/hype_shadow_router_v2_component_promotion_latest.json'),
            'paper' => $this->readJson($paperBase . '/hype_shadow_router_v2_paper_latest.json'),
            'paper_gate' => $this->readJson($paperBase . '/hype_shadow_router_v2_paper_gate_latest.json'),
            'paper_enable_plan' => $this->readJson($paperBase . '/hype_shadow_router_v2_paper_enable_plan_latest.json'),
            'prospective' => $prospectivePath ? $this->readJson($prospectivePath) : [],
            'prospective_file' => $prospectivePath,
            'quarantine' => $quarantinePath ? $this->readJson($quarantinePath) : [],
            'quarantine_file' => $quarantinePath,
            'quarantine_promotion' => $this->readJson($base . '/shadow_router_v2_quarantine/hype_shadow_router_v2_quarantine_promotion_latest.json'),
            'v7_gate_search' => $this->readJson($base . '/shadow_router_v2_quarantine/hype_shadow_v7_regime_gate_search_latest.json'),
            'v7_gate_walkforward' => $this->readJson($base . '/shadow_router_v2_quarantine/hype_shadow_v7_gate_walkforward_latest.json'),
            'long_gap' => $this->readJson($longGapBase . '/hype_long_gap_reality_latest.json'),
            'long_gap_current_routed' => $this->readJson($longGapBase . '/hype_long_gap_current_routed_candidates_latest.json'),
            'event_family_latest' => $eventFamilyPath ? $this->readJson($eventFamilyPath) : [],
            'event_family_file' => $eventFamilyPath,
            'marketplace_trace' => $this->readJson($strategyBase . '/marketplace_decision_trace_latest.json'),
            'live_entry_preview' => $this->readJson($strategyBase . '/live_entry_decision_preview_latest.json'),
            'ml_shadow' => $this->readJson($strategyBase . '/ml_shadow/ml_shadow_latest.json'),
            'next_days_plan' => $this->readJson($strategyBase . '/next_1_3_days_plan_latest.json'),
            'updated_at' => now()->format('Y-m-d H:i:s T'),
        ];
    }

    public function componentByName(array $components, string $name): array
    {
        return Arr::first($components, fn (array $component): bool => ($component['component'] ?? '') === $name) ?? [];
    }

    public function checkValue(array $component, string $name, mixed $default = null): mixed
    {
        $check = Arr::first($component['checks'] ?? [], fn (array $check): bool => ($check['name'] ?? '') === $name);

        return $check['value'] ?? $default;
    }

    public function checkMin(array $component, string $name, mixed $default = null): mixed
    {
        $check = Arr::first($component['checks'] ?? [], fn (array $check): bool => ($check['name'] ?? '') === $name);

        return $check['min'] ?? $default;
    }

    public function fmt(mixed $value, int $decimals = 2): string
    {
        if (! is_numeric($value)) {
            return 'n/a';
        }

        return number_format((float) $value, $decimals, '.', '');
    }

    private function readJson(string $path): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            return [];
        }

        $data = json_decode((string) @file_get_contents($path), true);

        return is_array($data) ? $data : [];
    }

    private function readText(string $path): string
    {
        if (! is_file($path) || ! is_readable($path)) {
            return '';
        }

        return trim((string) @file_get_contents($path));
    }

    private function latestFile(string $pattern): ?string
    {
        $files = array_values(array_filter(glob($pattern) ?: [], fn (string $path): bool => is_readable($path) && ! str_contains(basename($path), 'smoke')));

        if (empty($files)) {
            return null;
        }

        usort($files, fn (string $a, string $b): int => ((int) @filemtime($b)) <=> ((int) @filemtime($a)));

        return $files[0] ?? null;
    }
}
