<x-filament-panels::page>
    @php
        $snapshot = $this->snapshot();
        $data = $snapshot['data'] ?? [];
        $host = $data['host'] ?? [];
        $memory = $host['memory'] ?? [];
        $disk = $host['freqtrade_disk'] ?? [];
        $processes = $host['processes'] ?? [];
        $controller = $data['controller'] ?? [];
        $controllerState = $controller['state'] ?? [];
        $docker = $data['docker'] ?? [];
        $tradeDatabases = $data['trade_databases'] ?? [];
        $events = $controller['latest_events'] ?? [];
        $updatedAtLabel = null;
        $updatedAt = $data['generated_at_utc'] ?? null;
        if (is_string($updatedAt) && trim($updatedAt) !== '') {
            try {
                $updatedAtLabel = \Carbon\Carbon::parse($updatedAt, 'UTC')
                    ->setTimezone('Europe/Kyiv')
                    ->format('d.m.Y H:i:s');
            } catch (\Throwable) {
                $updatedAtLabel = null;
            }
        }
    @endphp

    <div wire:poll.10s style="display:grid; gap:1rem;">
        @if (! ($snapshot['available'] ?? false))
            <div style="padding:1rem; border:1px solid #7f1d1d; border-radius:.5rem; background:#1f0f12; color:#fecaca;">
                {{ $snapshot['error'] ?? 'Gateway недоступний' }}
            </div>
        @else
            <div style="padding:1rem; border:1px solid #1f2937; border-radius:.5rem; background:#020617; color:#cbd5e1; display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;">
                <div>
                    <div style="font-size:.75rem; color:#94a3b8;">read-only · автооновлення 10 сек</div>
                    <div style="font-size:1.1rem; font-weight:700; color:#f8fafc;">Стан системи</div>
                </div>
                <div style="font-size:.8rem; color:#86efac;">● Оновлено {{ $updatedAtLabel ?? '—' }} · Київ</div>
            </div>

            <div style="display:grid; gap:.7rem; grid-template-columns:repeat(auto-fit,minmax(11rem,1fr));">
                @foreach ([
                    ['CPU', ($host['cpu_count'] ?? 0) . ' cores', '#bfdbfe'],
                    ['Load 1m', $host['load_average'][0] ?? '—', '#fde68a'],
                    ['RAM вільно', $this->bytes($memory['memavailable_bytes'] ?? 0), '#86efac'],
                    ['RAM всього', $this->bytes($memory['memtotal_bytes'] ?? 0), '#e2e8f0'],
                    ['Disk вільно', $this->bytes($disk['free_bytes'] ?? 0), '#c4b5fd'],
                    ['Disk зайнято', $this->bytes($disk['used_bytes'] ?? 0), '#fca5a5'],
                ] as [$label, $value, $color])
                    <div style="border:1px solid #1f2937; border-radius:.5rem; background:#020617; padding:.8rem;">
                        <div style="font-size:.72rem; color:#94a3b8;">{{ $label }}</div>
                        <div style="font-size:1.2rem; font-weight:800; color:{{ $color }};">{{ $value }}</div>
                    </div>
                @endforeach
            </div>

            <div style="padding:1rem; border:1px solid #1f2937; border-radius:.5rem; background:#020617; color:#cbd5e1; overflow-x:auto;">
                <div style="font-weight:700; color:#f8fafc; margin-bottom:.65rem;">Навантаження процесів</div>
                @if (! ($processes['available'] ?? false))
                    <div style="color:#fca5a5; font-size:.82rem;">{{ $processes['error'] ?? 'Дані процесів недоступні' }}</div>
                @else
                    <table style="width:100%; border-collapse:collapse; font-size:.8rem; min-width:24rem;">
                        <thead><tr style="color:#94a3b8; text-align:left; border-bottom:1px solid #1f2937;"><th style="padding:.4rem;">що працює</th><th style="padding:.4rem;">CPU</th><th style="padding:.4rem;">RAM</th><th style="padding:.4rem;">PID</th></tr></thead>
                        <tbody>
                            @foreach (($processes['items'] ?? []) as $process)
                                <tr style="border-bottom:1px solid #111827;"><td style="padding:.45rem; color:#e2e8f0;">{{ $process['role'] }}</td><td style="padding:.45rem; color:#bfdbfe;">{{ number_format((float)($process['cpu_percent'] ?? 0), 1) }}%</td><td style="padding:.45rem;">{{ $this->bytes($process['memory_bytes'] ?? 0) }}</td><td style="padding:.45rem; color:#64748b;">{{ $process['pid'] }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            <div style="padding:1rem; border:1px solid #1f2937; border-radius:.5rem; background:#020617; color:#cbd5e1;">
                <div style="display:flex; justify-content:space-between; gap:1rem; flex-wrap:wrap; margin-bottom:.75rem;">
                    <div style="font-weight:700; color:#f8fafc;">Активна задача</div>
                    <div style="color:{{ ($controllerState['status'] ?? '') === 'running' ? '#86efac' : '#fde68a' }}; font-weight:700;">{{ $controllerState['status'] ?? 'unknown' }}</div>
                </div>
                <div style="display:grid; gap:.6rem; grid-template-columns:repeat(auto-fit,minmax(14rem,1fr)); font-size:.82rem;">
                    <div><span style="color:#94a3b8;">Batch:</span> <span style="font-family:ui-monospace,monospace; color:#e2e8f0;">{{ $controllerState['active_batch_id'] ?? '—' }}</span></div>
                    <div><span style="color:#94a3b8;">Controller PID:</span> {{ $controllerState['active_controller_pid'] ?? '—' }}</div>
                    <div><span style="color:#94a3b8;">Stop after circle:</span> {{ $controllerState['stop_after_circle'] ?? '—' }}</div>
                </div>
            </div>

            <div style="display:grid; gap:1rem; grid-template-columns:repeat(auto-fit,minmax(28rem,1fr));">
                <div style="padding:1rem; border:1px solid #1f2937; border-radius:.5rem; background:#020617; color:#cbd5e1; overflow-x:auto;">
                    <div style="font-weight:700; color:#f8fafc; margin-bottom:.65rem;">Контейнери</div>
                    @if (! ($docker['available'] ?? false))
                        <div style="color:#fca5a5; font-size:.82rem;">{{ $docker['error'] ?? 'Docker недоступний' }}</div>
                    @else
                        <table style="width:100%; border-collapse:collapse; font-size:.8rem; min-width:28rem;">
                            <thead><tr style="color:#94a3b8; text-align:left; border-bottom:1px solid #1f2937;"><th style="padding:.4rem;">container</th><th style="padding:.4rem;">CPU</th><th style="padding:.4rem;">RAM</th><th style="padding:.4rem;">PID</th></tr></thead>
                            <tbody>
                                @foreach (($docker['containers'] ?? []) as $container)
                                    <tr style="border-bottom:1px solid #111827;"><td style="padding:.45rem; font-family:ui-monospace,monospace; color:#e2e8f0;">{{ $container['name'] }}</td><td style="padding:.45rem; color:#bfdbfe;">{{ $container['cpu'] }}</td><td style="padding:.45rem;">{{ $container['memory'] }}</td><td style="padding:.45rem;">{{ $container['pids'] }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                <div style="padding:1rem; border:1px solid #1f2937; border-radius:.5rem; background:#020617; color:#cbd5e1; overflow-x:auto;">
                    <div style="font-weight:700; color:#f8fafc; margin-bottom:.65rem;">Live-боти / торгові БД</div>
                    <table style="width:100%; border-collapse:collapse; font-size:.8rem; min-width:28rem;">
                        <thead><tr style="color:#94a3b8; text-align:left; border-bottom:1px solid #1f2937;"><th style="padding:.4rem;">БД</th><th style="padding:.4rem;">угод</th><th style="padding:.4rem;">відкрито</th><th style="padding:.4rem;">остання активність</th></tr></thead>
                        <tbody>
                            @foreach ($tradeDatabases as $database)
                                <tr style="border-bottom:1px solid #111827;"><td style="padding:.45rem; font-family:ui-monospace,monospace; color:#e2e8f0;">{{ $database['name'] }}</td><td style="padding:.45rem;">{{ $database['total_trades'] ?? '—' }}</td><td style="padding:.45rem; color:{{ ($database['open_trades'] ?? 0) > 0 ? '#86efac' : '#94a3b8' }};">{{ $database['open_trades'] ?? '—' }}</td><td style="padding:.45rem; color:#94a3b8;">{{ $database['latest_open_date'] ?? ($database['latest_close_date'] ?? '—') }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div style="padding:1rem; border:1px solid #1f2937; border-radius:.5rem; background:#020617; color:#cbd5e1; overflow-x:auto;">
                <div style="font-weight:700; color:#f8fafc; margin-bottom:.65rem;">Останні події контролера</div>
                <table style="width:100%; border-collapse:collapse; font-size:.8rem; min-width:42rem;">
                    <thead><tr style="color:#94a3b8; text-align:left; border-bottom:1px solid #1f2937;"><th style="padding:.4rem;">час</th><th style="padding:.4rem;">етап</th><th style="padding:.4rem;">стан</th><th style="padding:.4rem;">candidate</th><th style="padding:.4rem;">batch</th></tr></thead>
                    <tbody>
                        @foreach ($events as $event)
                            <tr style="border-bottom:1px solid #111827;"><td style="padding:.45rem; color:#94a3b8;">{{ $event['occurred_at_utc'] ?? '' }}</td><td style="padding:.45rem;">{{ $event['stage'] ?? '' }}</td><td style="padding:.45rem; color:{{ in_array($event['status'] ?? '', ['failed', 'interrupted'], true) ? '#fca5a5' : '#86efac' }};">{{ $event['status'] ?? '' }}</td><td style="padding:.45rem;">{{ $event['candidate_id'] ?? '—' }}</td><td style="padding:.45rem; font-family:ui-monospace,monospace;">{{ $event['batch_id'] ?? '—' }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-filament-panels::page>
