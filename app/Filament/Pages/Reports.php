<?php

namespace App\Filament\Pages;

use App\Models\SystemReport;
use App\Services\CandidateTopPolicy;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class Reports extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationLabel = 'Звіти';
    protected static ?string $title = 'Звіти';
    protected static ?int $navigationSort = 3;
    protected static string $view = 'filament.pages.reports';

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

    public function reports()
    {
        return SystemReport::query()->latest()->limit(80)->get()
            ->reject(fn (SystemReport $report) => in_array(data_get($report->payload, 'source'), ['core_batch_controller', 'core_batch_controller_scalp'], true));
    }

    /** Completed CoreBatchController circles imported from its append-only ledger. */
    public function coreBatchReports()
    {
        return SystemReport::query()->orderByDesc('id')->limit(80)->get()
            ->filter(fn (SystemReport $report) => data_get($report->payload, 'source') === 'core_batch_controller');
    }

    /** Candidates safe to show in an individual historical circle. */
    public function displayCandidates(array $payload)
    {
        return collect($payload['candidates'] ?? [])
            ->map(fn (array $candidate) => CandidateTopPolicy::forDisplay($candidate))
            ->filter()
            ->values();
    }

    /** Both research sources share one top; their circle histories stay separate. */
    private function topCandidateReports()
    {
        return SystemReport::query()
            ->whereIn('payload->source', ['core_batch_controller', 'core_batch_controller_scalp'])
            ->orderByDesc('id')
            ->get();
    }

    /** Completed candidates that pass the frozen train, validation and holdout chain. */
    public function coreBatchTopCandidates(string $side)
    {
        return $this->topCandidateReports()
            ->flatMap(function (SystemReport $report) use ($side) {
                $payload = $report->payload ?? [];

                return collect($payload['candidates'] ?? [])
                    ->map(fn (array $candidate) => CandidateTopPolicy::eligible($candidate))
                    ->filter()
                    ->map(fn (array $candidate) => [...$candidate,
                        'circle_number' => $payload['circle_number'] ?? null,
                        'circle_label' => data_get($payload, 'source') === 'core_batch_controller_scalp'
                            ? 'Скалп '.($payload['circle_number'] ?? '—')
                            : 'Коло '.($payload['circle_number'] ?? '—'),
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
        $id = strtolower((string) ($candidate['id'] ?? ''));

        if (str_contains($id, '_long') || str_starts_with($id, 'nql_')) {
            return 'long';
        }

        // The original unsuffixed CoreBatchController catalogue was short-only.
        return 'short';
    }

    public function createReport(): void
    {
        try {
            $response = Http::timeout(2)->acceptJson()->get('http://127.0.0.1:8765/v1/snapshot');
            $payload = $response->json();
            if (! $response->successful() || ! is_array($payload) || ($payload['available'] ?? true) === false) {
                throw new \RuntimeException('Gateway недоступний');
            }

            SystemReport::create([
                'created_by' => auth()->id(),
                'status' => 'draft',
                'gateway_generated_at' => $this->timestamp($payload['generated_at_utc'] ?? null),
                'payload' => $payload,
            ]);

            Notification::make()->title('Чернетку звіту сформовано')->success()->send();
        } catch (\Throwable) {
            Notification::make()->title('Не вдалося сформувати звіт: Gateway недоступний')->danger()->send();
        }
    }

    public function publish(int $reportId): void
    {
        $report = SystemReport::query()->whereKey($reportId)->where('status', 'draft')->firstOrFail();
        $report->update(['status' => 'published', 'published_at' => now()]);

        Notification::make()->title('Звіт опубліковано')->success()->send();
    }

    private function timestamp(mixed $value): ?Carbon
    {
        return filled($value) ? Carbon::parse((string) $value) : null;
    }
}
