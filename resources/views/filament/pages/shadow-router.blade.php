<x-filament-panels::page>
    @php($router = $this->getShadowRouterData())
    @php($summary = $router['historical_union_summary'] ?? [])
    @php($lane = $router['active_lane'] ?? [])
    @php($shadowLane = $router['active_shadow_lane'] ?? [])
    @php($bestShadow = $router['best_shadow_candidate'] ?? [])
    @php($signal = $router['shadow_signal'] ?? [])
    @php($audit = $router['audit'] ?? [])
    @php($auditSummary = $audit['summary_by_lane'] ?? [])
    @php($guard = $audit['short_v9_guard_candidate'] ?? [])
    @php($signals = array_slice($audit['signals'] ?? [], 0, 12))
    @php($v2 = $router['v2'] ?? [])
    @php($v2Package = $v2['package'] ?? [])
    @php($v2Components = $v2Package['components'] ?? [])
    @php($v2Signal = $v2['shadow_signal'] ?? [])
    @php($v2Best = $v2Signal['best'] ?? [])
    @php($v2Fresh = $v2Signal['best_fresh'] ?? [])
    @php($v2Summaries = $v2['component_summaries'] ?? [])
    @php($v2Audit = $router['v2_audit'] ?? [])
    @php($v2AuditSummary = $v2Audit['summary_by_component'] ?? [])
    @php($v2Prospective = $router['v2_prospective'] ?? [])
    @php($v2ProspectiveSummary = $v2Prospective['summary'] ?? [])
    @php($v2ProspectiveCount = (int) ($v2ProspectiveSummary['prospective_unique_signals'] ?? 0))
    @php($v2Quarantine = $router['v2_quarantine'] ?? [])
    @php($v2QuarantineSummary = $v2Quarantine['summary'] ?? [])
    @php($v2QuarantineByLane = $v2QuarantineSummary['by_lane'] ?? [])
    @php($v2QuarantinePromotion = $router['v2_quarantine_promotion'] ?? [])
    @php($v2QuarantinePromotionLanes = $v2QuarantinePromotion['lanes'] ?? [])
    @php($v2DerivedCandidates = $v2QuarantinePromotion['derived_candidates'] ?? [])
    @php($v2V7GateSearch = $router['v2_v7_gate_search'] ?? [])
    @php($v2V7GateTop = array_slice($v2V7GateSearch['top_practical'] ?? [], 0, 3))
    @php($v2V7GateWalkForward = $router['v2_v7_gate_walkforward'] ?? [])
    @php($v2V7WfTop = array_slice($v2V7GateWalkForward['top_practical'] ?? [], 0, 3))
    @php($v2V7WfSummary = $v2V7GateWalkForward['walk_forward']['summary'] ?? [])
    @php($v2Readiness = $router['v2_readiness'] ?? [])
    @php($v2ReadinessComponents = $v2Readiness['components'] ?? [])
    @php($v2CandidateSets = $router['v2_candidate_sets'] ?? [])
    @php($v2CandidateSetSummaries = $v2CandidateSets['summaries'] ?? [])
    @php($v2LiveNow = $router['v2_live_now'] ?? [])
    @php($v2LiveBest = $v2LiveNow['best_fresh_signal'] ?? [])
    @php($v2NoSignal = $v2LiveNow['no_signal_diagnostics'] ?? [])
    @php($v2NoSignalLanes = array_slice($v2NoSignal['lanes'] ?? [], 0, 6))
    @php($v2Paper = $router['v2_paper'] ?? [])
    @php($v2PaperAction = $v2Paper['action'] ?? [])
    @php($v2PaperSummary = $v2Paper['summary'] ?? [])
    @php($v2PaperPosition = $v2Paper['position'] ?? [])
    @php($v2PaperLastTrade = $v2Paper['last_trade'] ?? [])
    @php($v2PaperRecentTrades = $v2Paper['recent_trades'] ?? [])
    @php($v2PaperRisk = $v2Paper['risk'] ?? [])
    @php($v2PaperGate = $router['v2_paper_gate'] ?? [])
    @php($v2PaperGateSummary = $v2PaperGate['summary'] ?? [])
    @php($v2PaperEnablePlan = $router['v2_paper_enable_plan'] ?? [])
    @php($v2PaperEnablePlannedChanges = $v2PaperEnablePlan['planned_changes'] ?? [])
    @php($v2SignalChainAudit = $router['v2_signal_chain_audit'] ?? [])
    @php($v2SignalChainSummary = $v2SignalChainAudit['summary'] ?? [])
    @php($v2ComponentPromotion = $router['v2_component_promotion'] ?? [])
    @php($v2ComponentPromotionProspective = $v2ComponentPromotion['prospective'] ?? [])
    @php($sourceHealth = $router['source_health'] ?? [])
    @php($sourceLabels = [
        'v2_live_now' => 'Live-now',
        'v2_signal' => 'V2 signal',
        'v2_audit' => 'V2 audit',
        'v2_prospective' => 'Prospective',
        'v2_quarantine' => 'Quarantine',
        'v2_readiness' => 'Readiness',
        'paper_gate' => 'Paper gate',
        'legacy_signal' => 'Legacy v1',
    ])
    @php($v2NoSignalText = $v2NoSignal['explanation'] ?? data_get($v2LiveNow, 'action_diagnostics.reason') ?? '—')
    @php($legacyVisible = !empty($router['raw']) || !empty($audit) || !empty($shadowLane) || !empty($signal))

    <div wire:poll.60s style="display: grid; gap: 1rem;">
        <div style="padding: 1rem; border: 1px solid #14532d; border-radius: 0.5rem; background: #052e16; color: #dcfce7;">
            <div style="display: flex; justify-content: space-between; gap: 1rem; align-items: baseline;">
                <div>
                    <div style="font-size: 0.8rem; color: #86efac;">Shadow Router v2 control</div>
                    <div style="font-size: 1.1rem; font-weight: 700; color: {{ !empty($v2LiveNow['has_fresh_signal']) ? '#86efac' : '#fde68a' }};">
                        {{ !empty($v2LiveNow['has_fresh_signal']) ? 'FRESH SIGNAL' : 'NO FRESH SIGNAL' }}
                    </div>
                </div>
                <div style="font-size: 0.85rem; color: #86efac; text-align: right;">
                    {{ $v2LiveNow['generated_at'] ?? ($sourceHealth['v2_live_now']['updated_at'] ?? '—') }}
                </div>
            </div>

            <div style="margin-top: 0.55rem; display: grid; gap: 0.55rem; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));">
                <div>
                    <div style="font-size: 0.78rem; color: #86efac;">Reason</div>
                    <div style="margin-top: 0.2rem; color: #f0fdf4;">{{ $v2NoSignalText }}</div>
                </div>
                <div>
                    <div style="font-size: 0.78rem; color: #86efac;">Fresh window</div>
                    <div style="margin-top: 0.2rem; color: #f0fdf4;">
                        {{ $v2LiveNow['fresh_window_min'] ?? '—' }}m,
                        features {{ $v2LiveNow['max_feature_utc'] ?? '—' }}
                    </div>
                </div>
                <div>
                    <div style="font-size: 0.78rem; color: #86efac;">Prospective</div>
                    <div style="margin-top: 0.2rem; color: #f0fdf4;">
                        {{ $v2ProspectiveSummary['prospective_unique_signals'] ?? 0 }}
                        / baseline {{ $v2ProspectiveSummary['baseline_unique_signals'] ?? 0 }},
                        cycles {{ $v2Prospective['cycles'] ?? 0 }}
                    </div>
                </div>
            </div>

            <div style="margin-top: 0.75rem; display: grid; gap: 0.35rem; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));">
                @foreach($sourceLabels as $sourceKey => $sourceLabel)
                    @php($source = $sourceHealth[$sourceKey] ?? [])
                    @php($sourceState = $source['state'] ?? 'missing')
                    @php($sourceColor = $sourceState === 'fresh' ? '#86efac' : ($sourceState === 'stale' ? '#fde68a' : '#fca5a5'))
                    <div style="padding: 0.45rem; border: 1px solid #166534; border-radius: 0.4rem; background: #022c22;">
                        <div style="font-size: 0.78rem; color: #86efac;">{{ $sourceLabel }}</div>
                        <div style="margin-top: 0.18rem; font-weight: 700; color: {{ $sourceColor }};">{{ strtoupper((string) $sourceState) }}</div>
                        <div style="margin-top: 0.15rem; font-size: 0.76rem; color: #bbf7d0;">
                            {{ isset($source['age_min']) ? $source['age_min'] . 'm ago' : '—' }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        @if(!empty($v2))
            <div style="padding: 1rem; border: 1px solid #14532d; border-radius: 0.5rem; background: #052e16; color: #dcfce7;">
                <div style="display: flex; justify-content: space-between; gap: 1rem; align-items: baseline;">
                    <div>
                        <div style="font-size: 0.8rem; color: #86efac;">Shadow Router v2</div>
                        <div style="font-size: 1.05rem; font-weight: 700; color: #f0fdf4;">
                            {{ !empty($v2Signal['has_signal']) ? 'Є v2 shadow signal' : 'V2 сигналу зараз немає' }}
                        </div>
                    </div>
                    <div style="font-size: 0.85rem; color: #86efac;">{{ $v2['generated_at'] ?? '' }}</div>
                </div>

                <div style="margin-top: 0.85rem; display: grid; gap: 0.75rem; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
                    @foreach($v2Components as $component)
                        @php($rob = $component['robustness'] ?? [])
                        @php($summaryItem = collect($v2Summaries)->firstWhere('component', $component['component'] ?? ''))
                        <div style="padding: 0.8rem; border: 1px solid #166534; border-radius: 0.45rem; background: #022c22;">
                            <div style="font-size: 0.82rem; color: #86efac;">{{ $component['component'] ?? 'component' }}</div>
                            <div style="margin-top: 0.25rem; color: #f0fdf4; font-weight: 700;">
                                PF {{ isset($rob['pf']) ? number_format((float) $rob['pf'], 3, '.', '') : '—' }}
                                / LOMO {{ isset($rob['lomo_min_pf']) ? number_format((float) $rob['lomo_min_pf'], 3, '.', '') : '—' }}
                            </div>
                            <div style="margin-top: 0.35rem; font-size: 0.86rem; color: #bbf7d0;">
                                {{ $component['exit_policy'] ?? '—' }}, delay {{ $component['entry_delay_bars'] ?? '—' }}b,
                                cooldown {{ $component['cooldown_min'] ?? '—' }}m
                            </div>
                            <div style="margin-top: 0.35rem; font-size: 0.86rem; color: #bbf7d0;">
                                current signals {{ $summaryItem['signals'] ?? 0 }}, top {{ isset($summaryItem['top_score']) ? number_format((float) $summaryItem['top_score'], 4, '.', '') : '—' }}
                            </div>
                            @if(!empty($v2AuditSummary[$component['component'] ?? '']))
                                @php($auditItem = $v2AuditSummary[$component['component']])
                                <div style="margin-top: 0.35rem; font-size: 0.86rem; color: #bbf7d0;">
                                    audit {{ $auditItem['signals'] ?? 0 }} / {{ isset($auditItem['net_pct']) ? number_format((float) $auditItem['net_pct'], 4, '.', '') : '—' }}%,
                                    PF {{ isset($auditItem['pf']) ? number_format((float) $auditItem['pf'], 3, '.', '') : '—' }}
                                </div>
                            @endif
                        </div>
                    @endforeach

                    <div style="padding: 0.8rem; border: 1px solid #166534; border-radius: 0.45rem; background: #022c22;">
                        <div style="font-size: 0.82rem; color: #86efac;">Best v2 signal</div>
                        <div style="margin-top: 0.25rem; color: #f0fdf4; font-weight: 700;">
                            {{ $v2Best['component'] ?? 'NONE' }} {{ isset($v2Best['side']) ? strtoupper((string) $v2Best['side']) : '' }}
                        </div>
                        <div style="margin-top: 0.35rem; font-size: 0.86rem; color: #bbf7d0;">
                            {{ $v2Best['lane'] ?? '—' }} / score {{ isset($v2Best['quality_score']) ? number_format((float) $v2Best['quality_score'], 4, '.', '') : '—' }}
                        </div>
                        <div style="margin-top: 0.35rem; font-size: 0.86rem; color: #bbf7d0;">
                            day {{ $v2Best['day'] ?? '—' }}
                        </div>
                        <div style="margin-top: 0.35rem; font-size: 0.86rem; color: {{ !empty($v2Signal['has_fresh_signal']) ? '#86efac' : '#fde68a' }};">
                            fresh {{ !empty($v2Signal['has_fresh_signal']) ? (($v2Fresh['lane'] ?? 'yes') . ' / ' . ($v2Fresh['day'] ?? '')) : 'none' }}
                        </div>
                        <div style="margin-top: 0.35rem; font-size: 0.82rem; color: #bbf7d0;">
                            scored {{ $v2Signal['max_scored_utc'] ?? '—' }},
                            age {{ isset($v2Signal['latest_signal_age_from_scored_min']) ? number_format((float) $v2Signal['latest_signal_age_from_scored_min'], 0, '.', '') . 'm' : '—' }}
                        </div>
                    </div>
                </div>

                @if(!empty($v2Prospective))
                    <div style="margin-top: 0.85rem; padding: 0.8rem; border: 1px solid #166534; border-radius: 0.45rem; background: #022c22;">
                        <div style="display: flex; justify-content: space-between; gap: 1rem; align-items: baseline;">
                            <div>
                                <div style="font-size: 0.82rem; color: #86efac;">Prospective watch</div>
                                <div style="margin-top: 0.25rem; color: #f0fdf4; font-weight: 700;">
                                    prospective {{ $v2ProspectiveSummary['prospective_unique_signals'] ?? 0 }}
                                    / baseline {{ $v2ProspectiveSummary['baseline_unique_signals'] ?? 0 }}
                                </div>
                            </div>
                            <div style="font-size: 0.82rem; color: #86efac;">
                                {{ $v2Prospective['generated_at'] ?? '' }}
                            </div>
                        </div>
                        <div style="margin-top: 0.35rem; font-size: 0.86rem; color: #bbf7d0;">
                            cycles {{ $v2Prospective['cycles'] ?? 0 }},
                            start {{ $v2Prospective['start_utc'] ?? '—' }},
                            total unique {{ $v2ProspectiveSummary['total_unique_signals'] ?? 0 }}
                        </div>
                        <div style="margin-top: 0.35rem; font-size: 0.86rem; color: {{ $v2ProspectiveCount >= 20 ? '#86efac' : '#fde68a' }};">
                            gate {{ $v2ProspectiveCount >= 20 ? 'paper review candidate' : 'collecting shadow evidence' }}
                        </div>
                        @if(!empty($v2ProspectiveSummary['by_component']))
                            <div style="margin-top: 0.55rem; display: grid; gap: 0.45rem; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
                                @foreach($v2ProspectiveSummary['by_component'] as $componentName => $item)
                                    <div style="padding: 0.55rem; border: 1px solid #14532d; border-radius: 0.4rem; background: #052e16;">
                                        <div style="font-size: 0.8rem; color: #86efac;">{{ $componentName }}</div>
                                        <div style="margin-top: 0.25rem; color: #f0fdf4;">
                                            {{ $item['signals'] ?? 0 }} signals,
                                            net {{ isset($item['net_pct']) ? number_format((float) $item['net_pct'], 4, '.', '') : '—' }}%
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                @if(!empty($v2LiveNow))
                    <div style="margin-top: 0.85rem; padding: 0.8rem; border: 1px solid #166534; border-radius: 0.45rem; background: #022c22;">
                        <div style="display: flex; justify-content: space-between; gap: 1rem; align-items: baseline;">
                            <div>
                                <div style="font-size: 0.82rem; color: #86efac;">Live-now shadow</div>
                                <div style="margin-top: 0.25rem; color: {{ !empty($v2LiveNow['has_fresh_signal']) ? '#86efac' : '#fde68a' }}; font-weight: 700;">
                                    {{ !empty($v2LiveNow['has_fresh_signal']) ? 'fresh signal' : 'no fresh signal' }}
                                </div>
                            </div>
                            <div style="font-size: 0.82rem; color: #86efac;">{{ $v2LiveNow['generated_at'] ?? '' }}</div>
                        </div>
                        <div style="margin-top: 0.35rem; font-size: 0.86rem; color: #bbf7d0;">
                            candles/features {{ $v2LiveNow['max_feature_utc'] ?? '—' }},
                            window {{ $v2LiveNow['fresh_window_min'] ?? '—' }}m
                        </div>
                        <div style="margin-top: 0.35rem; font-size: 0.86rem; color: #bbf7d0;">
                            {{ $v2LiveBest['component'] ?? '—' }} {{ $v2LiveBest['side'] ?? '' }} {{ $v2LiveBest['lane'] ?? '' }}
                            {{ isset($v2LiveBest['quality_score']) ? '/ score ' . number_format((float) $v2LiveBest['quality_score'], 4, '.', '') : '' }}
                        </div>
                        @if(empty($v2LiveNow['has_fresh_signal']) && !empty($v2NoSignalLanes))
                            <div style="margin-top: 0.55rem; display: grid; gap: 0.35rem; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));">
                                @foreach($v2NoSignalLanes as $laneDiag)
                                    <div style="padding: 0.5rem; border: 1px solid #14532d; border-radius: 0.4rem; background: #052e16;">
                                        <div style="font-size: 0.8rem; color: #86efac;">{{ $laneDiag['lane'] ?? 'lane' }}</div>
                                        <div style="margin-top: 0.2rem; color: #f0fdf4;">
                                            {{ $laneDiag['reason'] ?? '—' }}
                                        </div>
                                        <div style="margin-top: 0.2rem; font-size: 0.8rem; color: #bbf7d0;">
                                            fresh {{ isset($laneDiag['fresh_top_score']) ? number_format((float) $laneDiag['fresh_top_score'], 4, '.', '') : '—' }}
                                            / thr {{ isset($laneDiag['threshold']) ? number_format((float) $laneDiag['threshold'], 4, '.', '') : '—' }}
                                        </div>
                                        <div style="margin-top: 0.2rem; font-size: 0.8rem; color: #bbf7d0;">
                                            last {{ isset($laneDiag['last_signal_age_min']) ? number_format((float) $laneDiag['last_signal_age_min'], 0, '.', '') . 'm ago' : 'none' }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                @if(!empty($v2Quarantine))
                    <div style="margin-top: 0.85rem; padding: 0.8rem; border: 1px solid #166534; border-radius: 0.45rem; background: #022c22;">
                        <div style="display: flex; justify-content: space-between; gap: 1rem; align-items: baseline;">
                            <div>
                                <div style="font-size: 0.82rem; color: #86efac;">Quarantine lanes</div>
                                <div style="margin-top: 0.25rem; color: #f0fdf4; font-weight: 700;">
                                    unique {{ $v2QuarantineSummary['total_unique'] ?? 0 }},
                                    prospective {{ $v2QuarantineSummary['prospective_unique'] ?? 0 }}
                                </div>
                            </div>
                            <div style="font-size: 0.82rem; color: #86efac;">{{ $v2Quarantine['generated_at'] ?? '' }}</div>
                        </div>
                        @if(!empty($v2QuarantineByLane))
                            <div style="margin-top: 0.55rem; display: grid; gap: 0.45rem; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));">
                                @foreach($v2QuarantineByLane as $laneName => $item)
                                    <div style="padding: 0.55rem; border: 1px solid #14532d; border-radius: 0.4rem; background: #052e16;">
                                        <div style="font-size: 0.8rem; color: #86efac;">{{ $laneName }}</div>
                                        <div style="margin-top: 0.25rem; color: {{ isset($item['net_pct']) && (float) $item['net_pct'] < 0 ? '#fca5a5' : '#f0fdf4' }};">
                                            {{ $item['signals'] ?? 0 }} signals,
                                            net {{ isset($item['net_pct']) ? number_format((float) $item['net_pct'], 4, '.', '') : '—' }}%
                                        </div>
                                        <div style="margin-top: 0.25rem; font-size: 0.82rem; color: #bbf7d0;">
                                            prospective {{ $item['prospective'] ?? 0 }},
                                            last {{ $item['last_signal_utc'] ?? '—' }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        @if(!empty($v2QuarantinePromotionLanes))
                            <div style="margin-top: 0.55rem; font-size: 0.84rem; color: {{ ($v2QuarantinePromotion['overall_state'] ?? '') === 'has_promotion_candidate' ? '#86efac' : '#fde68a' }};">
                                promotion audit: {{ strtoupper((string) ($v2QuarantinePromotion['overall_state'] ?? '—')) }}
                            </div>
                            <div style="margin-top: 0.35rem; display: grid; gap: 0.35rem; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
                                @foreach($v2QuarantinePromotionLanes as $item)
                                    @php($hist = $item['historical'] ?? [])
                                    <div style="padding: 0.5rem; border: 1px solid #14532d; border-radius: 0.4rem; background: #052e16;">
                                        <div style="font-size: 0.8rem; color: #86efac;">{{ $item['lane'] ?? 'lane' }}</div>
                                        <div style="margin-top: 0.2rem; color: {{ ($item['decision'] ?? '') === 'promotion_test_candidate' ? '#86efac' : '#f0fdf4' }};">
                                            {{ $item['decision'] ?? '—' }}
                                        </div>
                                        <div style="margin-top: 0.2rem; font-size: 0.8rem; color: #bbf7d0;">
                                            PF {{ isset($hist['pf']) ? number_format((float) $hist['pf'], 3, '.', '') : '—' }},
                                            LOMO {{ isset($hist['lomo_min_pf']) ? number_format((float) $hist['lomo_min_pf'], 3, '.', '') : '—' }},
                                            trades {{ $hist['trades'] ?? '—' }}
                                        </div>
                                        @if(!empty($item['blockers']) && is_array($item['blockers']))
                                            <div style="margin-top: 0.2rem; font-size: 0.8rem; color: #fde68a;">
                                                {{ implode(', ', $item['blockers']) }}
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        @if(!empty($v2DerivedCandidates))
                            <div style="margin-top: 0.55rem; font-size: 0.84rem; color: #bfdbfe;">
                                derived candidates
                            </div>
                            <div style="margin-top: 0.35rem; display: grid; gap: 0.35rem; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">
                                @foreach($v2DerivedCandidates as $item)
                                    @php($filtered = $item['filtered'] ?? [])
                                    @php($wf = $item['walk_forward_summary'] ?? [])
                                    <div style="padding: 0.5rem; border: 1px solid #14532d; border-radius: 0.4rem; background: #052e16;">
                                        <div style="font-size: 0.8rem; color: #86efac;">{{ $item['lane'] ?? 'lane' }}</div>
                                        <div style="margin-top: 0.2rem; color: {{ ($item['decision'] ?? '') === 'derived_shadow_candidate' ? '#86efac' : '#fde68a' }};">
                                            {{ $item['decision'] ?? '—' }}
                                        </div>
                                        <div style="margin-top: 0.2rem; font-size: 0.8rem; color: #bbf7d0;">
                                            PF {{ isset($filtered['pf']) ? number_format((float) $filtered['pf'], 3, '.', '') : '—' }},
                                            LOMO {{ isset($filtered['lomo_min_pf']) ? number_format((float) $filtered['lomo_min_pf'], 3, '.', '') : '—' }},
                                            WF net {{ isset($wf['rank1_test_net_pct']) ? number_format((float) $wf['rank1_test_net_pct'], 3, '.', '') : '—' }}%
                                        </div>
                                        @if(!empty($item['blockers']) && is_array($item['blockers']))
                                            <div style="margin-top: 0.2rem; font-size: 0.8rem; color: #fde68a;">
                                                {{ implode(', ', $item['blockers']) }}
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        @if(!empty($v2V7WfTop))
                            <div style="margin-top: 0.55rem; font-size: 0.84rem; color: {{ ($v2V7GateWalkForward['decision'] ?? '') === 'shadow_lane_candidate' ? '#86efac' : '#fde68a' }};">
                                v7 gate walk-forward: {{ strtoupper((string) ($v2V7GateWalkForward['decision'] ?? '—')) }},
                                rank1 net {{ isset($v2V7WfSummary['rank1_test_net_pct']) ? number_format((float) $v2V7WfSummary['rank1_test_net_pct'], 3, '.', '') : '—' }}%,
                                months {{ $v2V7WfSummary['rank1_positive_months'] ?? 0 }}/{{ $v2V7WfSummary['rank1_months_with_trades'] ?? 0 }}
                            </div>
                            <div style="margin-top: 0.35rem; display: grid; gap: 0.35rem; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">
                                @foreach($v2V7WfTop as $item)
                                    <div style="padding: 0.5rem; border: 1px solid #14532d; border-radius: 0.4rem; background: #052e16;">
                                        <div style="font-size: 0.8rem; color: #86efac;">{{ $item['current_signals_retained'] ?? 0 }} current</div>
                                        <div style="margin-top: 0.2rem; color: #f0fdf4;">
                                            PF {{ isset($item['pf']) ? number_format((float) $item['pf'], 3, '.', '') : '—' }},
                                            LOMO {{ isset($item['lomo_min_pf']) ? number_format((float) $item['lomo_min_pf'], 3, '.', '') : '—' }},
                                            trades {{ $item['trades'] ?? '—' }}
                                        </div>
                                        <div style="margin-top: 0.2rem; font-size: 0.78rem; color: #bbf7d0;">
                                            {{ $item['filter'] ?? '—' }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        @if(!empty($v2V7GateTop))
                            <div style="margin-top: 0.55rem; font-size: 0.84rem; color: {{ ($v2V7GateSearch['decision'] ?? '') === 'has_gate_hypothesis' ? '#86efac' : '#fde68a' }};">
                                v7 gate search: {{ strtoupper((string) ($v2V7GateSearch['decision'] ?? '—')) }}
                            </div>
                            <div style="margin-top: 0.35rem; display: grid; gap: 0.35rem; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">
                                @foreach($v2V7GateTop as $item)
                                    <div style="padding: 0.5rem; border: 1px solid #14532d; border-radius: 0.4rem; background: #052e16;">
                                        <div style="font-size: 0.8rem; color: #86efac;">{{ $item['current_signals_retained'] ?? 0 }}/{{ $v2V7GateSearch['current_signal_count'] ?? 0 }} current</div>
                                        <div style="margin-top: 0.2rem; color: #f0fdf4;">
                                            PF {{ isset($item['pf']) ? number_format((float) $item['pf'], 3, '.', '') : '—' }},
                                            LOMO {{ isset($item['lomo_min_pf']) ? number_format((float) $item['lomo_min_pf'], 3, '.', '') : '—' }},
                                            trades {{ $item['trades'] ?? '—' }}
                                        </div>
                                        <div style="margin-top: 0.2rem; font-size: 0.78rem; color: #bbf7d0;">
                                            {{ $item['filter'] ?? '—' }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                @if(!empty($v2Paper))
                    <div style="margin-top: 0.85rem; padding: 0.8rem; border: 1px solid #166534; border-radius: 0.45rem; background: #022c22;">
                        <div style="display: flex; justify-content: space-between; gap: 1rem; align-items: baseline;">
                            <div>
                                <div style="font-size: 0.82rem; color: #86efac;">Paper Router</div>
                                <div style="margin-top: 0.25rem; color: {{ !empty($v2PaperSummary['has_position']) ? '#86efac' : '#fde68a' }}; font-weight: 700;">
                                    {{ !empty($v2PaperSummary['has_position']) ? 'paper position open' : ($v2PaperAction['open_result'] ?? 'waiting') }}
                                </div>
                            </div>
                            <div style="font-size: 0.82rem; color: #86efac;">{{ $v2Paper['generated_at'] ?? '' }}</div>
                        </div>

                        <div style="margin-top: 0.55rem; display: grid; gap: 0.45rem; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));">
                            <div style="padding: 0.55rem; border: 1px solid #14532d; border-radius: 0.4rem; background: #052e16;">
                                <div style="font-size: 0.8rem; color: #86efac;">Balance</div>
                                <div style="margin-top: 0.25rem; color: #f0fdf4;">
                                    {{ isset($v2PaperSummary['balance_usdt']) ? number_format((float) $v2PaperSummary['balance_usdt'], 4, '.', '') : '—' }} USDT
                                </div>
                            </div>
                            <div style="padding: 0.55rem; border: 1px solid #14532d; border-radius: 0.4rem; background: #052e16;">
                                <div style="font-size: 0.8rem; color: #86efac;">PnL / fees</div>
                                <div style="margin-top: 0.25rem; color: {{ isset($v2PaperSummary['realized_pnl_usdt']) && (float) $v2PaperSummary['realized_pnl_usdt'] < 0 ? '#fca5a5' : '#f0fdf4' }};">
                                    {{ isset($v2PaperSummary['realized_pnl_usdt']) ? number_format((float) $v2PaperSummary['realized_pnl_usdt'], 4, '.', '') : '—' }}
                                    / {{ isset($v2PaperSummary['total_fees_usdt']) ? number_format((float) $v2PaperSummary['total_fees_usdt'], 4, '.', '') : '—' }}
                                </div>
                            </div>
                            <div style="padding: 0.55rem; border: 1px solid #14532d; border-radius: 0.4rem; background: #052e16;">
                                <div style="font-size: 0.8rem; color: #86efac;">Trades</div>
                                <div style="margin-top: 0.25rem; color: #f0fdf4;">
                                    {{ $v2PaperSummary['trades'] ?? 0 }} ({{ $v2PaperSummary['wins'] ?? 0 }}/{{ $v2PaperSummary['losses'] ?? 0 }})
                                    PF {{ isset($v2PaperSummary['pf']) ? number_format((float) $v2PaperSummary['pf'], 3, '.', '') : '—' }}
                                </div>
                            </div>
                            <div style="padding: 0.55rem; border: 1px solid #14532d; border-radius: 0.4rem; background: #052e16;">
                                <div style="font-size: 0.8rem; color: #86efac;">Size / guard</div>
                                <div style="margin-top: 0.25rem; color: #f0fdf4;">
                                    {{ $v2PaperRisk['margin_usdt'] ?? '—' }} x{{ $v2PaperRisk['leverage'] ?? '—' }},
                                    stale {{ $v2PaperRisk['max_signal_age_min'] ?? '—' }}m
                                </div>
                            </div>
                        </div>

                        <div style="margin-top: 0.55rem; font-size: 0.86rem; color: #bbf7d0;">
                            last action: open={{ $v2PaperAction['open_result'] ?? '—' }},
                            close={{ $v2PaperAction['close_result'] ?? '—' }},
                            disabled={{ !empty($v2Paper['paper_disabled']) ? 'yes' : 'no' }}
                        </div>

                        @if(!empty($v2PaperPosition))
                            <div style="margin-top: 0.55rem; font-size: 0.86rem; color: #bbf7d0;">
                                position: {{ $v2PaperPosition['component'] ?? '—' }}
                                {{ strtoupper((string) ($v2PaperPosition['side'] ?? '')) }}
                                {{ $v2PaperPosition['lane'] ?? '' }},
                                entry {{ $v2PaperPosition['entry_price'] ?? '—' }},
                                notional {{ $v2PaperPosition['notional_usdt'] ?? '—' }} USDT,
                                opened {{ $v2PaperPosition['entry_utc'] ?? '—' }}
                            </div>
                        @elseif(!empty($v2PaperLastTrade))
                            <div style="margin-top: 0.55rem; font-size: 0.86rem; color: #bbf7d0;">
                                last trade: {{ $v2PaperLastTrade['component'] ?? '—' }}
                                {{ strtoupper((string) ($v2PaperLastTrade['side'] ?? '')) }},
                                pnl {{ isset($v2PaperLastTrade['net_pnl_usdt']) ? number_format((float) $v2PaperLastTrade['net_pnl_usdt'], 4, '.', '') : '—' }} USDT,
                                exit {{ $v2PaperLastTrade['exit_reason'] ?? '—' }}
                            </div>
                        @endif

                        @if(!empty($v2SignalChainAudit))
                            <div style="margin-top: 0.55rem; font-size: 0.86rem; color: {{ ($v2SignalChainAudit['state'] ?? '') === 'pass' ? '#86efac' : '#fca5a5' }};">
                                signal-chain audit: {{ strtoupper((string) ($v2SignalChainAudit['state'] ?? '—')) }},
                                blockers {{ is_array($v2SignalChainAudit['blockers'] ?? null) ? count($v2SignalChainAudit['blockers']) : '—' }},
                                duplicate routes {{ $v2SignalChainSummary['duplicated_route_count'] ?? '—' }}
                            </div>
                        @endif

                        @if(!empty($v2ComponentPromotion))
                            <div style="margin-top: 0.55rem; font-size: 0.86rem; color: {{ ($v2ComponentPromotion['decision'] ?? '') === 'paper_candidate_ready' ? '#86efac' : '#fde68a' }};">
                                component promotion: {{ strtoupper((string) ($v2ComponentPromotion['decision'] ?? '—')) }},
                                {{ $v2ComponentPromotion['component'] ?? 'component' }},
                                prospective {{ $v2ComponentPromotionProspective['signals'] ?? 0 }}/{{ data_get($v2ComponentPromotion, 'gate.min_prospective_signals', 15) }},
                                PF {{ isset($v2ComponentPromotionProspective['pf']) ? number_format((float) $v2ComponentPromotionProspective['pf'], 3, '.', '') : '—' }}
                            </div>
                            @if(!empty($v2ComponentPromotion['blockers']) && is_array($v2ComponentPromotion['blockers']))
                                <div style="margin-top: 0.25rem; font-size: 0.8rem; color: #fde68a;">
                                    {{ implode(', ', $v2ComponentPromotion['blockers']) }}
                                </div>
                            @endif
                        @endif

                        @if(!empty($v2PaperGate))
                            <div style="margin-top: 0.55rem; font-size: 0.86rem; color: {{ ($v2PaperGate['overall_state'] ?? '') === 'blocked' ? '#fca5a5' : '#86efac' }};">
                                paper gate: {{ strtoupper((string) ($v2PaperGate['overall_state'] ?? '—')) }},
                                trades {{ $v2PaperGateSummary['paper_trades'] ?? '—' }},
                                running {{ !empty($v2PaperGateSummary['paper_running']) ? 'yes' : 'no' }}
                            </div>
                        @endif

                        @if(!empty($v2PaperEnablePlan))
                            <div style="margin-top: 0.55rem; font-size: 0.86rem; color: {{ ($v2PaperEnablePlan['state'] ?? '') === 'ready_to_apply' ? '#86efac' : '#fde68a' }};">
                                paper enable plan: {{ strtoupper((string) ($v2PaperEnablePlan['state'] ?? '—')) }},
                                component {{ $v2PaperEnablePlan['component'] ?? '—' }},
                                applied {{ !empty($v2PaperEnablePlan['applied']) ? 'yes' : 'no' }}
                            </div>
                            @if(!empty($v2PaperEnablePlan['blockers']) && is_array($v2PaperEnablePlan['blockers']))
                                <div style="margin-top: 0.25rem; font-size: 0.8rem; color: #fde68a;">
                                    {{ implode(', ', $v2PaperEnablePlan['blockers']) }}
                                </div>
                            @endif
                            @if(!empty($v2PaperEnablePlannedChanges['config_priority_after']) && is_array($v2PaperEnablePlannedChanges['config_priority_after']))
                                <div style="margin-top: 0.25rem; font-size: 0.8rem; color: #bbf7d0;">
                                    priority after: {{ implode(', ', $v2PaperEnablePlannedChanges['config_priority_after']) }}
                                </div>
                            @endif
                        @endif

                        @if(!empty($v2PaperRecentTrades))
                            <div style="margin-top: 0.75rem; overflow-x: auto;">
                                <table style="width: 100%; border-collapse: collapse; font-size: 0.82rem; color: #bbf7d0;">
                                    <thead>
                                        <tr style="text-align: left; color: #86efac;">
                                            <th style="padding: 0.35rem; border-bottom: 1px solid #14532d;">Time</th>
                                            <th style="padding: 0.35rem; border-bottom: 1px solid #14532d;">Route</th>
                                            <th style="padding: 0.35rem; border-bottom: 1px solid #14532d;">Exit</th>
                                            <th style="padding: 0.35rem; border-bottom: 1px solid #14532d;">PnL</th>
                                            <th style="padding: 0.35rem; border-bottom: 1px solid #14532d;">Fees</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($v2PaperRecentTrades as $trade)
                                            <tr>
                                                <td style="padding: 0.35rem; border-bottom: 1px solid #14532d;">{{ $trade['closed_at'] ?? '—' }}</td>
                                                <td style="padding: 0.35rem; border-bottom: 1px solid #14532d;">
                                                    {{ $trade['component'] ?? '—' }} {{ strtoupper((string) ($trade['side'] ?? '')) }}
                                                </td>
                                                <td style="padding: 0.35rem; border-bottom: 1px solid #14532d;">{{ $trade['exit_reason'] ?? '—' }}</td>
                                                <td style="padding: 0.35rem; border-bottom: 1px solid #14532d; color: {{ isset($trade['net_pnl_usdt']) && (float) $trade['net_pnl_usdt'] < 0 ? '#fca5a5' : '#f0fdf4' }};">
                                                    {{ isset($trade['net_pnl_usdt']) ? number_format((float) $trade['net_pnl_usdt'], 4, '.', '') : '—' }}
                                                </td>
                                                <td style="padding: 0.35rem; border-bottom: 1px solid #14532d;">
                                                    {{ isset($trade['fees_usdt']) ? number_format((float) $trade['fees_usdt'], 4, '.', '') : '—' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                @endif

                @if(!empty($v2Readiness))
                    <div style="margin-top: 0.85rem; padding: 0.8rem; border: 1px solid #166534; border-radius: 0.45rem; background: #022c22;">
                        <div style="display: flex; justify-content: space-between; gap: 1rem; align-items: baseline;">
                            <div>
                                <div style="font-size: 0.82rem; color: #86efac;">Readiness gate</div>
                                <div style="margin-top: 0.25rem; color: #f0fdf4; font-weight: 700;">
                                    {{ $v2Readiness['overall_state'] ?? '—' }}
                                </div>
                            </div>
                            <div style="font-size: 0.82rem; color: #86efac;">{{ $v2Readiness['generated_at'] ?? '' }}</div>
                        </div>
                        <div style="margin-top: 0.55rem; display: grid; gap: 0.45rem; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
                            @foreach($v2ReadinessComponents as $item)
                                <div style="padding: 0.55rem; border: 1px solid #14532d; border-radius: 0.4rem; background: #052e16;">
                                    <div style="font-size: 0.8rem; color: #86efac;">{{ $item['component'] ?? 'component' }}</div>
                                    <div style="margin-top: 0.25rem; color: #f0fdf4;">
                                        {{ $item['state'] ?? '—' }}
                                    </div>
                                    <div style="margin-top: 0.25rem; font-size: 0.82rem; color: #bbf7d0;">
                                        blockers {{ !empty($item['blockers']) && is_array($item['blockers']) ? implode(', ', $item['blockers']) : 'none' }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if(!empty($v2CandidateSetSummaries))
                    <div style="margin-top: 0.85rem; padding: 0.8rem; border: 1px solid #166534; border-radius: 0.45rem; background: #022c22;">
                        <div style="display: flex; justify-content: space-between; gap: 1rem; align-items: baseline;">
                            <div>
                                <div style="font-size: 0.82rem; color: #86efac;">Candidate sets audit</div>
                                <div style="margin-top: 0.25rem; color: #f0fdf4; font-weight: 700;">current-slice only</div>
                            </div>
                            <div style="font-size: 0.82rem; color: #86efac;">{{ $v2CandidateSets['generated_at'] ?? '' }}</div>
                        </div>
                        <div style="margin-top: 0.55rem; display: grid; gap: 0.45rem; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
                            @foreach($v2CandidateSetSummaries as $setName => $item)
                                <div style="padding: 0.55rem; border: 1px solid #14532d; border-radius: 0.4rem; background: #052e16;">
                                    <div style="font-size: 0.8rem; color: #86efac;">{{ $setName }}</div>
                                    <div style="margin-top: 0.25rem; color: #f0fdf4;">
                                        {{ $item['signals'] ?? 0 }} / {{ isset($item['net_pct']) ? number_format((float) $item['net_pct'], 4, '.', '') : '—' }}%
                                    </div>
                                    <div style="margin-top: 0.25rem; font-size: 0.82rem; color: #bbf7d0;">
                                        PF {{ isset($item['pf']) ? number_format((float) $item['pf'], 3, '.', '') : '—' }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif

        @if($legacyVisible)
            <details style="padding: 1rem; border: 1px solid #334155; border-radius: 0.5rem; background: #0f172a; color: #cbd5e1;">
                <summary style="cursor: pointer; font-weight: 700; color: #f8fafc;">
                    Legacy Shadow Router v1
                    <span style="margin-left: 0.5rem; font-size: 0.82rem; font-weight: 400; color: #94a3b8;">
                        old reference, not live decision source
                    </span>
                </summary>
                <div style="margin-top: 1rem; display: grid; gap: 1rem;">
        <div style="padding: 1rem; border: 1px solid #1f2937; border-radius: 0.5rem; background: #0f172a; color: #cbd5e1;">
            <div style="display: flex; justify-content: space-between; gap: 1rem; align-items: baseline;">
                <div>
                    <div style="font-size: 0.8rem; color: #94a3b8;">Shadow Router</div>
                    <div style="font-size: 1.05rem; font-weight: 700; color: #f8fafc;">{{ $router['title'] }}</div>
                </div>
                <div style="font-size: 0.85rem; color: #94a3b8;">
                    {{ $router['updated_at'] ?: 'ще не оновлювалось' }}
                </div>
            </div>

            <div style="margin-top: 0.75rem; color: #e2e8f0;">{{ $router['message'] }}</div>
        </div>

        <div style="display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
            <div style="padding: 1rem; border: 1px solid #1f2937; border-radius: 0.5rem; background: #020617; color: #cbd5e1;">
                <div style="font-size: 0.8rem; color: #94a3b8;">Shadow active lane</div>
                <div style="margin-top: 0.35rem; font-size: 1.2rem; font-weight: 700; color: #f8fafc;">{{ $shadowLane['lane'] ?? '—' }}</div>
                <div style="margin-top: 0.5rem; font-size: 0.9rem; color: #94a3b8;">
                    threshold {{ isset($shadowLane['threshold']) ? $shadowLane['threshold'] : '—' }},
                    cooldown {{ isset($shadowLane['cooldown_min']) ? $shadowLane['cooldown_min'] : '—' }}m
                </div>
            </div>

            <div style="padding: 1rem; border: 1px solid #1f2937; border-radius: 0.5rem; background: #020617; color: #cbd5e1;">
                <div style="font-size: 0.8rem; color: #94a3b8;">Signal</div>
                <div style="margin-top: 0.35rem; font-size: 1.2rem; font-weight: 700; color: {{ !empty($signal['has_signal']) ? '#22c55e' : '#f8fafc' }};">
                    {{ !empty($signal['has_signal']) ? strtoupper((string) ($signal['side'] ?? 'signal')) : 'NONE' }}
                </div>
                <div style="margin-top: 0.5rem; font-size: 0.9rem; color: #94a3b8;">
                    score {{ isset($signal['quality_score']) ? number_format((float) $signal['quality_score'], 4, '.', '') : '—' }}
                </div>
            </div>

            <div style="padding: 1rem; border: 1px solid #1f2937; border-radius: 0.5rem; background: #020617; color: #cbd5e1;">
                <div style="font-size: 0.8rem; color: #94a3b8;">Historical PF</div>
                <div style="margin-top: 0.35rem; font-size: 1.2rem; font-weight: 700; color: #f8fafc;">
                    {{ isset($summary['pf']) ? number_format((float) $summary['pf'], 2, '.', '') : '—' }}
                </div>
                <div style="margin-top: 0.5rem; font-size: 0.9rem; color: #94a3b8;">
                    trades {{ $summary['trades'] ?? '—' }}, net {{ isset($summary['net_pct']) ? number_format((float) $summary['net_pct'], 2, '.', '') : '—' }}%
                </div>
            </div>

            <div style="padding: 1rem; border: 1px solid #1f2937; border-radius: 0.5rem; background: #020617; color: #cbd5e1;">
                <div style="font-size: 0.8rem; color: #94a3b8;">Coverage</div>
                <div style="margin-top: 0.35rem; font-size: 1.2rem; font-weight: 700; color: #f8fafc;">
                    {{ ($summary['positive_months'] ?? '—') }}/{{ ($summary['months'] ?? '—') }}
                </div>
                <div style="margin-top: 0.5rem; font-size: 0.9rem; color: #94a3b8;">
                    worst day {{ isset($summary['worst_day_pct']) ? number_format((float) $summary['worst_day_pct'], 2, '.', '') : '—' }}%
                </div>
            </div>
        </div>

        <div style="display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
            <div style="padding: 1rem; border: 1px solid #1f2937; border-radius: 0.5rem; background: #020617; color: #cbd5e1;">
                <div style="font-size: 0.9rem; font-weight: 700; color: #f8fafc; margin-bottom: 0.6rem;">Shadow active lane detail</div>
                <div style="display: grid; gap: 0.35rem; font-size: 0.92rem;">
                    <div>current rows: <span style="color: #f8fafc;">{{ $shadowLane['current_rows'] ?? '—' }}</span></div>
                    <div>above threshold: <span style="color: #f8fafc;">{{ $shadowLane['rows_above_threshold'] ?? '—' }}</span></div>
                    <div>signal count: <span style="color: #f8fafc;">{{ $shadowLane['signal_count'] ?? '—' }}</span></div>
                    <div>top score: <span style="color: #f8fafc;">{{ isset($shadowLane['top_score']) ? number_format((float) $shadowLane['top_score'], 4, '.', '') : '—' }}</span></div>
                    <div>top pnl hint: <span style="color: #f8fafc;">{{ isset($shadowLane['top_pnl_pct']) ? number_format((float) $shadowLane['top_pnl_pct'], 4, '.', '') : '—' }}</span></div>
                    <div>days: <span style="color: #f8fafc;">{{ !empty($shadowLane['signal_days']) && is_array($shadowLane['signal_days']) ? implode(', ', $shadowLane['signal_days']) : '—' }}</span></div>
                </div>
            </div>

            <div style="padding: 1rem; border: 1px solid #1f2937; border-radius: 0.5rem; background: #020617; color: #cbd5e1;">
                <div style="font-size: 0.9rem; font-weight: 700; color: #f8fafc; margin-bottom: 0.6rem;">Shadow signal detail</div>
                <div style="display: grid; gap: 0.35rem; font-size: 0.92rem;">
                    <div>has signal: <span style="color: #f8fafc;">{{ !empty($signal['has_signal']) ? 'yes' : 'no' }}</span></div>
                    <div>side: <span style="color: #f8fafc;">{{ $signal['side'] ?? '—' }}</span></div>
                    <div>lane: <span style="color: #f8fafc;">{{ $signal['lane'] ?? '—' }}</span></div>
                    <div>day: <span style="color: #f8fafc;">{{ $signal['day'] ?? '—' }}</span></div>
                    <div>quality score: <span style="color: #f8fafc;">{{ isset($signal['quality_score']) ? number_format((float) $signal['quality_score'], 6, '.', '') : '—' }}</span></div>
                    <div>tp hint: <span style="color: #f8fafc;">{{ isset($signal['target_pnl_hint_pct']) ? number_format((float) $signal['target_pnl_hint_pct'], 6, '.', '') : '—' }}</span></div>
                </div>
            </div>

            <div style="padding: 1rem; border: 1px solid #1f2937; border-radius: 0.5rem; background: #020617; color: #cbd5e1;">
                <div style="font-size: 0.9rem; font-weight: 700; color: #f8fafc; margin-bottom: 0.6rem;">Top shadow-only candidate</div>
                <div style="display: grid; gap: 0.35rem; font-size: 0.92rem;">
                    <div>lane: <span style="color: #f8fafc;">{{ $bestShadow['lane'] ?? '—' }}</span></div>
                    <div>side: <span style="color: #f8fafc;">{{ $bestShadow['side'] ?? '—' }}</span></div>
                    <div>day: <span style="color: #f8fafc;">{{ $bestShadow['day'] ?? '—' }}</span></div>
                    <div>score: <span style="color: #f8fafc;">{{ isset($bestShadow['quality_score']) ? number_format((float) $bestShadow['quality_score'], 6, '.', '') : '—' }}</span></div>
                    <div>tp hint: <span style="color: #f8fafc;">{{ isset($bestShadow['target_pnl_pct']) ? number_format((float) $bestShadow['target_pnl_pct'], 6, '.', '') : '—' }}</span></div>
                    <div>shadow only: <span style="color: #f8fafc;">{{ !empty($bestShadow) ? (!empty($bestShadow['shadow_only']) ? 'yes' : 'no') : '—' }}</span></div>
                </div>
            </div>
        </div>

        @if(!empty($audit))
            <div style="padding: 1rem; border: 1px solid #1f2937; border-radius: 0.5rem; background: #020617; color: #cbd5e1;">
                <div style="display: flex; justify-content: space-between; gap: 1rem; align-items: baseline; margin-bottom: 0.75rem;">
                    <div style="font-size: 0.95rem; font-weight: 700; color: #f8fafc;">Signal audit</div>
                    <div style="font-size: 0.82rem; color: #94a3b8;">{{ $audit['generated_at'] ?? '' }}</div>
                </div>

                <div style="display: grid; gap: 0.75rem; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
                    @foreach($auditSummary as $laneName => $item)
                        <div style="padding: 0.75rem; border: 1px solid #1f2937; border-radius: 0.45rem; background: #0f172a;">
                            <div style="font-size: 0.82rem; color: #94a3b8;">{{ $laneName }}</div>
                            <div style="margin-top: 0.25rem; color: #f8fafc; font-weight: 700;">
                                {{ $item['signals'] ?? 0 }} signals / {{ isset($item['net_pct']) ? number_format((float) $item['net_pct'], 4, '.', '') : '—' }}
                            </div>
                            <div style="margin-top: 0.25rem; font-size: 0.85rem; color: #94a3b8;">
                                TP {{ $item['tp'] ?? 0 }}, SL {{ $item['sl'] ?? 0 }}, wins {{ $item['wins'] ?? 0 }}, losses {{ $item['losses'] ?? 0 }}
                            </div>
                        </div>
                    @endforeach
                </div>

                @if(!empty($guard))
                    <div style="margin-top: 0.9rem; padding: 0.75rem; border: 1px solid #334155; border-radius: 0.45rem; background: #0f172a;">
                        <div style="font-size: 0.86rem; color: #94a3b8;">short_v9 guard candidate: {{ $guard['rule'] ?? '—' }}</div>
                        <div style="margin-top: 0.35rem; color: #f8fafc;">
                            base {{ $guard['base_signals'] ?? 0 }} / {{ isset($guard['base_net_pct']) ? number_format((float) $guard['base_net_pct'], 4, '.', '') : '—' }},
                            guarded {{ $guard['guarded_signals'] ?? 0 }} / {{ isset($guard['guarded_net_pct']) ? number_format((float) $guard['guarded_net_pct'], 4, '.', '') : '—' }},
                            losses {{ $guard['base_losses'] ?? 0 }} → {{ $guard['guarded_losses'] ?? 0 }}
                        </div>
                    </div>
                @endif

                @if(!empty($signals))
                    <div style="margin-top: 1rem; overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.86rem;">
                            <thead>
                                <tr style="color: #94a3b8; text-align: left; border-bottom: 1px solid #1f2937;">
                                    <th style="padding: 0.45rem;">UTC</th>
                                    <th style="padding: 0.45rem;">lane</th>
                                    <th style="padding: 0.45rem;">side</th>
                                    <th style="padding: 0.45rem;">score</th>
                                    <th style="padding: 0.45rem;">pnl</th>
                                    <th style="padding: 0.45rem;">result</th>
                                    <th style="padding: 0.45rem;">guard</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($signals as $row)
                                    @php($guardPass = data_get($row, 'v9_guard.crash_guard_candidate_pass'))
                                    <tr style="border-bottom: 1px solid #111827;">
                                        <td style="padding: 0.45rem; color: #f8fafc;">{{ $row['utc'] ?? '—' }}</td>
                                        <td style="padding: 0.45rem;">{{ $row['lane'] ?? '—' }}</td>
                                        <td style="padding: 0.45rem;">{{ $row['side'] ?? '—' }}</td>
                                        <td style="padding: 0.45rem;">{{ isset($row['quality_score']) ? number_format((float) $row['quality_score'], 4, '.', '') : '—' }}</td>
                                        <td style="padding: 0.45rem; color: {{ isset($row['pnl_pct']) && (float) $row['pnl_pct'] < 0 ? '#f87171' : '#22c55e' }};">
                                            {{ isset($row['pnl_pct']) ? number_format((float) $row['pnl_pct'], 4, '.', '') : '—' }}
                                        </td>
                                        <td style="padding: 0.45rem; color: {{ ($row['result_type'] ?? '') === 'SL' ? '#f87171' : ((($row['result_type'] ?? '') === 'TP') ? '#22c55e' : '#94a3b8') }};">
                                            {{ $row['result_type'] ?? '—' }}
                                        </td>
                                        <td style="padding: 0.45rem; color: {{ $guardPass === false ? '#f87171' : '#94a3b8' }};">
                                            {{ array_key_exists('v9_guard', $row) ? ($guardPass ? 'ok' : 'risk') : '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endif
                </div>
            </details>
        @endif
    </div>
</x-filament-panels::page>
