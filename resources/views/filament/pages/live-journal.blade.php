<x-filament-panels::page>
    @php($analysis = $this->getAnalysis())
    @php($journal = $analysis['journal'] ?? [])
    @php($summary = $journal['summary'] ?? [])
    @php($actionable = $analysis['actionable'] ?? [])
    @php($bots = $analysis['bots'] ?? [])
    @php($healthRows = $analysis['candidate_health'] ?? [])
    @php($missed = $analysis['missed_opportunities'] ?? [])
    @php($exactSnapshots = $analysis['exact_decision_snapshots'] ?? [])
    @php($entryPreviewRows = $analysis['entry_preview_rows'] ?? [])
    @php($timeline = $analysis['timeline'] ?? [])
    @php($decisions = $analysis['decisions'] ?? [])
    @php($signals = $analysis['signals'] ?? [])
    @php($ml = is_array($journal['ml_shadow_view'] ?? null) ? $journal['ml_shadow_view'] : [])
    @php($mlDataset = is_array($journal['ml_dataset'] ?? null) ? $journal['ml_dataset'] : [])
    @php($mlDatasetCounts = is_array($mlDataset['counts'] ?? null) ? $mlDataset['counts'] : [])
    @php($liveJournalMl = is_array($journal['live_journal_ml_shadow'] ?? null) ? $journal['live_journal_ml_shadow'] : [])
    @php($liveJournalMlCounts = is_array($liveJournalMl['counts'] ?? null) ? $liveJournalMl['counts'] : [])
    @php($feeds = is_array($journal['feed_status'] ?? null) ? $journal['feed_status'] : [])
    @php($displayBotName = function ($slug, $side = null) {
        $slug = (string) ($slug ?? '');
        if (preg_match('/^([A-Z0-9]+)_USDT_USDT_(long|short)/', $slug, $m)) {
            return $m[1] . '_' . $m[2];
        }
        if (preg_match('/^([A-Z0-9]+)_USDT_(long|short)/', $slug, $m)) {
            return $m[1] . '_' . $m[2];
        }
        return $slug;
    })

    <div wire:poll.300s style="display:grid; gap:1rem;">
        @if (! ($journal['available'] ?? false))
            <div style="padding:1rem; border:1px solid #7f1d1d; border-radius:0.5rem; background:#1f0f12; color:#fecaca;">
                Live Journal недоступний: {{ $journal['state'] ?? 'missing' }} · {{ $journal['path'] ?? '' }}
            </div>
        @else
            <div style="padding:1rem; border:1px solid #1f2937; border-radius:0.5rem; background:#020617; color:#cbd5e1;">
                <div style="display:flex; justify-content:space-between; gap:1rem; align-items:flex-start; flex-wrap:wrap;">
                    <div>
                        <div style="font-size:0.8rem; color:#94a3b8;">read-only analysis journal</div>
                        <div style="font-size:1.1rem; font-weight:700; color:#f8fafc;">Live Journal</div>
                    </div>
                    <div style="font-size:0.82rem; color:#94a3b8;">{{ $journal['generated_at'] ?? '' }}</div>
                </div>

                <div style="margin-top:0.8rem; display:flex; gap:0.5rem; flex-wrap:wrap; align-items:center;">
                    @foreach (['all' => 'All', 'long' => 'Long', 'short' => 'Short'] as $value => $label)
                        <button wire:click="setSideFilter('{{ $value }}')" style="font-size:0.76rem; padding:0.34rem 0.7rem; border-radius:0.375rem; border:1px solid {{ $this->sideFilter === $value ? '#60a5fa' : '#334155' }}; background:{{ $this->sideFilter === $value ? '#1d4ed833' : '#0f172a' }}; color:{{ $this->sideFilter === $value ? '#bfdbfe' : '#94a3b8' }}; cursor:pointer;">{{ $label }}</button>
                    @endforeach

                    @foreach (['fresh' => 'Fresh', 'executed' => 'Executed', 'replay' => 'Replay', 'all' => 'All signals'] as $value => $label)
                        <button wire:click="setSignalStatusFilter('{{ $value }}')" style="font-size:0.76rem; padding:0.34rem 0.7rem; border-radius:0.375rem; border:1px solid {{ $this->signalStatusFilter === $value ? '#22c55e' : '#334155' }}; background:{{ $this->signalStatusFilter === $value ? '#16653433' : '#0f172a' }}; color:{{ $this->signalStatusFilter === $value ? '#bbf7d0' : '#94a3b8' }}; cursor:pointer;">{{ $label }}</button>
                    @endforeach

                    @foreach (['all' => 'Live state all', 'live_enabled' => 'In live', 'not_in_live_config' => 'Not live'] as $value => $label)
                        <button wire:click="setLiveStateFilter('{{ $value }}')" style="font-size:0.76rem; padding:0.34rem 0.7rem; border-radius:0.375rem; border:1px solid {{ $this->liveStateFilter === $value ? '#f59e0b' : '#334155' }}; background:{{ $this->liveStateFilter === $value ? '#92400e33' : '#0f172a' }}; color:{{ $this->liveStateFilter === $value ? '#fde68a' : '#94a3b8' }}; cursor:pointer;">{{ $label }}</button>
                    @endforeach

                    @foreach (['active' => 'Active bots', 'all' => 'All bots'] as $value => $label)
                        <button wire:click="setBotFilter('{{ $value }}')" style="font-size:0.76rem; padding:0.34rem 0.7rem; border-radius:0.375rem; border:1px solid {{ $this->botFilter === $value ? '#a78bfa' : '#334155' }}; background:{{ $this->botFilter === $value ? '#5b21b633' : '#0f172a' }}; color:{{ $this->botFilter === $value ? '#ddd6fe' : '#94a3b8' }}; cursor:pointer;">{{ $label }}</button>
                    @endforeach

                    <input
                        type="search"
                        wire:model.live.debounce.500ms="candidateSearch"
                        placeholder="candidate / component / profile"
                        style="min-width:18rem; flex:1; font-size:0.78rem; padding:0.4rem 0.65rem; border-radius:0.375rem; border:1px solid #334155; background:#0f172a; color:#e2e8f0;"
                    />
                </div>
            </div>

            <div style="display:grid; gap:0.7rem; grid-template-columns:repeat(auto-fit,minmax(10rem,1fr));">
                @foreach ([
                    ['label' => 'Fresh in live', 'value' => $actionable['fresh_live'] ?? 0, 'color' => '#86efac'],
                    ['label' => 'Would enter now', 'value' => $actionable['entry_would_allow'] ?? 0, 'color' => '#86efac'],
                    ['label' => 'Waiting fresh', 'value' => $actionable['entry_waiting_fresh'] ?? 0, 'color' => '#fde68a'],
                    ['label' => 'Not-live fresh', 'value' => $actionable['fresh_not_live'] ?? 0, 'color' => '#fde68a'],
                    ['label' => 'Silent live', 'value' => $actionable['silent_live_candidates'] ?? 0, 'color' => '#fca5a5'],
                    ['label' => 'Open positions', 'value' => $actionable['open_positions'] ?? 0, 'color' => '#bfdbfe'],
                    ['label' => 'Hedge overlap', 'value' => $actionable['open_hedge_overlaps'] ?? 0, 'color' => '#fde68a'],
                    ['label' => 'Exact snapshots', 'value' => $actionable['exact_snapshots'] ?? 0, 'color' => '#c4b5fd'],
                    ['label' => 'ML labeled', 'value' => ($actionable['ml_labeled'] ?? 0) . '/' . ($actionable['ml_min'] ?? 0), 'color' => '#e2e8f0'],
                ] as $tile)
                    <div style="border:1px solid #1f2937; border-radius:0.5rem; background:#020617; padding:0.8rem;">
                        <div style="font-size:0.72rem; color:#94a3b8;">{{ $tile['label'] }}</div>
                        <div style="font-size:1.25rem; font-weight:800; color:{{ $tile['color'] }};">{{ $tile['value'] }}</div>
                    </div>
                @endforeach
            </div>

            <div style="padding:1rem; border:1px solid #1f2937; border-radius:0.5rem; background:#020617; color:#cbd5e1;">
                <div style="display:flex; justify-content:space-between; gap:1rem; align-items:baseline; margin-bottom:0.65rem;">
                    <div style="font-size:0.9rem; font-weight:700; color:#f8fafc;">Entry Eligibility Now</div>
                    <div style="font-size:0.75rem; color:#94a3b8;">live bot preview · read-only</div>
                </div>
                @if (empty($entryPreviewRows))
                    <div style="font-size:0.85rem; color:#94a3b8;">Немає preview-рядків під поточні фільтри.</div>
                @else
                    <div style="overflow-x:auto;">
                        <table style="width:100%; border-collapse:collapse; font-size:0.8rem; min-width:86rem;">
                            <thead>
                                <tr style="color:#94a3b8; text-align:left; border-bottom:1px solid #1f2937;">
                                    <th style="padding:0.45rem;">bot</th>
                                    <th style="padding:0.45rem;">side</th>
                                    <th style="padding:0.45rem;">decision</th>
                                    <th style="padding:0.45rem;">profile</th>
                                    <th style="padding:0.45rem;">fresh</th>
                                    <th style="padding:0.45rem;">potential</th>
                                    <th style="padding:0.45rem;">live W/L net</th>
                                    <th style="padding:0.45rem;">last potential</th>
                                    <th style="padding:0.45rem;">portfolio</th>
                                    <th style="padding:0.45rem;">ML</th>
                                    <th style="padding:0.45rem;">feed</th>
                                    <th style="padding:0.45rem;">reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($entryPreviewRows as $row)
                                    @php($decisionColor = match($row['decision']) {'would_allow_entry' => '#86efac', 'position_open_monitoring' => '#bfdbfe', 'waiting_fresh_signal' => '#fde68a', default => '#fca5a5'})
                                    @php($feedAge = $row['feed_age_min'])
                                    <tr style="border-bottom:1px solid #111827;">
                                        <td title="{{ $row['bot_slug'] }}" style="padding:0.5rem; color:#e2e8f0; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;">{{ $displayBotName($row['bot_slug'] ?? '', $row['side'] ?? null) }}</td>
                                        <td style="padding:0.5rem; color:{{ $row['side'] === 'long' ? '#93c5fd' : '#fca5a5' }};">{{ $row['side'] }}</td>
                                        <td style="padding:0.5rem; color:{{ $decisionColor }}; font-weight:700;">{{ $row['decision'] }}</td>
                                        <td style="padding:0.5rem;">{{ $row['profile'] }}</td>
                                        <td style="padding:0.5rem;">{{ $row['fresh_signal_count'] }} / {{ $row['fresh_or_open'] }}</td>
                                        <td style="padding:0.5rem;">{{ $row['potential_entries'] }}{{ is_numeric($row['entries_per_day']) ? ' · ' . number_format((float) $row['entries_per_day'], 2) . '/d' : '' }}</td>
                                        <td style="padding:0.5rem;">{{ $row['live_wins'] }}/{{ $row['live_losses'] }} · <span style="color:{{ $row['live_pnl_usdt'] >= 0 ? '#86efac' : '#fca5a5' }}">{{ number_format((float) $row['live_pnl_usdt'], 4) }}</span></td>
                                        <td style="padding:0.5rem; color:#94a3b8;">{{ $row['last_potential_kyiv'] ?: 'n/a' }}</td>
                                        <td style="padding:0.5rem; color:{{ $row['portfolio_state'] === 'hedge_if_entry' ? '#fde68a' : '#94a3b8' }};">{{ $row['portfolio_state'] }}{{ ($row['opposite_open_count'] ?? 0) > 0 ? ' · opp=' . $row['opposite_open_count'] . ' · ' . $row['opposite_open_notional'] . ' USDT' : '' }}</td>
                                        <td style="padding:0.5rem;">{{ is_numeric($row['ml_score']) ? number_format((float) $row['ml_score'], 3) : 'n/a' }} · {{ $row['ml_decision'] ?: 'n/a' }}</td>
                                        <td style="padding:0.5rem; color:{{ is_numeric($feedAge) && $feedAge > 10 ? '#fca5a5' : '#94a3b8' }};">
                                            fresh={{ $row['feed_fresh_signals'] }}; age={{ is_numeric($feedAge) ? $feedAge . 'm' : 'n/a' }}
                                        </td>
                                        <td style="padding:0.5rem; color:#94a3b8;">{{ $row['reasons'][0] ?? '' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div style="display:grid; gap:1rem; grid-template-columns:repeat(auto-fit,minmax(22rem,1fr));">
                <div style="padding:1rem; border:1px solid #1f2937; border-radius:0.5rem; background:#020617; color:#cbd5e1;">
                    <div style="display:flex; justify-content:space-between; gap:1rem; margin-bottom:0.65rem;">
                        <div style="font-size:0.9rem; font-weight:700; color:#f8fafc;">Actionable Now</div>
                        <div style="font-size:0.75rem; color:#94a3b8;">live-test focus</div>
                    </div>
                    <div style="display:grid; gap:0.45rem; font-size:0.8rem; line-height:1.45;">
                        @if (($actionable['fresh_not_live'] ?? 0) > 0)
                            <div style="color:#fde68a;">Є fresh not-live сигнали: їх треба переглянути як кандидатів на додавання.</div>
                        @endif
                        @if (($actionable['silent_live_candidates'] ?? 0) > 0)
                            <div style="color:#fca5a5;">Є live-кандидати без live potential у 120h. Вони не дають нам live-test статистику.</div>
                        @endif
                        @if (($actionable['exact_snapshots'] ?? 0) === 0)
                            <div style="color:#c4b5fd;">Exact snapshots ще 0: чекаємо наступного реального входу після рестарту.</div>
                        @endif
                        @if (($actionable['ml_labeled'] ?? 0) < ($actionable['ml_min'] ?? 0))
                            <div style="color:#94a3b8;">ML поки observe: labeled {{ $actionable['ml_labeled'] ?? 0 }}/{{ $actionable['ml_min'] ?? 0 }}.</div>
                        @endif
                    </div>
                </div>

                <div style="padding:1rem; border:1px solid #1f2937; border-radius:0.5rem; background:#020617; color:#cbd5e1;">
                    <div style="font-size:0.9rem; font-weight:700; color:#f8fafc; margin-bottom:0.65rem;">Bots / ML / Feeds</div>
                    <div style="display:grid; gap:0.65rem;">
                        @foreach ($bots as $bot)
                            @php($tradeSummary = is_array($bot['trade_summary_loaded'] ?? null) ? $bot['trade_summary_loaded'] : [])
                            @php($portfolio = is_array($bot['position_portfolio_context'] ?? null) ? $bot['position_portfolio_context'] : [])
                            <div style="border:1px solid #1f2937; border-radius:0.45rem; background:#0f172a; padding:0.7rem;">
                                <div style="display:flex; justify-content:space-between; gap:0.75rem; align-items:baseline;">
                                    <div title="{{ $bot['slug'] ?? '' }}" style="font-weight:700; color:#e2e8f0;">{{ $displayBotName($bot['slug'] ?? '', $bot['side'] ?? null) }}</div>
                                    <div style="font-size:0.78rem; color:{{ ($bot['status'] ?? '') === 'running' ? '#86efac' : '#94a3b8' }};">{{ $bot['status'] ?? '' }}</div>
                                </div>
                                <div style="margin-top:0.35rem; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.76rem; color:#94a3b8; line-height:1.45;">
                                    side={{ $bot['side'] ?? '' }};
                                    position={{ ! empty($bot['has_position']) ? 'open' : 'flat' }};
                                    candidates={{ is_array($bot['live_candidates'] ?? null) ? count($bot['live_candidates']) : 0 }};
                                    trades={{ $tradeSummary['trades'] ?? 0 }} ({{ $tradeSummary['wins'] ?? 0 }}/{{ $tradeSummary['losses'] ?? 0 }});
                                    net={{ $tradeSummary['net_usdt'] ?? 0 }} USDT;
                                    portfolio={{ $portfolio['overlap_kind'] ?? 'solo' }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div style="margin-top:0.7rem; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.76rem; color:#94a3b8; line-height:1.55;">
                        <div>dataset labeled={{ $mlDatasetCounts['labeled_rows'] ?? 0 }} · exact={{ $mlDatasetCounts['exact_decision_rows'] ?? 0 }} · reconstructed={{ $mlDatasetCounts['reconstructed_trade_rows'] ?? 0 }} · excluded_old={{ $mlDatasetCounts['reconstructed_excluded_by_current_filter'] ?? 0 }}</div>
                        <div>journal scorer={{ $liveJournalMl['mode'] ?? ($liveJournalMl['state'] ?? 'missing') }} · candidates={{ $liveJournalMlCounts['candidates'] ?? 0 }} · labeled={{ $liveJournalMlCounts['labeled_rows'] ?? 0 }}/{{ $liveJournalMlCounts['min_labeled_for_model'] ?? 0 }} · overlap={{ $liveJournalMlCounts['portfolio_overlap_labeled_rows'] ?? 0 }}</div>
                        <div>short feed fresh={{ $feeds['short_feed']['fresh_signals'] ?? 'n/a' }} · long feed fresh={{ $feeds['long_feed']['fresh_signals'] ?? 'n/a' }}</div>
                    </div>
                </div>
            </div>

            <div style="padding:1rem; border:1px solid #1f2937; border-radius:0.5rem; background:#020617; color:#cbd5e1;">
                <div style="display:flex; justify-content:space-between; gap:1rem; align-items:baseline; margin-bottom:0.65rem;">
                    <div style="font-size:0.9rem; font-weight:700; color:#f8fafc;">Missed / Not-Live Opportunities</div>
                    <div style="font-size:0.75rem; color:#94a3b8;">fresh signals outside live config</div>
                </div>
                @if (empty($missed))
                    <div style="font-size:0.85rem; color:#94a3b8;">Немає fresh not-live сигналів під поточний side filter.</div>
                @else
                    <div style="overflow-x:auto;">
                        <table style="width:100%; border-collapse:collapse; font-size:0.8rem; min-width:58rem;">
                            <thead>
                                <tr style="color:#94a3b8; text-align:left; border-bottom:1px solid #1f2937;">
                                    <th style="padding:0.45rem;">side</th>
                                    <th style="padding:0.45rem;">candidate</th>
                                    <th style="padding:0.45rem;">component</th>
                                    <th style="padding:0.45rem;">profile</th>
                                    <th style="padding:0.45rem;">fresh</th>
                                    <th style="padding:0.45rem;">quality</th>
                                    <th style="padding:0.45rem;">latest</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($missed as $row)
                                    <tr style="border-bottom:1px solid #111827;">
                                        <td style="padding:0.5rem; color:{{ ($row['side'] ?? '') === 'long' ? '#93c5fd' : '#fca5a5' }};">{{ $row['side'] }}</td>
                                        <td style="padding:0.5rem; color:#fde68a; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;">{{ $row['candidate_id'] }}</td>
                                        <td style="padding:0.5rem;">{{ $row['component'] }}</td>
                                        <td style="padding:0.5rem;">{{ $row['profile'] }}</td>
                                        <td style="padding:0.5rem;">{{ $row['count'] }}</td>
                                        <td style="padding:0.5rem;">{{ is_numeric($row['quality_score']) ? number_format((float) $row['quality_score'], 3) : 'n/a' }}</td>
                                        <td style="padding:0.5rem; color:#94a3b8;">{{ $row['latest_signal'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div style="padding:1rem; border:1px solid #1f2937; border-radius:0.5rem; background:#020617; color:#cbd5e1;">
                <div style="font-size:0.9rem; font-weight:700; color:#f8fafc; margin-bottom:0.65rem;">Candidates Health</div>
                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse:collapse; font-size:0.8rem; min-width:82rem;">
                        <thead>
                            <tr style="color:#94a3b8; text-align:left; border-bottom:1px solid #1f2937;">
                                <th style="padding:0.45rem;">health</th>
                                <th style="padding:0.45rem;">side</th>
                                <th style="padding:0.45rem;">candidate</th>
                                <th style="padding:0.45rem;">profile</th>
                                <th style="padding:0.45rem;">24h potential</th>
                                <th style="padding:0.45rem;">24h W/L net</th>
                                <th style="padding:0.45rem;">120h potential</th>
                                <th style="padding:0.45rem;">120h W/L net</th>
                                <th style="padding:0.45rem;">ML</th>
                                <th style="padding:0.45rem;">last</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($healthRows as $row)
                                @php($healthColor = match($row['health']) {'active' => '#86efac', 'losing' => '#fca5a5', 'silent' => '#fde68a', default => '#94a3b8'})
                                <tr style="border-bottom:1px solid #111827; color:#cbd5e1;">
                                    <td style="padding:0.5rem; color:{{ $healthColor }}; font-weight:700;">{{ $row['health'] }}</td>
                                    <td style="padding:0.5rem; color:{{ $row['side'] === 'long' ? '#93c5fd' : '#fca5a5' }};">{{ $row['side'] }}</td>
                                    <td style="padding:0.5rem; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;">{{ $row['candidate_id'] }}</td>
                                    <td style="padding:0.5rem;">{{ $row['profile'] }}</td>
                                    <td style="padding:0.5rem;">{{ $row['signals_24h'] }} / {{ $row['potential_24h'] }}</td>
                                    <td style="padding:0.5rem;">{{ $row['trades_24h'] }} ({{ $row['wins_24h'] }}/{{ $row['losses_24h'] }}) · <span style="color:{{ $row['net_24h'] >= 0 ? '#86efac' : '#fca5a5' }}">{{ number_format((float) $row['net_24h'], 4) }}</span></td>
                                    <td style="padding:0.5rem;">{{ $row['signals_120h'] }} / {{ $row['potential_120h'] }}</td>
                                    <td style="padding:0.5rem;">{{ $row['trades_120h'] }} ({{ $row['wins_120h'] }}/{{ $row['losses_120h'] }}) · <span style="color:{{ $row['net_120h'] >= 0 ? '#86efac' : '#fca5a5' }}">{{ number_format((float) $row['net_120h'], 4) }}</span></td>
                                    <td style="padding:0.5rem;">{{ is_numeric($row['ml_score']) ? number_format((float) $row['ml_score'], 3) : 'n/a' }} · {{ $row['ml_decision'] }}</td>
                                    <td style="padding:0.5rem; color:#94a3b8;">{{ $row['last_signal'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="10" style="padding:0.75rem; color:#94a3b8;">Немає кандидатів під поточні фільтри.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div style="padding:1rem; border:1px solid #1f2937; border-radius:0.5rem; background:#020617; color:#cbd5e1;">
                <div style="display:flex; justify-content:space-between; gap:1rem; align-items:baseline; margin-bottom:0.65rem;">
                    <div style="font-size:0.9rem; font-weight:700; color:#f8fafc;">Unified Timeline</div>
                    <div style="font-size:0.75rem; color:#94a3b8;">signals · exact decisions · open/closed trades</div>
                </div>
                @if (empty($timeline))
                    <div style="font-size:0.85rem; color:#94a3b8;">Немає подій під поточні фільтри.</div>
                @else
                    <div style="display:grid; gap:0.35rem;">
                        @foreach ($timeline as $event)
                            @php($typeColor = match($event['type']) {'exact_decision' => '#c4b5fd', 'open_position' => '#bfdbfe', 'trade_closed' => '#86efac', 'trade_open' => '#bfdbfe', default => (($event['live_state'] ?? '') === 'live_enabled' ? '#86efac' : '#fde68a')})
                            @php($pnl = $event['pnl'])
                            <div style="display:grid; grid-template-columns:8.6rem 7.4rem minmax(18rem,1fr) 8rem; gap:0.75rem; align-items:baseline; border-bottom:1px solid #111827; padding:0.45rem 0; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.76rem; line-height:1.4;">
                                <div style="color:#94a3b8;">{{ $event['time'] }}</div>
                                <div style="color:{{ $typeColor }}; font-weight:700;">{{ $event['type'] }}</div>
                                <div>
                                    <span style="color:{{ ($event['side'] ?? '') === 'long' ? '#93c5fd' : '#fca5a5' }};">{{ $event['side'] }}</span>
                                    <span style="color:#e2e8f0;">{{ $event['candidate_id'] }}</span>
                                    <span style="color:#64748b;"> · {{ $event['profile'] }} · {{ $event['status'] }}</span>
                                    <div style="color:#64748b; margin-top:0.1rem;">{{ $event['detail'] }}</div>
                                </div>
                                <div style="text-align:right; color:{{ is_numeric($pnl) && $pnl < 0 ? '#fca5a5' : '#86efac' }};">
                                    {{ is_numeric($pnl) ? number_format((float) $pnl, 4) . ' USDT' : '' }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div style="display:grid; gap:1rem; grid-template-columns:repeat(auto-fit,minmax(26rem,1fr));">
                <div style="padding:1rem; border:1px solid #1f2937; border-radius:0.5rem; background:#020617; color:#cbd5e1;">
                    <div style="font-size:0.9rem; font-weight:700; color:#f8fafc; margin-bottom:0.65rem;">Trade Decisions</div>
                    @if (empty($decisions))
                        <div style="font-size:0.85rem; color:#94a3b8;">No decisions loaded.</div>
                    @else
                        <div style="display:grid; gap:0.55rem;">
                            @foreach ($decisions as $row)
                                @php($trade = is_array($row['trade'] ?? null) ? $row['trade'] : [])
                                @php($path = is_array($trade['path_context'] ?? null) ? $trade['path_context'] : [])
                                @php($scores = is_array($trade['scores'] ?? null) ? $trade['scores'] : [])
                                @php($portfolio = is_array($trade['portfolio_context'] ?? null) ? $trade['portfolio_context'] : [])
                                @php($pnl = (float)($trade['pnl_usdt'] ?? 0))
                                <div style="border:1px solid #1f2937; border-radius:0.45rem; background:#0f172a; padding:0.7rem;">
                                    <div style="display:flex; justify-content:space-between; gap:0.75rem; align-items:baseline;">
                                        <div style="font-weight:700; color:#e2e8f0;">#{{ $trade['trade_id'] ?? '' }} · {{ $trade['side'] ?? '' }} · {{ $trade['entry_candidate_id'] ?? 'unknown' }}</div>
                                        <div style="color:{{ $pnl >= 0 ? '#86efac' : '#fca5a5' }}; font-weight:700;">{{ number_format($pnl, 4) }} USDT</div>
                                    </div>
                                    <div style="margin-top:0.35rem; font-size:0.76rem; color:#94a3b8; line-height:1.45;">
                                        exit={{ $trade['exit_reason'] ?? 'open' }};
                                        MFE={{ $path['mfe_pct'] ?? 'n/a' }}%;
                                        MAE={{ $path['mae_pct'] ?? 'n/a' }}%;
                                        score={{ $scores['total_score'] ?? 'n/a' }};
                                        portfolio={{ $portfolio['overlap_kind'] ?? 'solo' }};
                                        {{ ! empty($row['reconstructed']) ? 'reconstructed' : 'exact' }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div style="padding:1rem; border:1px solid #1f2937; border-radius:0.5rem; background:#020617; color:#cbd5e1;">
                    <div style="font-size:0.9rem; font-weight:700; color:#f8fafc; margin-bottom:0.65rem;">Recent Signals</div>
                    @if (empty($signals))
                        <div style="font-size:0.85rem; color:#94a3b8;">No signals under current filters.</div>
                    @else
                        <div style="display:grid; gap:0.35rem;">
                            @foreach ($signals as $signal)
                                @php($isLive = ($signal['live_state'] ?? '') === 'live_enabled')
                                <div style="border-bottom:1px solid #111827; padding:0.35rem 0; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.76rem; line-height:1.45;">
                                    <span style="color:#e2e8f0;">{{ $signal['open_kyiv'] ?? '' }}</span>
                                    <span style="color:#64748b;"> | </span>
                                    <span style="color:{{ ($signal['side'] ?? '') === 'long' ? '#93c5fd' : '#fca5a5' }};">{{ $signal['side'] ?? '' }}</span>
                                    <span style="color:{{ $isLive ? '#86efac' : '#fde68a' }};">{{ $signal['candidate_id'] ?? '' }}</span>
                                    <span style="color:#64748b;"> | {{ $signal['status'] ?? '' }} · {{ $signal['live_state'] ?? '' }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div style="padding:1rem; border:1px solid #1f2937; border-radius:0.5rem; background:#020617; color:#cbd5e1;">
                <div style="font-size:0.9rem; font-weight:700; color:#f8fafc; margin-bottom:0.65rem;">Exact Decision Snapshots</div>
                @if (empty($exactSnapshots))
                    <div style="font-size:0.85rem; color:#94a3b8;">No exact snapshots yet. They start from the next real entry decision after the bot restart.</div>
                @else
                    <ol style="margin:0; padding-left:1.25rem; display:grid; gap:0.5rem; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.8rem; line-height:1.45;">
                        @foreach ($exactSnapshots as $row)
                            @php($bot = is_array($row['bot'] ?? null) ? $row['bot'] : [])
                            @php($selected = is_array($row['selected_signal'] ?? null) ? $row['selected_signal'] : [])
                            @php($pool = is_array($row['candidate_pool'] ?? null) ? $row['candidate_pool'] : [])
                            <li>
                                <span style="color:#e2e8f0;">{{ $row['created_at_utc'] ?? '' }}</span>
                                <span style="color:#64748b;"> | </span>
                                <span>{{ $bot['side'] ?? '' }} {{ $selected['candidate_id'] ?? ($selected['id'] ?? '') }}</span>
                                <span style="color:#64748b;"> | pool={{ count($pool) }}</span>
                                <span style="color:{{ ! empty($row['entry_allowed']) ? '#86efac' : '#fca5a5' }};"> | {{ ! empty($row['entry_allowed']) ? 'allowed' : 'blocked' }}</span>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>
        @endif
    </div>
</x-filament-panels::page>
