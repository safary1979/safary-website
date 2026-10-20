@php
    $lastBatch = $job['last_batch'] ?? [];
    $lastCounts = $lastBatch['counts']['new'] ?? [];
    $totals = $continuous['counts'] ?? [];
@endphp
<div style="margin-top:.75rem;font-size:.82rem;line-height:1.8;">
    @if (! empty($job['last_completed_run']))
        <div style="color:#94a3b8;font-family:monospace;">{{ $job['last_completed_run'] }}</div>
    @endif
    <div>Базові replay: {{ $lastCounts['baseline_replayed'] ?? 0 }} · перенесені: {{ $lastCounts['baseline_reused'] ?? 0 }} · просторів Optuna: {{ $lastCounts['optuna_candidates'] ?? 0 }}.</div>
    <div>Optuna: {{ $lastCounts['optuna_trials'] ?? 0 }} пропозицій · нові replay: {{ $lastCounts['optuna_completed_replays'] ?? 0 }} · повторно використані результати: {{ $lastCounts['optuna_reused_observations'] ?? 0 }} · ранні відмови: {{ $lastCounts['optuna_early_rejections'] ?? 0 }}.</div>
    <div>Пройшли 2026: {{ $lastCounts['pass_2026'] ?? 0 }} · повний replay: {{ $lastCounts['qualified_full'] ?? 0 }}.</div>
    @if (! empty($totals))
        <div style="margin-top:.5rem;color:#94a3b8;">За всі завершені партії: {{ $totals['optuna_trials'] ?? 0 }} пропозицій · {{ $totals['optuna_completed_replays'] ?? 0 }} нових replay · {{ $totals['qualified_full'] ?? 0 }} повних проходів.</div>
    @endif
</div>
@if (! empty($lastBatch['arms']['new']['candidates']))
    <div style="overflow-x:auto;margin-top:.75rem;">
        <table style="width:100%;min-width:760px;border-collapse:collapse;font-size:.78rem;">
            <thead style="text-align:left;color:#94a3b8;"><tr><th style="padding:.45rem;">Кандидат / проба</th><th>2026, USDT</th><th>Угоди 2026</th><th>Повний replay, USDT</th><th>Результат</th></tr></thead>
            <tbody>
                @foreach ($lastBatch['arms']['new']['candidates'] as $finalist)
                    @php
                        $transfer = $finalist['transfer_2026'] ?? [];
                        $full = $finalist['full_2024'] ?? [];
                        $resultLabels = ['eligible' => 'Пройшов повну перевірку', 'no_train_finalist' => 'Немає навчального фіналіста', 'no_unprocessed_configuration' => 'Конфігурації вже перевірені', 'invalid_stop_budget_exhausted' => 'Вичерпано бюджет некоректних стоп-наказів', 'nonpositive_2026_net_pnl' => 'Непозитивний PnL 2026', 'fewer_than_51_closed_trades_in_2026' => 'Менше 51 угоди у 2026'];
                        $result = $full['reason'] ?? $transfer['reason'] ?? $full['status'] ?? $finalist['status'] ?? '—';
                    @endphp
                    <tr style="border-top:1px solid #334155;">
                        <td style="padding:.45rem;font-family:monospace;">{{ $finalist['candidate_id'] }} / {{ $finalist['selected_trial'] ?? '—' }}</td>
                        <td>{{ isset($transfer['net_pnl_usdt']) ? number_format((float) $transfer['net_pnl_usdt'], 2, ',', ' ') : '—' }}</td>
                        <td>{{ $transfer['completed_trades'] ?? $transfer['annual']['2026']['exits'] ?? '—' }}</td>
                        <td>{{ isset($full['net_pnl_usdt']) ? number_format((float) $full['net_pnl_usdt'], 2, ',', ' ') : '—' }}</td>
                        <td>{{ $resultLabels[$result] ?? $result }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
