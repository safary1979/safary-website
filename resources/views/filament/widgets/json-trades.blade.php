<x-filament-widgets::widget>
    <x-filament::section heading="Останні угоди">
        @if($trades->isEmpty())
            <p style="color:#6b7280; text-align:center; padding:1rem;">Угоди з'являться після закриття першої позиції.</p>
        @else
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; font-size:0.8rem;">
                    <thead>
                        <tr style="background:#111827; color:#6b7280; text-align:left; border-bottom:1px solid #1f2937;">
                            <th style="padding:0.4rem 0.6rem;">Бот</th>
                            <th style="padding:0.4rem 0.6rem;">Side</th>
                            <th style="padding:0.4rem 0.6rem;">Вхід</th>
                            <th style="padding:0.4rem 0.6rem;">Вихід</th>
                            <th style="padding:0.4rem 0.6rem;">Ціна вхід</th>
                            <th style="padding:0.4rem 0.6rem;">Ціна вихід</th>
                            <th style="padding:0.4rem 0.6rem;">PnL USDT</th>
                            <th style="padding:0.4rem 0.6rem;">PnL %</th>
                            <th style="padding:0.4rem 0.6rem;">Причина</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($trades as $t)
                        @php
                            $pnlColor = $t->pnl_usdt >= 0 ? '#22c55e' : '#ef4444';
                            $sideColor = $t->side === 'long' ? '#22c55e' : '#ef4444';
                            $entryTime = $t->entry_time ? \Carbon\Carbon::parse($t->entry_time)->format('m-d H:i') : '—';
                            $exitTime  = $t->exit_time  ? \Carbon\Carbon::parse($t->exit_time)->format('m-d H:i')  : '—';
                        @endphp
                        <tr style="border-top:1px solid #1f2937;">
                            <td style="padding:0.4rem 0.6rem; color:#e5e7eb;">{{ $t->bot_name }}</td>
                            <td style="padding:0.4rem 0.6rem; color:{{ $sideColor }}; font-weight:600; text-transform:uppercase;">{{ $t->side }}</td>
                            <td style="padding:0.4rem 0.6rem; color:#9ca3af;">{{ $entryTime }}</td>
                            <td style="padding:0.4rem 0.6rem; color:#9ca3af;">{{ $exitTime }}</td>
                            <td style="padding:0.4rem 0.6rem;">{{ number_format($t->entry_price, 3) }}</td>
                            <td style="padding:0.4rem 0.6rem;">{{ number_format($t->exit_price, 3) }}</td>
                            <td style="padding:0.4rem 0.6rem; color:{{ $pnlColor }}; font-weight:600;">
                                {{ ($t->pnl_usdt >= 0 ? '+' : '') . number_format($t->pnl_usdt, 2) }}
                            </td>
                            <td style="padding:0.4rem 0.6rem; color:{{ $pnlColor }};">
                                {{ ($t->pnl_pct >= 0 ? '+' : '') . number_format($t->pnl_pct, 2) }}%
                            </td>
                            <td style="padding:0.4rem 0.6rem;">
                                <span style="font-size:0.7rem; padding:0.15rem 0.5rem; border-radius:999px; background:#1f2937; color:#9ca3af;">
                                    {{ $t->exit_reason ?? '—' }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="border-top:2px solid #374151; background:#0f172a;">
                            <td colspan="6" style="padding:0.4rem 0.6rem; color:#6b7280; font-size:0.75rem;">Σ {{ $trades->count() }} угод</td>
                            <td style="padding:0.4rem 0.6rem; font-weight:700; color:{{ $trades->sum('pnl_usdt') >= 0 ? '#22c55e' : '#ef4444' }};">
                                {{ ($trades->sum('pnl_usdt') >= 0 ? '+' : '') . number_format($trades->sum('pnl_usdt'), 2) }}
                            </td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
