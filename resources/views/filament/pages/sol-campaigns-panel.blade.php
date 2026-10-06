@php
    $dualSnapshot = $this->dualSnapshot();
    $dual = $dualSnapshot['data'] ?? [];
    $dualLabels = ['prepared'=>'Підготовлено', 'running'=>'Працює', 'stopped'=>'Зупинено', 'needs_attention'=>'Потрібне втручання'];
    $dualStages = ['preparing'=>'Підготовка кандидатів', 'baseline'=>'Базовий replay', 'optuna'=>'Оптимізація', 'transfer_2026'=>'Перевірка 2026', 'full'=>'Повний replay', 'leader'=>'Перевірка лідера', 'proposal'=>'Підготовка кандидатів', 'initialization'=>'Перевірка кандидатів', 'backfill_selection'=>'Добір кандидатів', 'backfill_initialization'=>'Перевірка додаткових кандидатів', 'admission'=>'Відбір кандидатів', 'optimization'=>'Оптимізація', 'leader_control'=>'Перевірка лідера', 'verify_report'=>'Перевірка результатів', 'publish'=>'Оновлення результатів', 'recover_publication'=>'Відновлення результатів'];
@endphp
@if ($dual['available'] ?? false)
    <section style="padding:1rem;border:1px solid #2563eb;border-radius:.5rem;background:#020617;">
        <strong style="color:#f8fafc;">SOL / Bybit · окремі кампанії Long та Short</strong>
        <p style="color:#94a3b8;font-size:.85rem;">Рахунок 3000 USDT · позиція 4000 USDT · 4× · історичний funding. Ретроспективний пошук 2024–2025, перевірка 2026. Пара — наступний етап.</p>
        <p style="color:#94a3b8;font-size:.85rem;">Справна робота пілоту: {{ number_format(($dual['pilot']['healthy_seconds'] ?? 0)/3600, 2) }} год / {{ $dual['pilot']['hours'] ?? 72 }} год.</p>
        <p style="color:#94a3b8;font-size:.85rem;">Непідтверджений / несправний час під час роботи: {{ number_format((($dual['pilot']['unobserved_seconds'] ?? 0)+($dual['pilot']['unhealthy_seconds'] ?? 0))/3600,2) }} год. Процеси тестів: {{ $dual['worker_liveness']['alive'] ?? 0 }} / {{ $dual['worker_liveness']['reported'] ?? 0 }}.</p>
        @if (isset($dual['host_load_1_5_15']))<p style="color:#94a3b8;font-size:.85rem;">Load 1/5/15 хв: {{ implode(' / ', array_map(fn($value)=>number_format($value,2),$dual['host_load_1_5_15'])) }} · доступна RAM {{ number_format(($dual['available_memory_bytes'] ?? 0)/1073741824,1) }} GiB.</p>@endif
        @foreach (['long'=>'Long', 'short'=>'Short'] as $side=>$label)
            @php($campaign = $dual['campaigns'][$side] ?? [])
            <div style="margin-top:1rem;padding:1rem;border:1px solid #334155;border-radius:.5rem;">
                <div style="display:flex;justify-content:space-between;gap:1rem;">
                    <strong>{{ $label }} · {{ $dualLabels[$campaign['status'] ?? ''] ?? ($campaign['status'] ?? 'Недоступно') }}</strong>
                    @if (($campaign['status'] ?? '') === 'running' && ! ($campaign['stop_requested'] ?? false))
                        <button type="button" wire:click="stopCampaign('{{ $side }}')" wire:loading.attr="disabled" style="color:#fbbf24;">Завершити цикл і зупинити</button>
                    @endif
                </div>
                <p style="font-size:.85rem;color:#94a3b8;">Завершено партій: {{ $campaign['completed_batches'] ?? 0 }} · {{ $dualStages[$campaign['stage'] ?? ''] ?? '' }}
                    @if ($campaign['stop_requested'] ?? false) · Запит на зупинку збережено @endif
                </p>
                @if ($campaign['error'] ?? false)<p role="alert" style="color:#fca5a5;">{{ $campaign['error'] }}</p>@endif
                @if (isset($campaign['experiment']))
                    @php($experiment = $campaign['experiment'])
                    @php($experimentLabels = ['prepared'=>'Підготовлено','running'=>'Працює','verifying'=>'Перевірка результатів','completed'=>'Завершено','stopped'=>'Зупинено','needs_attention'=>'Потрібна перевірка'])
                    <div style="margin:.75rem 0;padding:.75rem;border:1px solid #3b82f6;border-radius:.5rem;">
                        <strong>Порівняння бюджету 40 → 48 · {{ $experimentLabels[$experiment['status'] ?? ''] ?? 'Недоступно' }}</strong>
                        @if (isset($experiment['fresh'], $experiment['budget'], $experiment['completed_cases'], $experiment['total_cases']))
                            <p>Виконано {{ $experiment['fresh'] }} / {{ $experiment['budget'] }} тестів · партій {{ $experiment['completed_cases'] }} / {{ $experiment['total_cases'] }}.</p>
                        @endif
                        @if (isset($experiment['arms']))
                            <p>Додаткові 8: {{ $experiment['arms']['extra']['fresh'] ?? 0 }}/48 · чинний добір: {{ $experiment['arms']['control']['fresh'] ?? 0 }}/48.</p>
                        @endif
                        @if ($experiment['stop_requested'] ?? false)
                            <p style="color:#fbbf24;">Запит на зупинку збережено.</p>
                        @elseif (in_array($experiment['status'] ?? '', ['prepared','running','verifying']))
                            <button type="button" wire:click="stopCampaign('short')" wire:loading.attr="disabled" style="color:#fbbf24;">Завершити поточні партії й зупинити</button>
                        @endif
                        @if ($experiment['error'] ?? false)<p role="alert" style="color:#fca5a5;">{{ $experiment['error'] }}</p>@endif
                        @if (isset($experiment['result']))
                            <p>{{ ($experiment['result']['gate_pass'] ?? false) ? 'Додатковий бюджет пройшов критерії порівняння на 2024–2025.' : 'Перевагу додаткового бюджету за всіма критеріями не підтверджено.' }} Звичайний пошук зберігає ліміт 40.</p>
                        @endif
                        @if (($experiment['followup']['verified'] ?? false) && ($experiment['verified_terminal'] ?? false))
                            @php($followup = $experiment['followup'])
                            <p><strong>Перевірка 2026/FULL завершена: {{ $followup['tested'] }} кандидати → {{ $followup['full'] }} FULL → {{ $followup['new_top'] }} нових Top.</strong></p>
                            @foreach ($followup['candidates'] as $candidate)
                                <p>{{ $candidate['family'] }} · 2026: {{ number_format($candidate['transfer_pnl'], 2) }} USDT / {{ $candidate['transfer_exits'] }} угод.
                                    @if (! $candidate['eligible_transfer'])
                                        Відсів: {{ $candidate['transfer_exits'] }} угод, мінімум {{ $candidate['transfer_required_exits'] }}.
                                    @elseif ($candidate['full_pnl'] !== null)
                                        FULL {{ number_format($candidate['full_pnl'], 2) }} USDT · поріг Top 10 {{ number_format($followup['top10_cutoff'], 2) }} USDT.
                                    @endif
                                </p>
                            @endforeach
                        @endif
                    </div>
                @endif

                <p style="font-size:.85rem;">Поточні пропозиції: {{ $campaign['current_complete'] ?? 0 }} · бюджет {{ $campaign['budget'] ?? 40 }} @if(isset($campaign['lifetime_budget']))на простір за відвідування · {{ $campaign['lifetime_budget'] }} загалом на простір@else на простір@endif · історія {{ $campaign['history'] ?? 0 }}.
                    Fresh {{ $campaign['counts']['fresh'] ?? 0 }} / reuse {{ $campaign['counts']['reuse'] ?? 0 }} / відсів {{ ($campaign['counts']['early_reject'] ?? 0) + ($campaign['counts']['order_reject'] ?? 0) }}.
                    @if (isset($dual['pilot']['queues'][$side]['running'], $dual['pilot']['queues'][$side]['queued']))
                        У роботі {{ $dual['pilot']['queues'][$side]['running'] }}, у черзі {{ $dual['pilot']['queues'][$side]['queued'] }}.
                    @endif
                </p>
                @if (isset($campaign['selected_spaces']))
                    <p style="font-size:.85rem;color:#94a3b8;">Відібрано просторів: {{ $campaign['selected_spaces'] }} · план нових тестів: {{ $campaign['planned_fresh'] ?? 0 }}.
                        @if (($campaign['slot_shortfall'] ?? 0) > 0) Недобір просторів: {{ $campaign['slot_shortfall'] }}. @endif
                        @if (($campaign['exploration_shortfall'] ?? 0) > 0) Недобір нових структур: {{ $campaign['exploration_shortfall'] }}. @endif
                    </p>
                @endif
                <strong>Top 10 · {{ count($campaign['top10'] ?? []) }}/10 перевірених конфігурацій</strong>
                @if (empty($campaign['top10']))
                    <p style="font-size:.85rem;color:#94a3b8;">У чинному контракті ще немає допущених результатів.</p>
                @else
                    <div style="overflow:auto;"><table style="width:100%;font-size:.85rem;text-align:left;">
                        <thead><tr><th>№</th><th>Механізм</th><th>Net USDT</th><th>2024</th><th>2025</th><th>2026</th><th>Угоди</th><th>DD</th><th>Найкращий місяць / частка net</th></tr></thead>
                        <tbody>@foreach ($campaign['top10'] as $row)
                            <tr><td>{{ $loop->iteration }}</td><td>{{ $row['spec']['logic']['core'] ?? $row['spec']['logic']['event'] ?? $row['spec']['logic']['mechanism'] ?? $row['family'] ?? '' }}</td>
                            <td>{{ number_format($row['net_pnl_usdt'],2) }}</td>
                            @foreach (['2024','2025','2026'] as $year)<td>{{ number_format($row['annual'][$year]['net_pnl_usdt'] ?? 0,2) }}</td>@endforeach
                            <td>{{ $row['completed_trades'] }}</td><td>{{ number_format($row['equity_drawdown']['loss_pct'] ?? 0, 1) }}%</td><td>{{ $row['best_month'] ?? '—' }} / {{ number_format(($row['best_month_share_of_net'] ?? 0)*100, 1) }}%</td></tr>
                        @endforeach</tbody>
                    </table></div>
                @endif
            </div>
        @endforeach
        @if ($this->campaignControlMessage)<p role="status" style="color:#93c5fd;">{{ $this->campaignControlMessage }}</p>@endif
    </section>
@elseif (($dual['status'] ?? '') === 'needs_attention')
    <section role="alert" style="padding:1rem;border:1px solid #dc2626;">Стан нових кампаній потребує перевірки: {{ $dual['error'] ?? 'status недоступний' }}</section>
@endif
