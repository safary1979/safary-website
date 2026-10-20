<x-filament-widgets::widget>
    @if(!empty($error ?? null))
        <div style="padding:0.75rem; color:#ef4444; font-size:0.8rem;">DB error: {{ $error }}</div>
    @else
        {{-- Session bar --}}
        @if(($canManageSession ?? false))
        <div class="dashboard-session-bar" style="display:flex; align-items:center; gap:0.75rem; margin-bottom:0.5rem; flex-wrap:wrap;">
            @if($sessionStart ?? null)
                <div style="display:flex; align-items:center; gap:0.4rem;">
                    <span style="width:6px; height:6px; border-radius:50%; background:#22c55e; display:inline-block;"></span>
                    <span style="font-size:0.7rem; color:#9ca3af;">
                        Сесія з {{ $sessionStart->format('d.m.Y H:i') }}
                    </span>
                </div>
                <button wire:click="clearSession"
                    style="font-size:0.65rem; padding:0.2rem 0.6rem; border-radius:999px;
                           background:#1f2937; color:#9ca3af; border:1px solid #374151; cursor:pointer;"
                    title="Показати всі угоди за весь час">
                    × Зняти фільтр
                </button>
            @else
                <span style="font-size:0.7rem; color:#4b5563;">Всі угоди (без фільтру сесії)</span>
            @endif

            @if($svertusAvailable ?? false)
                @foreach(['svertus' => ['Svertus', 'Показати двох ботів Svertus замість поточних; ще раз натиснути, щоб повернутись'],
                          'pairby' => ['PairBy', 'Показати пару PairBy (Long b7362bbc + Short c33e375a) замість поточних; ще раз натиснути, щоб повернутись']] as $sourceKey => [$sourceLabel, $sourceTitle])
                    @php($sourceOn = ($dashboardSource ?? null) === $sourceKey)
                    <button
                        wire:click="toggleSource('{{ $sourceKey }}')"
                        wire:loading.attr="disabled"
                        wire:target="toggleSource"
                        style="font-size:0.65rem; font-weight:600; padding:0.2rem 0.75rem; border-radius:999px; cursor:pointer;
                               border:1px solid {{ $sourceOn ? '#1d4ed8' : '#374151' }};
                               background:{{ $sourceOn ? '#1d4ed8' : '#1f2937' }};
                               color:{{ $sourceOn ? '#f9fafb' : '#9ca3af' }};"
                        title="{{ $sourceTitle }}"
                    >
                        <span wire:loading.remove wire:target="toggleSource">{{ $sourceLabel }}@if($sourceKey === 'pairby' && ($pairbyCount ?? null)) {{ $pairbyCount }}@endif</span>
                        <span wire:loading wire:target="toggleSource">…</span>
                    </button>
                @endforeach
            @endif

            @if(\App\Services\TenantService::DEMO_ENABLED)
            <div role="group" aria-label="Режим даних"
                style="display:flex; border:1px solid #374151; border-radius:999px; overflow:hidden;">
                @foreach(['live' => 'LIVE', 'demo' => 'DEMO'] as $modeValue => $modeLabel)
                    <button
                        wire:click="setDashboardMode('{{ $modeValue }}')"
                        wire:loading.attr="disabled"
                        wire:target="setDashboardMode"
                        style="font-size:0.65rem; font-weight:600; padding:0.2rem 0.6rem; border:0; cursor:pointer;
                               background:{{ ($dashboardMode ?? 'live') === $modeValue ? ($modeValue === 'live' ? '#166534' : '#92400e') : '#1f2937' }};
                               color:{{ ($dashboardMode ?? 'live') === $modeValue ? '#f9fafb' : '#9ca3af' }};"
                        title="Показувати тільки {{ $modeLabel }}-дані"
                    >
                        <span wire:loading.remove wire:target="setDashboardMode">{{ $modeLabel }}</span>
                        <span wire:loading wire:target="setDashboardMode">…</span>
                    </button>
                @endforeach
            </div>
            @endif

            @if(\App\Services\TenantService::OKX_ENABLED)
            <div role="group" aria-label="Біржа"
                style="display:flex; border:1px solid #374151; border-radius:999px; overflow:hidden;">
                @foreach(['okx' => 'OKX', 'bybit' => 'BYBIT'] as $exchangeValue => $exchangeLabel)
                    <button
                        wire:click="setDashboardExchange('{{ $exchangeValue }}')"
                        wire:loading.attr="disabled"
                        wire:target="setDashboardExchange"
                        style="font-size:0.65rem; font-weight:600; padding:0.2rem 0.6rem; border:0; cursor:pointer;
                               background:{{ ($dashboardExchange ?? 'okx') === $exchangeValue ? '#1d4ed8' : '#1f2937' }};
                               color:{{ ($dashboardExchange ?? 'okx') === $exchangeValue ? '#f9fafb' : '#9ca3af' }};"
                        title="Показувати тільки {{ $exchangeLabel }}"
                    >
                        <span wire:loading.remove wire:target="setDashboardExchange">{{ $exchangeLabel }}</span>
                        <span wire:loading wire:target="setDashboardExchange">…</span>
                    </button>
                @endforeach
            </div>
            @endif

            <button wire:click="newSession"
                style="font-size:0.7rem; padding:0.25rem 0.75rem; border-radius:999px;
                       background:#1e3a5f; color:#60a5fa; border:1px solid #1d4ed8; cursor:pointer;
                       margin-left:auto;"
                title="Почати нову сесію — статистика та угоди показуватимуться з цього моменту">
                🚀 Нова сесія
            </button>
        </div>
        @endif

        {{-- Stats grid --}}
        <div class="dashboard-stats-grid" style="display:grid; grid-template-columns:repeat(4, 1fr); gap:0.5rem;">
            @foreach($stats as $s)
                <div style="
                    background:#111827; border:1px solid #1f2937;
                    border-radius:0.5rem; padding:0.5rem 0.75rem;
                    display:flex; flex-direction:column; gap:0.1rem;
                ">
                    <div style="font-size:0.65rem; color:#9ca3af; text-transform:uppercase; letter-spacing:0.03em;">{{ $s['label'] }}</div>
                    <div style="font-size:0.95rem; font-weight:700; color:{{ $s['color'] }}; line-height:1.1;">{{ $s['value'] }}</div>
                    <div style="font-size:0.65rem; color:#6b7280;">{{ $s['sub'] }}</div>
                </div>
            @endforeach
        </div>
    @endif
</x-filament-widgets::widget>
