<x-filament-panels::page>
    @php($scalpReports = $this->scalpReports())
    @php($coreTopLong = $this->coreBatchTopCandidates('long'))
    @php($coreTopShort = $this->coreBatchTopCandidates('short'))
    @php($metricText = fn ($metrics) => $metrics ? ($metrics['trades'].' уг. · '.$metrics['wins'].'/'.$metrics['losses'].' W/L · '.number_format((float) $metrics['pnl_pct'], 2).'% · PF '.number_format((float) $metrics['pf'], 2).' · DD '.number_format((float) $metrics['dd_pct'], 2).'%') : '—')
    @php($riskText = fn ($risk) => $risk ? ('+'.number_format((float) ($risk['tp_pct'] ?? 0), 2).'/−'.number_format((float) ($risk['sl_pct'] ?? 0), 2).'%') : '—')
    @php($monthlyPath = storage_path('app/monthly_live_comparison.json'))
    @php($monthly = is_readable($monthlyPath) ? json_decode((string) @file_get_contents($monthlyPath), true) : null)

    <div style="display:grid; gap:1rem;">
        @if ($monthly)
            <div style="padding:1rem; border:1px solid #0f766e; border-radius:.5rem; background:#020617; color:#cbd5e1;">
                <div style="font-size:.76rem; color:#5eead4;">місячний replay live-кандидатів · {{ $monthly['period'] }}</div>
                <div style="font-size:1.1rem; font-weight:700; color:#f8fafc; margin-top:.15rem;">TP / SL по днях</div>
                @foreach ($monthly['candidates'] as $candidate)
                    <div style="margin-top:.7rem; padding-top:.7rem; border-top:1px solid #1f2937;">
                        <span style="font-weight:700; color:#e2e8f0;">{{ $candidate['label'] }}</span>
                        <span style="margin-left:.45rem; color:#94a3b8; font-size:.8rem;">@foreach ($candidate['daily'] as $day) {{ $day[0] }}: <span style="color:#86efac;">TP {{ $day[1] }}</span>/<span style="color:#fca5a5;">SL {{ $day[2] }}</span>@if (!$loop->last) · @endif @endforeach</span>
                    </div>
                @endforeach
                <div style="overflow-x:auto; margin-top:1rem; padding-top:.8rem; border-top:1px solid #334155;">
                    <div style="font-size:.82rem; font-weight:700; color:#e2e8f0; margin-bottom:.25rem;">Загальний результат</div>
                    <table style="width:100%; min-width:700px; border-collapse:collapse; font-size:.78rem;">
                        <thead style="color:#94a3b8; text-align:left; border-bottom:1px solid #334155;"><tr><th style="padding:.4rem;">Кандидат</th><th style="padding:.4rem;">Угоди W/L</th><th style="padding:.4rem;">PnL</th><th style="padding:.4rem;">PF</th><th style="padding:.4rem;">Max DD</th></tr></thead>
                        <tbody>@foreach ($monthly['candidates'] as $candidate)<tr style="border-bottom:1px solid #172033;"><td style="padding:.5rem .4rem; color:#e2e8f0; font-weight:600;">{{ $candidate['label'] }}</td><td style="padding:.5rem .4rem;">{{ $candidate['trades'] }} · {{ $candidate['wins'] }}/{{ $candidate['losses'] }}</td><td style="padding:.5rem .4rem; color:{{ $candidate['pnl_pct'] >= 0 ? '#86efac' : '#fca5a5' }};">{{ $candidate['pnl_pct'] >= 0 ? '+' : '' }}{{ number_format($candidate['pnl_pct'], 2) }}%</td><td style="padding:.5rem .4rem;">{{ number_format($candidate['pf'], 2) }}</td><td style="padding:.5rem .4rem;">{{ number_format($candidate['dd_pct'], 2) }}%</td></tr>@endforeach</tbody>
                    </table>
                </div>
            </div>
        @endif
        <div style="padding:1rem; border:1px solid #1d4ed8; border-radius:.5rem; background:#020617; color:#cbd5e1;">
            <div style="font-size:.76rem; color:#93c5fd;">тести CoreBatchController</div>
            <div style="font-size:1.1rem; font-weight:700; color:#f8fafc; margin-top:.15rem;">Top‑20 за кількістю угод на Train</div>

            @foreach (['Long' => $coreTopLong, 'Short' => $coreTopShort] as $sideLabel => $top)
                <div style="overflow-x:auto; margin-top:1rem; padding-top:1rem; border-top:1px solid #1f2937;">
                    <div style="font-size:.82rem; font-weight:700; color:#e2e8f0; margin-bottom:.2rem;">Top 20 {{ $sideLabel }} — за кількістю угод на Train</div>
                    @if ($top->isEmpty())
                        <div style="padding:.45rem 0; color:#94a3b8; font-size:.78rem;">Ще немає завершених кандидатів.</div>
                    @else
                        <table style="width:100%; min-width:1000px; border-collapse:collapse; font-size:.78rem;">
                            <thead style="color:#94a3b8; text-align:left; border-bottom:1px solid #334155;">
                                <tr><th style="padding:.4rem;">#</th><th style="padding:.4rem;">Коло</th><th style="padding:.4rem;">Кандидат</th><th style="padding:.4rem;">Гілка</th><th style="padding:.4rem;">TP/SL</th><th style="padding:.4rem;">Train</th><th style="padding:.4rem;">Validation</th><th style="padding:.4rem;">Holdout</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($top as $index => $candidate)
                                    <tr style="border-bottom:1px solid #172033;">
                                        <td style="padding:.5rem .4rem; color:#93c5fd; font-weight:700;">{{ $index + 1 }}</td>
                                        <td style="padding:.5rem .4rem; color:#cbd5e1; white-space:nowrap;">{{ $candidate['circle_label'] ?? '—' }}</td>
                                        <td style="padding:.5rem .4rem; color:#e2e8f0; font-weight:600;">{{ $candidate['id'] ?? '—' }}</td>
                                        <td style="padding:.5rem .4rem; color:#cbd5e1;">{{ $candidate['selected_branch']['label'] ?? '—' }}</td>
                                        <td style="padding:.5rem .4rem; color:#fef3c7; white-space:nowrap;">{{ $riskText($candidate['risk'] ?? null) }}</td>
                                        <td style="padding:.5rem .4rem; color:#cbd5e1; white-space:nowrap;">{{ $metricText($candidate['train'] ?? null) }}</td>
                                        <td style="padding:.5rem .4rem; color:#cbd5e1; white-space:nowrap;">{{ $metricText($candidate['validation'] ?? null) }} <span style="color:#93c5fd; font-weight:700;">{{ $candidate['policy_tier'] ?? '' }}</span></td>
                                        <td style="padding:.5rem .4rem; color:#bbf7d0; white-space:nowrap;">{{ $metricText($candidate['holdout'] ?? null) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            @endforeach

            @if ($scalpReports->isEmpty())
                <div style="margin-top:.8rem; color:#94a3b8;">Завершених scalp-кіл ще не імпортовано.</div>
            @else
                @foreach ($scalpReports as $report)
                    @php($payload = $report->payload ?? [])
                    @php($candidates = $payload['candidates'] ?? [])
                    <div style="margin-top:1rem; padding-top:1rem; border-top:1px solid #1f2937;">
                        <div style="display:flex; justify-content:space-between; gap:.75rem; flex-wrap:wrap; font-size:.82rem;">
                            <span><strong style="color:#bfdbfe;">Коло {{ $payload['circle_number'] ?? '—' }}</strong></span>
                            <span style="color:#94a3b8;">Завершено: {{ $payload['completed_at_utc'] ?? '—' }} · {{ count($candidates) }} тестових гілок</span>
                        </div>
                        <div style="overflow-x:auto; margin-top:.65rem;">
                            <table style="width:100%; min-width:760px; border-collapse:collapse; font-size:.78rem;">
                                <thead style="color:#94a3b8; text-align:left; border-bottom:1px solid #334155;">
                                    <tr><th style="padding:.4rem;">Кандидат</th><th style="padding:.4rem;">Гілка</th><th style="padding:.4rem;">Train</th><th style="padding:.4rem;">Validation</th><th style="padding:.4rem;">Holdout</th><th style="padding:.4rem;">Статус</th></tr>
                                </thead>
                                <tbody>
                                    @foreach ($candidates as $candidate)
                                        <tr style="border-bottom:1px solid #172033;">
                                            <td style="padding:.5rem .4rem; color:#e2e8f0; font-weight:600;">{{ $candidate['id'] ?? '—' }}</td>
                                            <td style="padding:.5rem .4rem; color:#cbd5e1;">{{ $candidate['selected_branch']['label'] ?? '—' }}</td>
                                            @foreach ([$candidate['train'] ?? null, $candidate['validation'] ?? null, $candidate['holdout'] ?? null] as $metrics)
                                                <td style="padding:.5rem .4rem; color:#cbd5e1; white-space:nowrap;">{{ $metricText($metrics) }}</td>
                                            @endforeach
                                            <td style="padding:.5rem .4rem; color:{{ ($candidate['status'] ?? '') === 'validation_passed' ? '#86efac' : '#fbbf24' }};">{{ $candidate['status'] ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</x-filament-panels::page>
