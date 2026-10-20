<x-filament-panels::page>
    @php
        $snapshot = $this->snapshot();
        $data = $snapshot['data'] ?? [];
        $job = $data['year2024_search'] ?? null;
        $continuous = $job['continuous'] ?? [];
        $status = $job['status'] ?? 'unavailable';
        $running = in_array($status, ['running', 'preparing', 'stop_requested', 'finalizing'], true) || collect($this->dualSnapshot()['data']['campaigns'] ?? [])->contains(fn ($c) => ($c['status'] ?? '') === 'running');
        $statusLabels = ['prepared' => 'Підготовлено', 'running' => 'Працює', 'preparing' => 'Підготовка', 'stop_requested' => 'Завершує поточну партію', 'stopped' => 'Зупинено', 'queue_exhausted' => 'Черга вичерпана', 'review_pending' => 'Результат готовий', 'needs_attention' => 'Потрібне втручання', 'finalizing' => 'Фінальна перевірка', 'ready_to_launch' => 'Готово до запуску'];
        $stageLabels = ['generation' => 'Формування нових входів', 'optuna_history' => 'Підготовка накопиченого досвіду', 'prepared' => 'Підготовлено', 'screen' => 'Відбір сигналів', 'baseline' => 'Базовий replay', 'optuna' => 'Оптимізація параметрів', 'transfer' => 'Перевірка 2026', 'review_pending' => 'Результат готовий', 'waiting_for_worker' => 'Очікує працівника', 'failed' => 'Технічна помилка', 'next_batch' => 'Підготовка наступної партії', 'paused' => 'Зупинено за командою', 'queue_exhausted' => 'Черга вичерпана'];
        $newTop10 = $job['top10_2024'] ?? null;
        $newPeriodDays = 0;
        $displayYears = [];
        if (isset($newTop10['period'][0], $newTop10['period'][1])) {
            $start = new \DateTimeImmutable($newTop10['period'][0]);
            $end = new \DateTimeImmutable($newTop10['period'][1]);
            $newPeriodDays = (int) $start->diff($end)->days + 1;
            $displayYears = array_map('strval', range((int) $start->format('Y'), (int) $end->format('Y')));
        }
        $annualAvailable = ! empty($newTop10['candidates']) && collect($newTop10['candidates'])->every(fn ($row) => ($row['annual_display_status'] ?? '') === 'verified');
    @endphp
    <div wire:init="refreshStatus" @if ($data['initializing'] ?? false) wire:poll.5s.visible="refreshStatus" @elseif ($running) wire:poll.15s.visible="refreshStatus" @else wire:poll.60s.visible="refreshStatus" @endif style="display:grid;gap:1rem;color:#cbd5e1;">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:.75rem;flex-wrap:wrap;">
            <span style="font-size:.8rem;color:#94a3b8;">
                @if (isset($data['generated_at_utc']))
                    Оновлено {{ (new \DateTimeImmutable($data['generated_at_utc']))->setTimezone(new \DateTimeZone('Europe/Kyiv'))->format('d.m.Y H:i:s') }} · Київ
                @endif
            </span>
            <div style="display:flex;gap:.75rem;align-items:center;">
                <button type="button" wire:click="refreshStatus" wire:loading.attr="disabled" style="color:#93c5fd;cursor:pointer;">Оновити</button>
                <a href="{{ \App\Filament\Pages\GeneratedAutofonArchive::getUrl() }}" style="color:#93c5fd;">Архів досліджень →</a>
            </div>
        </div>
        @include('filament.pages.sol-campaigns-panel')
        @if ($snapshot['loading'] ?? false)
            <section role="status" style="padding:1rem;border:1px solid #334155;border-radius:.5rem;background:#020617;">Завантаження поточного стану та Top 10…</section>
        @elseif ($data['initializing'] ?? false)
            <section role="status" style="padding:1rem;border:1px solid #334155;border-radius:.5rem;background:#020617;">Перевіряємо збережені результати… Стан і Top 10 з’являться автоматично.</section>
        @elseif (! $snapshot['available'])
            <section role="alert" style="padding:1rem;border:1px solid #7f1d1d;border-radius:.5rem;background:#1f0f12;color:#fecaca;">{{ $snapshot['error'] ?? 'Стан автофону недоступний' }}</section>
        @elseif (! is_array($job))
            <section style="padding:1rem;border:1px solid #334155;border-radius:.5rem;background:#020617;">Чинний автофон ще не налаштовано.</section>
        @else
            <section data-autofon-section="processing" style="padding:1rem;border:1px solid {{ $status === 'needs_attention' ? '#dc2626' : '#2563eb' }};border-radius:.5rem;background:#020617;">
                <div style="display:flex;align-items:baseline;justify-content:space-between;gap:.75rem;flex-wrap:wrap;">
                    <strong style="font-size:1.1rem;color:#f8fafc;">Архівний автофон v11 · SOL Long / Bybit</strong>
                    <strong style="color:{{ $status === 'needs_attention' ? '#fca5a5' : '#93c5fd' }};">{{ $statusLabels[$status] ?? $status }}</strong>
                </div>
                <div style="margin-top:.5rem;">
                    @if ($status === 'stopped')
                        Автофон зупинено після партії {{ $continuous['completed_batches'] ?? '—' }}. Активної партії немає.
                    @elseif ($status === 'queue_exhausted')
                        Чергу завершено. Завершених партій: {{ $continuous['completed_batches'] ?? '—' }}.
                    @else
                        {{ $stageLabels[$job['stage'] ?? ''] ?? ($job['stage'] ?? '—') }}
                        @if (isset($continuous['batch'])) · партія {{ $continuous['batch'] }} @endif
                        @if (isset($continuous['completed_batches'])) · завершено {{ $continuous['completed_batches'] }} @endif
                        @if ($running && ! empty($continuous['stop_requested'])) · запит зупинки прийнято @endif
                    @endif
                </div>
                @if ($running)
                    <div style="margin-top:.4rem;font-size:.82rem;color:#94a3b8;">
                        @if (isset($job['completed'], $job['total'])) Перевірено {{ $job['completed'] }} / {{ $job['total'] }} @endif
                        @if (isset($job['baseline_completed'], $job['baseline_target'])) · базові результати {{ $job['baseline_completed'] }} / {{ $job['baseline_target'] }} @endif
                        @if (isset($job['optuna_completed'], $job['optuna_target'])) · Optuna {{ $job['optuna_completed'] }} / {{ $job['optuna_target'] }} пропозицій @endif
                    </div>
                @endif
                @if (! empty($job['error']))
                    <div role="alert" style="margin-top:.5rem;color:#fca5a5;">Помилка: {{ $job['error'] }}</div>
                @endif
            </section>
            @include('filament.pages.generated-autofon-top10')
            @if (! empty($newTop10['candidates']) && ! $annualAvailable)
                <div style="font-size:.8rem;color:#94a3b8;">Річний розподіл для частини кандидатів недоступний. Загальний PnL наведено за повним replay.</div>
            @endif
            @if (empty($newTop10['candidates']))
                <section style="padding:1rem;border:1px solid #334155;border-radius:.5rem;background:#020617;">Перевірений Top 10 ще не опубліковано.</section>
            @endif
            <section data-autofon-section="last-batch" style="padding:1rem;border:1px solid #334155;border-radius:.5rem;background:#020617;">
                <button type="button" wire:click="toggleDetails" aria-expanded="{{ $showDetails ? 'true' : 'false' }}" style="display:flex;justify-content:space-between;gap:.75rem;width:100%;text-align:left;cursor:pointer;">
                    <strong style="color:#f8fafc;">Остання завершена партія{{ isset($continuous['completed_batches']) ? ' · '.$continuous['completed_batches'] : '' }}</strong>
                    <span style="color:#93c5fd;">{{ $showDetails ? 'Згорнути ↑' : 'Деталі ↓' }}</span>
                </button>
                @if ($showDetails)
                    @include('filament.pages.generated-autofon-last-batch')
                @endif
            </section>
        @endif
        <span wire:loading.delay role="status" style="font-size:.8rem;color:#94a3b8;">Оновлення даних…</span>
    </div>
</x-filament-panels::page>
