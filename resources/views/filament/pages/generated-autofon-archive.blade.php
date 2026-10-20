<x-filament-panels::page>
    @php
        $index = $this->index();
        $snapshot = $this->snapshot();
        $data = $snapshot['data'] ?? [];
        $sections = $index['data']['sections'] ?? [];
    @endphp
    <div wire:init="refreshStatus" style="display:grid;gap:1rem;color:#cbd5e1;">
        <div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
            <a href="{{ \App\Filament\Pages\GeneratedAutofon::getUrl() }}" style="color:#93c5fd;">← Поточний автофон і Top 10</a>
            <span style="font-size:.8rem;color:#94a3b8;">Історичні результати · оберіть дослід або цикл</span>
        </div>
        @if ($index['loading'] ?? false)
            <div role="status">Завантаження переліку дослідів…</div>
        @elseif (! $index['available'])
            <div role="alert" style="color:#fca5a5;">{{ $index['error'] }}</div>
            <button type="button" wire:click="refreshStatus" style="color:#93c5fd;">Повторити</button>
        @else
            <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
                @foreach ($sections as $key => $label)
                    <button type="button" wire:click="selectSection('{{ $key }}')" wire:loading.attr="disabled" aria-pressed="{{ $section === $key ? 'true' : 'false' }}" style="padding:.5rem .75rem;border:1px solid {{ $section === $key ? '#2563eb' : '#334155' }};border-radius:.5rem;background:{{ $section === $key ? '#172554' : '#020617' }};color:#cbd5e1;cursor:pointer;">{{ $label }}</button>
                @endforeach
            </div>
            @if ($section === null)
                <div style="padding:1rem;border:1px solid #334155;border-radius:.5rem;background:#020617;">Оберіть розділ архіву.</div>
            @elseif (! $snapshot['available'])
                <div role="alert" style="color:#fca5a5;">{{ $snapshot['error'] }}</div>
            @elseif (! empty($data['archive_error']))
                <div role="alert" style="color:#fca5a5;">{{ $data['archive_error'] }}</div>
            @else
                <div style="font-size:.9rem;color:#93c5fd;">{{ $sections[$section] ?? 'Архівний дослід' }}</div>
                @include('filament.pages.generated-autofon-archive-content')
            @endif
        @endif
        <span wire:loading.delay role="status" style="font-size:.8rem;color:#94a3b8;">Завантаження обраного досліду…</span>
    </div>
</x-filament-panels::page>
