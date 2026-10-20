<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Arr;

class ShadowRouter extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static ?string $navigationLabel = 'Shadow Router';
    protected static ?string $title = 'Shadow Router';
    protected static ?int $navigationSort = 32;
    protected static string $view = 'filament.pages.shadow-router';

    public static function canAccess(): bool
    {
        $tenant = app(\App\Services\TenantService::class);

        return $tenant->canSwitch()
            && $tenant->viewingTenantId() === \App\Services\TenantService::PRIMARY_USER_ID;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function getShadowRouterData(): array
    {
        $path = '/home/ubuntu/SafaryEngine/data/strategy_search/edge_factory/executable_quality/hype_multilane_shadow_signal_latest.json';
        $auditPath = '/home/ubuntu/SafaryEngine/data/strategy_search/edge_factory/executable_quality/hype_shadow_router_signal_audit_latest.json';
        $v2Path = '/home/ubuntu/SafaryEngine/data/strategy_search/edge_factory/executable_quality/hype_shadow_router_v2_signal_latest.json';
        $v2AuditPath = '/home/ubuntu/SafaryEngine/data/strategy_search/edge_factory/executable_quality/hype_shadow_router_v2_signal_audit_latest.json';
        $v2ReadinessPath = '/home/ubuntu/SafaryEngine/data/strategy_search/edge_factory/executable_quality/hype_shadow_router_v2_readiness_latest.json';
        $v2CandidateSetsPath = '/home/ubuntu/SafaryEngine/data/strategy_search/edge_factory/executable_quality/hype_shadow_router_v2_candidate_sets_latest.json';
        $v2LiveNowPath = '/home/ubuntu/SafaryEngine/data/strategy_search/edge_factory/executable_quality/hype_shadow_router_v2_live_now_latest.json';
        $v2PaperPath = '/home/ubuntu/SafaryEngine/data/strategy_search/paper_shadow_router_v2/hype_shadow_router_v2_paper_latest.json';
        $v2PaperGatePath = '/home/ubuntu/SafaryEngine/data/strategy_search/paper_shadow_router_v2/hype_shadow_router_v2_paper_gate_latest.json';
        $v2PaperEnablePlanPath = '/home/ubuntu/SafaryEngine/data/strategy_search/paper_shadow_router_v2/hype_shadow_router_v2_paper_enable_plan_latest.json';
        $v2SignalChainAuditPath = '/home/ubuntu/SafaryEngine/data/strategy_search/edge_factory/executable_quality/hype_shadow_router_v2_signal_chain_audit_latest.json';
        $v2ComponentPromotionPath = '/home/ubuntu/SafaryEngine/data/strategy_search/edge_factory/executable_quality/hype_shadow_router_v2_component_promotion_latest.json';
        $v2ProspectivePath = $this->latestFile('/home/ubuntu/SafaryEngine/data/strategy_search/edge_factory/executable_quality/shadow_router_v2_prospective/*_latest.json');
        $v2QuarantinePath = $this->latestFile('/home/ubuntu/SafaryEngine/data/strategy_search/edge_factory/executable_quality/shadow_router_v2_quarantine/hype_shadow_router_v2_quarantine_20*_latest.json');
        $v2QuarantinePromotionPath = '/home/ubuntu/SafaryEngine/data/strategy_search/edge_factory/executable_quality/shadow_router_v2_quarantine/hype_shadow_router_v2_quarantine_promotion_latest.json';
        $v2V7GateSearchPath = '/home/ubuntu/SafaryEngine/data/strategy_search/edge_factory/executable_quality/shadow_router_v2_quarantine/hype_shadow_v7_regime_gate_search_latest.json';
        $v2V7GateWalkForwardPath = '/home/ubuntu/SafaryEngine/data/strategy_search/edge_factory/executable_quality/shadow_router_v2_quarantine/hype_shadow_v7_gate_walkforward_latest.json';

        $sourceHealth = [
            'legacy_signal' => $this->sourceHealth($path),
            'legacy_audit' => $this->sourceHealth($auditPath),
            'v2_signal' => $this->sourceHealth($v2Path),
            'v2_audit' => $this->sourceHealth($v2AuditPath),
            'v2_live_now' => $this->sourceHealth($v2LiveNowPath),
            'v2_readiness' => $this->sourceHealth($v2ReadinessPath),
            'v2_candidate_sets' => $this->sourceHealth($v2CandidateSetsPath),
            'v2_prospective' => $this->sourceHealth($v2ProspectivePath),
            'v2_quarantine' => $this->sourceHealth($v2QuarantinePath),
            'paper_latest' => $this->sourceHealth($v2PaperPath),
            'paper_gate' => $this->sourceHealth($v2PaperGatePath),
        ];

        $rawData = is_file($path) ? json_decode((string) file_get_contents($path), true) : [];
        $data = is_array($rawData) ? $rawData : [];

        $signal = Arr::get($data, 'shadow_signal', []);
        $hasSignal = (bool) Arr::get($signal, 'has_signal', false);
        $legacyMissing = empty($data);

        return [
            'status' => $hasSignal ? 'signal' : 'idle',
            'title' => $legacyMissing ? 'Legacy v1 неактивний' : ($hasSignal ? 'Є shadow signal' : 'Сигналу зараз немає'),
            'message' => $legacyMissing
                ? 'Основний екран нижче використовує Shadow Router v2; старий v1 лишився як довідка.'
                : ($hasSignal
                ? 'Router бачить активну lane і сформував shadow signal.'
                : 'Router оновився, але фінального signal зараз немає.'),
            'updated_at' => Arr::get($data, 'generated_at') ?: (@date('Y-m-d H:i:s', (int) @filemtime($path)) ?: null),
            'historical_union_summary' => Arr::get($data, 'historical_union_summary', []),
            'active_lane' => Arr::get($data, 'active_lane', []),
            'active_shadow_lane' => Arr::get($data, 'active_shadow_lane', []),
            'best_shadow_candidate' => Arr::get($data, 'best_shadow_candidate', []),
            'shadow_signal' => $signal,
            'audit' => $this->readAudit($auditPath),
            'v2' => $this->readAudit($v2Path),
            'v2_audit' => $this->readAudit($v2AuditPath),
            'v2_readiness' => $this->readAudit($v2ReadinessPath),
            'v2_candidate_sets' => $this->readAudit($v2CandidateSetsPath),
            'v2_live_now' => $this->readAudit($v2LiveNowPath),
            'v2_paper' => $this->readAudit($v2PaperPath),
            'v2_paper_gate' => $this->readAudit($v2PaperGatePath),
            'v2_paper_enable_plan' => $this->readAudit($v2PaperEnablePlanPath),
            'v2_signal_chain_audit' => $this->readAudit($v2SignalChainAuditPath),
            'v2_component_promotion' => $this->readAudit($v2ComponentPromotionPath),
            'v2_prospective' => $v2ProspectivePath ? $this->readAudit($v2ProspectivePath) : [],
            'v2_quarantine' => $v2QuarantinePath ? $this->readAudit($v2QuarantinePath) : [],
            'v2_quarantine_promotion' => $this->readAudit($v2QuarantinePromotionPath),
            'v2_v7_gate_search' => $this->readAudit($v2V7GateSearchPath),
            'v2_v7_gate_walkforward' => $this->readAudit($v2V7GateWalkForwardPath),
            'source_health' => $sourceHealth,
            'raw' => $data,
        ];
    }

    private function readAudit(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }

        $data = json_decode((string) file_get_contents($path), true);

        return is_array($data) ? $data : [];
    }

    private function sourceHealth(?string $path): array
    {
        if (! $path || ! is_file($path)) {
            return [
                'state' => 'missing',
                'path' => $path,
                'updated_at' => null,
                'age_min' => null,
            ];
        }

        $mtime = (int) @filemtime($path);
        $ageMin = max(0, (int) floor((time() - $mtime) / 60));
        $state = $ageMin <= 15 ? 'fresh' : ($ageMin <= 180 ? 'stale' : 'old');
        $data = json_decode((string) @file_get_contents($path), true);

        return [
            'state' => $state,
            'path' => $path,
            'updated_at' => @date('Y-m-d H:i:s', $mtime) ?: null,
            'generated_at' => is_array($data) ? Arr::get($data, 'generated_at') : null,
            'age_min' => $ageMin,
        ];
    }

    private function latestFile(string $pattern): ?string
    {
        $files = glob($pattern) ?: [];
        if (empty($files)) {
            return null;
        }

        usort($files, fn (string $a, string $b): int => ((int) @filemtime($b)) <=> ((int) @filemtime($a)));

        return $files[0] ?? null;
    }
}
