<?php

namespace App\Filament\Pages;

use App\Models\SystemReport;
use App\Services\CandidateTopPolicy;
use Filament\Pages\Page;

class ReportsScalp extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-bolt';
    protected static ?string $navigationLabel = 'Звіти скальп';
    protected static ?string $title = 'Звіти скальп';
    protected static ?int $navigationSort = 4;
    protected static string $view = 'filament.pages.reports-scalp';

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

    public function scalpReports()
    {
        // Filter in SQL before applying the limit.  Filtering afterwards
        // eventually hides older scalp circles behind unrelated reports.
        return SystemReport::query()
            ->where('payload->source', 'core_batch_controller_scalp')
            ->orderByDesc('id')
            ->limit(80)
            ->get();
    }

    /** Top-20 long/short tests shown on the scalp reports page. */
    public function coreBatchTopCandidates(string $side)
    {
        return SystemReport::query()
            ->where('payload->source', 'core_batch_controller')
            ->orderByDesc('id')
            ->get()
            ->flatMap(function (SystemReport $report) use ($side) {
                $payload = $report->payload ?? [];

                return collect($payload['candidates'] ?? [])
                    ->map(fn (array $candidate) => CandidateTopPolicy::forDisplay($candidate))
                    ->filter(fn ($candidate): bool => is_array($candidate) && is_array($candidate['train'] ?? null))
                    ->map(fn (array $candidate) => [...$candidate,
                        'circle_number' => $payload['circle_number'] ?? null,
                        'circle_label' => 'Коло '.($payload['circle_number'] ?? '—'),
                        'side' => $this->candidateSide($candidate),
                    ])
                    ->filter(fn (array $candidate): bool => $candidate['side'] === $side);
            })
            ->sort(function (array $left, array $right): int {
                $trades = ((int) ($right['train']['trades'] ?? 0)) <=> ((int) ($left['train']['trades'] ?? 0));
                if ($trades !== 0) {
                    return $trades;
                }

                $pf = ((float) ($right['train']['pf'] ?? 0)) <=> ((float) ($left['train']['pf'] ?? 0));
                if ($pf !== 0) {
                    return $pf;
                }

                return ((float) ($right['train']['pnl_pct'] ?? 0)) <=> ((float) ($left['train']['pnl_pct'] ?? 0));
            })
            ->take(20)
            ->values();
    }

    public function scalpTopCandidates(string $side)
    {
        return $this->scalpReports()
            ->flatMap(function (SystemReport $report) use ($side) {
                $payload = $report->payload ?? [];

                return collect($payload['candidates'] ?? [])
                    ->map(fn (array $candidate) => CandidateTopPolicy::eligible($candidate))
                    ->filter()
                    ->map(fn (array $candidate) => [...$candidate,
                        'circle_number' => $payload['circle_number'] ?? null,
                        'side' => $this->candidateSide($candidate),
                    ])
                    ->filter(fn (array $candidate) => $candidate['side'] === $side);
            })
            ->sort(function (array $left, array $right): int {
                $pnl = ((float) ($right['holdout']['pnl_pct'] ?? 0)) <=> ((float) ($left['holdout']['pnl_pct'] ?? 0));

                return $pnl !== 0
                    ? $pnl
                    : ((float) ($right['holdout']['pf'] ?? 0)) <=> ((float) ($left['holdout']['pf'] ?? 0));
            })
            ->take(10)
            ->values();
    }

    private function candidateSide(array $candidate): string
    {
        return str_contains(strtolower((string) ($candidate['id'] ?? '')), '_long')
            ? 'long'
            : 'short';
    }
}
