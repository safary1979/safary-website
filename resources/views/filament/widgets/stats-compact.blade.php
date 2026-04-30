<x-filament-widgets::widget>
    @if(!empty($error ?? null))
        <div style="padding:0.75rem; color:#ef4444; font-size:0.8rem;">DB error: {{ $error }}</div>
    @else
        {{-- Session bar --}}
        @if(($canManageSession ?? false))
        <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:0.5rem; flex-wrap:wrap;">
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
        <div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:0.5rem;">
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
