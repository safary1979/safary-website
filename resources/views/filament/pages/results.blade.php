<x-filament-panels::page>
    @php($codex = $this->getCodexResult())
    @php($controlTower = $this->getControlTower())
    @php($background = $this->getBackgroundHealth())
    @php($executionPriority = $this->getExecutionPrioritySupervisor())
    @php($completionInbox = $this->getBackgroundCompletionInbox())
    @php($signalEvents = $this->getSignalEventSummary())
    @php($eventDriven = $this->getEventDrivenSignalEngine())
    @php($candidateStats = $this->getLiveCandidateStats())
    @php($promotionScorecards = $this->getCandidatePromotionScorecards())
    @php($longRegistry = $this->getLongCandidateRegistry())
    @php($longRouterPrep = $this->getLongRouterPrep())
    @php($longShadowFeed = $this->getLongShadowFeed())
    @php($mlShadow = $this->getMlShadow())
    @php($mlDecisionLab = $this->getMlDecisionLab())
    @php($mlReplay = $this->getMlReplay())
    @php($shadowPreflight = $this->getShadowPreflight())
    @php($marketplaceTrace = $this->getMarketplaceDecisionTrace())
    @php($liveEntryPreview = $this->getLiveEntryDecisionPreview())
    @php($normalizedCandidates = $this->getNormalizedCandidateReport())
    @php($candidateSelection = $this->getCandidateSelectionReport())
    @php($liveReplacementWatch = $this->getLiveCandidateReplacementWatch())
    @php($decisionCore = $this->getDecisionCoreSummary())
    @php($candidateRevalidation = $this->getCandidateRevalidationReport())
    @php($candidateConsistency = $this->getCandidateConsistencyAudit())
    @php($liveExitWhatif = $this->getLiveExitWhatif())
    @php($exitRobustness = $this->getExitRobustnessDecision())
    @php($longLiveDraft = $this->getLongLiveDraftPreflight())
    @php($directLongProtectedFeed = $this->getDirectLongProtectedFeed())
    @php($currentRegimeLong = $this->getCurrentRegimeLongSearch())

    <div wire:poll.30s style="display: grid; gap: 1rem;">
        <div style="padding: 1rem; border: 1px solid #1f2937; border-radius: 0.5rem; background: #0f172a; color: #cbd5e1;">
            <div style="display: flex; justify-content: space-between; gap: 1rem; align-items: baseline;">
                <div>
                    <div style="font-size: 0.8rem; color: #94a3b8;">Codex зараз</div>
                    <div style="font-size: 1.05rem; font-weight: 700; color: #f8fafc;">{{ $codex['title'] }}</div>
                </div>
                <div style="font-size: 0.85rem; color: #94a3b8;">
                    {{ $codex['updated_at'] ?: 'ще не оновлювалось' }}
                </div>
            </div>

            <div style="margin-top: 0.75rem; color: #e2e8f0;">{{ $codex['status'] }}</div>

            <div style="margin-top: 0.75rem; height: 0.6rem; overflow: hidden; border-radius: 999px; background: #1e293b;">
                <div style="height: 100%; width: {{ $codex['progress'] }}%; background: #38bdf8;"></div>
            </div>
            <div style="margin-top: 0.35rem; font-size: 0.85rem; color: #94a3b8;">{{ $codex['progress'] }}%</div>
        </div>

        @if (! empty($controlTower['available']))
            @php($towerState = $controlTower['state'] ?? '')
            @php($towerCounts = $controlTower['counts'] ?? [])
            @php($towerPeriod = $controlTower['current_period'] ?? [])
            @php($towerColor = $towerState === 'blocked' ? '#fca5a5' : ($towerState === 'action_required' ? '#fde68a' : ($towerState === 'warning' ? '#bfdbfe' : '#86efac')))
            <div style="padding:1rem; border:1px solid {{ $towerState === 'blocked' ? '#7f1d1d' : ($towerState === 'action_required' ? '#92400e' : '#1f2937') }}; border-radius:0.5rem; background:#020617; color:#cbd5e1;">
                <div style="display:flex; justify-content:space-between; gap:1rem; align-items:baseline; margin-bottom:0.65rem;">
                    <div>
                        <div style="font-size:0.9rem; font-weight:700; color:#f8fafc;">Control Tower</div>
                        <div style="font-size:0.78rem; color:#94a3b8; margin-top:0.18rem;">автономний нагляд: ризики, модулі, safe-auto межі</div>
                    </div>
                    <div style="font-size:0.8rem; color:#94a3b8;">{{ $controlTower['generated_at'] }}</div>
                </div>
                <div style="display:flex; flex-wrap:wrap; gap:0.45rem; margin-bottom:0.65rem; font-size:0.78rem;">
                    <span style="padding:0.2rem 0.45rem; border-radius:0.35rem; background:#0f172a; color:{{ $towerColor }}; font-weight:700;">state={{ $towerState }}</span>
                    <span style="padding:0.2rem 0.45rem; border-radius:0.35rem; background:#0f172a; color:#94a3b8;">mode={{ $controlTower['mode'] ?? '' }}</span>
                    <span style="padding:0.2rem 0.45rem; border-radius:0.35rem; background:#0f172a; color:#94a3b8;">period={{ $towerPeriod['major_key'] ?? 'unknown' }}</span>
                    <span style="padding:0.2rem 0.45rem; border-radius:0.35rem; background:#0f172a; color:#fca5a5;">P0={{ $towerCounts['P0'] ?? 0 }}</span>
                    <span style="padding:0.2rem 0.45rem; border-radius:0.35rem; background:#0f172a; color:#fde68a;">P1={{ $towerCounts['P1'] ?? 0 }}</span>
                    <span style="padding:0.2rem 0.45rem; border-radius:0.35rem; background:#0f172a; color:#bfdbfe;">safe={{ $towerCounts['safe_auto_candidates'] ?? 0 }}</span>
                    <span style="padding:0.2rem 0.45rem; border-radius:0.35rem; background:#0f172a; color:#e2e8f0;">GO={{ $towerCounts['requires_user_go'] ?? 0 }}</span>
                </div>
                @if (! empty($controlTower['top_risks']))
                    <ol style="margin:0; padding-left:1.25rem; display:grid; gap:0.35rem; font-size:0.82rem; line-height:1.45;">
                        @foreach ($controlTower['top_risks'] as $risk)
                            <li>
                                <span style="color:#f8fafc;">{{ $risk['severity'] ?? '' }}</span>
                                <span style="color:#94a3b8;">{{ $risk['module'] ?? '' }}</span>
                                <span style="color:#e2e8f0;">{{ $risk['title'] ?? '' }}</span>
                                @if (! empty($risk['recommended_action']))
                                    <span style="color:#64748b;"> -> {{ $risk['recommended_action'] }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>
        @endif

        @if (! empty($background['available']))
            @php($cpuMetrics = is_array($background['cpu_metrics'] ?? null) ? $background['cpu_metrics'] : [])
            <div style="padding: 1rem; border: 1px solid #1f2937; border-radius: 0.5rem; background: #020617; color: #cbd5e1;">
                <div style="display: flex; justify-content: space-between; gap: 1rem; align-items: baseline; margin-bottom: 0.6rem;">
                    <div style="font-size: 0.9rem; font-weight: 700; color: #f8fafc;">Фони / CPU</div>
                    <div style="font-size: 0.8rem; color: #94a3b8;">{{ $background['updated_at'] }}</div>
                </div>

                <div style="display:grid; gap:0.55rem; grid-template-columns:repeat(auto-fit,minmax(12rem,1fr)); margin-bottom:0.7rem;">
                    <div>
                        <div style="display:flex; justify-content:space-between; gap:0.75rem; font-size:0.78rem; color:#94a3b8;">
                            <span>CPU зараз</span>
                            <span style="color:{{ $cpuMetrics['usage_color'] ?? '#94a3b8' }}; font-weight:700;">{{ $cpuMetrics['usage_text'] ?? 'n/a' }}</span>
                        </div>
                        <div style="margin-top:0.25rem; height:0.45rem; overflow:hidden; border-radius:999px; background:#1e293b;">
                            <div style="height:100%; width:{{ $cpuMetrics['usage_bar'] ?? 0 }}%; background:{{ $cpuMetrics['usage_color'] ?? '#64748b' }};"></div>
                        </div>
                        <div style="margin-top:0.22rem; font-size:0.72rem; color:#64748b;">
                            {{ $cpuMetrics['usage_state'] ?? '' }}@if (! empty($cpuMetrics['interval'])) · avg {{ $cpuMetrics['interval'] }}@endif
                        </div>
                    </div>
                    <div>
                        <div style="display:flex; justify-content:space-between; gap:0.75rem; font-size:0.78rem; color:#94a3b8;">
                            <span>Load queue 5m</span>
                            <span style="color:{{ $cpuMetrics['load_color'] ?? '#94a3b8' }}; font-weight:700;">
                                {{ $cpuMetrics['load5_text'] ?? 'n/a' }}/{{ $cpuMetrics['cores'] ?? 0 }}
                            </span>
                        </div>
                        <div style="margin-top:0.25rem; height:0.45rem; overflow:hidden; border-radius:999px; background:#1e293b;">
                            <div style="height:100%; width:{{ $cpuMetrics['load5_bar'] ?? 0 }}%; background:{{ $cpuMetrics['load_color'] ?? '#64748b' }};"></div>
                        </div>
                        <div style="margin-top:0.22rem; font-size:0.72rem; color:#64748b;">
                            {{ $cpuMetrics['load_state'] ?? '' }} · {{ $cpuMetrics['load5_pct_text'] ?? 'n/a' }}
                        </div>
                    </div>
                    <div>
                        <div style="font-size:0.78rem; color:#94a3b8;">Процеси</div>
                        <div style="margin-top:0.18rem; display:flex; flex-wrap:wrap; gap:0.35rem; font-size:0.76rem;">
                            <span style="padding:0.18rem 0.45rem; border-radius:0.35rem; background:#0f172a; color:#e2e8f0;">running={{ $background['counts']['running'] ?? 0 }}</span>
                            @if (($background['child_counts']['total'] ?? 0) > 0)
                                <span style="padding:0.18rem 0.45rem; border-radius:0.35rem; background:#0f172a; color:#bfdbfe;">child={{ $background['child_counts']['running'] ?? 0 }}/{{ $background['child_counts']['total'] ?? 0 }}</span>
                            @endif
                            <span style="padding:0.18rem 0.45rem; border-radius:0.35rem; background:#0f172a; color:#94a3b8;">finished={{ $background['counts']['finished'] ?? 0 }}</span>
                            <span style="padding:0.18rem 0.45rem; border-radius:0.35rem; background:#0f172a; color:#fca5a5;">failed={{ $background['counts']['failed'] ?? 0 }}</span>
                        </div>
                    </div>
                </div>
                @if (! empty($background['canonical_candidate_refresh']))
                    @php($canon = $background['canonical_candidate_refresh'])
                    <div style="font-size:0.8rem; color:{{ ($canon['status'] ?? '') === 'failed' ? '#fca5a5' : '#86efac' }}; margin-bottom:0.6rem;">
                        canonical candidates: {{ $canon['status'] ?? 'unknown' }}
                        @if (! empty($canon['refreshed_at_kyiv']))
                            · {{ $canon['refreshed_at_kyiv'] }}
                        @endif
                    </div>
                @endif
                @if (! empty($background['running']))
                    <ol style="margin: 0; padding-left: 1.25rem; display: grid; gap: 0.35rem; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 0.82rem; line-height: 1.45;">
                        @foreach ($background['running'] as $job)
                            <li>
                                <span style="color:#e2e8f0;">{{ $job['remaining'] }}</span>
                                <span style="color:#64748b;"> | </span>
                                <span style="color:#e2e8f0;">{{ $job['progress'] }}%</span>
                                <span style="color:#64748b;"> | </span>
                                <span style="color:#cbd5e1;">{{ $job['label'] }}</span>
                                @if (! empty($job['extra']))
                                    <span style="color:#64748b;"> | </span>
                                    <span style="color:#86efac;">{{ $job['extra'] }}</span>
                                @endif
                                @if (! empty($job['eta']))
                                    <span style="color:#64748b;"> | ETA {{ $job['eta'] }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>
        @endif

        @if (! empty($executionPriority['available']))
            @php($longPriority = $executionPriority['candidate_summary']['long'] ?? [])
            @php($shortPriority = $executionPriority['candidate_summary']['short'] ?? [])
            @php($priorityClasses = $executionPriority['job_summary']['classes'] ?? [])
            @php($priorityStateColor = ($executionPriority['state'] ?? '') === 'attention' ? '#fde68a' : '#86efac')
            <div style="padding: 1rem; border: 1px solid #1f2937; border-radius: 0.5rem; background: #020617; color: #cbd5e1;">
                <div style="display:flex; justify-content:space-between; gap:1rem; align-items:baseline; margin-bottom:0.6rem;">
                    <div style="font-size:0.9rem; font-weight:700; color:#f8fafc;">Execution priority</div>
                    <div style="font-size:0.8rem; color:#94a3b8;">{{ $executionPriority['generated_at'] }}</div>
                </div>
                <div style="display:grid; gap:0.45rem; font-size:0.85rem; line-height:1.45;">
                    <div>
                        state=<span style="color:{{ $priorityStateColor }};">{{ $executionPriority['state'] }}</span>;
                        running long_search={{ $priorityClasses['long_search'] ?? 0 }}/{{ $executionPriority['desired_running_mix']['long_search'] ?? 0 }},
                        short_search={{ $priorityClasses['short_search'] ?? 0 }}/{{ $executionPriority['desired_running_mix']['short_search'] ?? 0 }}
                    </div>
                    <div>
                        long ready={{ $longPriority['ready'] ?? 0 }}, review={{ $longPriority['review_or_shadow'] ?? 0 }}, logic={{ $longPriority['ready_or_review_logic_groups'] ?? 0 }};
                        short ready={{ $shortPriority['ready'] ?? 0 }}, review={{ $shortPriority['review_or_shadow'] ?? 0 }}, logic={{ $shortPriority['ready_or_review_logic_groups'] ?? 0 }}
                    </div>
                    @if (! empty($executionPriority['top_issue']))
                        <div style="color:#fde68a;">
                            {{ $executionPriority['top_issue']['priority'] ?? '' }} {{ $executionPriority['top_issue']['side'] ?? '' }}:
                            {{ $executionPriority['top_issue']['recommended_action'] ?? '' }}
                        </div>
                    @endif
                </div>
            </div>
        @endif

        @if (! empty($decisionCore['available']))
            @php($dcState = $decisionCore['state'] ?? '')
            @php($dcCurrent = is_array($decisionCore['current_period'] ?? null) ? $decisionCore['current_period'] : [])
            @php($dc120 = is_array($decisionCore['dominance']['120h'] ?? null) ? $decisionCore['dominance']['120h'] : [])
            @php($dcTop120 = is_array($dc120['top'] ?? null) ? array_slice($dc120['top'], 0, 3) : [])
            <div style="padding:1rem; border:1px solid {{ $dcState === 'attention' ? '#92400e' : '#1f2937' }}; border-radius:0.5rem; background:#020617; color:#cbd5e1;">
                <div style="display:flex; justify-content:space-between; gap:1rem; align-items:baseline; margin-bottom:0.65rem;">
                    <div>
                        <div style="font-size:0.9rem; font-weight:700; color:#f8fafc;">Decision Core</div>
                        <div style="font-size:0.78rem; color:#94a3b8; margin-top:0.18rem;">
                            центр рішень: live board, режими, needs, research priorities
                        </div>
                    </div>
                    <div style="font-size:0.8rem; color:#94a3b8;">{{ $decisionCore['generated_at'] }}</div>
                </div>
                <div style="display:flex; flex-wrap:wrap; gap:0.45rem; margin-bottom:0.7rem; font-size:0.78rem;">
                    <span style="padding:0.2rem 0.45rem; border-radius:0.35rem; background:{{ $dcState === 'attention' ? '#78350f' : '#064e3b' }}; color:{{ $dcState === 'attention' ? '#fde68a' : '#bbf7d0' }}; font-weight:700;">state={{ $dcState }}</span>
                    <span style="padding:0.2rem 0.45rem; border-radius:0.35rem; background:#0f172a; color:#94a3b8;">regime={{ $dcCurrent['major_key'] ?? 'unknown' }}</span>
                    <span style="padding:0.2rem 0.45rem; border-radius:0.35rem; background:#0f172a; color:#94a3b8;">live={{ $decisionCore['board_summary']['live_candidates'] ?? 0 }}</span>
                    <span style="padding:0.2rem 0.45rem; border-radius:0.35rem; background:#0f172a; color:#94a3b8;">attention={{ $decisionCore['board_summary']['attention_candidates'] ?? 0 }}</span>
                    <a href="/admin/live-candidate-board" style="padding:0.2rem 0.45rem; border-radius:0.35rem; background:#1e293b; color:#e2e8f0; font-weight:700;">Open Live Board</a>
                </div>
                <div style="display:grid; gap:0.45rem; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.8rem; line-height:1.45;">
                    @if (! empty($dcTop120))
                        <div style="color:#cbd5e1;">
                            120h dominance:
                            @foreach ($dcTop120 as $period)
                                <span style="color:#e2e8f0;">{{ $period['major_key'] ?? '' }} {{ isset($period['pct']) ? number_format((float) $period['pct'], 1, '.', '') . '%' : '' }}</span>@if (! $loop->last)<span style="color:#64748b;">; </span>@endif
                            @endforeach
                        </div>
                    @endif
                    @if (! empty($decisionCore['actions']))
                        @foreach ($decisionCore['actions'] as $action)
                            <div>
                                <span style="color:{{ ($action['priority'] ?? '') === 'P0' ? '#fca5a5' : (($action['priority'] ?? '') === 'P1' ? '#fde68a' : '#cbd5e1') }}; font-weight:700;">{{ $action['priority'] ?? '' }}</span>
                                <span style="color:#64748b;"> | </span>
                                <span style="color:#e2e8f0;">{{ $action['title'] ?? '' }}</span>
                                <span style="color:#64748b;"> | {{ $action['mode'] ?? '' }}</span>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        @endif

        @if (! empty($completionInbox['available']))
            <div style="padding: 1rem; border: 1px solid #1f2937; border-radius: 0.5rem; background: #020617; color: #cbd5e1;">
                <div style="display: flex; justify-content: space-between; gap: 1rem; align-items: baseline; margin-bottom: 0.6rem;">
                    <div style="font-size: 0.9rem; font-weight: 700; color: #f8fafc;">Завершені / аналіз</div>
                    <div style="font-size: 0.8rem; color: #94a3b8;">{{ $completionInbox['generated_at'] }}</div>
                </div>
                <div style="font-size: 0.85rem; color: #cbd5e1; margin-bottom: 0.6rem;">
                    needs={{ $completionInbox['counts']['needs_analysis'] ?? 0 }};
                    analyzed={{ $completionInbox['counts']['analyzed'] ?? 0 }}
                    @if (isset($completionInbox['counts']['auto_summarized']))
                        (auto={{ $completionInbox['counts']['auto_summarized'] ?? 0 }}, manual={{ $completionInbox['counts']['manual_analyzed'] ?? 0 }})
                    @endif;
                    seen={{ $completionInbox['counts']['finished_seen'] ?? 0 }}
                </div>
                @php($completionItems = ! empty($completionInbox['needs_analysis']) ? $completionInbox['needs_analysis'] : array_slice($completionInbox['recent_finished'], 0, 5))
                @if (! empty($completionItems))
                    <ol style="margin: 0; padding-left: 1.25rem; display: grid; gap: 0.4rem; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 0.82rem; line-height: 1.45;">
                        @foreach ($completionItems as $job)
                            @php($shownResult = $job['result_normalized'] ?? ($job['result'] ?? ''))
                            <li>
                                <span style="color:#e2e8f0;">{{ $job['analysis_status'] ?? 'needs_analysis' }}</span>
                                <span style="color:#64748b;"> | </span>
                                <span style="color:#e2e8f0;">{{ $job['finished_at_kyiv'] ?? '' }}</span>
                                <span style="color:#64748b;"> | </span>
                                <span style="color:#cbd5e1;">{{ $job['label'] ?? '' }}</span>
                                @if (! empty($shownResult))
                                    <span style="color:#64748b;"> | {{ $shownResult }}</span>
                                @endif
                                @if (! empty($job['analysis_note']))
                                    <span style="color:#94a3b8;"> | note: {{ $job['analysis_note'] }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                @else
                    <div style="font-size:0.85rem; color:#94a3b8;">Нових завершених фонів немає.</div>
                @endif
            </div>
        @endif

        @if (! empty($signalEvents['available']))
            <div style="padding: 1rem; border: 1px solid #1f2937; border-radius: 0.5rem; background: #020617; color: #cbd5e1;">
                <div style="font-size: 0.9rem; font-weight: 700; color: #f8fafc; margin-bottom: 0.6rem;">Signal events</div>
                <div style="display:grid; gap:0.65rem; grid-template-columns:repeat(auto-fit,minmax(18rem,1fr));">
                    @foreach ($signalEvents['sources'] as $source)
                        @php($isAvailable = ! empty($source['available']))
                        <div style="border:1px solid #1f2937; border-radius:0.45rem; padding:0.8rem; background:#0f172a;">
                            <div style="display:flex; justify-content:space-between; gap:0.75rem; align-items:baseline;">
                                <div style="font-weight:700; color:#f8fafc;">{{ $source['label'] ?? '' }}</div>
                                <div style="font-size:0.78rem; color:{{ $isAvailable ? '#86efac' : '#fca5a5' }};">
                                    {{ $isAvailable ? 'event-log' : 'old/no log' }}
                                </div>
                            </div>
                            @if ($isAvailable)
                                <div style="margin-top:0.45rem; font-size:0.78rem; color:#94a3b8;">
                                    feed {{ $source['generated_at'] ?? '' }} · feature {{ $source['max_feature_utc'] ?? '' }}
                                </div>
                                <div style="margin-top:0.5rem; display:grid; gap:0.25rem; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.8rem; line-height:1.45;">
                                    <div>
                                        fresh now <span style="color:#e2e8f0;">{{ $source['fresh_now'] ?? 0 }}</span>
                                        <span style="color:#64748b;"> | age-window {{ $source['age_window_now'] ?? 0 }}</span>
                                        <span style="color:#64748b;"> | cursor {{ $source['last_processed_utc'] ?? '' }}</span>
                                    </div>
                                    <div>
                                        base <span style="color:#cbd5e1;">{{ $source['baseline_replay'] ?? 0 }}</span>
                                        <span style="color:#64748b;"> | fresh total {{ $source['fresh_total'] ?? 0 }}</span>
                                        <span style="color:#64748b;"> | exec {{ $source['executed_open'] ?? 0 }}/{{ $source['executed_closed'] ?? 0 }}</span>
                                        <span style="color:#64748b;"> | late {{ $source['late_backfill'] ?? 0 }}</span>
                                        <span style="color:#64748b;"> | replay {{ $source['replay_seen'] ?? 0 }}</span>
                                    </div>
                                    <div style="color:#94a3b8;">
                                        snapshot rows {{ $source['snapshot_signals'] ?? 0 }},
                                        inserted {{ $source['inserted'] ?? 0 }},
                                        already {{ $source['already_seen'] ?? 0 }}
                                    </div>
                                </div>
                            @else
                                <div style="margin-top:0.45rem; font-size:0.82rem; color:#94a3b8;">Ще немає event-log у цьому feed.</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if (! empty($eventDriven['available']))
            <div style="padding: 1rem; border: 1px solid #1f2937; border-radius: 0.5rem; background: #020617; color: #cbd5e1;">
                <div style="display:flex; justify-content:space-between; gap:1rem; align-items:baseline; margin-bottom:0.65rem;">
                    <div style="font-size:0.9rem; font-weight:700; color:#f8fafc;">Event-driven entries</div>
                    <div style="font-size:0.8rem; color:#94a3b8;">{{ $eventDriven['generated_at'] }}</div>
                </div>
                <div style="font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.82rem; color:#cbd5e1; line-height:1.45; margin-bottom:0.7rem;">
                    raw={{ $eventDriven['summary']['raw_events'] ?? 0 }};
                    candidate potential={{ $eventDriven['summary']['candidate_potential_trades'] ?? 0 }};
                    router potential={{ $eventDriven['summary']['router_potential_entries'] ?? 0 }};
                    exec open/closed={{ $eventDriven['summary']['executed_open'] ?? 0 }}/{{ $eventDriven['summary']['executed_closed'] ?? 0 }};
                    outcome={{ $eventDriven['summary']['closed_with_live_outcome'] ?? 0 }};
                    aliases={{ $eventDriven['summary']['legacy_execution_aliases'] ?? 0 }}
                </div>
                @if (! empty($eventDriven['top_candidates']))
                    <ol style="margin:0; padding-left:1.25rem; display:grid; gap:0.42rem; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.82rem; line-height:1.45;">
                        @foreach ($eventDriven['top_candidates'] as $candidate)
                            @php($live = is_array($candidate['live'] ?? null) ? $candidate['live'] : [])
                            <li>
                                <span style="color:#e2e8f0;">{{ $candidate['side'] ?? '' }}</span>
                                <span style="color:#64748b;"> | </span>
                                <span style="color:#cbd5e1;">{{ $candidate['candidate_id'] ?? '' }}</span>
                                @if (! empty($candidate['profile']))
                                    <span style="color:#64748b;"> / {{ $candidate['profile'] }}</span>
                                @endif
                                <span style="color:#64748b;"> | </span>
                                <span style="color:#cbd5e1;">
                                    potential {{ $candidate['potential_trades'] ?? 0 }}
                                    ({{ number_format((float) ($candidate['potential_trades_per_day'] ?? 0), 2, '.', '') }}/d),
                                    raw {{ $candidate['raw_events'] ?? 0 }}
                                </span>
                                <span style="color:#64748b;"> | live {{ $live['wins'] ?? 0 }}/{{ $live['losses'] ?? 0 }}, pnl {{ $live['pnl_usdt'] ?? 0 }}</span>
                                <span style="color:#64748b;"> | {{ $candidate['last_potential_kyiv'] ?? '' }}</span>
                            </li>
                        @endforeach
                    </ol>
                @endif
                @if (! empty($eventDriven['recent_router_entries']))
                    <div style="margin-top:0.75rem; padding-top:0.65rem; border-top:1px solid #1f2937; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.78rem; color:#94a3b8;">
                        router recent:
                        @foreach (array_slice($eventDriven['recent_router_entries'], 0, 5) as $entry)
                            <span style="color:#cbd5e1;">{{ $entry['kyiv'] ?? '' }} {{ $entry['side'] ?? '' }} {{ $entry['status'] ?? '' }}</span>@if (! $loop->last)<span style="color:#64748b;">; </span>@endif
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        @if (! empty($normalizedCandidates['available']))
            <div style="padding: 1rem; border: 1px solid #1f2937; border-radius: 0.5rem; background: #020617; color: #cbd5e1;">
                <div style="display:flex; justify-content:space-between; gap:1rem; align-items:baseline; margin-bottom:0.65rem;">
                    <div style="font-size:0.9rem; font-weight:700; color:#f8fafc;">Normalized candidates V3</div>
                    <div style="font-size:0.8rem; color:#94a3b8;">{{ $normalizedCandidates['generated_at'] }}</div>
                </div>
                <div style="font-size:0.8rem; color:#94a3b8; margin-bottom:0.7rem;">
                    primary: own_period / current_period / last120h / last24h as potential entries after live-style lock; raw signals are debug only.
                    raw={{ $normalizedCandidates['summary']['raw_candidate_count'] ?? 0 }},
                    unique={{ $normalizedCandidates['summary']['candidate_count'] ?? 0 }},
                    dup={{ $normalizedCandidates['summary']['duplicates_collapsed'] ?? 0 }}
                </div>
                <div style="display:grid; gap:0.85rem;">
                    @foreach (['long' => 'LONG normalized top-7', 'short' => 'SHORT normalized top-7'] as $sideKey => $sideTitle)
                        @php($rows = is_array($normalizedCandidates['by_side'][$sideKey] ?? null) ? $normalizedCandidates['by_side'][$sideKey] : [])
                        <div>
                            <div style="font-size:0.82rem; font-weight:700; color:#e2e8f0; margin-bottom:0.35rem;">{{ $sideTitle }}</div>
                            @if (! empty($rows))
                                <ol style="margin:0; padding-left:1.25rem; display:grid; gap:0.45rem; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.8rem; line-height:1.45;">
                                    @foreach ($rows as $row)
                                        @php($periods = is_array($row['periods'] ?? null) ? $row['periods'] : [])
                                        @php($own = is_array($periods['own_period'] ?? null) ? $periods['own_period'] : [])
                                        @php($current = is_array($periods['current_period'] ?? null) ? $periods['current_period'] : [])
                                        @php($last120 = is_array($periods['last_120h'] ?? null) ? $periods['last_120h'] : [])
                                        @php($last24 = is_array($periods['last_24h'] ?? null) ? $periods['last_24h'] : [])
                                        <li>
                                            <span style="color:#e2e8f0;">{{ $row['candidate_id'] ?? '' }}</span>
                                            <span style="color:#64748b;"> / {{ $row['profile'] ?? '' }}</span>
                                            <span style="color:#64748b;"> | {{ $row['status'] ?? '' }}</span>
                                            @if (! empty($row['logic_family']))
                                                <span style="color:#64748b;"> | logic={{ $row['logic_family'] }}</span>
                                            @endif
                                            @if (($row['duplicate_count'] ?? 1) > 1)
                                                <span style="color:#fbbf24;"> | dup={{ $row['duplicate_count'] }}</span>
                                            @endif
                                            <br>
                                            <span style="color:#cbd5e1;">own={{ $this->formatNormalizedPeriod($own) }}</span>
                                            <br>
                                            <span style="color:#86efac;">current={{ $this->formatNormalizedPeriod($current) }}</span>
                                            <br>
                                            <span style="color:#fbbf24;">last120h={{ $this->formatNormalizedPeriod($last120) }}</span>
                                            <span style="color:#64748b;"> | last24h={{ $this->formatNormalizedPeriod($last24) }}</span>
                                        </li>
                                    @endforeach
                                </ol>
                            @else
                                <div style="font-size:0.8rem; color:#94a3b8;">No normalized candidates for this side.</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if (false && ! empty($candidateSelection['available']))
            <div style="padding: 1rem; border: 1px solid #1f2937; border-radius: 0.5rem; background: #020617; color: #cbd5e1;">
                <div style="display:flex; justify-content:space-between; gap:1rem; align-items:baseline; margin-bottom:0.65rem;">
                    <div style="font-size:0.9rem; font-weight:700; color:#f8fafc;">Candidate selection</div>
                    <div style="font-size:0.8rem; color:#94a3b8;">{{ $candidateSelection['generated_at'] }}</div>
                </div>
                <div style="font-size:0.8rem; color:#94a3b8; margin-bottom:0.7rem;">
                    first: 24/120h potential entries → safety → own-period quality → history/ML/router context
                </div>
                <div style="display:grid; gap:0.85rem;">
                    @foreach (['long' => 'LONG top-6', 'short' => 'SHORT top-6'] as $sideKey => $sideTitle)
                        @php($rows = is_array($candidateSelection['top_candidates'][$sideKey] ?? null) ? $candidateSelection['top_candidates'][$sideKey] : (is_array($candidateSelection['top_non_live'][$sideKey] ?? null) ? $candidateSelection['top_non_live'][$sideKey] : []))
                        <div>
                            <div style="font-size:0.82rem; font-weight:700; color:#e2e8f0; margin-bottom:0.35rem;">{{ $sideTitle }}</div>
                            @if (! empty($rows))
                                <ol style="margin:0; padding-left:1.25rem; display:grid; gap:0.45rem; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.8rem; line-height:1.45;">
                                    @foreach ($rows as $row)
                                        @php($searchHistory = is_array($row['own_period'] ?? null) ? $row['own_period'] : (is_array($row['history'] ?? null) ? $row['history'] : []))
                                        @php($searchCurrent = is_array($row['current'] ?? null) ? $row['current'] : [])
                                        @php($searchLast120 = is_array($row['last120h'] ?? null) ? $row['last120h'] : [])
                                        @php($now = is_array($row['now_availability'] ?? null) ? $row['now_availability'] : [])
                                        @php($active = is_array($row['active_window'] ?? null) ? $row['active_window'] : [])
                                        @php($active24 = is_array($active['window_24h'] ?? null) ? $active['window_24h'] : [])
                                        @php($active120 = is_array($active['window_120h'] ?? null) ? $active['window_120h'] : [])
                                        @php($activeAll = is_array($active['since_2025'] ?? null) ? $active['since_2025'] : [])
                                        @php($replay = is_array($row['replay_outcome'] ?? null) ? $row['replay_outcome'] : [])
                                        @php($replay24 = is_array($replay['window_24h'] ?? null) ? $replay['window_24h'] : [])
                                        @php($replay120 = is_array($replay['window_120h'] ?? null) ? $replay['window_120h'] : [])
                                        @php($replayAll = is_array($replay['since_2025'] ?? null) ? $replay['since_2025'] : [])
                                        @php($period = is_array($row['candidate_period_evidence'] ?? null) ? $row['candidate_period_evidence'] : [])
                                        @php($history = is_array($row['history_sanity'] ?? null) ? $row['history_sanity'] : [])
                                        @php($ml = is_array($row['ml_router_context'] ?? null) ? $row['ml_router_context'] : [])
                                        <li>
                                            @if (! empty($row['is_live']) || (($row['source'] ?? '') === 'live'))
                                                <span style="color:#020617; background:#86efac; padding:0.05rem 0.3rem; border-radius:0.25rem; font-weight:700;">LIVE</span>
                                                <span style="color:#64748b;"> </span>
                                            @endif
                                            <span style="color:#e2e8f0;">{{ $row['candidate_id'] ?? '' }}</span>
                                            <span style="color:#64748b;"> / {{ $row['profile'] ?? '' }}</span>
                                            <span style="color:#64748b;"> | {{ $row['status'] ?? ($row['selection_state'] ?? '') }}</span>
                                            @if (! empty($row['logic_family']))
                                                <span style="color:#64748b;"> | logic={{ $row['logic_family'] }}</span>
                                            @endif
                                            <br>
                                            @if (! empty($searchCurrent) || ! empty($searchLast120))
                                                @php($histDays = array_key_exists('active_days', $searchHistory) && $searchHistory['active_days'] !== null ? $searchHistory['active_days'] . 'd' : 'n/a')
                                                @php($histDay = array_key_exists('per_day', $searchHistory) && $searchHistory['per_day'] !== null ? number_format((float) $searchHistory['per_day'], 2, '.', '') . '/d' : 'n/a')
                                                @php($histPf = array_key_exists('pf', $searchHistory) && $searchHistory['pf'] !== null ? number_format((float) $searchHistory['pf'], 2, '.', '') : 'n/a')
                                                @php($histNet = array_key_exists('net_pct', $searchHistory) && $searchHistory['net_pct'] !== null ? number_format((float) $searchHistory['net_pct'], 2, '.', '') . '%' : 'n/a')
                                                @php($curDays = array_key_exists('active_days', $searchCurrent) && $searchCurrent['active_days'] !== null ? $searchCurrent['active_days'] . 'd' : 'n/a')
                                                @php($curDay = array_key_exists('per_day', $searchCurrent) && $searchCurrent['per_day'] !== null ? number_format((float) $searchCurrent['per_day'], 2, '.', '') . '/d' : 'n/a')
                                                @php($curPf = array_key_exists('pf', $searchCurrent) && $searchCurrent['pf'] !== null ? number_format((float) $searchCurrent['pf'], 2, '.', '') : 'n/a')
                                                @php($curNet = array_key_exists('net_pct', $searchCurrent) && $searchCurrent['net_pct'] !== null ? number_format((float) $searchCurrent['net_pct'], 2, '.', '') . '%' : 'n/a')
                                                @php($lastDays = array_key_exists('active_days', $searchLast120) && $searchLast120['active_days'] !== null ? $searchLast120['active_days'] . 'd' : 'n/a')
                                                @php($lastDay = array_key_exists('per_day', $searchLast120) && $searchLast120['per_day'] !== null ? number_format((float) $searchLast120['per_day'], 2, '.', '') . '/d' : 'n/a')
                                                @php($lastPf = array_key_exists('pf', $searchLast120) && $searchLast120['pf'] !== null ? number_format((float) $searchLast120['pf'], 2, '.', '') : 'n/a')
                                                @php($lastNet = array_key_exists('net_pct', $searchLast120) && $searchLast120['net_pct'] !== null ? number_format((float) $searchLast120['net_pct'], 2, '.', '') . '%' : 'n/a')
                                                <span style="color:#cbd5e1;">own_period={{ $searchHistory['entries'] ?? 'n/a' }}/{{ $histDays }}={{ $histDay }}, PF={{ $histPf }}, net={{ $histNet }}</span>
                                                @if (! empty($searchHistory['basis']))
                                                    <span style="color:#64748b;"> | {{ $searchHistory['basis'] }}</span>
                                                @endif
                                                <br>
                                                <span style="color:#86efac;">current={{ $searchCurrent['entries'] ?? 0 }}/{{ $curDays }}={{ $curDay }}, PF={{ $curPf }}, net={{ $curNet }}</span>
                                                <br>
                                                <span style="color:#fbbf24;">last120h={{ $searchLast120['entries'] ?? 0 }}/{{ $lastDays }}={{ $lastDay }}, PF={{ $lastPf }}, net={{ $lastNet }}</span>
                                                @if (! empty($row['source']))
                                                    <span style="color:#64748b;"> | src={{ $row['source'] }}</span>
                                                @endif
                                            @else
                                                <span style="color:#86efac;">now 24h={{ $now['last_24h_potential_entries'] ?? 0 }}, 120h={{ $now['last_120h_potential_entries'] ?? 0 }}, fresh={{ ! empty($now['fresh_now']) ? 'yes' : 'no' }}, last={{ $now['last_potential_kyiv'] ?? 'n/a' }}</span>
                                                <br>
                                                <span style="color:#38bdf8;">active 24h={{ $active24['observed_active_hours'] ?? 'n/a' }}h/{{ $active24['raw_event_rows'] ?? 'n/a' }} rows, 120h={{ $active120['observed_active_hours'] ?? 'n/a' }}h/{{ $active120['raw_event_rows'] ?? 'n/a' }} rows, since25={{ $activeAll['observed_active_hours'] ?? 'n/a' }}h/{{ $activeAll['raw_event_rows'] ?? 'n/a' }} rows, lock24={{ $active24['potential_trade_lock_hours'] ?? 'n/a' }}h</span>
                                                <br>
                                                <span style="color:#fbbf24;">replay 24h={{ $replay24['entries'] ?? 0 }}(tp/sl/time/open={{ $replay24['tp'] ?? 0 }}/{{ $replay24['sl'] ?? 0 }}/{{ $replay24['time'] ?? 0 }}/{{ $replay24['open'] ?? 0 }}) net={{ $replay24['net_pct'] ?? 'n/a' }}%; 120h={{ $replay120['entries'] ?? 0 }}(tp/sl/time/open={{ $replay120['tp'] ?? 0 }}/{{ $replay120['sl'] ?? 0 }}/{{ $replay120['time'] ?? 0 }}/{{ $replay120['open'] ?? 0 }}) net={{ $replay120['net_pct'] ?? 'n/a' }}%; since25={{ $replayAll['entries'] ?? 0 }}(tp/sl/time/open={{ $replayAll['tp'] ?? 0 }}/{{ $replayAll['sl'] ?? 0 }}/{{ $replayAll['time'] ?? 0 }}/{{ $replayAll['open'] ?? 0 }}) net={{ $replayAll['net_pct'] ?? 'n/a' }}%</span>
                                                <br>
                                                <span style="color:#cbd5e1;">period event={{ $period['event_potential_trades'] ?? 'n/a' }} ({{ $period['event_trades_per_day'] ?? 'n/a' }}/d), eventNet={{ $period['event_net_pct'] ?? 'n/a' }}%, op={{ $period['operating_trades'] ?? 'n/a' }} ({{ $period['operating_trades_per_day'] ?? 'n/a' }}/d), opPF={{ $period['operating_pf'] ?? 'n/a' }}, opNet={{ $period['operating_net_pct'] ?? 'n/a' }}%</span>
                                                <span style="color:#64748b;"> | hist={{ $history['history_trades'] ?? 'n/a' }}, histPF={{ $history['history_pf'] ?? 'n/a' }}, histNet={{ $history['history_net_pct'] ?? 'n/a' }}%, ML={{ $ml['ml_score'] ?? 'n/a' }}, router={{ $ml['router_score'] ?? 'n/a' }}</span>
                                            @endif
                                            @if (! empty($row['why_not_live_now']))
                                                <div style="color:#fde68a; margin-top:0.15rem;">why_not_live_now: {{ implode('; ', array_slice($row['why_not_live_now'], 0, 3)) }}</div>
                                            @endif
                                        </li>
                                    @endforeach
                                </ol>
                            @else
                                <div style="font-size:0.8rem; color:#94a3b8;">No non-live candidate passed strict display filter.</div>
                            @endif
                        </div>
                    @endforeach
                </div>
                @if (! empty($candidateSelection['gaps']))
                    <div style="margin-top:0.75rem; padding-top:0.65rem; border-top:1px solid #1f2937; font-size:0.8rem; color:#fde68a;">
                        {{ implode(' | ', $candidateSelection['gaps']) }}
                    </div>
                @endif
            </div>
        @endif

        @if (false && ! empty($liveReplacementWatch['available']))
            @php($replacementState = $liveReplacementWatch['state'] ?? '')
            <div style="padding: 1rem; border: 1px solid {{ $replacementState === 'attention' ? '#92400e' : '#1f2937' }}; border-radius: 0.5rem; background: #020617; color: #cbd5e1;">
                <div style="display:flex; justify-content:space-between; gap:1rem; align-items:baseline; margin-bottom:0.65rem;">
                    <div>
                        <div style="font-size:0.9rem; font-weight:700; color:#f8fafc;">Live replacement watch</div>
                        <div style="font-size:0.78rem; color:#94a3b8; margin-top:0.18rem;">read-only порівняння: live кандидат проти доступних зараз; заміна тільки вручну</div>
                    </div>
                    <div style="font-size:0.8rem; color:#94a3b8;">{{ $liveReplacementWatch['generated_at'] }}</div>
                </div>
                <div style="display:flex; flex-wrap:wrap; gap:0.5rem; margin-bottom:0.75rem; font-size:0.78rem;">
                    <span style="padding:0.2rem 0.45rem; border-radius:0.35rem; background:{{ $replacementState === 'attention' ? '#78350f' : '#064e3b' }}; color:{{ $replacementState === 'attention' ? '#fde68a' : '#bbf7d0' }}; font-weight:700;">state={{ $replacementState ?: 'unknown' }}</span>
                    <span style="padding:0.2rem 0.45rem; border-radius:0.35rem; background:#0f172a; color:#94a3b8;">actions={{ count($liveReplacementWatch['actions'] ?? []) }}</span>
                    <span style="padding:0.2rem 0.45rem; border-radius:0.35rem; background:#0f172a; color:#94a3b8;">manual only</span>
                </div>
                @if (! empty($liveReplacementWatch['actions']))
                    <div style="display:flex; flex-wrap:wrap; gap:0.45rem; margin-bottom:0.8rem; font-size:0.76rem;">
                        @foreach ($liveReplacementWatch['actions'] as $action)
                            @php($actionSide = $action['side'] ?? '')
                            @php($actionLabel = $actionSide === 'long' ? 'Prepare long replace' : 'Prepare short add')
                            <span style="padding:0.28rem 0.55rem; border-radius:0.35rem; border:1px solid #334155; background:#0f172a; color:#e2e8f0; font-weight:700;">{{ $actionLabel }}</span>
                            <span style="padding:0.28rem 0.55rem; border-radius:0.35rem; border:1px solid #92400e; background:#78350f33; color:#fde68a;">Apply only after GO</span>
                        @endforeach
                    </div>
                @endif
                <div style="display:grid; gap:0.85rem;">
                    @foreach (['long' => 'LONG', 'short' => 'SHORT'] as $sideKey => $sideTitle)
                        @php($side = is_array($liveReplacementWatch['sides'][$sideKey] ?? null) ? $liveReplacementWatch['sides'][$sideKey] : [])
                        @if (! empty($side))
                            @php($live = is_array($side['best_live'] ?? null) ? $side['best_live'] : [])
                            @php($challenger = is_array($side['best_challenger'] ?? null) ? $side['best_challenger'] : [])
                            @php($delta = $side['score_delta_best_challenger_minus_live'] ?? null)
                            <div style="border:1px solid #1f2937; border-radius:0.45rem; padding:0.85rem; background:#0f172a;">
                                <div style="display:flex; justify-content:space-between; gap:1rem; align-items:baseline; margin-bottom:0.55rem;">
                                    <div style="font-size:0.82rem; font-weight:700; color:#e2e8f0;">{{ $sideTitle }} · {{ $side['decision'] ?? 'n/a' }}</div>
                                    <div style="font-size:0.78rem; color:{{ ((float) ($delta ?? 0)) > 0 ? '#fbbf24' : '#94a3b8' }};">delta={{ $delta === null ? 'n/a' : number_format((float) $delta, 2, '.', '') }}</div>
                                </div>
                                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:0.75rem; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.78rem; line-height:1.45;">
                                    @foreach ([['title' => 'LIVE', 'row' => $live, 'color' => '#86efac'], ['title' => 'BEST NOW', 'row' => $challenger, 'color' => '#fbbf24']] as $card)
                                        @php($row = $card['row'])
                                        @php($cur = is_array($row['current'] ?? null) ? $row['current'] : [])
                                        @php($last24 = is_array($row['last24h'] ?? null) ? $row['last24h'] : [])
                                        @php($last120 = is_array($row['last120h'] ?? null) ? $row['last120h'] : [])
                                        @php($own = is_array($row['own_period'] ?? null) ? $row['own_period'] : [])
                                        <div>
                                            <div style="font-weight:700; color:{{ $card['color'] }}; margin-bottom:0.25rem;">{{ $card['title'] }}</div>
                                            @if (! empty($row))
                                                <div style="color:#e2e8f0;">{{ $row['candidate_id'] ?? '' }}</div>
                                                <div style="color:#64748b;">{{ $row['profile'] ?? '' }} · {{ $row['logic_root'] ?? ($row['logic_family'] ?? '') }}</div>
                                                <div style="color:#64748b;">status={{ $row['status'] ?? '' }} · score={{ isset($row['score']) ? number_format((float) $row['score'], 2, '.', '') : 'n/a' }}</div>
                                                <div style="color:#86efac;">24h={{ $last24['entries'] ?? 0 }}, net={{ isset($last24['net_pct']) ? number_format((float) $last24['net_pct'], 2, '.', '') . '%' : 'n/a' }}</div>
                                                <div style="color:#e2e8f0;">current={{ $cur['entries'] ?? 0 }}{{ isset($cur['active_days']) ? '/' . $cur['active_days'] . 'd' : '' }}, PF={{ isset($cur['pf']) ? number_format((float) $cur['pf'], 2, '.', '') : 'n/a' }}, net={{ isset($cur['net_pct']) ? number_format((float) $cur['net_pct'], 2, '.', '') . '%' : 'n/a' }}</div>
                                                <div style="color:#fbbf24;">120h={{ $last120['entries'] ?? 0 }}{{ isset($last120['active_days']) ? '/' . $last120['active_days'] . 'd' : '' }}, PF={{ isset($last120['pf']) ? number_format((float) $last120['pf'], 2, '.', '') : 'n/a' }}, net={{ isset($last120['net_pct']) ? number_format((float) $last120['net_pct'], 2, '.', '') . '%' : 'n/a' }}</div>
                                                <div style="color:#cbd5e1;">own={{ $own['entries'] ?? 'n/a' }}{{ isset($own['active_days']) ? '/' . $own['active_days'] . 'd' : '' }}{{ isset($own['per_day']) ? '=' . number_format((float) $own['per_day'], 2, '.', '') . '/d' : '' }}, PF={{ isset($own['pf']) ? number_format((float) $own['pf'], 2, '.', '') : 'n/a' }}, net={{ isset($own['net_pct']) ? number_format((float) $own['net_pct'], 2, '.', '') . '%' : 'n/a' }}</div>
                                            @else
                                                <div style="color:#94a3b8;">немає даних</div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                                @if (! empty($side['reasons']))
                                    <div style="margin-top:0.65rem; padding-top:0.55rem; border-top:1px solid #1f2937; font-size:0.78rem; color:#fde68a; line-height:1.45;">
                                        {{ implode('; ', array_slice($side['reasons'], 0, 8)) }}
                                    </div>
                                @endif
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif

        @if (! empty($codex['plan']))
            <div style="padding: 1rem; border: 1px solid #1f2937; border-radius: 0.5rem; background: #020617; color: #cbd5e1;">
                <div style="font-size: 0.9rem; font-weight: 700; color: #f8fafc; margin-bottom: 0.6rem;">План</div>
                <ol style="margin: 0; padding-left: 1.25rem; display: grid; gap: 0.45rem;">
                    @foreach ($codex['plan'] as $item)
                        <li>
                            <span style="color: #e2e8f0;">{{ is_array($item) ? ($item['step'] ?? '') : $item }}</span>
                            @if (is_array($item) && ! empty($item['status']))
                                <span style="color: #94a3b8;"> — {{ $item['status'] }}</span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif

        @if (! empty($candidateRevalidation['available']))
            @php($revalidationCounts = $candidateRevalidation['counts'] ?? [])
            @php($byDecision = is_array($revalidationCounts['by_decision'] ?? null) ? $revalidationCounts['by_decision'] : [])
            <div style="padding: 1rem; border: 1px solid #1f2937; border-radius: 0.5rem; background: #020617; color: #cbd5e1;">
                <div style="display:flex; justify-content:space-between; gap:1rem; align-items:baseline; margin-bottom:0.65rem;">
                    <div style="font-size:0.9rem; font-weight:700; color:#f8fafc;">Candidate revalidation</div>
                    <div style="font-size:0.8rem; color:#94a3b8;">{{ $candidateRevalidation['generated_at'] }}</div>
                </div>
                <div style="font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.82rem; color:#cbd5e1; line-height:1.45; margin-bottom:0.7rem;">
                    live_allowed={{ $byDecision['live_test_allowed'] ?? 0 }};
                    open_block_after_flat={{ $byDecision['live_open_block_after_flat'] ?? 0 }};
                    confirm_needed={{ $byDecision['confirm_needed'] ?? 0 }};
                    shadow_candidate={{ $byDecision['shadow_candidate'] ?? 0 }};
                    blocked={{ $byDecision['blocked'] ?? 0 }}
                </div>
                @if (! empty($candidateRevalidation['live_test_allowed']))
                    <div style="font-size:0.82rem; font-weight:700; color:#86efac; margin-bottom:0.4rem;">Live allowed</div>
                    <ol style="margin:0 0 0.75rem 0; padding-left:1.25rem; display:grid; gap:0.35rem; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.8rem; line-height:1.45;">
                        @foreach ($candidateRevalidation['live_test_allowed'] as $row)
                            <li>
                                {{ $row['side'] ?? '' }} {{ $row['candidate_id'] ?? '' }} {{ $row['profile'] ?? '' }}
                                <span style="color:#64748b;"> | event {{ $row['event_potential_trades'] ?? 0 }} ({{ number_format((float) ($row['event_trades_per_day'] ?? 0), 2, '.', '') }}/d)</span>
                                <span style="color:#64748b;"> | PF {{ number_format((float) ($row['history_pf'] ?? 0), 2, '.', '') }}</span>
                                <span style="color:#64748b;"> | live {{ $row['live_wins'] ?? 0 }}/{{ $row['live_losses'] ?? 0 }}</span>
                            </li>
                        @endforeach
                    </ol>
                @endif
                @if (! empty($candidateRevalidation['candidate_next']))
                    <div style="font-size:0.82rem; font-weight:700; color:#f8fafc; margin-bottom:0.4rem;">Next review</div>
                    <ol style="margin:0 0 0.75rem 0; padding-left:1.25rem; display:grid; gap:0.35rem; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.8rem; line-height:1.45;">
                        @foreach ($candidateRevalidation['candidate_next'] as $row)
                            <li>
                                <span style="color:#e2e8f0;">{{ $row['decision'] ?? '' }}</span>
                                <span style="color:#64748b;"> | </span>
                                {{ $row['side'] ?? '' }} {{ $row['candidate_id'] ?? '' }} {{ $row['profile'] ?? '' }}
                                <span style="color:#64748b;"> | event {{ $row['event_potential_trades'] ?? 0 }} ({{ number_format((float) ($row['event_trades_per_day'] ?? 0), 2, '.', '') }}/d)</span>
                                <span style="color:#64748b;"> | {{ $row['reason'] ?? '' }}</span>
                            </li>
                        @endforeach
                    </ol>
                @endif
                @if (! empty($candidateRevalidation['blockers_focus']))
                    <div style="font-size:0.82rem; font-weight:700; color:#fca5a5; margin-bottom:0.4rem;">Blockers</div>
                    <ol style="margin:0; padding-left:1.25rem; display:grid; gap:0.3rem; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.78rem; line-height:1.45;">
                        @foreach ($candidateRevalidation['blockers_focus'] as $row)
                            <li>{{ $row['side'] ?? '' }} {{ $row['candidate_id'] ?? '' }} {{ $row['profile'] ?? '' }} <span style="color:#64748b;"> | {{ $row['reason'] ?? '' }}</span></li>
                        @endforeach
                    </ol>
                @endif
            </div>
        @endif

        @if (! empty($candidateConsistency['available']))
            @php($consistencyCounts = $candidateConsistency['counts'] ?? [])
            @php($protectedLongAudit = $candidateConsistency['protected_long'] ?? [])
            <div style="padding: 1rem; border: 1px solid #1f2937; border-radius: 0.5rem; background: #020617; color: #cbd5e1;">
                <div style="display:flex; justify-content:space-between; gap:1rem; align-items:baseline; margin-bottom:0.65rem;">
                    <div style="font-size:0.9rem; font-weight:700; color:#f8fafc;">Candidate consistency</div>
                    <div style="font-size:0.8rem; color:#94a3b8;">{{ $candidateConsistency['generated_at'] }}</div>
                </div>
                <div style="font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.82rem; color:#cbd5e1; line-height:1.45; margin-bottom:0.65rem;">
                    state={{ $candidateConsistency['state'] }};
                    critical={{ $consistencyCounts['critical_candidate_ids'] ?? 0 }};
                    focused={{ $consistencyCounts['focused_candidate_ids'] ?? 0 }};
                    errors={{ $consistencyCounts['errors'] ?? 0 }};
                    warnings={{ $consistencyCounts['warnings'] ?? 0 }};
                    aliases={{ $consistencyCounts['alias_groups'] ?? 0 }}
                </div>
                @if (! empty($protectedLongAudit))
                    <div style="font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.8rem; line-height:1.45; color:#cbd5e1; margin-bottom:0.65rem;">
                        protected long:
                        sources={{ implode(',', $protectedLongAudit['sources'] ?? []) }};
                        registry={{ $protectedLongAudit['registry_status'] ?? '' }};
                        revalidation={{ $protectedLongAudit['revalidation_decision'] ?? '' }};
                        ML={{ $protectedLongAudit['ml_decision'] ?? '' }};
                        draft={{ $protectedLongAudit['long_draft_decision'] ?? '' }};
                        event
                        @foreach (($protectedLongAudit['event_potential'] ?? []) as $source => $value)
                            {{ $source }}={{ $value }}
                        @endforeach
                    </div>
                @endif
                @if (! empty($candidateConsistency['issues']))
                    <ol style="margin:0; padding-left:1.25rem; display:grid; gap:0.3rem; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.78rem; line-height:1.45;">
                        @foreach ($candidateConsistency['issues'] as $issue)
                            <li>
                                <span style="color:{{ ($issue['severity'] ?? '') === 'error' ? '#fca5a5' : '#facc15' }};">{{ $issue['severity'] ?? '' }}</span>
                                <span style="color:#64748b;"> | </span>
                                <span style="color:#e2e8f0;">{{ $issue['candidate_id'] ?? '' }}</span>
                                <span style="color:#64748b;"> | {{ $issue['message'] ?? '' }}</span>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>
        @endif

        <div style="padding: 1rem; border: 1px solid #1f2937; border-radius: 0.5rem; background: #020617; color: #cbd5e1;">
            <div style="font-size: 0.9rem; font-weight: 700; color: #f8fafc; margin-bottom: 0.6rem;">Результат</div>
            <pre style="white-space: pre-wrap; margin: 0; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 0.9rem; line-height: 1.45;">{{ trim($codex['details'] . "\n\n" . $codex['result']) }}</pre>
        </div>

        @if (! empty($candidateStats['available']) && ! empty($candidateStats['candidates']))
            <div style="padding: 1rem; border: 1px solid #1f2937; border-radius: 0.5rem; background: #020617; color: #cbd5e1;">
                <div style="display:flex; justify-content:space-between; gap:1rem; align-items:baseline; margin-bottom:0.7rem;">
                    <div style="font-size: 0.9rem; font-weight: 700; color: #f8fafc;">Кандидати live</div>
                    <div style="font-size:0.8rem; color:#94a3b8;">{{ $candidateStats['generated_at'] }}</div>
                </div>
                <div style="font-size:0.8rem; color:#94a3b8; margin-bottom:0.75rem;">
                    Останній місяць: {{ $candidateStats['last_month_definition'] }}
                </div>
                <div style="display:grid; gap:0.8rem;">
                    @foreach ($candidateStats['candidates'] as $candidate)
                        @php($tested = $candidate['tested_period']['stats'] ?? [])
                        @php($month = $candidate['last_month']['stats'] ?? [])
                        <div style="border:1px solid #1f2937; border-radius:0.45rem; padding:0.85rem; background:#0f172a;">
                            <div style="font-weight:700; color:#f8fafc; margin-bottom:0.35rem;">
                                {{ $candidate['component'] ?? '' }} / {{ $candidate['lane'] ?? '' }}
                            </div>
                            <div style="font-size:0.8rem; color:#94a3b8; margin-bottom:0.55rem;">
                                {{ $candidate['side'] ?? '' }} · {{ $candidate['profile'] ?? '' }} · threshold {{ $candidate['threshold'] ?? '' }} · cooldown {{ $candidate['cooldown_min'] ?? '' }}m
                            </div>
                            <div style="display:grid; gap:0.35rem; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.82rem; line-height:1.45;">
                                <div>
                                    <span style="color:#94a3b8;">Тест:</span>
                                    {{ $candidate['tested_period']['from'] ?? '' }} → {{ $candidate['tested_period']['to'] ?? '' }};
                                    entries {{ $tested['trades'] ?? 0 }},
                                    net {{ $tested['net_pct'] ?? 0 }}%,
                                    PF {{ $tested['pf'] ?? 0 }},
                                    win {{ $tested['win_pct'] ?? 0 }}%,
                                    worst {{ $tested['worst_pct'] ?? 0 }}%,
                                    best {{ $tested['best_pct'] ?? 0 }}%
                                </div>
                                <div>
                                    <span style="color:#94a3b8;">30d:</span>
                                    {{ $candidate['last_month']['from'] ?? '' }} → {{ $candidate['last_month']['to'] ?? '' }};
                                    entries {{ $month['trades'] ?? 0 }},
                                    net {{ $month['net_pct'] ?? 0 }}%,
                                    PF {{ $month['pf'] ?? 0 }},
                                    win {{ $month['win_pct'] ?? 0 }}%,
                                    worst {{ $month['worst_pct'] ?? 0 }}%,
                                    best {{ $month['best_pct'] ?? 0 }}%
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if (! empty($promotionScorecards['available']) && ! empty($promotionScorecards['top']))
            <div style="padding: 1rem; border: 1px solid #1f2937; border-radius: 0.5rem; background: #020617; color: #cbd5e1;">
                <div style="display:flex; justify-content:space-between; gap:1rem; align-items:baseline; margin-bottom:0.7rem;">
                    <div style="font-size: 0.9rem; font-weight: 700; color: #f8fafc;">Promotion scorecards</div>
                    <div style="font-size:0.8rem; color:#94a3b8;">{{ $promotionScorecards['generated_at'] }}</div>
                </div>
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(14rem,1fr)); gap:0.45rem; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.8rem; color:#94a3b8; margin-bottom:0.75rem;">
                    <div>
                        router={{ $promotionScorecards['counts']['router_ready'] ?? 0 }};
                        shadow={{ $promotionScorecards['counts']['shadow_watch'] ?? 0 }};
                        research={{ $promotionScorecards['counts']['research'] ?? 0 }};
                        library={{ $promotionScorecards['counts']['regime_library'] ?? 0 }}
                    </div>
                    <div>
                        long r/s/re={{ $promotionScorecards['by_side']['long']['router_ready'] ?? 0 }}/{{ $promotionScorecards['by_side']['long']['shadow_watch'] ?? 0 }}/{{ $promotionScorecards['by_side']['long']['research'] ?? 0 }};
                        short r/s/re={{ $promotionScorecards['by_side']['short']['router_ready'] ?? 0 }}/{{ $promotionScorecards['by_side']['short']['shadow_watch'] ?? 0 }}/{{ $promotionScorecards['by_side']['short']['research'] ?? 0 }}
                    </div>
                    <div>
                        op known={{ $promotionScorecards['operating_coverage']['available'] ?? 0 }};
                        stream={{ $promotionScorecards['operating_coverage']['stream_available'] ?? 0 }};
                        op unknown={{ $promotionScorecards['operating_coverage']['unknown'] ?? 0 }}
                    </div>
                </div>
                <ol style="margin:0; padding-left:1.25rem; display:grid; gap:0.42rem; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.82rem; line-height:1.45;">
                    @foreach ($promotionScorecards['top'] as $candidate)
                        @php($metrics = $candidate['metrics'] ?? [])
                        @php($blockers = $candidate['blockers'] ?? [])
                        @php($ml = $candidate['ml'] ?? [])
                        @php($operating = $candidate['operating'] ?? [])
                        @php($role = $candidate['role'] ?? '')
                        @php($roleColor = [
                            'router_ready' => '#86efac',
                            'shadow_watch' => '#93c5fd',
                            'research' => '#fde68a',
                            'regime_library' => '#c4b5fd',
                            'archive_only' => '#fca5a5',
                        ][$role] ?? '#e2e8f0')
                        <li>
                            <span style="color:{{ $roleColor }};">{{ $role }}</span>
                            <span style="color:#64748b;"> | </span>
                            <span style="color:#e2e8f0;">{{ $candidate['side'] ?? '' }}</span>
                            <span style="color:#64748b;"> | </span>
                            <span style="color:#cbd5e1;">{{ $candidate['candidate_id'] ?? ($candidate['family'] ?? '') }}</span>
                            @if (! empty($candidate['profile']))
                                <span style="color:#64748b;"> / {{ $candidate['profile'] }}</span>
                            @endif
                            <span style="color:#64748b;"> | </span>
                            <span style="color:#cbd5e1;">
                                potential {{ $metrics['trades'] ?? 0 }},
                                PF {{ $metrics['pf'] ?? 0 }},
                                combo {{ $metrics['combo_pf'] ?? 'n/a' }},
                                raw current rows(debug) {{ $metrics['current_signals'] ?? ($metrics['current_signal_rows'] ?? 0) }}
                            </span>
                            @if (! empty($operating['available']))
                                <span style="color:#64748b;"> | </span>
                                <span style="color:#cbd5e1;">
                                    op {{ $operating['activity_label'] ?? 'unknown' }},
                                    {{ number_format((float) ($operating['trades_per_active_day'] ?? 0), 2, '.', '') }}/d,
                                    net/d {{ number_format((float) ($operating['net_per_active_day'] ?? 0), 2, '.', '') }},
                                    PF {{ number_format((float) ($operating['operating_pf'] ?? 0), 2, '.', '') }}
                                </span>
                            @else
                                <span style="color:#64748b;"> | </span>
                                <span style="color:#fbbf24;">op unknown</span>
                            @endif
                            @if (! empty($ml['decision']))
                                <span style="color:#64748b;"> | ML {{ $ml['decision'] }} {{ $ml['score'] ?? '' }}</span>
                            @endif
                            @if (! empty($blockers))
                                <span style="color:#64748b;"> | </span>
                                <span style="color:#fca5a5;">{{ implode(', ', array_slice($blockers, 0, 3)) }}</span>
                            @endif
                        </li>
                    @endforeach
                </ol>
                @if (! empty($promotionScorecards['operating_gap_top']))
                    <div style="margin-top:0.75rem; padding-top:0.65rem; border-top:1px solid #1f2937; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.78rem; color:#94a3b8;">
                        <div style="color:#fbbf24; margin-bottom:0.35rem;">op gaps to confirm:</div>
                        <ol style="margin:0; padding-left:1.25rem; display:grid; gap:0.25rem;">
                            @foreach (array_slice($promotionScorecards['operating_gap_top'], 0, 5) as $gap)
                                <li>
                                    <span style="color:#cbd5e1;">{{ $gap['side'] ?? '' }} {{ $gap['candidate_id'] ?? '' }}</span>
                                    @if (! empty($gap['profile']))
                                        <span style="color:#64748b;"> / {{ $gap['profile'] }}</span>
                                    @endif
                                    <span style="color:#64748b;">
                                        | {{ $gap['role'] ?? '' }},
                                        potential {{ $gap['trades'] ?? 0 }},
                                        PF {{ $gap['pf'] ?? 0 }},
                                        raw current rows(debug) {{ $gap['current_signals'] ?? 0 }}
                                    </span>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                @endif
            </div>
        @endif

        @if (! empty($longRegistry['available']) && ! empty($longRegistry['top']))
            <div style="padding: 1rem; border: 1px solid #1f2937; border-radius: 0.5rem; background: #020617; color: #cbd5e1;">
                <div style="display:flex; justify-content:space-between; gap:1rem; align-items:baseline; margin-bottom:0.7rem;">
                    <div style="font-size: 0.9rem; font-weight: 700; color: #f8fafc;">Long shortlist</div>
                    <div style="font-size:0.8rem; color:#94a3b8;">{{ $longRegistry['generated_at'] }}</div>
                </div>
                <div style="font-size:0.82rem; color:#94a3b8; margin-bottom:0.65rem;">
                    review={{ $longRegistry['counts']['live_ready_review'] ?? 0 }};
                    router={{ $longRegistry['counts']['router_shortlist'] ?? 0 }};
                    shadow={{ $longRegistry['counts']['shadow_only'] ?? 0 }};
                    library={{ $longRegistry['counts']['regime_library'] ?? 0 }}
                </div>
                <ol style="margin:0; padding-left:1.25rem; display:grid; gap:0.4rem; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.82rem; line-height:1.45;">
                    @foreach ($longRegistry['top'] as $candidate)
                        @php($metrics = $candidate['metrics'] ?? [])
                        @php($scores = $candidate['scores'] ?? [])
                        <li>
                            <span style="color:#e2e8f0;">{{ $candidate['status'] ?? '' }}</span>
                            <span style="color:#64748b;"> | </span>
                            <span style="color:#e2e8f0;">score {{ $scores['final'] ?? 0 }}</span>
                            <span style="color:#64748b;"> | </span>
                            <span style="color:#cbd5e1;">{{ $candidate['lane'] ?? '' }} {{ $candidate['exit_policy_real'] ?? '' }} cd{{ $candidate['cooldown_min'] ?? 0 }}</span>
                            <span style="color:#64748b;"> | </span>
                            <span style="color:#cbd5e1;">potential {{ $metrics['trades'] ?? 0 }}, PF {{ $metrics['pf'] ?? 0 }}, DD {{ $metrics['max_drawdown_pct'] ?? 0 }}%, raw current rows(debug) {{ $metrics['current_signal_rows'] ?? 0 }}</span>
                            <span style="color:#64748b;"> | </span>
                            <span style="color:#94a3b8;">{{ $candidate['condition_text'] ?? '' }}</span>
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif

        @if (! empty($longRouterPrep['available']) && ! empty($longRouterPrep['candidates']))
            <div style="padding: 1rem; border: 1px solid #1f2937; border-radius: 0.5rem; background: #020617; color: #cbd5e1;">
                <div style="display:flex; justify-content:space-between; gap:1rem; align-items:baseline; margin-bottom:0.7rem;">
                    <div style="font-size: 0.9rem; font-weight: 700; color: #f8fafc;">Long router prep</div>
                    <div style="font-size:0.8rem; color:#94a3b8;">{{ $longRouterPrep['generated_at'] }}</div>
                </div>
                <div style="font-size:0.82rem; color:#94a3b8; margin-bottom:0.65rem;">
                    {{ $longRouterPrep['state'] }};
                    selected={{ $longRouterPrep['counts']['selected'] ?? 0 }};
                    live={{ ! empty($longRouterPrep['live_enabled']) ? 'yes' : 'no' }};
                    audit={{ $longRouterPrep['audit_state'] ?: 'n/a' }};
                    ok={{ $longRouterPrep['audit_counts']['ok_for_shadow_prep'] ?? 0 }}
                </div>
                @if (! empty($longRouterPrep['audit_blockers']))
                    <div style="font-size:0.82rem; color:#fca5a5; margin-bottom:0.55rem;">
                        {{ implode('; ', $longRouterPrep['audit_blockers']) }}
                    </div>
                @elseif (! empty($longRouterPrep['audit_warnings']))
                    <div style="font-size:0.82rem; color:#facc15; margin-bottom:0.55rem;">
                        {{ implode('; ', $longRouterPrep['audit_warnings']) }}
                    </div>
                @endif
                <ol style="margin:0; padding-left:1.25rem; display:grid; gap:0.4rem; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.82rem; line-height:1.45;">
                    @foreach ($longRouterPrep['candidates'] as $candidate)
                        @php($metrics = $candidate['metrics'] ?? [])
                        <li>
                            <span style="color:#e2e8f0;">{{ $candidate['id'] ?? '' }}</span>
                            <span style="color:#64748b;"> | </span>
                            <span style="color:#e2e8f0;">{{ $candidate['status'] ?? '' }}</span>
                            <span style="color:#64748b;"> | </span>
                            <span style="color:#cbd5e1;">{{ $candidate['entry_exit_profile']['profile'] ?? '' }}</span>
                            <span style="color:#64748b;"> | </span>
                            <span style="color:#cbd5e1;">PF {{ $metrics['pf'] ?? 0 }}, raw current rows(debug) {{ $metrics['current_signal_rows'] ?? 0 }}</span>
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif

        @if (! empty($longShadowFeed['available']) && ! empty($longShadowFeed['candidates']))
            <div style="padding: 1rem; border: 1px solid #1f2937; border-radius: 0.5rem; background: #020617; color: #cbd5e1;">
                <div style="display:flex; justify-content:space-between; gap:1rem; align-items:baseline; margin-bottom:0.7rem;">
                    <div style="font-size: 0.9rem; font-weight: 700; color: #f8fafc;">Long shadow feed</div>
                    <div style="font-size:0.8rem; color:#94a3b8;">{{ $longShadowFeed['generated_at'] }}</div>
                </div>
                <div style="font-size:0.82rem; color:#94a3b8; margin-bottom:0.65rem;">
                    max={{ $longShadowFeed['max_open'] }};
                    fresh rows={{ $longShadowFeed['counts']['fresh_signals'] ?? 0 }};
                    candidates={{ $longShadowFeed['counts']['candidates'] ?? 0 }};
                    with rows={{ $longShadowFeed['counts']['with_signals'] ?? 0 }}
                </div>
                <ol style="margin:0; padding-left:1.25rem; display:grid; gap:0.4rem; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.82rem; line-height:1.45;">
                    @foreach ($longShadowFeed['candidates'] as $candidate)
                        <li>
                            <span style="color:#e2e8f0;">{{ ! empty($candidate['has_fresh_signal']) ? 'fresh' : 'idle' }}</span>
                            <span style="color:#64748b;"> | </span>
                            <span style="color:#e2e8f0;">{{ $candidate['id'] ?? '' }}</span>
                            <span style="color:#64748b;"> | </span>
                            <span style="color:#cbd5e1;">matched rows {{ $candidate['signal_count'] ?? 0 }}, fresh {{ $candidate['fresh_signal_count'] ?? 0 }}</span>
                            <span style="color:#64748b;"> | </span>
                            <span style="color:#94a3b8;">{{ $candidate['condition_text'] ?? '' }}</span>
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif

        @if (! empty($currentRegimeLong['available']) && ! empty($currentRegimeLong['best']))
            @php($best = $currentRegimeLong['best'])
            <div style="padding: 1rem; border: 1px solid #1f2937; border-radius: 0.5rem; background: #020617; color: #cbd5e1;">
                <div style="display:flex; justify-content:space-between; gap:1rem; align-items:baseline; margin-bottom:0.7rem;">
                    <div style="font-size: 0.9rem; font-weight: 700; color: #f8fafc;">Current regime long</div>
                    <div style="font-size:0.8rem; color:#94a3b8;">{{ $currentRegimeLong['generated_at'] }}</div>
                </div>
                <div style="font-size:0.82rem; color:#94a3b8; margin-bottom:0.65rem;">
                    {{ $currentRegimeLong['decision'] }};
                    regime={{ $currentRegimeLong['current_major'] }};
                    filter={{ $currentRegimeLong['regime_filter'] }};
                    rows={{ $currentRegimeLong['regime_rows'] }}
                </div>
                <div style="font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.82rem; line-height:1.45; color:#cbd5e1;">
                    best {{ $best['lane'] ?? '' }} {{ $best['exit_policy_real'] ?? '' }} cd{{ $best['cooldown_min'] ?? 0 }};
                    PF {{ $best['pf'] ?? 0 }};
                    potential {{ $best['trades'] ?? 0 }};
                    win {{ $best['win_pct'] ?? 0 }}%;
                    DD {{ $best['max_drawdown_pct'] ?? 0 }}%;
                    raw current rows(debug) {{ $best['current_signal_rows'] ?? 0 }};
                    {{ $best['condition_text'] ?? '' }}
                </div>
            </div>
        @endif

        @if (! empty($shadowPreflight['available']))
            <div style="padding: 1rem; border: 1px solid #1f2937; border-radius: 0.5rem; background: #020617; color: #cbd5e1;">
                <div style="display:flex; justify-content:space-between; gap:1rem; align-items:baseline; margin-bottom:0.7rem;">
                    <div style="font-size: 0.9rem; font-weight: 700; color: #f8fafc;">Shadow preflight</div>
                    <div style="font-size:0.8rem; color:#94a3b8;">{{ $shadowPreflight['generated_at'] }}</div>
                </div>
                <div style="font-size:0.82rem; color:#94a3b8; margin-bottom:0.65rem;">
                    state={{ $shadowPreflight['state'] }};
                    allowed={{ $shadowPreflight['counts']['allow_shadow'] ?? 0 }};
                    blockers={{ $shadowPreflight['counts']['blockers'] ?? 0 }};
                    warnings={{ $shadowPreflight['counts']['warnings'] ?? 0 }}
                </div>
                @if (! empty($shadowPreflight['blockers']))
                    <div style="font-size:0.82rem; color:#fca5a5; margin-bottom:0.55rem;">
                        {{ implode('; ', $shadowPreflight['blockers']) }}
                    </div>
                @endif
                @if (! empty($shadowPreflight['candidates']))
                    <ol style="margin:0; padding-left:1.25rem; display:grid; gap:0.4rem; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.82rem; line-height:1.45;">
                        @foreach ($shadowPreflight['candidates'] as $candidate)
                            <li>
                                <span style="color:#e2e8f0;">{{ $candidate['shadow_preflight'] ?? '' }}</span>
                                <span style="color:#64748b;"> | </span>
                                <span style="color:#e2e8f0;">{{ $candidate['side'] ?? '' }} {{ $candidate['profile'] ?? '' }}</span>
                                <span style="color:#64748b;"> | </span>
                                <span style="color:#cbd5e1;">ml {{ $candidate['ml_score'] ?? 0 }}</span>
                                @if (! empty($candidate['metrics']))
                                    <span style="color:#64748b;"> | </span>
                                    <span style="color:#94a3b8;">PF {{ $candidate['metrics']['pf'] ?? 0 }}, potential {{ $candidate['metrics']['trades'] ?? 0 }}, raw current rows(debug) {{ $candidate['metrics']['current_signal_rows'] ?? 0 }}</span>
                                @endif
                                @if (! empty($candidate['warnings']))
                                    <div style="color:#fde68a; margin-top:0.15rem;">warn: {{ implode('; ', array_slice($candidate['warnings'], 0, 3)) }}</div>
                                @endif
                                @if (! empty($candidate['blockers']))
                                    <div style="color:#fca5a5; margin-top:0.15rem;">block: {{ implode('; ', array_slice($candidate['blockers'], 0, 3)) }}</div>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>
        @endif

        @if (! empty($marketplaceTrace['available']))
            <div style="padding: 1rem; border: 1px solid #1f2937; border-radius: 0.5rem; background: #020617; color: #cbd5e1;">
                <div style="display:flex; justify-content:space-between; gap:1rem; align-items:baseline; margin-bottom:0.7rem;">
                    <div style="font-size: 0.9rem; font-weight: 700; color: #f8fafc;">Marketplace decision trace</div>
                    <div style="font-size:0.8rem; color:#94a3b8;">{{ $marketplaceTrace['generated_at'] }}</div>
                </div>
                <div style="font-size:0.82rem; color:#94a3b8; margin-bottom:0.65rem;">
                    total={{ $marketplaceTrace['summary']['total'] ?? 0 }};
                    unique={{ $marketplaceTrace['summary']['top_unique'] ?? 0 }};
                    allowed={{ $marketplaceTrace['summary']['live_monitor_allowed'] ?? 0 }};
                    preflight={{ $marketplaceTrace['summary']['preflight_pass'] ?? 0 }};
                    review={{ $marketplaceTrace['summary']['review_candidate'] ?? 0 }};
                    blocked={{ $marketplaceTrace['summary']['blocked'] ?? 0 }}
                </div>
                @if (! empty($marketplaceTrace['active_candidates']))
                    <div style="margin-bottom:0.6rem; display:grid; gap:0.35rem; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.82rem; line-height:1.45;">
                        @foreach ($marketplaceTrace['active_candidates'] as $item)
                            <div>
                                <span style="color:#38bdf8;">active {{ $item['state'] ?? '' }}</span>
                                <span style="color:#64748b;"> | </span>
                                <span style="color:#e2e8f0;">{{ $item['side'] ?? '' }} {{ $item['lane'] ?? '' }} {{ $item['profile'] ?? '' }}</span>
                                <span style="color:#64748b;"> | </span>
                                <span style="color:#94a3b8;">{{ $item['candidate_id'] ?? '' }}</span>
                                @if (! empty($item['event_driven']['available']))
                                    <span style="color:#64748b;"> | </span>
                                    <span style="color:#86efac;">event {{ $item['event_driven']['potential_entries'] ?? 0 }} ({{ number_format((float) ($item['event_driven']['entries_per_day'] ?? 0), 2, '.', '') }}/d)</span>
                                @endif
                                @if (! empty($item['reasons']['positives']))
                                    <span style="color:#64748b;"> | </span>
                                    <span style="color:#94a3b8;">{{ implode('; ', array_slice($item['reasons']['positives'], 0, 3)) }}</span>
                                @endif
                                @if (! empty($item['reasons']['warnings']))
                                    <div style="color:#fde68a; margin-top:0.15rem;">warn: {{ implode('; ', array_slice($item['reasons']['warnings'], 0, 3)) }}</div>
                                @endif
                                @if (! empty($item['reasons']['blockers']))
                                    <div style="color:#fca5a5; margin-top:0.15rem;">block: {{ implode('; ', array_slice($item['reasons']['blockers'], 0, 3)) }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
                @if (! empty($marketplaceTrace['top_unique']))
                    <ol style="margin:0; padding-left:1.25rem; display:grid; gap:0.45rem; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.82rem; line-height:1.45;">
                        @foreach ($marketplaceTrace['top_unique'] as $item)
                            <li>
                                <span style="color:#e2e8f0;">{{ $item['state'] ?? '' }}</span>
                                <span style="color:#64748b;"> | </span>
                                <span style="color:#e2e8f0;">{{ $item['side'] ?? '' }} {{ $item['lane'] ?? '' }} {{ $item['profile'] ?? '' }}</span>
                                <span style="color:#64748b;"> | </span>
                                <span style="color:#cbd5e1;">router {{ $item['router_score'] ?? 0 }}, ml {{ $item['ml_score'] ?? 0 }} {{ $item['ml_decision'] ?? '' }}</span>
                                @if (! empty($item['event_driven']['available']))
                                    <span style="color:#64748b;"> | </span>
                                    <span style="color:#86efac;">event {{ $item['event_driven']['potential_entries'] ?? 0 }} ({{ number_format((float) ($item['event_driven']['entries_per_day'] ?? 0), 2, '.', '') }}/d)</span>
                                @endif
                                @if (! empty($item['condition_text']))
                                    <span style="color:#64748b;"> | </span>
                                    <span style="color:#94a3b8;">{{ $item['condition_text'] }}</span>
                                @endif
                                @if (! empty($item['reasons']['positives']))
                                    <span style="color:#64748b;"> | </span>
                                    <span style="color:#94a3b8;">{{ implode('; ', array_slice($item['reasons']['positives'], 0, 3)) }}</span>
                                @endif
                                @if (! empty($item['reasons']['blockers']))
                                    <span style="color:#64748b;"> | </span>
                                    <span style="color:#fca5a5;">{{ implode('; ', array_slice($item['reasons']['blockers'], 0, 3)) }}</span>
                                @endif
                                @if (! empty($item['reasons']['warnings']))
                                    <div style="color:#fde68a; margin-top:0.15rem;">warn: {{ implode('; ', array_slice($item['reasons']['warnings'], 0, 2)) }}</div>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>
        @endif

        @if (! empty($liveEntryPreview['available']))
            <div style="padding: 1rem; border: 1px solid #1f2937; border-radius: 0.5rem; background: #020617; color: #cbd5e1;">
                <div style="display:flex; justify-content:space-between; gap:1rem; align-items:baseline; margin-bottom:0.7rem;">
                    <div style="font-size: 0.9rem; font-weight: 700; color: #f8fafc;">Live entry decision</div>
                    <div style="font-size:0.8rem; color:#94a3b8;">{{ $liveEntryPreview['generated_at'] }}</div>
                </div>
                <div style="font-size:0.82rem; color:#94a3b8; margin-bottom:0.65rem;">
                    bots={{ $liveEntryPreview['summary']['bots'] ?? 0 }};
                    would_allow={{ $liveEntryPreview['summary']['would_allow_entry'] ?? 0 }};
                    waiting={{ $liveEntryPreview['summary']['waiting_fresh_signal'] ?? 0 }};
                    blocked={{ $liveEntryPreview['summary']['blocked'] ?? 0 }};
                    bot_not_running={{ $liveEntryPreview['summary']['bot_not_running'] ?? 0 }};
                    event_fresh_or_open={{ $liveEntryPreview['summary']['event_fresh_or_open'] ?? 0 }};
                    posMode={{ $liveEntryPreview['okx_position_check']['posMode'] ?? '' }};
                    open_pos={{ $liveEntryPreview['okx_position_check']['positions']['open_count'] ?? 0 }}
                </div>
                @if (! empty($liveEntryPreview['decisions']))
                    <ol style="margin:0; padding-left:1.25rem; display:grid; gap:0.45rem; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.82rem; line-height:1.45;">
                        @foreach ($liveEntryPreview['decisions'] as $item)
                            <li>
                                <span style="color:#e2e8f0;">{{ $item['decision'] ?? '' }}</span>
                                <span style="color:#64748b;"> | </span>
                                <span style="color:#e2e8f0;">{{ $item['side'] ?? '' }} {{ $item['lane'] ?? '' }} {{ $item['profile'] ?? '' }}</span>
                                <span style="color:#64748b;"> | </span>
                                <span style="color:#cbd5e1;">fresh {{ $item['fresh_signal_count'] ?? 0 }}, bot {{ $item['bot_status'] ?? '' }}</span>
                                @if (! empty($item['event_driven']['available']))
                                    <span style="color:#64748b;"> | </span>
                                    <span style="color:#86efac;">event {{ $item['event_driven']['potential_entries'] ?? 0 }} ({{ number_format((float) ($item['event_driven']['entries_per_day'] ?? 0), 2, '.', '') }}/d), fresh/open {{ $item['event_driven']['fresh_or_open'] ?? 0 }}</span>
                                    @if (! empty($item['event_driven']['last_potential_kyiv']))
                                        <span style="color:#64748b;"> | </span>
                                        <span style="color:#94a3b8;">last {{ $item['event_driven']['last_potential_kyiv'] }}</span>
                                    @endif
                                @endif
                                <span style="color:#64748b;"> | </span>
                                <span style="color:#94a3b8;">trace {{ $item['trace_state'] ?? '' }}, ml {{ $item['ml_score'] ?? 0 }} {{ $item['ml_decision'] ?? '' }}</span>
                                @if (! empty($item['reasons']))
                                    <span style="color:#64748b;"> | </span>
                                    <span style="color:#94a3b8;">{{ implode('; ', array_slice($item['reasons'], 0, 3)) }}</span>
                                @endif
                                @if (! empty($item['position_summary']) && is_array($item['position_summary']))
                                    @php($pos = $item['position_summary'])
                                    @php($posWarnings = is_array($pos['warnings'] ?? null) ? $pos['warnings'] : [])
                                    <div style="color:#bae6fd; margin-top:0.15rem;">
                                        pos:
                                        {{ $pos['side'] ?? '' }}
                                        entry {{ $pos['avg_price'] ?? 'n/a' }},
                                        mark {{ $pos['mark_price'] ?? 'n/a' }},
                                        pnl {{ $pos['pnl_usdt'] ?? 0 }} USDT / {{ $pos['pnl_pct'] ?? 0 }}%;
                                        bars {{ $pos['bars_held'] ?? 0 }}/{{ $pos['max_holding_bars'] ?? 0 }},
                                        left {{ $pos['remaining_bars'] ?? 'n/a' }},
                                        time-exit {{ $pos['time_exit_eta_kyiv'] ?? '' }};
                                        tp {{ $pos['take_profit_pct'] ?? 0 }} / sl {{ $pos['stop_loss_pct'] ?? 0 }}
                                    </div>
                                    @if (! empty($posWarnings))
                                        <div style="color:#fbbf24; margin-top:0.15rem;">position warn: {{ implode('; ', array_slice($posWarnings, 0, 3)) }}</div>
                                    @endif
                                @endif
                                @if (! empty($item['trace_reasons']['positives']))
                                    <div style="color:#86efac; margin-top:0.15rem;">why: {{ implode('; ', array_slice($item['trace_reasons']['positives'], 0, 4)) }}</div>
                                @endif
                                @if (! empty($item['trace_reasons']['warnings']))
                                    <div style="color:#fde68a; margin-top:0.15rem;">warn: {{ implode('; ', array_slice($item['trace_reasons']['warnings'], 0, 3)) }}</div>
                                @endif
                                @if (! empty($item['trace_reasons']['blockers']))
                                    <div style="color:#fca5a5; margin-top:0.15rem;">block: {{ implode('; ', array_slice($item['trace_reasons']['blockers'], 0, 3)) }}</div>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>
        @endif

        @if (! empty($liveExitWhatif['available']) && ! empty($liveExitWhatif['latest']))
            @php($exitLatest = $liveExitWhatif['latest'])
            @php($actualExit = is_array($exitLatest['actual'] ?? null) ? $exitLatest['actual'] : [])
            @php($bestExit = is_array($exitLatest['best'] ?? null) ? $exitLatest['best'] : [])
            @php($topExit = is_array($exitLatest['top'] ?? null) ? array_slice($exitLatest['top'], 0, 5) : [])
            <div style="padding: 1rem; border: 1px solid #1f2937; border-radius: 0.5rem; background: #020617; color: #cbd5e1;">
                <div style="display:flex; justify-content:space-between; gap:1rem; align-items:baseline; margin-bottom:0.7rem;">
                    <div style="font-size: 0.9rem; font-weight: 700; color: #f8fafc;">Live exit what-if</div>
                    <div style="font-size:0.8rem; color:#94a3b8;">{{ $liveExitWhatif['generated_at'] }}</div>
                </div>
                <div style="font-size:0.82rem; color:#94a3b8; margin-bottom:0.65rem;">
                    trade #{{ $exitLatest['trade_id'] ?? '' }};
                    actual={{ $actualExit['exit_reason'] ?? '' }} {{ $actualExit['profile'] ?? '' }} {{ $actualExit['pnl_usdt'] ?? 0 }} USDT;
                    best={{ $bestExit['exit_reason'] ?? '' }} {{ $bestExit['profile'] ?? '' }} {{ $bestExit['pnl_usdt'] ?? 0 }} USDT
                    @if (! empty($bestExit['exit_time_kyiv']))
                        at {{ $bestExit['exit_time_kyiv'] }}
                    @endif
                </div>
                @if (! empty($topExit))
                    <ol style="margin:0; padding-left:1.25rem; display:grid; gap:0.35rem; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.82rem; line-height:1.45;">
                        @foreach ($topExit as $item)
                            <li>
                                <span style="color:#e2e8f0;">{{ $item['profile'] ?? '' }}</span>
                                <span style="color:#64748b;"> | </span>
                                <span style="color:#cbd5e1;">{{ $item['exit_reason'] ?? '' }} {{ $item['pnl_usdt'] ?? 0 }} USDT</span>
                                <span style="color:#64748b;"> | </span>
                                <span style="color:#94a3b8;">bars {{ $item['bars_held'] ?? 0 }}</span>
                                @if (! empty($item['exit_time_kyiv']))
                                    <span style="color:#64748b;"> | </span>
                                    <span style="color:#94a3b8;">{{ $item['exit_time_kyiv'] }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>
        @endif

        @if (! empty($exitRobustness['available']))
            @php($exitDecision = is_array($exitRobustness['decision'] ?? null) ? $exitRobustness['decision'] : [])
            @php($weightedExit = is_array($exitRobustness['weighted_regime_decision'] ?? null) ? $exitRobustness['weighted_regime_decision'] : [])
            @php($periodContext = is_array($weightedExit['period_context'] ?? null) ? $weightedExit['period_context'] : [])
            @php($currentPeriod = is_array($periodContext['current_period'] ?? null) ? $periodContext['current_period'] : [])
            @php($weightedRanking = is_array($weightedExit['ranking'] ?? null) ? array_slice($weightedExit['ranking'], 0, 6) : [])
            @php($scopeData = is_array($weightedExit['scopes'] ?? null) ? $weightedExit['scopes'] : [])
            <div style="padding: 1rem; border: 1px solid #1f2937; border-radius: 0.5rem; background: #020617; color: #cbd5e1;">
                <div style="display:flex; justify-content:space-between; gap:1rem; align-items:baseline; margin-bottom:0.7rem;">
                    <div style="font-size: 0.9rem; font-weight: 700; color: #f8fafc;">Exit robustness</div>
                    <div style="font-size:0.8rem; color:#94a3b8;">{{ $exitRobustness['generated_at'] }}</div>
                </div>
                <div style="font-size:0.82rem; color:#94a3b8; margin-bottom:0.65rem;">
                    {{ $exitDecision['summary'] ?? '' }}
                    @if (! empty($currentPeriod))
                        <span style="color:#64748b;"> | </span>
                        current={{ $currentPeriod['major_key'] ?? '' }} {{ $currentPeriod['start_kyiv'] ?? '' }}-{{ $currentPeriod['end_kyiv'] ?? '' }};
                        similar_periods={{ $periodContext['similar_periods_count'] ?? 0 }}
                    @endif
                </div>
                @if (! empty($weightedRanking))
                    <div style="font-size:0.78rem; color:#64748b; margin-bottom:0.35rem;">weighted: history + similar regime + current period</div>
                    <ol style="margin:0; padding-left:1.25rem; display:grid; gap:0.35rem; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.82rem; line-height:1.45;">
                        @foreach ($weightedRanking as $item)
                            <li>
                                <span style="color:#e2e8f0;">{{ $item['policy'] ?? '' }}</span>
                                <span style="color:#64748b;"> | </span>
                                <span style="color:#cbd5e1;">weighted {{ $item['weighted_score'] ?? 0 }}</span>
                            </li>
                        @endforeach
                    </ol>
                @endif
                @if (! empty($scopeData['similar_regime']) || ! empty($scopeData['current_period']))
                    @php($similarTop = is_array($scopeData['similar_regime'] ?? null) ? array_slice($scopeData['similar_regime'], 0, 3) : [])
                    @php($currentTop = is_array($scopeData['current_period'] ?? null) ? array_slice($scopeData['current_period'], 0, 3) : [])
                    <div style="margin-top:0.65rem; display:grid; gap:0.3rem; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.8rem; line-height:1.4;">
                        @if (! empty($similarTop))
                            <div style="color:#94a3b8;">
                                similar:
                                @foreach ($similarTop as $item)
                                    <span style="color:#cbd5e1;">{{ $item['exit_policy_real'] ?? '' }} PF {{ $item['pf'] ?? 0 }} potential {{ $item['trades'] ?? 0 }}</span>@if (! $loop->last)<span style="color:#64748b;">; </span>@endif
                                @endforeach
                            </div>
                        @endif
                        @if (! empty($currentTop))
                            <div style="color:#94a3b8;">
                                current:
                                @foreach ($currentTop as $item)
                                    <span style="color:#cbd5e1;">{{ $item['exit_policy_real'] ?? '' }} potential {{ $item['trades'] ?? 0 }}</span>@if (! $loop->last)<span style="color:#64748b;">; </span>@endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                @elseif (! empty($exitRobustness['live_like']))
                    <ol style="margin:0; padding-left:1.25rem; display:grid; gap:0.35rem; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.82rem; line-height:1.45;">
                        @foreach ($exitRobustness['live_like'] as $item)
                            <li>
                                <span style="color:#e2e8f0;">{{ $item['exit_policy_real'] ?? '' }}</span>
                                <span style="color:#64748b;"> | </span>
                                <span style="color:#cbd5e1;">PF {{ $item['pf'] ?? 0 }}, net {{ $item['net_pct'] ?? 0 }}%, potential {{ $item['trades'] ?? 0 }}</span>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>
        @endif

        @if (! empty($longLiveDraft['available']))
            @php($routerRisk = is_array($longLiveDraft['router_risk_decision'] ?? null) ? $longLiveDraft['router_risk_decision'] : [])
            <div style="padding: 1rem; border: 1px solid #1f2937; border-radius: 0.5rem; background: #020617; color: #cbd5e1;">
                <div style="display:flex; justify-content:space-between; gap:1rem; align-items:baseline; margin-bottom:0.7rem;">
                    <div style="font-size: 0.9rem; font-weight: 700; color: #f8fafc;">Long live draft</div>
                    <div style="font-size:0.8rem; color:#94a3b8;">{{ $longLiveDraft['generated_at'] }}</div>
                </div>
                <div style="font-size:0.82rem; color:#94a3b8; margin-bottom:0.65rem;">
                    prep={{ $longLiveDraft['prep_state'] }};
                    launch={{ $longLiveDraft['launch_state'] }};
                    entry={{ $longLiveDraft['entry_state'] ?? '' }};
                    fresh={{ $longLiveDraft['counts']['fresh_target_signals'] ?? 0 }};
                    prep_blockers={{ $longLiveDraft['counts']['prep_blockers'] ?? 0 }};
                    launch_blockers={{ $longLiveDraft['counts']['launch_blockers'] ?? 0 }};
                    entry_blockers={{ $longLiveDraft['counts']['entry_blockers'] ?? 0 }}
                </div>
                @if (! empty($longLiveDraft['prep_blockers']) || ! empty($longLiveDraft['launch_blockers']) || ! empty($longLiveDraft['entry_blockers']))
                    @php($longDraftBlockers = array_values(array_unique(array_merge($longLiveDraft['prep_blockers'], $longLiveDraft['launch_blockers']))))
                    <div style="font-size:0.82rem; color:#facc15; margin-bottom:0.55rem;">
                        {{ implode('; ', $longDraftBlockers) }}
                    </div>
                @endif
                <div style="font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.82rem; line-height:1.45; color:#cbd5e1;">
                    {{ $longLiveDraft['target']['id'] ?? '' }} · {{ $longLiveDraft['target']['profile'] ?? '' }} · {{ $longLiveDraft['target']['condition'] ?? '' }};
                    hist PF {{ $longLiveDraft['metrics']['pf'] ?? 0 }};
                    operating PF {{ $longLiveDraft['metrics']['operating_pf'] ?? 0 }};
                    operating {{ $longLiveDraft['metrics']['operating_trades'] ?? 0 }} ({{ $longLiveDraft['metrics']['operating_trades_per_day'] ?? 0 }}/d);
                    DD {{ $longLiveDraft['metrics']['max_drawdown_pct'] ?? 0 }}%;
                    ML {{ $longLiveDraft['ml']['decision'] ?? '' }} {{ $longLiveDraft['ml']['score'] ?? 0 }};
                    feed potential {{ $longLiveDraft['feed']['target_signal_count'] ?? 0 }};
                    feed fresh {{ $longLiveDraft['feed']['target_fresh_signal_count'] ?? 0 }}
                </div>
                @if (! empty($routerRisk))
                    <div style="margin-top:0.65rem; padding-top:0.6rem; border-top:1px solid #1f2937; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.8rem; line-height:1.45;">
                        <span style="color:#94a3b8;">router/risk:</span>
                        <span style="color:{{ ! empty($routerRisk['allow_new_live_entry']) ? '#86efac' : '#fca5a5' }};">{{ $routerRisk['final_state'] ?? '' }}</span>
                        <span style="color:#64748b;"> | mode {{ $routerRisk['decision_mode'] ?? '' }}</span>
                        <span style="color:#64748b;"> | router {{ $routerRisk['router_decision'] ?? '' }}</span>
                        <span style="color:#64748b;"> | risk {{ $routerRisk['risk_gate'] ?? '' }}</span>
                        <span style="color:#64748b;"> | entry {{ $routerRisk['entry_gate'] ?? '' }}</span>
                        <span style="color:#64748b;"> | ML {{ $routerRisk['ml_role'] ?? '' }} {{ $routerRisk['ml_decision'] ?? '' }}</span>
                        <span style="color:#64748b;"> | OKX {{ $routerRisk['okx_state'] ?? '' }}</span>
                    </div>
                    @if (! empty($longLiveDraft['next_actions']))
                        <div style="margin-top:0.45rem; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.78rem; color:#94a3b8;">
                            next: {{ implode('; ', array_slice($longLiveDraft['next_actions'], 0, 4)) }}
                        </div>
                    @endif
                @endif
            </div>
        @endif

        @if (! empty($directLongProtectedFeed['available']))
            <div style="padding: 1rem; border: 1px solid #1f2937; border-radius: 0.5rem; background: #020617; color: #cbd5e1;">
                <div style="display:flex; justify-content:space-between; gap:1rem; align-items:baseline; margin-bottom:0.7rem;">
                    <div style="font-size: 0.9rem; font-weight: 700; color: #f8fafc;">Direct protected long feed</div>
                    <div style="font-size:0.8rem; color:#94a3b8;">{{ $directLongProtectedFeed['generated_at'] }}</div>
                </div>
                <div style="font-size:0.82rem; color:#94a3b8; margin-bottom:0.65rem;">
                    state={{ $directLongProtectedFeed['state'] }};
                    max={{ $directLongProtectedFeed['max_feature_kyiv'] ?? '' }};
                    matched={{ $directLongProtectedFeed['counts']['matched_rows'] ?? 0 }};
                    potential={{ $directLongProtectedFeed['counts']['potential_trades'] ?? 0 }};
                    fresh={{ $directLongProtectedFeed['counts']['fresh_signals'] ?? 0 }}
                </div>
                <div style="font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.82rem; line-height:1.45; color:#cbd5e1; margin-bottom:0.5rem;">
                    {{ $directLongProtectedFeed['candidate']['candidate_id'] ?? '' }} · {{ $directLongProtectedFeed['candidate']['profile'] ?? '' }} · {{ $directLongProtectedFeed['candidate']['condition_text'] ?? '' }}
                </div>
                @if (! empty($directLongProtectedFeed['recent']))
                    <ol style="margin:0; padding-left:1.2rem; font-size:0.82rem; color:#cbd5e1;">
                        @foreach ($directLongProtectedFeed['recent'] as $item)
                            <li>
                                {{ $item['kyiv'] ?? '' }} · price {{ $item['signal_price'] ?? '' }} · fresh={{ ! empty($item['fresh']) ? 'yes' : 'no' }}
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>
        @endif

        @if (! empty($mlShadow['available']))
            <div style="padding: 1rem; border: 1px solid #1f2937; border-radius: 0.5rem; background: #020617; color: #cbd5e1;">
                <div style="display:flex; justify-content:space-between; gap:1rem; align-items:baseline; margin-bottom:0.7rem;">
                    <div style="font-size: 0.9rem; font-weight: 700; color: #f8fafc;">ML Shadow</div>
                    <div style="font-size:0.8rem; color:#94a3b8;">{{ $mlShadow['generated_at'] }}</div>
                </div>
                <div style="font-size:0.82rem; color:#94a3b8; margin-bottom:0.65rem;">
                    mode={{ $mlShadow['mode'] }};
                    rows={{ $mlShadow['dataset_counts']['rows'] ?? 0 }};
                    labels={{ $mlShadow['dataset_counts']['labeled_rows'] ?? 0 }};
                    wins={{ $mlShadow['dataset_counts']['wins'] ?? 0 }};
                    losses={{ $mlShadow['dataset_counts']['losses'] ?? 0 }};
                    current-like labels={{ $mlShadow['regime_counts']['current_like_labeled_rows'] ?? ($mlShadow['regime_counts']['current_major_labeled_rows'] ?? 0) }}
                </div>
                @if (! empty($mlShadow['candidates']))
                    <ol style="margin:0; padding-left:1.25rem; display:grid; gap:0.4rem; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.82rem; line-height:1.45;">
                        @foreach ($mlShadow['candidates'] as $candidate)
                            @php($mlWhy = is_array($candidate['why'] ?? null) ? array_slice($candidate['why'], 0, 3) : [])
                            @php($mlWhyNot = is_array($candidate['why_not'] ?? null) ? array_slice($candidate['why_not'], 0, 3) : [])
                            @php($mlBlockers = is_array($candidate['blockers'] ?? null) ? array_slice($candidate['blockers'], 0, 2) : [])
                            @php($mlAllowed = ! empty($candidate['live_allowed_by_revalidation']))
                            <li>
                                <span style="color:#e2e8f0;">{{ $candidate['decision'] ?? '' }}</span>
                                <span style="color:#64748b;"> | </span>
                                <span style="color:#e2e8f0;">ml {{ $candidate['ml_score'] ?? 0 }}</span>
                                <span style="color:#64748b;"> | </span>
                                <span style="color:{{ $mlAllowed ? '#86efac' : '#cbd5e1' }};">{{ $candidate['revalidation_decision'] ?? 'observe' }}</span>
                                <span style="color:#64748b;"> | </span>
                                <span style="color:#cbd5e1;">{{ $candidate['side'] ?? '' }} {{ $candidate['candidate_id'] ?? '' }} {{ $candidate['profile'] ?? '' }}</span>
                                <span style="color:#64748b;"> | </span>
                                <span style="color:#94a3b8;">PF {{ $candidate['pf_hist'] ?? 0 }}, event {{ $candidate['event_potential_entries'] ?? 0 }} ({{ number_format((float) ($candidate['event_entries_per_day'] ?? 0), 2, '.', '') }}/d)</span>
                                <span style="color:#64748b;"> | </span>
                                <span style="color:#94a3b8;">regime {{ $candidate['current_regime_trades'] ?? 0 }}t/{{ $candidate['current_regime_wins'] ?? 0 }}w/{{ $candidate['current_regime_losses'] ?? 0 }}l, avg {{ $candidate['current_regime_avg_pnl_pct'] ?? 0 }}%, {{ $candidate['regime_support'] ?? 'thin' }}</span>
                                @if (! empty($mlWhy))
                                    <span style="color:#64748b;"> | why: </span>
                                    <span style="color:#cbd5e1;">{{ implode('; ', $mlWhy) }}</span>
                                @endif
                                @if (! empty($mlBlockers))
                                    <span style="color:#64748b;"> | block: </span>
                                    <span style="color:#fca5a5;">{{ implode('; ', $mlBlockers) }}</span>
                                @elseif (! empty($mlWhyNot))
                                    <span style="color:#64748b;"> | watch: </span>
                                    <span style="color:#fbbf24;">{{ implode('; ', $mlWhyNot) }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>
        @endif

        @if (! empty($mlDecisionLab['available']))
            @php($labReplay = $mlDecisionLab['walk_forward'] ?? [])
            @php($labRouter = is_array($labReplay['router_all'] ?? null) ? $labReplay['router_all'] : [])
            @php($labBest = is_array($labReplay['best_variant'] ?? null) ? $labReplay['best_variant'] : [])
            @php($labBestMetrics = is_array($labBest['metrics'] ?? null) ? $labBest['metrics'] : [])
            @php($labSides = is_array($mlDecisionLab['segments']['side'] ?? null) ? $mlDecisionLab['segments']['side'] : [])
            @php($labPeriods = is_array($mlDecisionLab['segments']['period'] ?? null) ? $mlDecisionLab['segments']['period'] : [])
            @php($labProfiles = is_array($mlDecisionLab['exit_optimizer']['by_profile'] ?? null) ? array_slice($mlDecisionLab['exit_optimizer']['by_profile'], 0, 3) : [])
            @php($labFeatures = is_array($mlDecisionLab['feature_importance']['top_features'] ?? null) ? array_slice($mlDecisionLab['feature_importance']['top_features'], 0, 5) : [])
            @php($labLabelGaps = is_array($mlDecisionLab['label_gap_plan']['priority'] ?? null) ? array_slice($mlDecisionLab['label_gap_plan']['priority'], 0, 4) : [])
            @php($labNextPlan = is_array($mlDecisionLab['ml_next_action_plan'] ?? null) ? $mlDecisionLab['ml_next_action_plan'] : [])
            @php($labNextActions = is_array($labNextPlan['top_actions'] ?? null) ? array_slice($labNextPlan['top_actions'], 0, 3) : [])
            @php($labNextRefresh = is_array($labNextPlan['refresh_policy'] ?? null) ? $labNextPlan['refresh_policy'] : [])
            @php($labNextReady = is_array($labNextPlan['ready_now']['segments'] ?? null) ? $labNextPlan['ready_now']['segments'] : [])
            @php($labLearning = is_array($mlDecisionLab['live_learning'] ?? null) ? $mlDecisionLab['live_learning'] : [])
            @php($labLearningOverall = is_array($labLearning['overall'] ?? null) ? $labLearning['overall'] : [])
            @php($labLearningBest = is_array($labLearning['best_threshold_diagnostic'] ?? null) ? $labLearning['best_threshold_diagnostic'] : [])
            @php($labLearningAllowed = is_array($labLearningBest['allowed'] ?? null) ? $labLearningBest['allowed'] : [])
            @php($labLearningOnline = is_array($labLearning['online_threshold_replay'] ?? null) ? $labLearning['online_threshold_replay'] : [])
            @php($labLearningOnlineAllowed = is_array($labLearningOnline['allowed'] ?? null) ? $labLearningOnline['allowed'] : [])
            @php($labLearningCal = is_array($labLearning['calibration'] ?? null) ? $labLearning['calibration'] : [])
            @php($labLearningMem = is_array($labLearning['memory'] ?? null) ? $labLearning['memory'] : [])
            @php($labPromotion = is_array($mlDecisionLab['promotion_readiness'] ?? null) ? $mlDecisionLab['promotion_readiness'] : [])
            @php($labPromotionBlockers = is_array($labPromotion['blockers'] ?? null) ? array_slice($labPromotion['blockers'], 0, 4) : [])
            @php($labPromotionCriteria = is_array($labPromotion['criteria'] ?? null) ? $labPromotion['criteria'] : [])
            @php($labPromotionTrend = is_array($mlDecisionLab['promotion_trend'] ?? null) ? $mlDecisionLab['promotion_trend'] : [])
            @php($labPromotionDelta = is_array($labPromotionTrend['delta'] ?? null) ? $labPromotionTrend['delta'] : [])
            @php($labSegmentPromotion = is_array($mlDecisionLab['segment_promotion'] ?? null) ? $mlDecisionLab['segment_promotion'] : [])
            @php($labPromoExact = is_array($labPromotionCriteria['live_exact_scored_labels'] ?? null) ? $labPromotionCriteria['live_exact_scored_labels'] : [])
            @php($labPromoOnlineTrades = is_array($labPromotionCriteria['online_allowed_trades'] ?? null) ? $labPromotionCriteria['online_allowed_trades'] : [])
            @php($labPromoOnlinePf = is_array($labPromotionCriteria['online_allowed_pf'] ?? null) ? $labPromotionCriteria['online_allowed_pf'] : [])
            @php($labPromoMono = is_array($labPromotionCriteria['score_monotonicity'] ?? null) ? $labPromotionCriteria['score_monotonicity'] : [])
            @php($labPromoWinCorr = is_array($labPromotionCriteria['score_vs_win_corr'] ?? null) ? $labPromotionCriteria['score_vs_win_corr'] : [])
            @php($labPromoBrier = is_array($labPromotionCriteria['brier_score'] ?? null) ? $labPromotionCriteria['brier_score'] : [])
            <div style="padding: 1rem; border: 1px solid #1f2937; border-radius: 0.5rem; background: #020617; color: #cbd5e1;">
                <div style="display:flex; justify-content:space-between; gap:1rem; align-items:baseline; margin-bottom:0.7rem;">
                    <div style="font-size: 0.9rem; font-weight: 700; color: #f8fafc;">ML Decision Lab</div>
                    <div style="font-size:0.8rem; color:#94a3b8;">{{ $mlDecisionLab['generated_at'] }}</div>
                </div>
                <div style="font-size:0.82rem; color:#94a3b8; margin-bottom:0.65rem;">
                    authority={{ $mlDecisionLab['authority'] }};
                    model={{ $mlDecisionLab['model_version'] }};
                    rows={{ $mlDecisionLab['dataset_counts']['rows'] ?? 0 }};
                    labels={{ $mlDecisionLab['dataset_counts']['labeled_rows'] ?? 0 }};
                    current_exact={{ $mlDecisionLab['dataset_counts']['current_exact_labeled_rows'] ?? 0 }};
                    registry={{ $mlDecisionLab['registry']['records'] ?? 0 }};
                    memory+={{ $mlDecisionLab['memory']['written'] ?? 0 }}
                </div>
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:0.6rem; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.82rem; line-height:1.45;">
                    <div style="padding:0.65rem; border:1px solid #1f2937; border-radius:0.4rem;">
                        <div style="color:#94a3b8;">router</div>
                        <div>trades={{ $labRouter['trades'] ?? 0 }} net={{ $labRouter['net_pct'] ?? 0 }} PF={{ $labRouter['pf'] ?? 0 }} DD={{ $labRouter['max_drawdown_pct'] ?? 0 }}</div>
                    </div>
                    <div style="padding:0.65rem; border:1px solid #1f2937; border-radius:0.4rem;">
                        <div style="color:#94a3b8;">best shadow gate</div>
                        <div>{{ $labBest['variant'] ?? '' }} · trades={{ $labBestMetrics['trades'] ?? 0 }} net={{ $labBestMetrics['net_pct'] ?? 0 }} PF={{ $labBestMetrics['pf'] ?? 0 }} DD={{ $labBestMetrics['max_drawdown_pct'] ?? 0 }}</div>
                    </div>
                    <div style="padding:0.65rem; border:1px solid #1f2937; border-radius:0.4rem;">
                        <div style="color:#94a3b8;">live learning</div>
                        <div>{{ $labLearning['counts']['exact_selected_scored_labels'] ?? 0 }} labels · net={{ $labLearningOverall['net_usdt'] ?? 0 }} · PF={{ $labLearningOverall['pf'] ?? 0 }} · bestT={{ $labLearningBest['threshold'] ?? '-' }} net={{ $labLearningAllowed['net_usdt'] ?? 0 }} · online net={{ $labLearningOnlineAllowed['net_usdt'] ?? 0 }} · brier={{ $labLearningCal['brier_score'] ?? '-' }} · mem={{ $labLearningMem['total'] ?? 0 }}</div>
                    </div>
                    <div style="padding:0.65rem; border:1px solid #1f2937; border-radius:0.4rem;">
                        <div style="color:#94a3b8;">promotion</div>
                        <div>{{ $labPromotion['state'] ?? 'shadow_only' }} · {{ $labPromotion['stage'] ?? 'observe' }}</div>
                        <div style="color:#94a3b8;">
                            labels={{ $labPromoExact['value'] ?? 0 }}/{{ $labPromoExact['min'] ?? 0 }};
                            online={{ $labPromoOnlineTrades['value'] ?? 0 }}/{{ $labPromoOnlineTrades['min'] ?? 0 }};
                            PF={{ $labPromoOnlinePf['value'] ?? 0 }}/{{ $labPromoOnlinePf['min'] ?? 0 }};
                            mono={{ $labPromoMono['value'] ?? '-' }};
                            corr={{ $labPromoWinCorr['value'] ?? 0 }};
                            brier={{ $labPromoBrier['value'] ?? 0 }}/{{ $labPromoBrier['max'] ?? 0 }}
                        </div>
                        @if (! empty($labPromotionBlockers))
                            <div style="color:#fbbf24;">{{ implode('; ', $labPromotionBlockers) }}</div>
                        @else
                            <div style="color:#86efac;">no hard blockers</div>
                        @endif
                        @if (($labPromotionTrend['records_with_promotion'] ?? 0) > 1)
                            <div style="color:#64748b;">
                                trend rec={{ $labPromotionTrend['records_with_promotion'] ?? 0 }};
                                Δlabels={{ $labPromotionDelta['live_exact_scored_labels'] ?? 0 }};
                                Δonline={{ $labPromotionDelta['online_allowed_trades'] ?? 0 }};
                                ΔPF={{ $labPromotionDelta['online_allowed_pf'] ?? 0 }};
                                removed={{ implode(',', array_slice($labPromotionDelta['blockers_removed'] ?? [], 0, 2)) ?: '-' }}
                            </div>
                        @endif
                    </div>
                    <div style="padding:0.65rem; border:1px solid #1f2937; border-radius:0.4rem;">
                        <div style="color:#94a3b8;">ML next action</div>
                        <div>{{ $labNextPlan['state'] ?? 'collect_labels' }} · {{ $labNextPlan['authority'] ?? 'shadow_only' }}</div>
                        <div style="color:#94a3b8;">
                            ready={{ implode(',', array_slice($labNextReady, 0, 3)) ?: '-' }};
                            refresh exact={{ $labNextRefresh['live_exact_scored_labels'] ?? 0 }}/{{ $labNextRefresh['live_exact_scored_target'] ?? 0 }};
                            online={{ $labNextRefresh['online_allowed_trades'] ?? 0 }}/{{ $labNextRefresh['online_allowed_target'] ?? 0 }};
                            PF={{ $labNextRefresh['online_allowed_pf'] ?? 0 }}/{{ $labNextRefresh['online_allowed_pf_target'] ?? 0 }}
                        </div>
                        @foreach ($labNextActions as $action)
                            <div style="color:#f8fafc;">
                                P{{ $action['priority'] ?? '?' }} {{ $action['scope'] ?? '' }}.{{ $action['key'] ?? '' }}
                                · {{ $action['bucket'] ?? '' }}
                                · need r{{ $action['need_rows'] ?? 0 }} w{{ $action['need_wins'] ?? 0 }} l{{ $action['need_losses'] ?? 0 }}
                                · {{ $action['side_hint'] ?? '-' }}
                            </div>
                        @endforeach
                    </div>
                </div>
                <div style="margin-top:0.7rem; display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap:0.6rem; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.8rem; line-height:1.45;">
                    <div>
                        <div style="color:#94a3b8; margin-bottom:0.25rem;">side models</div>
                        @foreach ($labSides as $side => $item)
                            @php($sideReplay = is_array($item['replay'] ?? null) ? $item['replay'] : [])
                            @php($sideBest = is_array($sideReplay['best_variant'] ?? null) ? $sideReplay['best_variant'] : [])
                            @php($sideMetrics = is_array($sideBest['metrics'] ?? null) ? $sideBest['metrics'] : [])
                            @php($sideDiag = is_array($item['diagnostic_holdout'] ?? null) ? $item['diagnostic_holdout'] : [])
                            @php($sideDiagBest = is_array($sideDiag['best_variant'] ?? null) ? $sideDiag['best_variant'] : [])
                            @php($sideDiagMetrics = is_array($sideDiagBest['metrics'] ?? null) ? $sideDiagBest['metrics'] : [])
                            <div>
                                {{ $side }} · {{ $item['status'] ?? '' }} · rows={{ $item['rows'] ?? 0 }} runs={{ $sideReplay['model_runs'] ?? 0 }}
                                @if (($sideReplay['model_runs'] ?? 0) > 0)
                                    · best={{ $sideBest['variant'] ?? '-' }} net={{ $sideMetrics['net_pct'] ?? 0 }} PF={{ $sideMetrics['pf'] ?? 0 }}
                                @else
                                    · diag={{ $sideDiagBest['variant'] ?? '-' }} {{ $sideDiag['train_rows'] ?? 0 }}/{{ $sideDiag['test_rows'] ?? 0 }} net={{ $sideDiagMetrics['net_pct'] ?? 0 }} PF={{ $sideDiagMetrics['pf'] ?? 0 }}
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <div>
                        <div style="color:#94a3b8; margin-bottom:0.25rem;">period models</div>
                        @foreach ($labPeriods as $period => $item)
                            @php($periodReplay = is_array($item['replay'] ?? null) ? $item['replay'] : [])
                            @php($periodBest = is_array($periodReplay['best_variant'] ?? null) ? $periodReplay['best_variant'] : [])
                            @php($periodMetrics = is_array($periodBest['metrics'] ?? null) ? $periodBest['metrics'] : [])
                            @php($periodDiag = is_array($item['diagnostic_holdout'] ?? null) ? $item['diagnostic_holdout'] : [])
                            @php($periodDiagBest = is_array($periodDiag['best_variant'] ?? null) ? $periodDiag['best_variant'] : [])
                            @php($periodDiagMetrics = is_array($periodDiagBest['metrics'] ?? null) ? $periodDiagBest['metrics'] : [])
                            <div>
                                {{ $period }} · {{ $item['status'] ?? '' }} · rows={{ $item['rows'] ?? 0 }} runs={{ $periodReplay['model_runs'] ?? 0 }}
                                @if (($periodReplay['model_runs'] ?? 0) > 0)
                                    · best={{ $periodBest['variant'] ?? '-' }} net={{ $periodMetrics['net_pct'] ?? 0 }} PF={{ $periodMetrics['pf'] ?? 0 }}
                                @else
                                    · diag={{ $periodDiagBest['variant'] ?? '-' }} {{ $periodDiag['train_rows'] ?? 0 }}/{{ $periodDiag['test_rows'] ?? 0 }} net={{ $periodDiagMetrics['net_pct'] ?? 0 }} PF={{ $periodDiagMetrics['pf'] ?? 0 }}
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <div>
                        <div style="color:#94a3b8; margin-bottom:0.25rem;">exit profiles</div>
                        @foreach ($labProfiles as $profile)
                            @php($profileMetrics = is_array($profile['metrics'] ?? null) ? $profile['metrics'] : [])
                            <div>{{ $profile['key'] ?? '' }} · {{ $profileMetrics['trades'] ?? 0 }}t net={{ $profileMetrics['net_pct'] ?? 0 }} PF={{ $profileMetrics['pf'] ?? 0 }}</div>
                        @endforeach
                    </div>
                    <div>
                        <div style="color:#94a3b8; margin-bottom:0.25rem;">top features</div>
                        @foreach ($labFeatures as $feature)
                            <div>{{ $feature['feature'] ?? '' }} · {{ $feature['importance'] ?? 0 }}</div>
                        @endforeach
                    </div>
                    <div>
                        <div style="color:#94a3b8; margin-bottom:0.25rem;">label gaps</div>
                        @foreach ($labLabelGaps as $gap)
                            @php($gapCounts = is_array($gap['counts'] ?? null) ? $gap['counts'] : [])
                            @php($need = is_array($gap['needed_for_walk_forward'] ?? null) ? $gap['needed_for_walk_forward'] : [])
                            <div>{{ $gap['scope'] ?? '' }}.{{ $gap['key'] ?? '' }} · {{ $gap['bucket'] ?? '' }} · {{ $gapCounts['rows'] ?? 0 }}r {{ $gapCounts['wins'] ?? 0 }}/{{ $gapCounts['losses'] ?? 0 }} · need r{{ $need['rows'] ?? 0 }} w{{ $need['wins'] ?? 0 }} l{{ $need['losses'] ?? 0 }}</div>
                        @endforeach
                    </div>
                    <div>
                        <div style="color:#94a3b8; margin-bottom:0.25rem;">segment promotion</div>
                        @foreach (['side', 'period'] as $scope)
                            @foreach (($labSegmentPromotion[$scope] ?? []) as $segmentKey => $segment)
                                @php($segMetrics = is_array($segment['best_metrics'] ?? null) ? $segment['best_metrics'] : [])
                                @php($segBlockers = is_array($segment['blockers'] ?? null) ? $segment['blockers'] : [])
                                <div>{{ $scope }}.{{ $segmentKey }} · {{ $segment['state'] ?? 'shadow_only' }} · rows={{ $segment['rows'] ?? 0 }} runs={{ $segment['model_runs'] ?? 0 }} · best={{ $segment['best_variant'] ?? '-' }} {{ $segMetrics['trades'] ?? 0 }}t PF={{ $segMetrics['pf'] ?? 0 }} · block={{ implode('; ', array_slice($segBlockers, 0, 2)) ?: 'none' }}</div>
                            @endforeach
                        @endforeach
                    </div>
                </div>
                @if (! empty($mlDecisionLab['blockers']))
                    <div style="margin-top:0.7rem; font-size:0.8rem; color:#fbbf24;">
                        block: {{ implode('; ', array_slice($mlDecisionLab['blockers'], 0, 4)) }}
                    </div>
                @endif
            </div>
        @endif

        @if (! empty($mlReplay['available']))
            @php($best = $mlReplay['best_variant'] ?? [])
            @php($base = $best['baseline'] ?? [])
            @php($gated = $best['ml_gated'] ?? [])
            @php($wfRegime = $mlReplay['leakage_audit']['walkforward_regime'] ?? [])
            <div style="padding: 1rem; border: 1px solid #1f2937; border-radius: 0.5rem; background: #020617; color: #cbd5e1;">
                <div style="display:flex; justify-content:space-between; gap:1rem; align-items:baseline; margin-bottom:0.7rem;">
                    <div style="font-size: 0.9rem; font-weight: 700; color: #f8fafc;">ML Replay</div>
                    <div style="font-size:0.8rem; color:#94a3b8;">{{ $mlReplay['generated_at'] }}</div>
                </div>
                <div style="font-size:0.82rem; color:#94a3b8; margin-bottom:0.65rem;">
                    {{ $mlReplay['decision'] }};
                    stream={{ $mlReplay['stream']['rows'] ?? 0 }} rows / {{ $mlReplay['stream']['lanes'] ?? 0 }} lanes;
                    q={{ $best['quantile'] ?? '' }};
                    kept={{ $best['kept_ratio'] ?? 0 }}
                </div>
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:0.65rem; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.82rem; line-height:1.45;">
                    <div style="padding:0.65rem; border:1px solid #1f2937; border-radius:0.45rem; background:#0f172a;">
                        <div style="color:#94a3b8;">router-only</div>
                        <div style="color:#e2e8f0;">{{ $base['trades'] ?? 0 }} entries · net {{ $base['net_pct'] ?? 0 }} · PF {{ $base['pf'] ?? 0 }}</div>
                        <div style="color:#94a3b8;">worst {{ $base['worst_month_pct'] ?? 0 }} · DD {{ $base['max_drawdown_pct'] ?? 0 }}</div>
                    </div>
                    <div style="padding:0.65rem; border:1px solid #1f2937; border-radius:0.45rem; background:#0f172a;">
                        <div style="color:#94a3b8;">ML-gated</div>
                        <div style="color:#e2e8f0;">{{ $gated['trades'] ?? 0 }} entries · net {{ $gated['net_pct'] ?? 0 }} · PF {{ $gated['pf'] ?? 0 }}</div>
                        <div style="color:#94a3b8;">worst {{ $gated['worst_month_pct'] ?? 0 }} · DD {{ $gated['max_drawdown_pct'] ?? 0 }}</div>
                    </div>
                    <div style="padding:0.65rem; border:1px solid #1f2937; border-radius:0.45rem; background:#0f172a;">
                        <div style="color:#94a3b8;">delta</div>
                        <div style="color:#e2e8f0;">net {{ $best['delta_net_pct'] ?? 0 }} · PF {{ $best['delta_pf'] ?? 0 }}</div>
                        <div style="color:#94a3b8;">worst {{ $best['delta_worst_month_pct'] ?? 0 }} · DD {{ $best['delta_drawdown_pct'] ?? 0 }}</div>
                    </div>
                </div>
                @if (! empty($mlReplay['strengths']))
                    <div style="margin-top:0.65rem; font-size:0.82rem; color:#86efac;">{{ implode('; ', $mlReplay['strengths']) }}</div>
                @endif
                @if (! empty($mlReplay['blockers']))
                    <div style="margin-top:0.65rem; font-size:0.82rem; color:#facc15;">{{ implode('; ', $mlReplay['blockers']) }}</div>
                @endif
                @if (! empty($wfRegime['enabled']))
                    <div style="margin-top:0.55rem; font-size:0.8rem; color:#94a3b8;">
                        wf-regime known={{ $wfRegime['known_rows'] ?? 0 }}/{{ $wfRegime['stream_rows'] ?? 0 }};
                        keys={{ $wfRegime['unique_major_keys'] ?? 0 }};
                        shifted=1 row
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-filament-panels::page>
