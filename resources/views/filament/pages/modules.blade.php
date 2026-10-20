<x-filament-panels::page>
    @php($modules = $this->getModules())

    <div style="display:grid; gap:0.9rem;">
        @foreach ($modules as $module)
            <div style="border:1px solid #1f2937; border-radius:0.5rem; background:#020617; color:#cbd5e1; padding:1rem;">
                <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
                    <div>
                        <div style="font-size:1rem; font-weight:700; color:#f8fafc;">{{ $module['name'] }}</div>
                        <div style="margin-top:0.25rem; color:#94a3b8;">{{ $module['responsibility'] }}</div>
                    </div>
                    <div style="padding:0.2rem 0.45rem; border-radius:0.35rem; background:#0f172a; color:#bfdbfe; font-weight:700;">
                        {{ $module['status'] }}
                    </div>
                </div>

                @if (! empty($module['replaces']))
                    <div style="margin-top:0.75rem;">
                        <span style="color:#94a3b8;">План/заміна:</span>
                        <span style="color:#e2e8f0;">{{ $module['replaces'] }}</span>
                    </div>
                @endif

                @if (! empty($module['important']))
                    <div style="margin-top:0.45rem;">
                        <span style="color:#94a3b8;">Важливо:</span>
                        <span style="color:#fde68a;">{{ $module['important'] }}</span>
                    </div>
                @endif

                <div style="margin-top:0.45rem; color:#64748b; font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size:0.8rem; word-break:break-word;">
                    {{ $module['files'] }}
                </div>
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
