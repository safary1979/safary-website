<x-filament-panels::page>
    @php($reports = $this->reports())
    @php($coreBatchReports = $this->coreBatchReports())
    @php($coreBatchTopLong = $this->coreBatchTopCandidates('long'))
    @php($coreBatchTopShort = $this->coreBatchTopCandidates('short'))
    @php($metricText = fn ($metrics) => $metrics ? ($metrics['trades'].'/'.$metrics['wins'].'/'.$metrics['losses'].' · '.number_format((float) $metrics['pnl_pct'], 2).'% · PF '.number_format((float) $metrics['pf'], 2).' · DD '.number_format((float) $metrics['dd_pct'], 2).'%') : '—')
    @php($riskText = fn ($risk) => $risk ? ('+'.number_format((float) $risk['tp_pct'], 2).'/−'.number_format((float) $risk['sl_pct'], 2).'%') : '—')

    <div style="display:grid; gap:1rem;">
        <div style="padding:1rem; border:1px solid #1d4ed8; border-radius:.5rem; background:#020617; color:#cbd5e1;">
            <div style="font-size:.76rem; color:#93c5fd;">лише завершені кола CoreBatchController</div>
            <div style="font-size:1.1rem; font-weight:700; color:#f8fafc; margin-top:.15rem;">Результати фонового дослідження</div>

            @foreach (['Long' => $coreBatchTopLong, 'Short' => $coreBatchTopShort] as $sideLabel => $top)
                <div style="overflow-x:auto; margin-top:1rem; padding-top:1rem; border-top:1px solid #1f2937;">
                    <div style="font-size:.82rem; font-weight:700; color:#e2e8f0; margin-bottom:.2rem;">Top 10 {{ $sideLabel }} — пройшли Train, Validation і Holdout; за Holdout PnL, далі PF</div>
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

            @if ($coreBatchReports->isEmpty())
                <div style="margin-top:.8rem; color:#94a3b8;">Завершених кіл ще не імпортовано.</div>
            @else
                @foreach ($coreBatchReports as $report)
                    @php($payload = $report->payload ?? [])
                    @php($candidates = $this->displayCandidates($payload))
                    <div style="margin-top:1rem; padding-top:1rem; border-top:1px solid #1f2937;">
                        <div style="display:flex; justify-content:space-between; gap:.75rem; flex-wrap:wrap; font-size:.82rem;">
                            <span><strong style="color:#bfdbfe;">Коло {{ $payload['circle_number'] ?? '—' }}</strong></span>
                            <span style="color:#94a3b8;">Завершено: {{ $payload['completed_at_utc'] ?? '—' }} · {{ count($candidates) }} кандидатів</span>
                        </div>
                        <div style="overflow-x:auto; margin-top:.65rem;">
                            <table style="width:100%; min-width:840px; border-collapse:collapse; font-size:.78rem;">
                                <thead style="color:#94a3b8; text-align:left; border-bottom:1px solid #334155;">
                                    <tr>
                                        <th style="padding:.4rem;">Кандидат</th>
                                        <th style="padding:.4rem;">Обрана гілка</th>
                                        <th style="padding:.4rem;">TP/SL</th>
                                        <th style="padding:.4rem;">Train</th>
                                        <th style="padding:.4rem;">Validation</th>
                                        <th style="padding:.4rem;">Holdout</th>
                                        <th style="padding:.4rem;">Статус</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($candidates as $candidate)
                                        @php($train = $candidate['train'] ?? null)
                                        @php($validation = $candidate['validation'] ?? null)
                                        @php($holdout = $candidate['holdout'] ?? null)
                                        @php($branch = $candidate['selected_branch'] ?? null)
                                        <tr style="border-bottom:1px solid #172033;">
                                            <td style="padding:.5rem .4rem; color:#e2e8f0; font-weight:600;">{{ $candidate['id'] ?? '—' }}</td>
                                            <td style="padding:.5rem .4rem; color:#cbd5e1;">{{ $branch['label'] ?? '—' }}</td>
                                            <td style="padding:.5rem .4rem; color:#fef3c7; white-space:nowrap;">{{ $riskText($candidate['risk'] ?? null) }}</td>
                                            @foreach ([$train, $validation, $holdout] as $metrics)
                                                <td style="padding:.5rem .4rem; color:#cbd5e1; white-space:nowrap;">
                                                    @if ($metrics)
                                                        {{ $metricText($metrics) }}
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                            @endforeach
                                            <td style="padding:.5rem .4rem; color:{{ ($candidate['status'] ?? '') === 'complete' ? '#86efac' : '#fbbf24' }};">{{ $candidate['status'] ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        <div style="padding:1rem; border:1px solid #1f2937; border-radius:.5rem; background:#020617; color:#cbd5e1; display:flex; justify-content:space-between; gap:1rem; align-items:center; flex-wrap:wrap;">
            <div>
                <div style="font-size:.76rem; color:#94a3b8;">лише ручне формування</div>
                <div style="font-size:1.1rem; font-weight:700; color:#f8fafc;">Звіти</div>
            </div>
            <button wire:click="createReport" wire:loading.attr="disabled" style="padding:.5rem .85rem; border:0; border-radius:.4rem; background:#2563eb; color:white; font-weight:700; cursor:pointer;">
                <span wire:loading.remove wire:target="createReport">Сформувати звіт</span>
                <span wire:loading wire:target="createReport">Формування…</span>
            </button>
        </div>

        @if ($reports->isEmpty())
            <div style="padding:1rem; border:1px solid #1f2937; border-radius:.5rem; background:#020617; color:#94a3b8;">Ще немає сформованих звітів.</div>
        @else
            @foreach ($reports as $report)
                @php($payload = $report->payload ?? [])
                @php($controller = $payload['controller']['state'] ?? [])
                @php($host = $payload['host'] ?? [])
                @php($tradeDatabases = $payload['trade_databases'] ?? [])
                @php($openTrades = collect($tradeDatabases)->sum(fn ($database) => (int) ($database['open_trades'] ?? 0)))

                <div style="padding:1rem; border:1px solid {{ $report->status === 'published' ? '#166534' : '#1f2937' }}; border-radius:.5rem; background:#020617; color:#cbd5e1;">
                    <div style="display:flex; justify-content:space-between; gap:1rem; align-items:flex-start; flex-wrap:wrap;">
                        <div>
                            <div style="font-size:.75rem; color:#94a3b8;">#{{ $report->id }} · {{ $report->created_at?->format('Y-m-d H:i:s') }}</div>
                            <div style="font-size:1rem; font-weight:700; color:#f8fafc;">{{ $report->status === 'published' ? 'Опублікований звіт' : 'Чернетка звіту' }}</div>
                        </div>
                        @if ($report->status === 'draft')
                            <button wire:click="publish({{ $report->id }})" style="padding:.42rem .75rem; border:1px solid #22c55e; border-radius:.4rem; background:#16653433; color:#bbf7d0; font-weight:700; cursor:pointer;">Опублікувати</button>
                        @else
                            <div style="font-size:.8rem; color:#86efac;">Опубліковано {{ $report->published_at?->format('Y-m-d H:i:s') }}</div>
                        @endif
                    </div>

                    <div style="display:grid; gap:.6rem; grid-template-columns:repeat(auto-fit,minmax(11rem,1fr)); margin-top:.9rem; font-size:.82rem;">
                        <div><span style="color:#94a3b8;">Controller:</span> <span style="color:#86efac;">{{ $controller['status'] ?? '—' }}</span></div>
                        <div><span style="color:#94a3b8;">Batch:</span> {{ $controller['active_batch_id'] ?? '—' }}</div>
                        <div><span style="color:#94a3b8;">Load 1m:</span> {{ $host['load_average'][0] ?? '—' }}</div>
                        <div><span style="color:#94a3b8;">Відкрито угод:</span> {{ $openTrades }}</div>
                        <div><span style="color:#94a3b8;">Знімок Gateway:</span> {{ $payload['generated_at_utc'] ?? '—' }}</div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</x-filament-panels::page>
