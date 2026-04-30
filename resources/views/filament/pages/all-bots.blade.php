<x-filament-panels::page>
    @if($bots->isEmpty())
        <div style="padding:1rem; color:#9ca3af;">Ботів не знайдено.</div>
    @else
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:0.8rem;">
                <thead>
                    <tr style="background:#111827; color:#9ca3af; text-align:left;">
                        <th style="padding:0.5rem;">Назва</th>
                        <th style="padding:0.5rem;">Pair</th>
                        <th style="padding:0.5rem;">Direction</th>
                        <th style="padding:0.5rem;">TF</th>
                        <th style="padding:0.5rem;">Leverage</th>
                        <th style="padding:0.5rem;">Статус</th>
                        <th style="padding:0.5rem;">Позиція</th>
                        <th style="padding:0.5rem;">PnL</th>
                        <th style="padding:0.5rem;">Equity</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bots as $b)
                        @php
                            $pos = is_object($b->state ?? null) ? $b->state->position_json : null;
                            if (is_string($pos)) $pos = json_decode($pos, true);
                            $side = is_array($pos) ? strtoupper($pos['side_str'] ?? '') : '—';
                            $pnlPct  = is_array($pos) ? (float)($pos['pnl_pct'] ?? 0) : null;
                            $pnlUsdt = is_array($pos) ? (float)($pos['pnl_usdt'] ?? 0) : null;
                            $statusColor = match($b->status ?? '') {
                                'running' => '#22c55e', 'paused' => '#f59e0b',
                                'stopped' => '#6b7280', 'error'  => '#ef4444',
                                default   => '#6b7280',
                            };
                        @endphp
                        <tr style="border-top:1px solid #1f2937;">
                            <td style="padding:0.5rem; color:#e5e7eb;">{{ $b->slug }}</td>
                            <td style="padding:0.5rem;">{{ $b->pair ?? $b->symbol ?? '—' }}</td>
                            <td style="padding:0.5rem; color:{{ ($b->direction ?? '') === 'long' ? '#22c55e' : '#ef4444' }};">{{ strtoupper($b->direction ?? '—') }}</td>
                            <td style="padding:0.5rem;">{{ $b->timeframe ?? '—' }}</td>
                            <td style="padding:0.5rem;">x{{ $b->leverage ?? 1 }}</td>
                            <td style="padding:0.5rem; color:{{ $statusColor }};">{{ $b->status ?? '—' }}</td>
                            <td style="padding:0.5rem;">{{ $side ?: '—' }}</td>
                            <td style="padding:0.5rem; color:{{ $pnlUsdt === null ? '#6b7280' : ($pnlUsdt >= 0 ? '#22c55e' : '#ef4444') }};">
                                @if($pnlUsdt !== null)
                                    {{ ($pnlUsdt >= 0 ? '+' : '') . number_format($pnlUsdt, 2) }} USDT ({{ ($pnlPct >= 0 ? '+' : '') . number_format($pnlPct, 2) }}%)
                                @else — @endif
                            </td>
                            <td style="padding:0.5rem;">
                                {{ number_format((float)($b->state->equity ?? 0), 2) }} USDT
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-filament-panels::page>
