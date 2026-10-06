<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Http;

class GeneratedAutofon extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationLabel = 'Автофон';
    protected static ?string $title = 'Автофон стратегій';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.pages.generated-autofon';

    public bool $loaded = false;
    public bool $showDetails = false;
    public ?string $expandedCandidateId = null;
    protected ?array $snapshotCache = null;
    protected ?array $dualSnapshotCache = null;
    public ?string $campaignControlMessage = null;

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

    public function refreshStatus(): void
    {
        abort_unless(static::canAccess(), 403);
        $this->loaded = true;
        $this->snapshotCache = null;
        $this->dualSnapshotCache = null;
    }

    public function toggleDetails(): void
    {
        $this->refreshStatus();
        $this->showDetails = ! $this->showDetails;
    }

    public function toggleCandidate(string $candidateId): void
    {
        abort_unless(preg_match('/^gen_[a-f0-9]{16}$/D', $candidateId), 404);
        $this->refreshStatus();
        $this->expandedCandidateId = $this->expandedCandidateId === $candidateId ? null : $candidateId;
    }

    protected function fetchSnapshot(string $endpoint, array $query = []): array
    {
        abort_unless(static::canAccess(), 403);
        try {
            $response = Http::connectTimeout(1)->timeout(20)->acceptJson()
                ->get('http://127.0.0.1:8765'.$endpoint, $query);
            $data = $response->json();
            if ($response->successful() && is_array($data)
                && ($data['schema'] ?? null) === ($endpoint === '/v1/autofon/campaigns' ? 'sol_dual_gateway_v1' : 'bybit_generated_gateway_v2')) {
                return ['available' => true, 'data' => $data];
            }

            return ['available' => false, 'error' => 'Gateway відповів HTTP '.$response->status()];
        } catch (\Throwable) {
            return ['available' => false, 'error' => 'Gateway недоступний'];
        }
    }

    public function dualSnapshot(): array
    {
        if (! $this->loaded) {
            return ['available' => false, 'loading' => true];
        }
        return $this->dualSnapshotCache ??= $this->fetchSnapshot('/v1/autofon/campaigns');
    }

    protected function readControlToken(): string
    {
        $path = '/home/ubuntu/Freqtrade/freqtrade_gateway/sol_control.token';
        if (! is_readable($path)) {
            throw new \RuntimeException('Control token is not readable');
        }
        $token = trim(file_get_contents($path));
        if ($token === '') {
            throw new \RuntimeException('Control token is empty');
        }
        return $token;
    }

    public function stopCampaign(string $side): void
    {
        abort_unless(static::canAccess(), 403);
        abort_unless(in_array($side, ['long', 'short'], true), 404);
        try {
            $token = $this->readControlToken();
        } catch (\Throwable) {
            $this->campaignControlMessage = 'Сайт не має доступу до ключа керування: запит на зупинку не надіслано.';
            $this->refreshStatus();
            return;
        }
        try {
            $response = Http::connectTimeout(1)->timeout(30)->acceptJson()
                ->withHeaders(['X-Sol-Control' => $token])
                ->post('http://127.0.0.1:8765/v1/autofon/campaigns/stop', ['side' => $side]);
            $this->campaignControlMessage = $response->successful()
                && $response->json('status') === 'stop_requested'
                && $response->json('side') === $side
                && $response->json('boundary') === 'after_current_batch'
                ? ($response->json('mode') === 'budget_comparison' ? strtoupper($side).' та експеримент завершать поточні партії й зупиняться.' : strtoupper($side).' завершить поточний цикл і зупиниться.')
                : ($response->json('status') === 'stop_partial'
                    ? 'Зупинку підтверджено частково: '.implode(', ', array_intersect(['short', 'long', 'experiment'], $response->json('acknowledged') ?? [])).'. Решта потребує перевірки.'
                    : 'Запит не прийнято. Стан кампанії потребує перевірки.');
        } catch (\Throwable) {
            $this->campaignControlMessage = 'Відповідь Gateway не отримано. Перевірте індикатор «Запит на зупинку збережено» — команда могла бути прийнята.';
        }
        $this->refreshStatus();
    }

    public function snapshot(): array
    {
        if (! $this->loaded) {
            return ['available' => false, 'loading' => true];
        }

        return $this->snapshotCache ??= $this->fetchSnapshot('/v1/autofon/current',
            $this->showDetails ? ['details' => '1'] : []);
    }
}
