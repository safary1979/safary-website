            @if (! empty($newTop10['candidates']))
                <section data-autofon-section="top10-current" style="padding:1rem;border:1px solid #1d4ed8;border-radius:.5rem;background:#020617;color:#cbd5e1;">
                    <div style="display:flex;justify-content:space-between;align-items:baseline;gap:.75rem;flex-wrap:wrap;">
                        <div style="font-size:1.1rem;font-weight:700;color:#f8fafc;">Top 10 · SOL Long / Bybit</div>
                        <div style="font-size:.8rem;color:#94a3b8;">{{ count($newTop10['candidates']) }} кандидатів · рейтинг за PnL за весь період</div>
                    </div>
                    <div style="font-size:.78rem;color:#94a3b8;margin-top:.35rem;">Безперервний replay {{ isset($newTop10['period'][0]) ? (new \DateTimeImmutable($newTop10['period'][0]))->format('d.m.Y') : '—' }}–{{ isset($newTop10['period'][1]) ? (new \DateTimeImmutable($newTop10['period'][1]))->format('d.m.Y') : '—' }} · USDT · одна максимальна просадка за весь період</div>
                    <div style="overflow-x:auto;margin-top:.75rem;">
                        <table style="width:100%;min-width:940px;border-collapse:collapse;font-size:.78rem;">
                            <thead style="color:#94a3b8;text-align:left;">
                                <tr style="border-bottom:1px solid #334155;">
                                    <th style="padding:.55rem .45rem;width:2.5rem;">#</th>
                                    <th style="padding:.55rem .45rem;min-width:12rem;">Кандидат</th>
                                    <th style="padding:.55rem .45rem;white-space:nowrap;">TP / SL</th>
                                    @foreach ($displayYears as $year)
                                        <th style="padding:.55rem .45rem;white-space:nowrap;">{{ $year }}{{ $year === end($displayYears) ? ' · до '.(new \DateTimeImmutable($newTop10['period'][1]))->format('d.m') : '' }}</th>
                                    @endforeach
                                    <th style="padding:.55rem .45rem;white-space:nowrap;">PnL · весь період</th>
                                    <th style="padding:.55rem .45rem;">Макс. просадка за весь період, USDT</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($newTop10['candidates'] as $candidate)
                                    @php
                                        $newParameters = $candidate['parameters'] ?? [];
                                        $newCandidateId = $candidate['candidate_id'] ?? '—';
                                        $newTrades = $candidate['completed_trades'] ?? null;
                                        $newAverageTrades = $newTrades !== null && $newPeriodDays > 0 ? $newTrades / $newPeriodDays : null;
                                        $newParameterLabels = ['basis_z_min' => 'Поріг z-score базису', 'efficiency_max' => 'Максимум ефективності', 'flow_min' => 'Мінімальний потік', 'vol_ratio_min' => 'Поріг волатильності', 'volume_ratio_min' => 'Поріг обсягу', 'trend_return_min' => 'Поріг тренду', 'wait_bars' => 'Очікування, свічок', 'time_stop_bars' => 'Часовий вихід, свічок', 'trail_atr_multiple' => 'Множник ATR для трейлінгу', 'htf_ema_span' => 'Період EMA старшого таймфрейму'];
                                    @endphp
                                    <tr style="border-bottom:1px solid #263449;vertical-align:top;">
                                        <td style="padding:.7rem .45rem;color:#93c5fd;font-size:.92rem;font-weight:700;">{{ $candidate['rank'] ?? '—' }}</td>
                                        <td style="padding:.7rem .45rem;">
                                            <div style="color:#94a3b8;">
                                                <button type="button" wire:click="toggleCandidate('{{ $newCandidateId }}')" aria-expanded="{{ $expandedCandidateId === $newCandidateId ? 'true' : 'false' }}" title="Розкрити параметри {{ $newCandidateId }}" style="cursor:pointer;color:#93c5fd;font:.75rem ui-monospace,SFMono-Regular,monospace;white-space:nowrap;">{{ $newCandidateId }}</button>
                                                @if ($expandedCandidateId === $newCandidateId)
                                                <div style="margin-top:.35rem;font-size:.72rem;line-height:1.6;">
                                                    <div>Джерело: {{ ($candidate['source'] ?? '') === 'frozen_v4' ? 'v4' : 'пошук 2024–2025' }}</div>
                                                    @foreach ($newParameterLabels as $key => $label)
                                                        @if (array_key_exists($key, $newParameters))
                                                            <div>{{ $label }}: {{ is_float($newParameters[$key]) ? number_format((float) $newParameters[$key], 3, ',', ' ') : $newParameters[$key] }}</div>
                                                        @endif
                                                    @endforeach
                                                </div>
                                                @endif
                                            </div>
                                        </td>
                                        <td style="padding:.7rem .45rem;color:#fde68a;white-space:nowrap;font-variant-numeric:tabular-nums;">
                                            TP @if (isset($newParameters['tp_pct'])) +{{ number_format((float) $newParameters['tp_pct'], 2, ',', ' ') }}% @else — @endif<br>
                                            SL @if (isset($newParameters['sl_pct'])) −{{ number_format((float) $newParameters['sl_pct'], 2, ',', ' ') }}% @else — @endif
                                        </td>
                                        @foreach ($displayYears as $year)
                                            @php $newYearMetrics = $candidate['annual_display'][$year] ?? null; @endphp
                                            <td style="padding:.7rem .45rem;white-space:nowrap;font-variant-numeric:tabular-nums;">
                                                @if (isset($newYearMetrics['net_pnl_usdt']))
                                                    <span style="color:{{ (float) $newYearMetrics['net_pnl_usdt'] >= 0 ? '#86efac' : '#fca5a5' }};">{{ number_format((float) $newYearMetrics['net_pnl_usdt'], 2, ',', ' ') }} USDT</span>
                                                    <small style="display:block;color:#94a3b8;font-size:.68rem;">{{ $newYearMetrics['exits'] ?? '—' }} угод</small>
                                                @else
                                                    <span style="color:#64748b;">—</span>
                                                @endif
                                            </td>
                                        @endforeach
                                        <td style="padding:.7rem .45rem;color:{{ (float) ($candidate['net_pnl_usdt'] ?? 0) >= 0 ? '#86efac' : '#fca5a5' }};font-weight:700;white-space:nowrap;font-variant-numeric:tabular-nums;">
                                            {{ isset($candidate['net_pnl_usdt']) ? number_format((float) $candidate['net_pnl_usdt'], 2, ',', ' ') : '—' }} USDT
                                            <small style="display:block;margin-top:.18rem;color:#94a3b8;font-size:.68rem;font-weight:400;">{{ $newTrades ?? '—' }} угод@if ($newAverageTrades !== null) ({{ number_format($newAverageTrades, 2, ',', ' ') }}/день)@endif</small>
                                        </td>
                                        <td style="padding:.7rem .45rem;color:#e2e8f0;font-weight:600;white-space:nowrap;font-variant-numeric:tabular-nums;">{{ isset($candidate['max_dd_usdt']) ? number_format((float) $candidate['max_dd_usdt'], 2, ',', ' ') : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif
