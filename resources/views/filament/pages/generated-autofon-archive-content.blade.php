@php
        $current = $data['current'] ?? null;
        $supervisor = $data['supervisor'] ?? null;
        $review = $data['review'] ?? null;
        $v5 = $data['v5'] ?? null;
        $v52 = $data['v5_2'] ?? null;
        $v53 = $data['v5_3'] ?? null;
        $v5Upgrade = $data['v5_upgrade'] ?? null;
        $v5Upgrade2 = $data['v5_upgrade_cycle2'] ?? null;
        $v5Upgrade3 = $data['v5_upgrade_cycle3'] ?? null;
        $v5Upgrade4 = $data['v5_upgrade_cycle4'] ?? null;
        $v5Upgrade4Prep = $data['v5_upgrade_cycle4_prepare'] ?? null;
        $v5Upgrade5 = $data['v5_upgrade_cycle5'] ?? null;
        $v5Upgrade5Prep = $data['v5_upgrade_cycle5_prepare'] ?? null;
        $v5Upgrade6 = $data['v5_upgrade_cycle6'] ?? null;
        $continuousV4 = $data['continuous_v4'] ?? null;
        $top10UpgradeStatus = $data['top10_upgrade_status'] ?? null;
        $cycles = $data['cycles'] ?? [];
@endphp
        @if (is_array($continuousV4))
            @php
                $continuousProgress = $continuousV4['progress'] ?? [];
                $continuousBatch = $continuousV4['latest_batch'] ?? null;
                $continuousReport = $continuousBatch['report'] ?? null;
                $continuousLabels = ['running' => 'Працює', 'complete' => 'Чергу завершено',
                    'needs_attention' => 'Технічний збій', 'review_pending' => 'Зупинено для аналізу', 'preparing' => 'Підготовка'];
            @endphp
            <section style="padding:.8rem 1rem;border:1px solid {{ ($continuousV4['status'] ?? '') === 'needs_attention' ? '#dc2626' : '#1d4ed8' }};border-radius:.5rem;background:#020617;color:#cbd5e1;">
                <div style="font-weight:700;color:#f8fafc;">Автофон стратегій v4</div>
                <div style="margin-top:.3rem;font-size:.82rem;">
                    Стан: {{ $continuousLabels[$continuousV4['status'] ?? ''] ?? ($continuousV4['status'] ?? '—') }} · етап {{ $continuousV4['stage'] ?? '—' }}
                    · порцій {{ $continuousProgress['chunks_complete'] ?? 0 }} / {{ $continuousProgress['chunks_total'] ?? 10 }}
                    · базовий replay {{ $continuousProgress['baseline_completed'] ?? 0 }}
                    · пройшли {{ $continuousProgress['baseline_eligible'] ?? 0 }}
                    · партій {{ $continuousProgress['batches_complete'] ?? 0 }}
                    @if (($continuousProgress['optuna_total'] ?? 0) > 0)
                        · Optuna {{ $continuousProgress['optuna_completed'] ?? 0 }} / {{ $continuousProgress['optuna_total'] }}
                    @endif
                </div>
                @if (is_array($continuousBatch))
                    <div style="margin-top:.35rem;font-size:.82rem;">
                        Партія {{ $continuousBatch['batch'] ?? '—' }} · {{ $continuousBatch['selected'] ?? 0 }} кандидатів · {{ ($continuousBatch['status'] ?? '') === 'complete' ? 'завершена' : 'виконується' }}
                        @if (is_array($continuousReport))
                            · Optuna {{ $continuousReport['optuna_trials'] ?? 0 }} проб
                            · пройшли 2025: {{ $continuousReport['selected_2025'] ?? 0 }}
                            · пройшли 2026: {{ $continuousReport['passed_2026'] ?? 0 }}
                            · допущені до Top 10: {{ $continuousReport['qualified_top10'] ?? 0 }}
                        @endif
                    </div>
                @endif
                @if (! empty($continuousV4['error']))
                    <div style="margin-top:.35rem;color:#fca5a5;font-size:.8rem;">Помилка: {{ $continuousV4['error'] }}</div>
                    @if (! empty($continuousV4['trial_error']))
                        <div style="margin-top:.2rem;color:#fca5a5;font-size:.8rem;">
                            Кандидат: {{ $continuousV4['trial_error']['candidate_id'] ?? '—' }}
                            · проба: {{ $continuousV4['trial_error']['trial'] ?? '—' }}
                            · причина: {{ $continuousV4['trial_error']['reason'] ?? '—' }}
                        </div>
                    @endif
                @endif
            </section>
        @endif
        @if (is_array($v5Upgrade5Prep))
            <section style="padding:.8rem 1rem;border:1px solid #1d4ed8;border-radius:.5rem;background:#020617;color:#cbd5e1;">
                <div style="font-weight:700;color:#f8fafc;">Об’єднаний автофон · підготовка циклу 5</div>
                <div style="margin-top:.3rem;font-size:.82rem;">Стан: {{ $v5Upgrade5Prep['status'] ?? '—' }} · нові trade/mark структури: {{ $v5Upgrade5Prep['screened'] ?? 0 }} · базовий replay: {{ $v5Upgrade5Prep['completed'] ?? 0 }} / {{ $v5Upgrade5Prep['queued'] ?? 0 }} · пройшли: {{ $v5Upgrade5Prep['eligible'] ?? 0 }}</div>
                @if (! empty($v5Upgrade5Prep['error']))
                    <div style="margin-top:.3rem;color:#fca5a5;font-size:.8rem;">Помилка: {{ $v5Upgrade5Prep['error'] }}</div>
                @endif
            </section>
        @endif
        @if (is_array($v5Upgrade4Prep))
            <section style="padding:.8rem 1rem;border:1px solid #1d4ed8;border-radius:.5rem;background:#020617;color:#cbd5e1;">
                <div style="font-weight:700;color:#f8fafc;">Об’єднаний автофон · підготовка циклу 4</div>
                <div style="margin-top:.3rem;font-size:.82rem;">Стан: {{ $v5Upgrade4Prep['status'] ?? '—' }} · нові структури v4: {{ $v5Upgrade4Prep['screened'] ?? 0 }} · базовий replay: {{ $v5Upgrade4Prep['completed'] ?? 0 }} / {{ $v5Upgrade4Prep['queued'] ?? 0 }} · пройшли: {{ $v5Upgrade4Prep['eligible'] ?? 0 }}</div>
                @if (! empty($v5Upgrade4Prep['error']))
                    <div style="margin-top:.3rem;color:#fca5a5;font-size:.8rem;">Помилка: {{ $v5Upgrade4Prep['error'] }}</div>
                @endif
            </section>
        @endif
            @foreach ([$v5Upgrade6, $v5Upgrade5, $v5Upgrade4, $v5Upgrade3, $v5Upgrade2, $v5Upgrade] as $v5Upgrade)
            @if (is_array($v5Upgrade))
                @php
                    $upgradeStateLabels = [
                        'ready_to_launch' => 'Готовий до запуску',
                        'running' => 'Виконується',
                        'finalizing' => 'Завершується',
                        'review_pending' => ! empty($v5Upgrade['review_analysis']) ? 'Завершено, аналіз готовий; очікує рішення' : 'Завершено; очікує рішення',
                        'needs_attention' => 'Технічний збій',
                    ];
                @endphp
                <section style="padding:.8rem 1rem;border:1px solid #1d4ed8;border-radius:.5rem;background:#020617;color:#cbd5e1;">
                    <div style="font-weight:700;color:#f8fafc;">Об’єднаний автофон v4 + нові логіки · цикл {{ $v5Upgrade['cycle'] ?? '—' }}{{ in_array(($v5Upgrade['cycle'] ?? null), [3, 4, 5, 6], true) ? ' · DD лише діагностика' : '' }}</div>
                    <div style="margin-top:.3rem;font-size:.82rem;">
                        Стан: {{ $upgradeStateLabels[$v5Upgrade['status'] ?? ''] ?? ($v5Upgrade['status'] ?? '—') }} · {{ $v5Upgrade['cohorts']['v4'] ?? 5 }} кандидатів v4 + {{ $v5Upgrade['cohorts']['v5_new_logic'] ?? 2 }} нові · Optuna {{ $v5Upgrade['optuna_completed'] ?? 0 }} / {{ $v5Upgrade['optuna_total'] ?? 280 }}
                        @if (($v5Upgrade['cycle'] ?? null) === 1 && ($top10UpgradeStatus ?? null) === 'published')
                            · Покращений Top 10 опубліковано
                        @endif
                    </div>
                    @if (isset($v5Upgrade['selected_2025'], $v5Upgrade['passed_2026']))
                        <div style="margin-top:.3rem;font-size:.82rem;">Пройшли 2025: {{ $v5Upgrade['selected_2025'] }} із {{ $v5Upgrade['selected'] ?? 7 }} · пройшли 2026: {{ $v5Upgrade['passed_2026'] }} із {{ $v5Upgrade['selected'] ?? 7 }}</div>
                        @if (! empty($v5Upgrade['full_period_comparisons']))
                            <div style="margin-top:.45rem;font-size:.8rem;color:#93c5fd;">Безперервний replay 2023–2026 · незалежний повтор лідера: {{ $v5Upgrade['full_period_repeat'] ?? '—' }}</div>
                            <div style="overflow-x:auto;margin-top:.3rem;">
                                <table style="width:100%;border-collapse:collapse;font-size:.78rem;">
                                    <thead><tr><th style="text-align:left;padding:.3rem;">Кандидат</th><th>PnL, USDT</th><th>Просадка, USDT</th><th>Частка PnL 2023</th><th>Допуск</th></tr></thead>
                                    <tbody>
                                        @foreach ($v5Upgrade['full_period_comparisons'] as $comparison)
                                            <tr style="border-top:1px solid #334155;"><td style="padding:.3rem;font-family:monospace;">{{ $comparison['candidate_id'] }}</td><td style="padding:.3rem;text-align:right;">{{ number_format((float) $comparison['net_pnl_usdt'], 2, ',', ' ') }}</td><td style="padding:.3rem;text-align:right;">{{ number_format((float) $comparison['max_drawdown_usdt'], 2, ',', ' ') }}</td><td style="padding:.3rem;text-align:right;">{{ number_format((float) $comparison['pnl_2023_share_pct'], 1, ',', ' ') }}%</td><td style="padding:.3rem;">{{ ($comparison['admission_status'] ?? '') === 'qualified_for_top10' ? 'Пройшов критерії Top 10' : (($comparison['admission_status'] ?? '') === 'below_pnl_floor' ? 'PnL нижче 7-го місця' : 'Не пройшов перевірку') }}</td></tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                        @php
                            $upgradeStatusLabels = ['not_selected_2025' => 'Не пройшов 2025', 'retrospective_2026_fail' => 'Не пройшов 2026', 'retrospective_2026_pass' => 'Пройшов 2026'];
                            $upgradeReasonLabels = ['nonpositive_2026_net_pnl' => 'Збиток у 2026'];
                        @endphp
                        <details style="margin-top:.4rem;font-size:.78rem;">
                            <summary style="cursor:pointer;color:#93c5fd;">Результат кожного кандидата</summary>
                            <div style="overflow-x:auto;margin-top:.4rem;">
                                <table style="width:100%;border-collapse:collapse;">
                                    <thead><tr><th style="text-align:left;padding:.3rem;">Кандидат</th><th style="text-align:left;padding:.3rem;">Походження</th><th style="text-align:left;padding:.3rem;">Результат</th><th style="text-align:left;padding:.3rem;">Причина</th></tr></thead>
                                    <tbody>
                                        @foreach (($v5Upgrade['candidates'] ?? []) as $candidate)
                                            <tr style="border-top:1px solid #334155;"><td style="padding:.3rem;font-family:monospace;">{{ $candidate['candidate_id'] ?? '—' }}</td><td style="padding:.3rem;">{{ ($candidate['cohort'] ?? '') === 'v4' ? 'v4' : 'нова логіка' }}</td><td style="padding:.3rem;">{{ $upgradeStatusLabels[$candidate['status'] ?? ''] ?? ($candidate['status'] ?? '—') }}</td><td style="padding:.3rem;">{{ $upgradeReasonLabels[$candidate['reason'] ?? ''] ?? ($candidate['reason'] ?? '—') }}</td></tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </details>
                    @endif
                    @if (($v5Upgrade['status'] ?? null) === 'needs_attention')
                        <div style="margin-top:.3rem;color:#fca5a5;">Потрібна увага: {{ $v5Upgrade['reason'] ?? 'невідома причина' }}</div>
                    @endif
                    @if (! empty($v5Upgrade['review_analysis']))
                        <details style="margin-top:.4rem;font-size:.78rem;"><summary style="cursor:pointer;color:#93c5fd;">Разовий аналіз результату</summary><pre style="white-space:pre-wrap;margin-top:.4rem;">{{ $v5Upgrade['review_analysis'] }}</pre></details>
                    @endif
                    @if (! empty($v5Upgrade['review_analysis_error']))
                        <div style="margin-top:.3rem;color:#fca5a5;">Разовий аналіз: {{ $v5Upgrade['review_analysis_error'] }}</div>
                    @endif
                </section>
            @endif
            @endforeach
            @php
                $year2024Recheck = $data['year2024_recheck'] ?? null;
                $year2024Ab = $data['year2024_ab'] ?? null;
                $year2024Search = $data['year2024_search'] ?? null;
            @endphp
            @if (is_array($data['year2024_optuna_control'] ?? null))
                @php
                    $warmControl = $data['year2024_optuna_control'];
                    $warmStatus = ['running' => 'Працює', 'review_pending' => 'Завершено · результат готовий', 'needs_attention' => 'Помилка'][$warmControl['status'] ?? ''] ?? '—';
                @endphp
                <section style="padding:1rem;border:1px solid #2563eb;border-radius:.5rem;background:#020617;color:#cbd5e1;">
                    <div style="font-size:1.1rem;font-weight:700;color:#f8fafc;">Перевірка поліпшення Optuna · 2024–2025</div>
                    <div style="margin-top:.3rem;font-size:.8rem;color:#94a3b8;">3 кандидати × 40 проб. Перевірені повні параметри як старт пошуку. Порівняння з попередніми 40 пробами тих самих кандидатів.</div>
                    <div style="margin-top:.4rem;">Стан: {{ $warmStatus }}
                        @if (isset($warmControl['optuna_completed'], $warmControl['optuna_target']))
                            · {{ $warmControl['optuna_completed'] }} / {{ $warmControl['optuna_target'] }} проб
                        @endif
                    </div>
                    @if (! empty($warmControl['error']))
                        <div style="color:#fca5a5;margin-top:.4rem;">Помилка: {{ $warmControl['error'] }}</div>
                    @endif
                    @if (! empty($warmControl['comparison']))
                        @php $warmComparison = $warmControl['comparison']; @endphp
                        <div style="margin-top:.5rem;">Допустимі проби: {{ $warmComparison['old_eligible'] }} → {{ $warmComparison['new_eligible'] }} із 120.</div>
                        <div style="margin-top:.3rem;font-size:.8rem;">Пошук після стартових конфігурацій: {{ $warmComparison['old_search_eligible'] }}/{{ $warmComparison['old_search_trials'] }} → {{ $warmComparison['new_search_eligible'] }}/{{ $warmComparison['new_search_trials'] }} допустимих проб.</div>
                        <div style="overflow-x:auto;margin-top:.5rem;">
                            <table style="width:100%;font-size:.8rem;border-collapse:collapse;">
                                <thead><tr style="text-align:left;"><th>Кандидат</th><th>Було / стало допустимих</th><th>Відомі стартові / пошук</th><th>Слабший рік, USDT: було → стало</th></tr></thead>
                                <tbody>
                                    @foreach ($warmComparison['candidates'] as $warmCandidate)
                                        <tr style="border-top:1px solid #334155;">
                                            <td style="padding:.4rem;">{{ $warmCandidate['candidate_id'] }}</td>
                                            <td>{{ $warmCandidate['old_eligible'] }} / {{ $warmCandidate['new_eligible'] }}</td>
                                            <td>{{ $warmCandidate['seed_eligible'] }} / {{ $warmCandidate['new_search_eligible'] }}</td>
                                            <td>{{ isset($warmCandidate['old_weaker_pnl']) ? number_format($warmCandidate['old_weaker_pnl'], 2, ',', ' ') : '—' }} → {{ isset($warmCandidate['new_weaker_pnl']) ? number_format($warmCandidate['new_weaker_pnl'], 2, ',', ' ') : '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div style="margin-top:.4rem;font-size:.8rem;">{{ $warmComparison['decision'] === 'improved_on_control' ? 'На контрольній партії ефективність зросла; потрібен аналіз перед наступним циклом.' : 'Поліпшення потребує аналізу за наведеними результатами.' }}</div>
                    @endif
                </section>
            @endif

            @if (is_array($year2024Recheck))
                <section style="padding:1rem;border:1px solid #0f766e;border-radius:.5rem;background:#020617;color:#cbd5e1;">
                    <div style="font-size:1.1rem;font-weight:700;color:#f8fafc;">Порівняння 2024–2026 · без торгівлі у 2023</div>
                    <div style="margin-top:.3rem;font-size:.8rem;color:#94a3b8;">Окремий replay тих самих параметрів · 10 із чинного Top 10 + 3 претенденти · 2023 лише для прогріву · рейтинг v4 не змінено</div>
                    <div style="margin-top:.3rem;font-size:.8rem;">Стан: {{ $year2024Recheck['status'] ?? '—' }}
                        @if (isset($year2024Recheck['progress']))
                            · replay {{ $year2024Recheck['progress']['completed'] ?? 0 }} / {{ $year2024Recheck['progress']['total'] ?? 13 }}
                        @endif
                    </div>
                    @if (! empty($year2024Recheck['error']))
                        <div style="margin-top:.3rem;color:#fca5a5;">Помилка: {{ $year2024Recheck['error'] }}</div>
                    @endif
                    @if (! empty($year2024Recheck['candidates']))
                        <div style="overflow-x:auto;margin-top:.6rem;">
                            <table style="width:100%;min-width:850px;border-collapse:collapse;font-size:.78rem;">
                                <thead style="color:#94a3b8;text-align:left;"><tr style="border-bottom:1px solid #334155;">
                                    <th style="padding:.45rem;">#</th><th>Кандидат</th><th>2024</th><th>2025</th><th>2026 · до 19.09</th><th>PnL 2024–2026</th><th>Попереднє місце</th><th>Статус</th>
                                </tr></thead>
                                <tbody>
                                    @foreach ($year2024Recheck['candidates'] as $candidate)
                                        <tr style="border-bottom:1px solid #263449;">
                                            <td style="padding:.45rem;color:#5eead4;">{{ $candidate['new_rank'] ?? '—' }}</td>
                                            <td style="font-family:monospace;">{{ $candidate['candidate_id'] ?? '—' }}{{ ($candidate['cohort'] ?? '') === 'challenger' ? ' · претендент' : '' }}</td>
                                            @foreach (['2024', '2025', '2026'] as $year)
                                                <td style="text-align:right;padding:.45rem;">{{ number_format((float) ($candidate['annual'][$year]['net_pnl_usdt'] ?? 0), 2, ',', ' ') }}</td>
                                            @endforeach
                                            <td style="text-align:right;padding:.45rem;font-weight:600;">{{ number_format((float) ($candidate['net_pnl_usdt'] ?? 0), 2, ',', ' ') }}</td>
                                            <td style="text-align:center;">{{ $candidate['old_rank'] ?? '—' }}</td>
                                            <td style="padding:.45rem;">{{ ($candidate['status'] ?? '') === 'eligible' ? 'Пройшов' : ($candidate['reason'] ?? $candidate['status'] ?? '—') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>
            @endif
            @if (is_array($year2024Ab))
                <section style="padding:.8rem 1rem;border:1px solid #0f766e;border-radius:.5rem;background:#020617;color:#cbd5e1;">
                    <div style="font-weight:700;color:#f8fafc;">Порівняння пошуку: v4 проти оптимізації 2024–2025</div>
                    <div style="margin-top:.3rem;font-size:.8rem;">Стан: {{ $year2024Ab['status'] ?? '—' }} · етап {{ $year2024Ab['stage'] ?? '—' }}{{ ! empty($year2024Ab['arm']) ? ' · схема '.$year2024Ab['arm'] : '' }}
                        @if (isset($year2024Ab['completed'], $year2024Ab['total']))
                            · {{ $year2024Ab['completed'] }} / {{ $year2024Ab['total'] }}
                        @endif
                        @if (isset($year2024Ab['optuna_completed'], $year2024Ab['optuna_target']))
                            · Optuna {{ $year2024Ab['optuna_completed'] }} / {{ $year2024Ab['optuna_target'] }}
                        @endif
                        @if (isset($year2024Ab['baseline_completed']))
                            · базовий replay {{ $year2024Ab['baseline_completed'] }}
                        @endif
                    </div>
                    @if (! empty($year2024Ab['error']))
                        <div style="margin-top:.3rem;color:#fca5a5;">Помилка: {{ $year2024Ab['error'] }}</div>
                    @endif
                    @if (! empty($year2024Ab['counts']))
                        <div style="margin-top:.4rem;font-size:.8rem;">
                            @foreach (['old' => 'v4 · 2023–2024', 'new' => 'нова · 2024–2025'] as $arm => $label)
                                <div>{{ $label }}: базовий replay {{ $year2024Ab['counts'][$arm]['baseline_replayed'] ?? 0 }} · Optuna {{ $year2024Ab['counts'][$arm]['optuna_trials'] ?? 0 }} проб · пройшли 2026: {{ $year2024Ab['counts'][$arm]['pass_2026'] ?? 0 }}</div>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endif
            @php
                $top10 = $data['top10'] ?? null;
                $periodDays = 0;
                if (is_array($top10) && isset($top10['period']['start_inclusive'], $top10['period']['end_exclusive'])) {
                    $periodStart = new \DateTimeImmutable($top10['period']['start_inclusive']);
                    $periodEnd = new \DateTimeImmutable($top10['period']['end_exclusive']);
                    $periodDays = (int) $periodStart->diff($periodEnd)->days;
                }
            @endphp
            @if (is_array($top10))
            <section style="padding:1rem;border:1px solid #1d4ed8;border-radius:.5rem;background:#020617;color:#cbd5e1;">
                <div style="display:flex;justify-content:space-between;align-items:baseline;gap:.75rem;flex-wrap:wrap;">
                    <div style="font-size:1.1rem;font-weight:700;color:#f8fafc;">Історичний Top 10 · SOL Long / Bybit · 2023–2026</div>
                    @if (is_array($top10) && ($top10['candidate_count'] ?? 0) > 0)
                        <div style="font-size:.8rem;color:#94a3b8;">{{ $top10['candidate_count'] }} кандидатів · рейтинг за PnL за весь період</div>
                    @endif
                </div>
                @if (is_array($top10) && ($top10['candidate_count'] ?? 0) > 0)
                    <div style="font-size:.78rem;color:#94a3b8;margin-top:.35rem;">
                        Перевірки 2025 і 2026 пройдено · безперервний replay 01.01.2023–19.09.2026 · USDT
                    </div>
                    <div style="overflow-x:auto;margin-top:.75rem;">
                        <table style="width:100%;min-width:940px;border-collapse:collapse;font-size:.78rem;">
                            <thead style="color:#94a3b8;text-align:left;">
                                <tr style="border-bottom:1px solid #334155;">
                                    <th style="padding:.55rem .45rem;width:2.5rem;">#</th>
                                    <th style="padding:.55rem .45rem;min-width:15rem;">Кандидат / логіка</th>
                                    <th style="padding:.55rem .45rem;white-space:nowrap;">TP / SL</th>
                                    @foreach (['2023', '2024', '2025', '2026'] as $year)
                                        <th style="padding:.55rem .45rem;white-space:nowrap;">{{ $year }}{{ $year === '2026' ? ' · до 19.09' : '' }}</th>
                                    @endforeach
                                    <th style="padding:.55rem .45rem;white-space:nowrap;">PnL · весь період</th>
                                    <th style="padding:.55rem .45rem;white-space:nowrap;">Макс. просадка за весь період, USDT</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($top10['candidates'] as $candidate)
                                    @php
                                        $parameters = $candidate['effective_parameters'] ?? [];
                                        $fullPeriod = $candidate['full_period'] ?? [];
                                        $fullPeriodTrades = (int) ($fullPeriod['completed_trades'] ?? 0);
                                        $averageTradesPerDay = $periodDays > 0 ? $fullPeriodTrades / $periodDays : null;
                                        $annual = $candidate['annual'] ?? [];
                                        $candidateId = $candidate['candidate_id'] ?? '—';
                                        $candidateCycle = (int) ($candidate['cycle'] ?? 0);
                                        $sourceCycle = null;
                                        $sourceCycleCandidate = null;
                                        foreach ($cycles as $cycleData) {
                                            if ((int) ($cycleData['cycle'] ?? 0) !== $candidateCycle) {
                                                continue;
                                            }
                                            $sourceCycle = $cycleData;
                                            foreach (($cycleData['candidates'] ?? []) as $cycleCandidate) {
                                                if (($cycleCandidate['id'] ?? null) === $candidateId) {
                                                    $sourceCycleCandidate = $cycleCandidate;
                                                    break;
                                                }
                                            }
                                            break;
                                        }
                                        $monthlyDiagnostics = $sourceCycleCandidate['monthly'] ?? [];
                                        $monthlyWarnings = [];
                                        foreach (($sourceCycle['analysis']['positive_pnl_dependent_on_best_month'] ?? []) as $warning) {
                                            if (($warning['candidate_id'] ?? null) === $candidateId) {
                                                $monthlyWarnings[$warning['period'] ?? ''] = $warning;
                                            }
                                        }
                                        $eventLabels = [
                                            'volatility_jump' => 'Стрибок волатильності',
                                            'basis_discount' => 'Знижка базису',
                                        ];
                                        $confirmLabels = [
                                            'volume_recovery' => 'відновлення обсягу',
                                            'prior_high_break' => 'пробій попереднього максимуму',
                                            'basis_narrowing' => 'звуження базису',
                                            'close_recovery' => 'відновлення ціни закриття',
                                        ];
                                        $detailLabels = [
                                            'regime' => 'Режим',
                                            'invalidate' => 'Інвалідація сигналу',
                                            'exit' => 'Вихід',
                                            'htf' => 'Фільтр старшого таймфрейму',
                                        ];
                                        $parameterLabels = [
                                            'basis_z_min' => 'Мінімальний z-score базису',
                                            'efficiency_max' => 'Максимум ефективності',
                                            'vol_ratio_min' => 'Мінімальне співвідношення волатильності',
                                            'flow_min' => 'Мінімальний потік',
                                            'volume_ratio_min' => 'Мінімальне співвідношення обсягу',
                                            'trend_return_min' => 'Мінімальна дохідність тренду',
                                            'wait_bars' => 'Очікування, свічок 15 хв',
                                            'time_stop_bars' => 'Часовий вихід, свічок 15 хв',
                                            'trail_atr_multiple' => 'Множник ATR для трейлінгу',
                                            'htf_ema_span' => 'Період EMA старшого таймфрейму',
                                        ];
                                        $event = $candidate['event'] ?? '';
                                        $confirm = $candidate['confirm'] ?? '';
                                        $logic = ($eventLabels[$event] ?? str_replace('_', ' ', $event))
                                            .' → '.($confirmLabels[$confirm] ?? str_replace('_', ' ', $confirm));
                                        $shortId = strlen($candidateId) > 19 ? substr($candidateId, 0, 15).'…' : $candidateId;
                                    @endphp
                                    <tr style="border-bottom:1px solid #263449;vertical-align:top;">
                                        <td style="padding:.7rem .45rem;color:#93c5fd;font-size:.92rem;font-weight:700;">{{ $candidate['rank'] ?? '—' }}</td>
                                        <td style="padding:.7rem .45rem;">
                                            <div style="font-weight:600;color:#e2e8f0;">{{ $logic }}</div>
                                            <span style="display:block;margin-top:.2rem;color:#94a3b8;font: .7rem ui-monospace,SFMono-Regular,monospace;">{{ isset($candidate['continuous_batch']) ? 'Партія '.$candidate['continuous_batch'] : 'Коло '.($candidate['cycle'] ?? '—') }} · {{ $shortId }}</span>
                                            <details style="margin-top:.4rem;color:#94a3b8;">
                                                <summary style="cursor:pointer;color:#93c5fd;font-size:.72rem;">Правила й параметри</summary>
                                                <div style="margin-top:.35rem;font-size:.72rem;line-height:1.6;">
                                                    <div>ID: {{ $candidateId }}</div>
                                                    <div>Підтвердження: {{ $confirmLabels[$confirm] ?? str_replace('_', ' ', $confirm) }}</div>
                                                    @foreach ($detailLabels as $key => $label)
                                                        <div>{{ $label }}: {{ $candidate[$key] ?? '—' }}</div>
                                                    @endforeach
                                                    @foreach ($parameterLabels as $key => $label)
                                                        @if (array_key_exists($key, $parameters))
                                                            <div>{{ $label }}: {{ is_float($parameters[$key]) ? number_format((float)$parameters[$key], 3) : $parameters[$key] }}</div>
                                                        @endif
                                                    @endforeach
                                                    <div>Угод за весь період: {{ $fullPeriod['completed_trades'] ?? '—' }}</div>

                                                </div>
                                            </details>
                                        </td>
                                        <td style="padding:.7rem .45rem;color:#fde68a;white-space:nowrap;font-variant-numeric:tabular-nums;">TP +{{ number_format((float)($parameters['tp_pct'] ?? 0), 2) }}%<br>SL −{{ number_format((float)($parameters['sl_pct'] ?? 0), 2) }}%</td>
                                        @foreach (['2023', '2024', '2025', '2026'] as $year)
                                            @php $yearMetrics = $annual[$year] ?? null; @endphp
                                            <td style="padding:.7rem .45rem;white-space:nowrap;font-variant-numeric:tabular-nums;">
                                                @if (is_array($yearMetrics))
                                                    <span style="color:{{ (float)($yearMetrics['net_pnl_usdt'] ?? 0) >= 0 ? '#86efac' : '#fca5a5' }};">{{ number_format((float)($yearMetrics['net_pnl_usdt'] ?? 0), 2) }} USDT</span>
                                                    <small style="display:block;color:#94a3b8;font-size:.68rem;">{{ $yearMetrics['exits'] ?? '—' }} угод</small>
                                                @else
                                                    <span style="color:#64748b;">—</span>
                                                @endif
                                            </td>
                                        @endforeach
                                        <td style="padding:.7rem .45rem;color:{{ (float)($fullPeriod['net_pnl_usdt'] ?? 0) >= 0 ? '#86efac' : '#fca5a5' }};font-weight:700;white-space:nowrap;font-variant-numeric:tabular-nums;">
                                            {{ number_format((float)($fullPeriod['net_pnl_usdt'] ?? 0), 2) }} USDT
                                            <small style="display:block;margin-top:.18rem;color:#94a3b8;font-size:.68rem;font-weight:400;">{{ $fullPeriodTrades }} угод@if ($averageTradesPerDay !== null) ({{ number_format($averageTradesPerDay, 2, ',', ' ') }}/день)@endif</small>
                                        </td>
                                        <td style="padding:.7rem .45rem;color:#e2e8f0;font-weight:600;white-space:nowrap;font-variant-numeric:tabular-nums;">
                                            <div>{{ number_format((float)($fullPeriod['max_drawdown_usdt'] ?? 0), 2) }}</div>
                                            @if (count($monthlyDiagnostics) > 0 || count($monthlyWarnings) > 0)
                                                <details style="max-width:14rem;margin-top:.35rem;white-space:normal;font-weight:400;">
                                                    <summary style="cursor:pointer;color:{{ count($monthlyWarnings) > 0 ? '#fbbf24' : '#93c5fd' }};font-size:.68rem;line-height:1.35;">
                                                        @if (count($monthlyWarnings) > 0)
                                                            ⚠ Попередження ({{ count($monthlyWarnings) }})
                                                        @else
                                                            Помісячна діагностика
                                                        @endif
                                                    </summary>
                                                    <div style="margin-top:.35rem;color:#94a3b8;font-size:.68rem;line-height:1.5;">
                                                        <div style="color:#e2e8f0;">Цикл {{ $candidateCycle }}</div>
                                                        @foreach (['selection_2025' => '2025', 'transfer_2026' => '2026'] as $periodKey => $periodLabel)
                                                            @php $periodDiagnostic = $monthlyDiagnostics[$periodKey] ?? null; @endphp
                                                            @if (is_array($periodDiagnostic))
                                                                @php
                                                                    $bestMonthPnl = (float) ($periodDiagnostic['best_month_pnl_usdt'] ?? 0);
                                                                    $pnlWithoutBestMonth = (float) ($periodDiagnostic['pnl_excluding_best_month_usdt'] ?? 0);
                                                                    $periodPnl = $bestMonthPnl + $pnlWithoutBestMonth;
                                                                    $bestMonthShare = $periodPnl > 0 ? 100 * $bestMonthPnl / $periodPnl : null;
                                                                    $hasMonthlyWarning = isset($monthlyWarnings[$periodKey]);
                                                                @endphp
                                                                <div style="margin-top:.2rem;color:{{ $hasMonthlyWarning ? '#fbbf24' : '#cbd5e1' }};">
                                                                    {{ $periodLabel }} · {{ $periodDiagnostic['best_month'] ?? '—' }}: {{ number_format($bestMonthPnl, 2) }} USDT
                                                                    · без нього {{ number_format($pnlWithoutBestMonth, 2) }} USDT
                                                                    @if ($bestMonthShare !== null)
                                                                        · {{ number_format($bestMonthShare, 1) }}%
                                                                    @endif
                                                                    @if ($hasMonthlyWarning)
                                                                        <strong> · без найкращого місяця PnL від’ємний</strong>
                                                                    @endif
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                        @if (count($monthlyDiagnostics) === 0)
                                                            @foreach ($monthlyWarnings as $warning)
                                                                <div style="margin-top:.2rem;color:#fbbf24;">{{ $warning['period'] ?? 'Період' }} · без найкращого місяця {{ number_format((float) ($warning['pnl_excluding_best_month_usdt'] ?? 0), 2) }} USDT</div>
                                                            @endforeach
                                                        @endif
                                                    </div>
                                                </details>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div style="font-size:.82rem;color:#94a3b8;margin-top:.5rem;">Рейтинг за безперервним replay ще не сформовано.</div>
                @endif
            </section>
            @endif
            @if (is_array($v5))
                @php
                    $v5StatusLabels = ['pre_screen_complete' => 'попередній відбір завершено', 'running' => 'виконується', 'technical_pass' => 'технічна перевірка пройдена', 'needs_attention' => 'потрібна увага', 'review_pending' => ! empty($v5['stage5_review']) ? 'аналіз готовий, очікує рішення' : 'очікує аналізу'];
                    $v5StageLabels = ['stage2' => 'попередній відбір', 'v4_parity' => 'звірка з v4', 'new_logic_replays' => 'контрольні replay', 'ready_for_stage4_baseline' => 'готово до повного базового replay', 'prescreen' => 'попередній відбір', 'baseline' => 'повний базовий replay', 'optuna' => 'Optuna', 'selection_2025' => 'вибір на 2025', 'transfer_2026' => 'перенос на 2026', 'cycle_complete' => 'цикл завершено', 'review_pending' => ! empty($v5['stage5_review']) ? 'предметний аналіз завершено' : 'потрібен аналіз результатів'];
                    $v5FamilyLabels = ['compression_break_retest' => 'Стиснення → пробій → ретест', 'trend_flip_first_pullback' => 'Зміна тренду → перший відкат'];
                    $v5ExitLabels = ['bracket' => 'TP/SL', 'time_stop' => 'часовий вихід', 'atr_trail' => 'ATR трейлінг', 'regime_exit' => 'вихід за трендом'];
                    $v5TradeLabels = ['eligible' => 'допущено', 'risk_rejected' => 'відхилено за ризиком', 'insufficient_trades' => 'замало угод'];
                    $v5ReasonLabels = ['entry_capital_exhausted' => 'нестача вільного капіталу', 'isolated_liquidation_possible' => 'ризик ліквідації', 'tp_and_sl_same_1m_bar' => 'TP і SL в одній хвилині', 'nonpositive_2026_net_pnl' => 'неприбутковий 2026', 'nonpositive_2025_net_pnl' => 'неприбутковий 2025', 'fewer_than_51_closed_trades_in_2026' => 'менше 51 угоди у 2026'];
                    $v5FinalLabels = ['not_selected_2025' => 'не пройшов 2025', 'retrospective_2026_pass' => 'пройшов 2026', 'retrospective_2026_fail' => 'не пройшов 2026'];
                @endphp
                <section style="padding:1rem;border:1px solid #2563eb;border-radius:.5rem;background:#020617;overflow-x:auto;">
                    <div style="font-size:1.1rem;font-weight:700;color:#f8fafc;">v5 · нові логіки входу</div>
                    <div style="font-size:.8rem;color:#94a3b8;margin-top:.3rem;">Ретроспективне дослідження · {{ $v5['generated_structures'] ?? 0 }} структур · {{ $v5['prescreen_selected'] ?? 0 }} після попереднього відбору.</div>
                    <div style="font-size:.88rem;margin-top:.55rem;">Етап: <strong>{{ $v5StageLabels[$v5['stage'] ?? ''] ?? ($v5['stage'] ?? '—') }}</strong> · технічний стан: <strong style="color:{{ ($v5['status'] ?? '') === 'needs_attention' ? '#fca5a5' : '#86efac' }};">{{ $v5StatusLabels[$v5['status'] ?? ''] ?? ($v5['status'] ?? '—') }}</strong>
                        @if (isset($v5['v4_parity'])) · паритет із v4: {{ $v5['v4_parity'] }} @endif
                    </div>
                    @if (isset($v5['optuna_completed_trials']))
                        <div style="margin-top:.35rem;font-size:.8rem;color:#93c5fd;">Цикл {{ $v5['cycle'] ?? 1 }}/3 · базовий replay: {{ $v5['baseline_replayed'] ?? 0 }}/{{ $v5['baseline_total'] ?? ($v5['prescreen_selected'] ?? 0) }} · Optuna: {{ $v5['optuna_completed_trials'] }}/{{ $v5['optuna_target_trials'] ?? 0 }} проб.</div>
                    @elseif (($v5['status'] ?? '') === 'running')
                        <div style="margin-top:.35rem;font-size:.8rem;color:#93c5fd;">Технічні replay: {{ $v5['technical_replays_completed'] ?? 0 }}/{{ $v5['technical_replays_total'] ?? 0 }}</div>
                    @endif
                    @if (! empty($v5['completed_cycles']))
                        <table style="width:100%;min-width:720px;margin-top:.65rem;border-collapse:collapse;font-size:.78rem;">
                            <thead style="color:#94a3b8;text-align:left;"><tr style="border-bottom:1px solid #334155;"><th style="padding:.4rem;">Цикл</th><th style="padding:.4rem;">Нові структури</th><th style="padding:.4rem;">Повний replay</th><th style="padding:.4rem;">Архів порівняно</th><th style="padding:.4rem;">Optuna</th><th style="padding:.4rem;">Прохід 2026</th></tr></thead>
                            <tbody>
                                @foreach ($v5['completed_cycles'] as $item)
                                    <tr style="border-bottom:1px solid #263449;"><td style="padding:.4rem;">{{ $item['cycle'] }}</td><td style="padding:.4rem;">{{ $item['new_structures'] }} → {{ $item['prescreen_selected'] }}</td><td style="padding:.4rem;">{{ $item['baseline_replayed'] }}</td><td style="padding:.4rem;">{{ $item['cached_compared'] }}</td><td style="padding:.4rem;">{{ $item['admitted'] }} × 40</td><td style="padding:.4rem;">{{ $item['retrospective_2026_pass'] }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                    @if (! empty($v5['stage5_review']['cycles']))
                        <div style="font-size:.88rem;font-weight:700;color:#e2e8f0;margin-top:.85rem;">Предметний аналіз трьох циклів</div>
                        <div style="font-size:.77rem;color:#94a3b8;margin-top:.25rem;">Ретроспективне дослідження. Воронка показує нові структури окремо від кандидатів, перенесених з архіву.</div>
                        <table style="width:100%;min-width:800px;margin-top:.45rem;border-collapse:collapse;font-size:.78rem;">
                            <thead style="color:#94a3b8;text-align:left;"><tr style="border-bottom:1px solid #334155;"><th style="padding:.4rem;">Цикл</th><th style="padding:.4rem;">Дублі / мало сигналів</th><th style="padding:.4rem;">Replay нових</th><th style="padding:.4rem;">Ризик / мало угод</th><th style="padding:.4rem;">Придатні нові</th><th style="padding:.4rem;">Архів у рейтингу</th><th style="padding:.4rem;">Додаткові при ліміті 4*</th></tr></thead>
                            <tbody>
                                @foreach ($v5['stage5_review']['cycles'] as $reviewCycle)
                                    @php $screen = $reviewCycle['prescreen_statuses']; $base = $reviewCycle['new_baseline_statuses']; $cached = $reviewCycle['cached_baseline_statuses']; @endphp
                                    <tr style="border-bottom:1px solid #263449;"><td style="padding:.4rem;">{{ $reviewCycle['cycle'] }}</td><td style="padding:.4rem;">{{ $screen['duplicate'] ?? 0 }} / {{ $screen['insufficient_raw_signals'] ?? 0 }}</td><td style="padding:.4rem;">{{ $screen['pre_screen_selected'] ?? 0 }}</td><td style="padding:.4rem;">{{ $base['risk_rejected'] ?? 0 }} / {{ $base['insufficient_trades'] ?? 0 }}</td><td style="padding:.4rem;">{{ ($base['eligible'] ?? 0) + ($base['eligible_not_admitted_capacity'] ?? 0) }}</td><td style="padding:.4rem;">{{ array_sum($cached) }}</td><td style="padding:.4rem;">{{ $reviewCycle['cap4_extra_count'] }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div style="font-size:.74rem;color:#94a3b8;margin-top:.3rem;">* Лише перерахунок допуску 2023–2024; Optuna та перевірки 2025–2026 для цих додаткових кандидатів не виконувались. Максимум перекриття входів між циклами: {{ number_format((float) ($v5['stage5_review']['max_cross_cycle_entry_overlap'] ?? 0), 3) }}.</div>
                        <table style="width:100%;min-width:960px;margin-top:.7rem;border-collapse:collapse;font-size:.77rem;">
                            <thead style="color:#94a3b8;text-align:left;"><tr style="border-bottom:1px solid #334155;"><th style="padding:.4rem;">Кандидат</th><th style="padding:.4rem;">Рік</th><th style="padding:.4rem;">Угоди</th><th style="padding:.4rem;">PnL / DD, USDT</th><th style="padding:.4rem;">Fees / funding, USDT</th><th style="padding:.4rem;">Найкращий місяць</th><th style="padding:.4rem;">PnL без нього</th></tr></thead>
                            <tbody>
                                @foreach ($v5['stage5_review']['cycles'] as $reviewCycle)
                                    @foreach ($reviewCycle['selected_2025'] as $selected)
                                        @foreach (['2025', '2026'] as $period)
                                            @php $numbers = $selected['periods'][$period]; @endphp
                                            <tr style="border-bottom:1px solid #263449;"><td style="padding:.4rem;">{{ $reviewCycle['cycle'] }} · {{ $selected['candidate_id'] }}</td><td style="padding:.4rem;">{{ $period }}</td><td style="padding:.4rem;">{{ $numbers['closed_trades'] }}</td><td style="padding:.4rem;">{{ number_format((float) $numbers['pnl_usdt'], 2) }} / {{ number_format((float) $numbers['dd_usdt'], 2) }}</td><td style="padding:.4rem;">{{ number_format((float) $numbers['fees_usdt'], 2) }} / {{ number_format((float) $numbers['funding_net_usdt'], 2) }}</td><td style="padding:.4rem;">{{ $numbers['best_month'] }}: {{ number_format((float) $numbers['best_month_pnl_usdt'], 2) }}</td><td style="padding:.4rem;">{{ number_format((float) $numbers['pnl_excluding_best_month_usdt'], 2) }}</td></tr>
                                        @endforeach
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                        <div style="font-size:.76rem;color:#94a3b8;margin-top:.45rem;">Порівняння бюджетів: v5 — 192 нові структури, 110 базових replay, 480 Optuna-проб, 1 формальний прохід 2026; v4 — {{ $v5['stage5_review']['v4_comparator']['new_structures'] }} нові структури, {{ $v5['stage5_review']['v4_comparator']['new_baseline_replays'] }} replay, {{ $v5['stage5_review']['v4_comparator']['optuna_trials'] }} проб, {{ $v5['stage5_review']['v4_comparator']['retrospective_2026_passes_including_cached'] }} проходи разом з архівними. Це різні пошукові бюджети.</div>
                        @if (! empty($v5['stage5_review']['balanced_optuna_review_pending']))
                            <div style="font-size:.76rem;color:#fbbf24;margin-top:.4rem;">Баланс Optuna між 2023 і 2024 роками проаналізовано; правило наступного policy очікує рішення.</div>
                        @endif
                    @endif
                    @if (! empty($v5['finals']))
                        <table style="width:100%;min-width:720px;margin-top:.65rem;border-collapse:collapse;font-size:.78rem;">
                            <thead style="color:#94a3b8;text-align:left;"><tr style="border-bottom:1px solid #334155;"><th style="padding:.4rem;">Цикл / кандидат</th><th style="padding:.4rem;">Перенос 2026</th><th style="padding:.4rem;">Угоди</th><th style="padding:.4rem;">PnL / DD, USDT</th></tr></thead>
                            <tbody>
                                @foreach ($v5['finals'] as $cycleFinal)
                                    @foreach ($cycleFinal['candidates'] as $item)
                                        <tr style="border-bottom:1px solid #263449;"><td style="padding:.4rem;">{{ $cycleFinal['cycle'] }} · {{ $item['candidate_id'] }}</td><td style="padding:.4rem;color:{{ ($item['status'] ?? '') === 'retrospective_2026_pass' ? '#86efac' : '#fbbf24' }};">{{ $v5FinalLabels[$item['status'] ?? ''] ?? ($item['status'] ?? '—') }}<br><span style="color:#94a3b8;">{{ $v5ReasonLabels[$item['reason'] ?? ''] ?? ($item['reason'] ?? '') }}</span></td><td style="padding:.4rem;">{{ $item['completed_trades'] ?? '—' }}</td><td style="padding:.4rem;">@if (isset($item['net_pnl_usdt'])) {{ number_format((float) $item['net_pnl_usdt'], 2) }} / {{ number_format((float) $item['max_dd_usdt'], 2) }} @else — @endif</td></tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                    @if (($v5['status'] ?? '') === 'needs_attention')
                        <div style="margin-top:.35rem;font-size:.82rem;color:#fca5a5;">Причина: {{ $v5['reason'] ?? 'невідома' }}</div>
                    @endif
                    @if (! empty($v5['technical_replays']))
                        <div style="font-size:.8rem;color:#94a3b8;margin-top:.55rem;">Контрольні replay перевіряють виконання й облік. Вони не є підсумком повного відбору.</div>
                        <table style="width:100%;min-width:720px;margin-top:.45rem;border-collapse:collapse;font-size:.78rem;">
                            <thead style="color:#94a3b8;text-align:left;"><tr style="border-bottom:1px solid #334155;">
                                <th style="padding:.4rem;">Логіка / вихід</th><th style="padding:.4rem;">Технічний аудит</th><th style="padding:.4rem;">Торговий відбір</th><th style="padding:.4rem;">Угоди 2023 / 2024</th><th style="padding:.4rem;">PnL / DD, USDT</th>
                            </tr></thead>
                            <tbody>
                                @foreach ($v5['technical_replays'] as $item)
                                    <tr style="border-bottom:1px solid #263449;">
                                        <td style="padding:.5rem;">{{ $v5FamilyLabels[$item['family'] ?? ''] ?? ($item['family'] ?? '—') }}<br><span style="color:#94a3b8;">{{ $v5ExitLabels[$item['exit'] ?? ''] ?? ($item['exit'] ?? '—') }}</span></td>
                                        <td style="padding:.5rem;color:#86efac;">{{ $item['technical_status'] ?? '—' }}</td>
                                        <td style="padding:.5rem;color:{{ ($item['trading_status'] ?? '') === 'eligible' ? '#86efac' : '#fbbf24' }};">{{ $v5TradeLabels[$item['trading_status'] ?? ''] ?? ($item['trading_status'] ?? '—') }}<br><span style="color:#94a3b8;">{{ $v5ReasonLabels[$item['trading_reason'] ?? ''] ?? ($item['trading_reason'] ?? '') }}</span></td>
                                        <td style="padding:.5rem;">{{ $item['annual']['2023']['exits'] ?? 0 }} / {{ $item['annual']['2024']['exits'] ?? 0 }}</td>
                                        <td style="padding:.5rem;white-space:nowrap;">{{ number_format((float) ($item['pnl_usdt'] ?? 0), 2) }} / {{ number_format((float) ($item['max_dd_usdt'] ?? 0), 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </section>
            @endif
            @if (is_array($v52))
                <section style="padding:1rem;border:1px solid #2563eb;border-radius:.5rem;background:#020617;overflow-x:auto;">
                    <div style="font-size:1.1rem;font-weight:700;color:#f8fafc;">v5.2 · останні 64 структури</div>
                    <div style="font-size:.8rem;color:#94a3b8;margin-top:.3rem;">Ретроспективне дослідження · окремий версійований цикл із перевіркою перекриття входів між циклами.</div>
                    <div style="font-size:.88rem;margin-top:.55rem;">Стан: <strong style="color:{{ ($v52['status'] ?? '') === 'needs_attention' ? '#fca5a5' : '#86efac' }};">{{ $v52['status'] ?? '—' }}</strong> · етап: {{ $v52['stage'] ?? '—' }} · структур: {{ $v52['generated_structures'] ?? 0 }} → {{ $v52['prescreen_selected'] ?? 0 }} · базовий replay: {{ $v52['baseline_replayed'] ?? 0 }}/{{ $v52['baseline_total'] ?? ($v52['prescreen_selected'] ?? 0) }} · Optuna: {{ $v52['optuna_completed_trials'] ?? 0 }}/{{ $v52['optuna_target_trials'] ?? 0 }}</div>
                    @if (isset($v52['new_overlap_prior_rejected']))
                        <div style="font-size:.78rem;color:#94a3b8;margin-top:.35rem;">Із 64 нових структур {{ $v52['new_overlap_prior_rejected'] }} повторюють входи попередніх циклів, {{ $v52['new_insufficient_raw_signals'] ?? 0 }} мають замало сигналів. Раніше перевірених кандидатів у рейтингу: {{ $v52['cached_compared'] ?? 0 }}; допущено з них: {{ $v52['cached_admitted'] ?? 0 }}.</div>
                    @endif
                    @if (($v52['status'] ?? '') === 'needs_attention')
                        <div style="font-size:.8rem;color:#fca5a5;margin-top:.4rem;">Причина: {{ $v52['reason'] ?? 'невідома' }}</div>
                    @endif
                    @if (is_array($v52['report'] ?? null))
                        @php $v52Report = $v52['report']; $v52Base = $v52Report['new_baseline_statuses']; $v52Cached = $v52Report['cached_baseline_statuses']; @endphp
                        <div style="font-size:.82rem;margin-top:.65rem;">Нових: {{ $v52Report['generated_new'] }} · відсів через перекриття з попереднім v5: {{ $v52Report['prior_entry_overlap_rejected'] }} · ризиковий відсів: {{ $v52Base['risk_rejected'] ?? 0 }} · придатних нових: {{ ($v52Base['eligible'] ?? 0) + ($v52Base['eligible_not_admitted_capacity'] ?? 0) }} · архівних у рейтингу: {{ array_sum($v52Cached) }} · Optuna: {{ $v52Report['optuna_trials'] }} проб.</div>
                        @if (! empty($v52Report['selection_2025_decisions']))
                            <div style="font-size:.82rem;font-weight:700;color:#e2e8f0;margin-top:.7rem;">Відбір 2025</div>
                            <table style="width:100%;min-width:820px;margin-top:.35rem;border-collapse:collapse;font-size:.77rem;">
                                <thead style="color:#94a3b8;text-align:left;"><tr style="border-bottom:1px solid #334155;"><th style="padding:.4rem;">Кандидат</th><th style="padding:.4rem;">Проба</th><th style="padding:.4rem;">Рішення</th><th style="padding:.4rem;">Угоди</th><th style="padding:.4rem;">PnL / DD, USDT</th></tr></thead>
                                <tbody>
                                    @foreach ($v52Report['selection_2025_decisions'] as $selection)
                                        @foreach ($selection['options'] as $option)
                                            <tr style="border-bottom:1px solid #263449;"><td style="padding:.4rem;">{{ $selection['candidate_id'] }}</td><td style="padding:.4rem;">{{ $option['trial'] }}</td><td style="padding:.4rem;">{{ $option['reason'] === 'fewer_than_70_closed_trades_in_2025' ? 'менше 70 угод' : ($option['reason'] === 'nonpositive_2025_net_pnl' ? 'неприбутковий 2025' : ($option['status'] ?? '—')) }}</td><td style="padding:.4rem;">{{ $option['completed_trades'] ?? '—' }}</td><td style="padding:.4rem;">{{ number_format((float) ($option['net_pnl_usdt'] ?? 0), 2) }} / {{ number_format((float) ($option['max_dd_usdt'] ?? 0), 2) }}</td></tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                        <table style="width:100%;min-width:900px;margin-top:.55rem;border-collapse:collapse;font-size:.77rem;">
                            <thead style="color:#94a3b8;text-align:left;"><tr style="border-bottom:1px solid #334155;"><th style="padding:.4rem;">Кандидат</th><th style="padding:.4rem;">Рік</th><th style="padding:.4rem;">Угоди</th><th style="padding:.4rem;">PnL / DD, USDT</th><th style="padding:.4rem;">Fees / funding, USDT</th><th style="padding:.4rem;">Найкращий місяць</th><th style="padding:.4rem;">PnL без нього</th></tr></thead>
                            <tbody>
                                @foreach ($v52Report['selected_2025'] as $selected)
                                    @foreach (['2025', '2026'] as $period)
                                        @php $numbers = $selected['periods'][$period]; @endphp
                                        <tr style="border-bottom:1px solid #263449;"><td style="padding:.4rem;">{{ $selected['candidate_id'] }}</td><td style="padding:.4rem;">{{ $period }}</td><td style="padding:.4rem;">{{ $numbers['closed_trades'] }}</td><td style="padding:.4rem;">{{ number_format((float) $numbers['pnl_usdt'], 2) }} / {{ number_format((float) $numbers['dd_usdt'], 2) }}</td><td style="padding:.4rem;">{{ number_format((float) $numbers['fees_usdt'], 2) }} / {{ number_format((float) $numbers['funding_net_usdt'], 2) }}</td><td style="padding:.4rem;">{{ $numbers['monthly']['best_month'] }}: {{ number_format((float) $numbers['monthly']['best_month_pnl_usdt'], 2) }}</td><td style="padding:.4rem;">{{ number_format((float) $numbers['monthly']['pnl_excluding_best_month_usdt'], 2) }}</td></tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                        @if (! empty($v52Report['balanced_optuna_review_pending']))
                            <div style="font-size:.76rem;color:#fbbf24;margin-top:.4rem;">Баланс Optuna між 2023 і 2024 роками проаналізовано; правило наступного policy очікує рішення.</div>
                        @endif
                    @endif
                </section>
            @endif
            @if (is_array($v53))
                <section style="padding:1rem;border:1px solid #2563eb;border-radius:.5rem;background:#020617;overflow-x:auto;">
                    <div style="font-size:1.1rem;font-weight:700;color:#f8fafc;">v5.3 · Open interest Bybit</div>
                    <div style="font-size:.8rem;color:#94a3b8;margin-top:.3rem;">Ретроспективне дослідження · SOLUSDT · перевірка за 2023–2025 роки.</div>
                    @if (($v53['status'] ?? '') === 'needs_attention')
                        <div style="margin-top:.55rem;color:#fca5a5;">Потрібна увага: {{ $v53['reason'] ?? 'джерело результату недоступне' }}</div>
                    @else
                        <div style="font-size:.85rem;margin-top:.55rem;">Optuna: {{ $v53['completed_trials'] }} проб, {{ $v53['eligible_trials'] }} допущено за 2023–2024 · результат 2025: жодного проходу · перенос на 2026: не відбувся.</div>
                        <table style="width:100%;min-width:750px;margin-top:.6rem;border-collapse:collapse;font-size:.78rem;">
                            <thead style="color:#94a3b8;text-align:left;"><tr style="border-bottom:1px solid #334155;"><th style="padding:.45rem;">Проба</th><th style="padding:.45rem;">PnL 2023–2024, USDT</th><th style="padding:.45rem;">PnL 2025, USDT</th><th style="padding:.45rem;">Угоди 2025</th><th style="padding:.45rem;">Рішення</th></tr></thead>
                            <tbody>
                                @foreach (($v53['trials'] ?? []) as $trial)
                                    <tr style="border-bottom:1px solid #263449;">
                                        <td style="padding:.45rem;">{{ $trial['trial'] }}</td>
                                        <td style="padding:.45rem;">{{ number_format((float) $trial['development_pnl_usdt'], 2) }}</td>
                                        <td style="padding:.45rem;">{{ number_format((float) $trial['pnl_2025_usdt'], 2) }}</td>
                                        <td style="padding:.45rem;">{{ $trial['trades_2025'] }}</td>
                                        <td style="padding:.45rem;">{{ ($trial['reason_2025'] ?? '') === 'entry_capital_exhausted' ? 'вичерпано капітал' : (($trial['reason_2025'] ?? '') === 'nonpositive_2025_net_pnl' ? 'від’ємний PnL' : ($trial['status_2025'] ?? '—')) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </section>
            @endif
            <div style="padding:1rem;border:1px solid #1f2937;border-radius:.5rem;background:#020617;">
                @if ($current || $supervisor)
            <div style="font-size:1.15rem;font-weight:700;color:#f8fafc;">Генератор стратегій · SOL Long / Bybit</div>
                <div style="font-size:.8rem;color:#94a3b8;margin-top:.3rem;">Результати 2023–2026 — ретроспективне дослідження. Історичний стан обраного циклу.</div>
                @if ($supervisor)
                    <div style="margin-top:.65rem;font-size:.88rem;">
                        Автономні цикли: <strong>{{ $supervisor['status'] ?? '—' }}</strong>
                        · наступний / поточний №{{ $supervisor['cycle'] ?? '—' }}
                        · останній перевірений №{{ $supervisor['last_verified_cycle'] ?? '—' }}
                    </div>
                    @if (in_array(($supervisor['status'] ?? ''), ['needs_attention', 'review_pending', 'exhausted'], true))
                        <div style="margin-top:.35rem;color:#fbbf24;word-break:break-word;">
                            Причина: {{ $supervisor['reason'] ?? 'невідома' }}
                            @if (! empty($supervisor['error']))
                                · {{ $supervisor['error'] }}
                            @endif
                        </div>
                    @endif
                @endif
                @if ($current)
                    <div style="margin-top:.75rem;font-size:.88rem;">
                        Цикл {{ $current['cycle'] ?? '—' }} · стан <strong>{{ $current['status'] ?? '—' }}</strong>
                        · етап {{ $current['stage'] ?? '—' }} · допущено {{ $current['admitted'] ?? 0 }}/7
                    </div>
                    @if (isset($current['progress']))
                        <div style="margin-top:.25rem;font-size:.82rem;color:#93c5fd;">
                            {{ $current['progress']['stage'] === 'baseline' ? 'Базовий replay' : 'Optuna' }}:
                            {{ $current['progress']['completed'] ?? 0 }}/{{ $current['progress']['total'] ?? 0 }}
                            @if (($current['progress']['phase'] ?? '') === 'selected_cache_parity')
                                · звірка архівних фіналістів
                            @endif
                        </div>
                    @endif
                    @if (isset($current['last_activity_utc']))
                        <div style="margin-top:.25rem;font-size:.82rem;color:#94a3b8;">
                            Останній запис результату: {{ $current['last_activity_utc'] }} UTC
                            · {{ intdiv((int)($current['idle_seconds'] ?? 0), 60) }} хв тому
                        </div>
                    @endif
                @else
                    <div style="margin-top:.75rem;color:#94a3b8;">Новий цикл ще не запускався.</div>
                @endif
            </div>

            @endif
            @if ($review)
                @php $reviewCounts = $review['counts']; $shadow = $review['shadow']; @endphp
                <section style="padding:1rem;border:1px solid #475569;border-radius:.5rem;background:#020617;">
                    <div style="font-weight:700;color:#f8fafc;">Контрольний огляд циклів 011–013</div>
                    <div style="font-size:.82rem;color:#94a3b8;margin-top:.3rem;">Ретроспективне дослідження · контрольна точка після циклу 013.</div>
                    <div style="font-size:.88rem;margin-top:.65rem;">
                        {{ $reviewCounts['new_structures'] ?? 0 }} нових структур ·
                        {{ $reviewCounts['new_baseline_replays'] ?? 0 }} повних replay ·
                        {{ $reviewCounts['new_baseline_eligible'] ?? 0 }} пройшли 2023–2024 ·
                        {{ $reviewCounts['passed_2026'] ?? 0 }}/{{ $reviewCounts['tested_2026'] ?? 0 }} пройшли 2026.
                    </div>
                    <div style="font-size:.82rem;color:#fbbf24;margin-top:.35rem;">
                        У {{ $reviewCounts['best_month_august_2026'] ?? 0 }} із {{ $reviewCounts['tested_2026'] ?? 0 }} найкращий місяць — серпень 2026.
                    </div>
                    <div style="font-size:.82rem;margin-top:.35rem;">
                        Контрольна зміна ранжування Optuna: {{ $shadow['passed_2026'] ?? 0 }} проходження 2026 проти {{ $reviewCounts['passed_2026'] ?? 0 }} за чинним відбором.
                    </div>
                    <div style="font-size:.88rem;margin-top:.55rem;color:#e2e8f0;">{{ $review['conclusion'] }}</div>
                </section>
            @endif

            @foreach ($cycles as $cycle)
                @php
                    $counts = $cycle['counts'] ?? [];
                    $candidates = $cycle['candidates'] ?? [];
                @endphp
                <section style="padding:1rem;border:1px solid #1f2937;border-radius:.5rem;background:#020617;overflow-x:auto;">
                    <div style="display:flex;justify-content:space-between;gap:.5rem;flex-wrap:wrap;">
                        <div style="font-weight:700;color:#f8fafc;">Цикл {{ $cycle['cycle'] ?? '—' }} · {{ ($cycle['status'] ?? '') === 'complete' ? '7 допущених' : 'менше 7 допущених' }}</div>
                        <div style="font-size:.8rem;color:#94a3b8;">{{ $cycle['completed_at_utc'] ?? '—' }} UTC</div>
                    </div>
                    <div style="font-size:.82rem;margin:.6rem 0 1rem;">
                        Структур {{ $counts['structures'] ?? 0 }}
                        @if (isset($counts['new_structures']))
                            (нових {{ $counts['new_structures'] }}, архівних {{ $counts['cached_structures'] ?? 0 }})
                            · порівняно архівних {{ $counts['cached_compared'] ?? 0 }}
                        @endif
                        · у черзі {{ $counts['queued'] ?? 0 }}
                        · повних replay {{ $counts['baseline_replays'] ?? 0 }} · допущено {{ $counts['admitted'] ?? 0 }}/7
                    </div>
                    @if (isset($cycle['analysis']))
                        @php $analysis = $cycle['analysis']; $a = $analysis['counts'] ?? []; @endphp
                        <div style="font-size:.82rem;margin:.6rem 0 1rem;padding:.7rem;border:1px solid #334155;border-radius:.4rem;">
                            Автоматична діагностика: Optuna {{ $a['eligible_optuna_trials'] ?? 0 }}/{{ $a['completed_optuna_trials'] ?? 0 }} придатних проб
                            · 2025: {{ $a['selected_2025'] ?? 0 }}/{{ $a['admitted'] ?? 0 }} обрано після {{ $a['tested_2025_options'] ?? 0 }} перевірок
                            · 2026: {{ $a['passed_2026'] ?? 0 }}/{{ $a['tested_2026'] ?? 0 }} пройшло.
                            @if (($a['selected_2025'] ?? 0) === 0)
                                <div style="color:#fca5a5;margin-top:.3rem;">Жоден кандидат не пройшов відбір 2025, тому replay 2026 не запускався.</div>
                            @endif
                            @foreach (($analysis['positive_pnl_dependent_on_best_month'] ?? []) as $flag)
                                <div style="color:#fbbf24;margin-top:.3rem;">{{ $flag['candidate_id'] }} · {{ $flag['period'] }}: без найкращого місяця {{ number_format((float)$flag['pnl_excluding_best_month_usdt'], 2) }} USDT.</div>
                            @endforeach
                            <details style="margin-top:.3rem;"><summary style="cursor:pointer;color:#93c5fd;">Причини відсіву 2025</summary>
                                @foreach (($analysis['selection_2025_reason_counts'] ?? []) as $reason => $count)
                                    <div>{{ $reason }}: {{ $count }}</div>
                                @endforeach
                            </details>
                        </div>
                    @endif
                    @if (count($candidates) === 0)
                        <div style="color:#fca5a5;">Жоден кандидат не пройшов базовий replay.</div>
                    @else
                        <table style="width:100%;min-width:65rem;border-collapse:collapse;font-size:.78rem;">
                            <thead><tr style="text-align:left;color:#94a3b8;border-bottom:1px solid #334155;">
                                <th style="padding:.45rem;">Кандидат / логіка</th><th>2023</th><th>2024</th><th>Optuna</th><th>2025</th><th>2026</th><th>Ефективні параметри</th>
                            </tr></thead>
                            <tbody>
                                @foreach ($candidates as $candidate)
                                    @php
                                        $base = $candidate['baseline'] ?? [];
                                        $annual = $base['annual'] ?? [];
                                        $y25 = $candidate['selection_2025'] ?? [];
                                        $y26 = $candidate['transfer_2026'] ?? [];
                                    @endphp
                                    <tr style="border-bottom:1px solid #1e293b;vertical-align:top;">
                                        <td style="padding:.5rem;max-width:16rem;">
                                            <div style="font-family:ui-monospace,monospace;color:#e2e8f0;">{{ $candidate['id'] ?? '—' }}</div>
                                            @if (isset($candidate['origin']))
                                                <div style="color:#93c5fd;">{{ $candidate['origin'] === 'cached' ? 'Архів циклу '.($candidate['cached_from_cycle'] ?? '—') : 'Нова логіка' }}</div>
                                            @endif
                                            <div>{{ $candidate['event'] ?? '—' }} · {{ $candidate['regime'] ?? '—' }} · {{ $candidate['htf'] ?? '—' }} · {{ $candidate['exit'] ?? '—' }}</div>
                                        </td>
                                        @foreach (['2023', '2024'] as $year)
                                            <td style="padding:.5rem;">{{ $annual[$year]['exits'] ?? '—' }} угод<br>{{ isset($annual[$year]['net_pnl_usdt']) ? number_format((float)$annual[$year]['net_pnl_usdt'], 2) : '—' }} USDT</td>
                                        @endforeach
                                        <td style="padding:.5rem;">{{ $candidate['optuna']['eligible_trials'] ?? 0 }}/{{ $candidate['optuna']['completed_trials'] ?? 40 }} проб</td>
                                        <td style="padding:.5rem;">{{ $y25['status'] ?? '—' }}<br>{{ $y25['annual']['2025']['exits'] ?? '—' }} угод · {{ isset($y25['net_pnl_usdt']) ? number_format((float)$y25['net_pnl_usdt'], 2) : '—' }} USDT
                                            @if (isset($candidate['monthly']['selection_2025']))
                                                @php
                                                    $m25 = $candidate['monthly']['selection_2025'];
                                                    $total25 = (float)$m25['best_month_pnl_usdt'] + (float)$m25['pnl_excluding_best_month_usdt'];
                                                @endphp
                                                <br>Найкращий місяць {{ $m25['best_month'] }}: {{ number_format((float)$m25['best_month_pnl_usdt'], 2) }} USDT
                                                @if ($total25 > 0)
                                                    · {{ number_format(100 * (float)$m25['best_month_pnl_usdt'] / $total25, 1) }}% підсумку
                                                @endif
                                                <br>Без нього: {{ number_format((float)$m25['pnl_excluding_best_month_usdt'], 2) }} USDT
                                                <details><summary style="cursor:pointer;color:#93c5fd;">Місяці 2025</summary>
                                                    @foreach (($m25['months'] ?? []) as $month)
                                                        <div>{{ $month['month'] }}: {{ number_format((float)$month['net_pnl_usdt'], 2) }} USDT · {{ $month['closed_trades'] }} угод</div>
                                                    @endforeach
                                                </details>
                                            @endif
                                        </td>
                                        <td style="padding:.5rem;">{{ $y26['status'] ?? '—' }}<br>{{ $y26['annual']['2026']['exits'] ?? '—' }} угод · {{ isset($y26['net_pnl_usdt']) ? number_format((float)$y26['net_pnl_usdt'], 2) : '—' }} USDT<br>{{ $y26['reason'] ?? '' }}
                                            @if (isset($candidate['monthly']['transfer_2026']))
                                                @php
                                                    $m26 = $candidate['monthly']['transfer_2026'];
                                                    $total26 = (float)$m26['best_month_pnl_usdt'] + (float)$m26['pnl_excluding_best_month_usdt'];
                                                @endphp
                                                <br>Найкращий місяць {{ $m26['best_month'] }}: {{ number_format((float)$m26['best_month_pnl_usdt'], 2) }} USDT
                                                @if ($total26 > 0)
                                                    · {{ number_format(100 * (float)$m26['best_month_pnl_usdt'] / $total26, 1) }}% підсумку
                                                @endif
                                                <br>Без нього: {{ number_format((float)$m26['pnl_excluding_best_month_usdt'], 2) }} USDT
                                                <details><summary style="cursor:pointer;color:#93c5fd;">Місяці 2026</summary>
                                                    @foreach (($m26['months'] ?? []) as $month)
                                                        <div>{{ $month['month'] }}: {{ number_format((float)$month['net_pnl_usdt'], 2) }} USDT · {{ $month['closed_trades'] }} угод</div>
                                                    @endforeach
                                                </details>
                                            @endif
                                        </td>
                                        <td style="padding:.5rem;font-family:ui-monospace,monospace;word-break:break-word;max-width:19rem;">{{ isset($candidate['effective_parameters']) ? json_encode($candidate['effective_parameters'], JSON_UNESCAPED_UNICODE) : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                    <details style="margin-top:1rem;">
                        <summary style="cursor:pointer;color:#93c5fd;">Попередній відбір і причини відсіву ({{ count($cycle['attempts'] ?? []) }} структур)</summary>
                        <table style="width:100%;min-width:42rem;border-collapse:collapse;font-size:.78rem;margin-top:.6rem;">
                            <thead><tr style="text-align:left;color:#94a3b8;"><th>Кандидат</th><th>Подія</th><th>Сигнали 2023/2024</th><th>Рішення</th><th>Причина</th></tr></thead>
                            <tbody>
                                @foreach (($cycle['attempts'] ?? []) as $attempt)
                                    <tr style="border-top:1px solid #1e293b;"><td style="padding:.35rem;font-family:ui-monospace,monospace;">{{ $attempt['id'] ?? '—' }}</td><td>{{ $attempt['event'] ?? '—' }}</td><td>{{ $attempt['signal_counts']['2023'] ?? '—' }}/{{ $attempt['signal_counts']['2024'] ?? '—' }}</td><td>{{ $attempt['status'] ?? '—' }}</td><td>{{ $attempt['reason'] ?? '—' }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </details>
                    <details style="margin-top:.6rem;">
                        <summary style="cursor:pointer;color:#93c5fd;">Повні базові replay та причини відсіву</summary>
                        <table style="width:100%;min-width:42rem;border-collapse:collapse;font-size:.78rem;margin-top:.6rem;">
                            <thead><tr style="text-align:left;color:#94a3b8;"><th>Кандидат</th><th>Стан</th><th>Причина</th><th>PnL, USDT</th></tr></thead>
                            <tbody>
                                @foreach (($cycle['baseline_decisions'] ?? []) as $decision)
                                    <tr style="border-top:1px solid #1e293b;"><td style="padding:.35rem;font-family:ui-monospace,monospace;">{{ $decision['id'] ?? '—' }}</td><td>{{ $decision['status'] ?? '—' }}</td><td>{{ $decision['reason'] ?? '—' }}</td><td>{{ isset($decision['net_pnl_usdt']) ? number_format((float)$decision['net_pnl_usdt'], 2) : '—' }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </details>
                </section>
            @endforeach
