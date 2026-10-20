<x-filament-widgets::widget>
    <x-filament::section heading="Останні угоди">
        @php
            $formatTradeTime = static function ($value): string {
                if ($value === null || trim((string) $value) === '') {
                    return '—';
                }

                try {
                    return \Carbon\Carbon::parse((string) $value, 'UTC')
                        ->setTimezone('Europe/Kyiv')
                        ->format('d.m.Y H:i:s');
                } catch (\Throwable) {
                    return (string) $value;
                }
            };
        @endphp
        <div style="margin:-0.35rem 0 0.55rem; font-size:0.68rem; color:#64748b;">
            Режим: {{ strtoupper($dashboardMode ?? 'live') }} · час Києва
        </div>
        @if (!($modeAvailable ?? true))
            <p style="color:#f59e0b; text-align:center; padding:1rem;">{{ strtoupper($dashboardMode ?? 'live') }}: дані недоступні.</p>
        @elseif ($trades->isEmpty())
            <p style="color:#6b7280; text-align:center; padding:1rem;">Угоди з'являться після закриття першої позиції.</p>
        @else
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; font-size:.8rem;">
                    <thead>
                        <tr style="background:#111827; color:#9ca3af; text-align:left; border-bottom:1px solid #1f2937;">
                            <th style="padding:.4rem .6rem;">Бот</th>
                            <th style="padding:.4rem .6rem;">Side</th>
                            <th style="padding:.4rem .6rem;">Вхід (Київ)</th>
                            <th style="padding:.4rem .6rem;">Вихід (Київ)</th>
                            <th style="padding:.4rem .6rem;">PnL USDT</th>
                            <th style="padding:.4rem .6rem;">Причина</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($trades as $trade)
                            <tr wire:key="trade-{{ $loop->index }}-{{ $trade->bot_name ?? '' }}-{{ $trade->exit_time ?? '' }}" style="border-top:1px solid #1f2937;">
                                <td style="padding:.4rem .6rem; color:#e5e7eb;">{{ $trade->bot_name ?? '—' }}</td>
                                <td style="padding:.4rem .6rem;">{{ $trade->side ?? '—' }}</td>
                                <td style="padding:.4rem .6rem; color:#9ca3af; white-space:nowrap;">{{ $formatTradeTime($trade->entry_time ?? null) }}</td>
                                <td style="padding:.4rem .6rem; color:#9ca3af; white-space:nowrap;">{{ $formatTradeTime($trade->exit_time ?? null) }}</td>
                                <td style="padding:.4rem .6rem; color:{{ ($trade->pnl_usdt ?? 0) >= 0 ? '#22c55e' : '#ef4444' }}; font-weight:600;">{{ number_format((float) ($trade->pnl_usdt ?? 0), 2) }}</td>
                                <td style="padding:.4rem .6rem;">{{ $trade->exit_reason ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
