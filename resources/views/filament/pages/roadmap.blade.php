<x-filament-panels::page>
    @php
        $roadmap = $this->getRoadmapState();
        $readiness = $roadmap['readiness'] ?? [];
        $components = $readiness['components'] ?? [];
        $broad = $this->componentByName($components, 'broad_no_v5');
        $short = $this->componentByName($components, 'short_v9_only');
        $liveNow = $roadmap['live_now'] ?? [];
        $noSignal = $liveNow['no_signal_diagnostics'] ?? [];
        $noSignalLanes = array_slice($noSignal['lanes'] ?? [], 0, 4);
        $prospective = $roadmap['prospective'] ?? [];
        $prospectiveSummary = $prospective['summary'] ?? [];
        $background = $roadmap['background'] ?? [];
        $counts = $background['counts'] ?? [];
        $runningJobs = array_values(array_filter($background['jobs'] ?? [], fn (array $job): bool => ($job['derived_state'] ?? '') === 'running'));
        $paper = $roadmap['paper'] ?? [];
        $paperSummary = $paper['summary'] ?? [];
        $paperGate = $roadmap['paper_gate'] ?? [];
        $paperGateSummary = $paperGate['summary'] ?? [];
        $paperEnablePlan = $roadmap['paper_enable_plan'] ?? [];
        $paperEnablePlannedChanges = $paperEnablePlan['planned_changes'] ?? [];
        $signalChain = $roadmap['signal_chain'] ?? [];
        $componentPromotion = $roadmap['component_promotion'] ?? [];
        $componentPromotionProspective = $componentPromotion['prospective'] ?? [];
        $quarantine = $roadmap['quarantine'] ?? [];
        $quarantineSummary = $quarantine['summary'] ?? [];
        $quarantineByLane = $quarantineSummary['by_lane'] ?? [];
        $quarantinePromotion = $roadmap['quarantine_promotion'] ?? [];
        $quarantinePromotionLanes = $quarantinePromotion['lanes'] ?? [];
        $derivedCandidates = $quarantinePromotion['derived_candidates'] ?? [];
        $derivedV7Gate = $derivedCandidates[0] ?? [];
        $v7GateSearch = $roadmap['v7_gate_search'] ?? [];
        $v7GateWalkForward = $roadmap['v7_gate_walkforward'] ?? [];
        $topV7Gate = $v7GateSearch['top_practical'][0] ?? [];
        $topV7WfGate = $v7GateWalkForward['top_practical'][0] ?? [];
        $v7WfSummary = $v7GateWalkForward['walk_forward']['summary'] ?? [];
        $longGap = $roadmap['long_gap'] ?? [];
        $longGapHistorical = $longGap['best'] ?? [];
        $longGapCurrentPresent = $longGap['best_current_present'] ?? [];
        $longGapCurrentRouted = $roadmap['long_gap_current_routed'] ?? [];
        $longGapRoutedItems = array_slice($longGapCurrentRouted['items'] ?? [], 0, 3);
        $longGapTopRouted = $longGapRoutedItems[0] ?? [];
        $eventFamily = $roadmap['event_family_latest'] ?? [];
        $eventFamilyTop = array_slice($eventFamily['top_results'] ?? [], 0, 3);
        $marketplaceTrace = $roadmap['marketplace_trace'] ?? [];
        $marketplaceSummary = $marketplaceTrace['summary'] ?? [];
        $activeCandidates = array_slice($marketplaceTrace['active_candidates'] ?? [], 0, 3);
        $liveEntryPreview = $roadmap['live_entry_preview'] ?? [];
        $liveEntrySummary = $liveEntryPreview['summary'] ?? [];
        $liveEntryDecisions = array_slice($liveEntryPreview['decisions'] ?? [], 0, 3);
        $mlShadow = $roadmap['ml_shadow'] ?? [];
        $mlCounts = $mlShadow['counts'] ?? [];
        $nextDaysPlan = $roadmap['next_days_plan'] ?? [];
        $nextDayStages = $nextDaysPlan['stages'] ?? [];
        $promotionRules = $nextDaysPlan['promotion_rules'] ?? [];
        $readyToAdvance = $nextDaysPlan['ready_to_advance_when'] ?? [];
        $projectRoadmap = $roadmap['project_roadmap'] ?? [];
        $projectPipeline = $projectRoadmap['canonical_pipeline'] ?? [];
        $projectRules = array_slice($projectRoadmap['architecture_rules'] ?? [], 0, 7);
        $moduleDecisions = array_slice($projectRoadmap['module_decisions'] ?? [], 0, 6);
        $strategyFamilies = $projectRoadmap['strategy_families'] ?? [];
        $latestStrategyOutput = $projectRoadmap['latest_strategy_output'] ?? [];
        $latestStrategySummary = $latestStrategyOutput['summary'] ?? [];
        $latestFamilyDecisions = $latestStrategyOutput['family_decisions'] ?? [];
        $latestCandidateOutput = $projectRoadmap['latest_candidate_entry_output'] ?? [];
        $latestCandidateBest = $latestCandidateOutput['best'] ?? [];
        $codexLivePlan = $roadmap['codex_live_plan'] ?? '';

        $broadSignals = $this->checkValue($broad, 'prospective_signals', 0);
        $broadMin = $this->checkMin($broad, 'prospective_signals', 20);
        $shortSignals = $this->checkValue($short, 'prospective_signals', 0);
        $shortMin = $this->checkMin($short, 'prospective_signals', 15);
        $hasFreshSignal = (bool) ($liveNow['has_fresh_signal'] ?? false);

        $panel = 'padding:1rem;border:1px solid #1f2937;border-radius:0.5rem;background:#020617;color:#cbd5e1;';
        $subpanel = 'padding:0.7rem;border:1px solid #1f2937;border-radius:0.45rem;background:#0f172a;color:#cbd5e1;';
        $muted = 'color:#94a3b8;';
        $badge = 'display:inline-flex;align-items:center;padding:0.2rem 0.5rem;border-radius:999px;font-size:0.75rem;font-weight:700;';
        $okBadge = $badge . 'background:#052e16;color:#bbf7d0;border:1px solid #166534;';
        $warnBadge = $badge . 'background:#422006;color:#fde68a;border:1px solid #854d0e;';
        $nextBadge = $badge . 'background:#172554;color:#bfdbfe;border:1px solid #1d4ed8;';
        $badBadge = $badge . 'background:#450a0a;color:#fecaca;border:1px solid #991b1b;';
        $statusBadge = function (string $status) use ($okBadge, $warnBadge, $nextBadge, $badBadge): string {
            return match ($status) {
                'active', 'running', 'done', 'converted_initial', 'implementation_started', 'fixed_baseline' => $okBadge,
                'watch', 'next', 'planned', 'validate_first', 'candidate_entry_baseline_next' => $nextBadge,
                'defer', 'deferred' => $warnBadge,
                'blocked', 'risk' => $badBadge,
                default => $warnBadge,
            };
        };
    @endphp

    <div style="display:grid;gap:1rem;">
        <div style="{{ $panel }}">
            <div style="display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;">
                <div>
                    <div style="font-size:0.8rem;{{ $muted }}">Кінцева ціль</div>
                    <div style="margin-top:0.35rem;font-size:1.05rem;line-height:1.45;font-weight:700;color:#f8fafc;">
                        HYPE OKX multi-lane ML/router: не одна стратегія, а набір long/short lane, які router вмикає тільки під відповідний режим ринку.
                    </div>
                    <div style="margin-top:0.55rem;font-size:0.9rem;line-height:1.45;">
                        Найближча ціль: live-test на малому розмірі, де router відкриває тільки fresh signal, а ML поки ранжує/попереджає в shadow observe.
                    </div>
                </div>
                <div style="font-size:0.78rem;white-space:nowrap;{{ $muted }}">{{ $roadmap['updated_at'] ?? '' }}</div>
            </div>
        </div>

        @if(!empty($nextDaysPlan))
            <div style="{{ $panel }}">
                <div style="display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;margin-bottom:0.75rem;">
                    <div>
                        <div style="font-weight:800;color:#f8fafc;font-size:1rem;">{{ $nextDaysPlan['title'] ?? 'Next 1-3 Days' }}</div>
                        <div style="margin-top:0.35rem;font-size:0.9rem;line-height:1.45;color:#e2e8f0;">{{ $nextDaysPlan['goal'] ?? '' }}</div>
                        <div style="margin-top:0.35rem;font-size:0.82rem;{{ $muted }}">Фокус: {{ $nextDaysPlan['current_focus'] ?? 'n/a' }}</div>
                    </div>
                    <div style="font-size:0.78rem;white-space:nowrap;{{ $muted }}">{{ $nextDaysPlan['generated_at_kyiv'] ?? '' }}</div>
                </div>

                <div style="display:grid;gap:0.7rem;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));">
                    @foreach($nextDayStages as $stage)
                        <div style="{{ $subpanel }}">
                            <div style="display:flex;justify-content:space-between;gap:0.75rem;align-items:center;">
                                <div style="font-weight:700;color:#f8fafc;">{{ $stage['period'] ?? 'stage' }}</div>
                                <span style="{{ $statusBadge((string) ($stage['status'] ?? 'next')) }}">{{ $stage['status'] ?? 'next' }}</span>
                            </div>
                            <div style="margin-top:0.65rem;display:grid;gap:0.55rem;">
                                @foreach(($stage['items'] ?? []) as $item)
                                    <div style="padding-top:0.55rem;border-top:1px solid #1f2937;">
                                        <div style="display:flex;gap:0.45rem;align-items:center;flex-wrap:wrap;">
                                            <span style="{{ $statusBadge((string) ($item['status'] ?? 'next')) }}">{{ $item['status'] ?? 'next' }}</span>
                                            <span style="font-weight:700;color:#f8fafc;">{{ $item['title'] ?? '' }}</span>
                                        </div>
                                        <div style="margin-top:0.35rem;font-size:0.84rem;line-height:1.4;color:#cbd5e1;">{{ $item['why'] ?? '' }}</div>
                                        <div style="margin-top:0.25rem;font-size:0.8rem;{{ $muted }}">done: {{ $item['done_when'] ?? 'n/a' }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <div style="margin-top:0.8rem;display:grid;gap:0.7rem;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));">
                    <div style="{{ $subpanel }}">
                        <div style="font-weight:700;color:#f8fafc;margin-bottom:0.45rem;">Promotion rules</div>
                        <ol style="margin:0;padding-left:1.2rem;display:grid;gap:0.3rem;font-size:0.84rem;line-height:1.4;">
                            @foreach(array_slice($promotionRules, 0, 5) as $rule)
                                <li>{{ $rule }}</li>
                            @endforeach
                        </ol>
                    </div>
                    <div style="{{ $subpanel }}">
                        <div style="font-weight:700;color:#f8fafc;margin-bottom:0.45rem;">Ready to advance</div>
                        <ol style="margin:0;padding-left:1.2rem;display:grid;gap:0.3rem;font-size:0.84rem;line-height:1.4;">
                            @foreach(array_slice($readyToAdvance, 0, 5) as $rule)
                                <li>{{ $rule }}</li>
                            @endforeach
                        </ol>
                    </div>
                </div>
            </div>
        @endif

        @if(!empty($projectRoadmap))
            <div style="{{ $panel }}">
                <div style="display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;margin-bottom:0.75rem;">
                    <div>
                        <div style="font-weight:800;color:#f8fafc;font-size:1rem;">{{ $projectRoadmap['title'] ?? 'Architecture Roadmap' }}</div>
                        <div style="margin-top:0.35rem;font-size:0.9rem;line-height:1.45;color:#e2e8f0;">{{ $projectRoadmap['decision'] ?? '' }}</div>
                    </div>
                    <div style="font-size:0.78rem;white-space:nowrap;{{ $muted }}">{{ $projectRoadmap['generated_at_kyiv'] ?? '' }}</div>
                </div>

                <div style="{{ $subpanel }}">
                    <div style="font-weight:700;color:#f8fafc;margin-bottom:0.45rem;">Canonical pipeline</div>
                    <div style="display:flex;gap:0.4rem;flex-wrap:wrap;align-items:center;font-size:0.84rem;">
                        @foreach($projectPipeline as $idx => $step)
                            <span style="{{ $nextBadge }}">{{ $step }}</span>
                            @if($idx < count($projectPipeline) - 1)
                                <span style="color:#64748b;">→</span>
                            @endif
                        @endforeach
                    </div>
                </div>

                <div style="margin-top:0.75rem;display:grid;gap:0.7rem;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));">
                    <div style="{{ $subpanel }}">
                        <div style="font-weight:700;color:#f8fafc;margin-bottom:0.45rem;">Architecture rules</div>
                        <ol style="margin:0;padding-left:1.2rem;display:grid;gap:0.3rem;font-size:0.84rem;line-height:1.4;">
                            @foreach($projectRules as $rule)
                                <li>{{ $rule }}</li>
                            @endforeach
                        </ol>
                    </div>
                    <div style="{{ $subpanel }}">
                        <div style="font-weight:700;color:#f8fafc;margin-bottom:0.45rem;">Strategy families</div>
                        <div style="display:flex;gap:0.35rem;flex-wrap:wrap;">
                            @foreach($strategyFamilies as $family)
                                <span style="{{ $nextBadge }}">{{ $family }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div style="margin-top:0.75rem;display:grid;gap:0.55rem;">
                    @foreach($moduleDecisions as $module)
                        <div style="padding-top:0.55rem;border-top:1px solid #1f2937;font-size:0.86rem;line-height:1.4;">
                            <span style="{{ $statusBadge((string) ($module['status'] ?? 'next')) }}">{{ $module['status'] ?? 'next' }}</span>
                            <span style="font-weight:700;color:#f8fafc;margin-left:0.35rem;">{{ $module['module'] ?? '' }}</span>
                            <span style="color:#cbd5e1;"> — {{ $module['decision'] ?? '' }}</span>
                        </div>
                    @endforeach
                </div>

                @if(!empty($latestStrategyOutput))
                    <div style="margin-top:0.75rem;{{ $subpanel }}">
                        <div style="display:flex;justify-content:space-between;gap:0.75rem;align-items:flex-start;">
                            <div>
                                <div style="font-weight:700;color:#f8fafc;">Strategy Module latest</div>
                                <div style="margin-top:0.35rem;font-size:0.82rem;{{ $muted }}">
                                    {{ $latestStrategyOutput['version'] ?? 'strategy output' }} ·
                                    contexts={{ $latestStrategySummary['contexts'] ?? 'n/a' }} ·
                                    research_now={{ $latestStrategySummary['research_now_contexts'] ?? 'n/a' }} ·
                                    validate_next={{ $latestStrategySummary['validate_next_contexts'] ?? 'n/a' }}
                                </div>
                            </div>
                            <span style="{{ $statusBadge((string) ($latestStrategyOutput['state'] ?? 'next')) }}">{{ $latestStrategyOutput['state'] ?? 'next' }}</span>
                        </div>
                        <div style="margin-top:0.65rem;display:grid;gap:0.45rem;">
                            @foreach(array_slice($latestFamilyDecisions, 0, 3) as $decision)
                                <div style="font-size:0.84rem;line-height:1.4;color:#cbd5e1;">
                                    <span style="font-weight:700;color:#f8fafc;">{{ $decision['family'] ?? '' }}</span>
                                    <span style="{{ $muted }}"> — {{ $decision['decision'] ?? '' }}</span>
                                    <div style="font-size:0.78rem;{{ $muted }}">{{ $decision['best_context'] ?? '' }}</div>
                                </div>
                            @endforeach
                        </div>
                        <div style="margin-top:0.55rem;font-size:0.82rem;color:#e2e8f0;">
                            Наступний крок: {{ $latestStrategyOutput['next_step'] ?? 'n/a' }}
                        </div>
                    </div>
                @endif

                @if(!empty($latestCandidateOutput))
                    <div style="margin-top:0.75rem;{{ $subpanel }}">
                        <div style="display:flex;justify-content:space-between;gap:0.75rem;align-items:flex-start;">
                            <div>
                                <div style="font-weight:700;color:#f8fafc;">Candidate/Entry latest</div>
                                <div style="margin-top:0.35rem;font-size:0.82rem;{{ $muted }}">
                                    {{ $latestCandidateOutput['version'] ?? 'candidate output' }} ·
                                    family={{ $latestCandidateOutput['family'] ?? 'n/a' }} ·
                                    prior={{ $latestCandidateBest['suitability_before_entry_test'] ?? 'n/a' }}
                                </div>
                            </div>
                            <span style="{{ $statusBadge((string) ($latestCandidateOutput['state'] ?? 'next')) }}">{{ $latestCandidateOutput['state'] ?? 'next' }}</span>
                        </div>
                        <div style="margin-top:0.55rem;font-size:0.84rem;line-height:1.45;color:#cbd5e1;">
                            <span style="font-weight:700;color:#f8fafc;">Best:</span>
                            {{ $latestCandidateBest['context'] ?? '' }},
                            {{ $latestCandidateBest['trigger'] ?? '' }} /
                            {{ $latestCandidateBest['policy'] ?? '' }},
                            trades={{ $latestCandidateBest['trades'] ?? 'n/a' }},
                            PF={{ $latestCandidateBest['pf'] ?? 'n/a' }},
                            net={{ $latestCandidateBest['net_usdt_1000_notional'] ?? 'n/a' }},
                            TP/SL/time={{ $latestCandidateBest['tp_sl_time'] ?? 'n/a' }}
                        </div>
                        <div style="margin-top:0.45rem;font-size:0.82rem;color:#e2e8f0;">
                            {{ $latestCandidateOutput['interpretation'] ?? '' }}
                        </div>
                        <div style="margin-top:0.45rem;font-size:0.82rem;color:#e2e8f0;">
                            Наступний крок: {{ $latestCandidateOutput['next_step'] ?? 'n/a' }}
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <div style="{{ $panel }}">
            <div style="display:flex;justify-content:space-between;gap:1rem;align-items:baseline;margin-bottom:0.75rem;">
                <div style="font-weight:700;color:#f8fafc;">Live router / ML rule</div>
                <div style="font-size:0.8rem;{{ $muted }}">{{ $liveEntryPreview['generated_at_kyiv'] ?? ($marketplaceTrace['generated_at_kyiv'] ?? '') }}</div>
            </div>
            <div style="font-size:0.92rem;line-height:1.45;color:#e2e8f0;">
                candidate signal -> regime check -> ML score -> router ranking -> risk gate -> live execution.
                Зараз ML не hard-block, а observe/warning; hard-block тільки після достатньої live/shadow вибірки або явного block_shadow.
            </div>
            <div style="margin-top:0.65rem;display:grid;gap:0.6rem;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));">
                <div style="{{ $subpanel }}">
                    <span style="{{ ($liveEntrySummary['blocked'] ?? 0) > 0 ? $badBadge : $okBadge }}">live gate</span>
                    <div style="margin-top:0.45rem;font-size:0.88rem;">
                        bots {{ $liveEntrySummary['bots'] ?? 0 }},
                        waiting {{ $liveEntrySummary['waiting_fresh_signal'] ?? 0 }},
                        allowed_now {{ $liveEntrySummary['would_allow_entry'] ?? 0 }},
                        blocked {{ $liveEntrySummary['blocked'] ?? 0 }}.
                    </div>
                </div>
                <div style="{{ $subpanel }}">
                    <span style="{{ $nextBadge }}">marketplace</span>
                    <div style="margin-top:0.45rem;font-size:0.88rem;">
                        live_allowed {{ $marketplaceSummary['live_monitor_allowed'] ?? 0 }},
                        preflight {{ $marketplaceSummary['preflight_pass'] ?? 0 }},
                        review {{ $marketplaceSummary['review_candidate'] ?? 0 }},
                        blocked {{ $marketplaceSummary['blocked'] ?? 0 }}.
                    </div>
                </div>
                <div style="{{ $subpanel }}">
                    <span style="{{ $warnBadge }}">ML shadow</span>
                    <div style="margin-top:0.45rem;font-size:0.88rem;">
                        rows {{ $mlCounts['rows'] ?? 0 }},
                        long {{ $mlCounts['long_rows'] ?? 0 }},
                        short {{ $mlCounts['short_rows'] ?? 0 }},
                        current {{ $mlCounts['current_major_labeled_rows'] ?? 0 }}.
                    </div>
                </div>
            </div>
            @if(!empty($liveEntryDecisions))
                <div style="margin-top:0.7rem;display:grid;gap:0.4rem;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;font-size:0.82rem;line-height:1.45;">
                    @foreach($liveEntryDecisions as $item)
                        <div>
                            <span style="color:#e2e8f0;">{{ $item['side'] ?? '' }} {{ $item['decision'] ?? '' }}</span>
                            <span style="color:#64748b;"> | </span>
                            <span style="color:#94a3b8;">trace {{ $item['trace_state'] ?? '' }}, ml {{ $item['ml_score'] ?? 0 }} {{ $item['ml_decision'] ?? '' }}, fresh {{ $item['fresh_signal_count'] ?? 0 }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        @if(!empty($codexLivePlan))
            <details style="{{ $panel }}">
                <summary style="cursor:pointer;font-weight:700;color:#f8fafc;">Legacy Codex launch plan (archive)</summary>
                <div style="margin-top:0.45rem;margin-bottom:0.75rem;font-size:0.82rem;{{ $muted }}">
                    Старий shadow-router план залишено як довідку. Актуальний маршрут зараз вище: 7 пунктів "Що Робити Першочергово".
                </div>
                <pre style="white-space:pre-wrap;margin:0;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;font-size:0.82rem;line-height:1.45;color:#cbd5e1;">{{ $codexLivePlan }}</pre>
            </details>
        @endif

        <div style="font-size:0.82rem;{{ $muted }}">
            Нижче legacy/context діагностика зі старих router/paper/v7 гілок. Вона корисна для довідки, але не є головним планом запуску.
        </div>

        <div style="display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));">
            <div style="{{ $panel }}">
                <div style="font-weight:700;color:#f8fafc;margin-bottom:0.5rem;">Стадія</div>
                <span style="{{ $warnBadge }}">{{ $readiness['overall_state'] ?? 'shadow_only' }}</span>
                <div style="margin-top:0.6rem;font-size:0.85rem;{{ $muted }}">Readiness: {{ $readiness['generated_at'] ?? 'n/a' }}</div>
            </div>

            <div style="{{ $panel }}">
                <div style="font-weight:700;color:#f8fafc;margin-bottom:0.5rem;">Фон</div>
                <div>
                    running {{ $counts['running'] ?? 0 }},
                    stale {{ $counts['stale'] ?? 0 }},
                    failed {{ $counts['failed'] ?? 0 }}
                </div>
                <div style="margin-top:0.6rem;font-size:0.85rem;{{ $muted }}">Активні процеси: {{ count($runningJobs) }}</div>
            </div>

            <div style="{{ $panel }}">
                <div style="font-weight:700;color:#f8fafc;margin-bottom:0.5rem;">Live-now</div>
                <div>{{ $hasFreshSignal ? 'Є свіжий shadow signal.' : 'Свіжого shadow signal зараз немає.' }}</div>
                <div style="margin-top:0.6rem;font-size:0.85rem;{{ $muted }}">Feature UTC: {{ $liveNow['max_feature_utc'] ?? 'n/a' }}</div>
            </div>

            <div style="{{ $panel }}">
                <div style="font-weight:700;color:#f8fafc;margin-bottom:0.5rem;">Paper</div>
                <span style="{{ ($paperGate['overall_state'] ?? '') === 'blocked' ? $badBadge : $warnBadge }}">
                    {{ $paperGate['overall_state'] ?? 'paper_collecting' }}
                </span>
                <div style="margin-top:0.6rem;font-size:0.85rem;{{ $muted }}">
                    trades {{ $paperSummary['trades'] ?? 0 }},
                    running {{ !empty($paperGateSummary['paper_running']) ? 'yes' : 'no' }}
                </div>
            </div>
        </div>

        <div style="{{ $panel }}">
            <div style="font-weight:700;color:#f8fafc;margin-bottom:0.75rem;">Поточний факт</div>
            <div style="display:grid;gap:0.6rem;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));">
                <div style="{{ $subpanel }}">
                    <span style="{{ $okBadge }}">done</span>
                    <div style="margin-top:0.45rem;font-weight:700;color:#f8fafc;">Historical router base</div>
                    <div style="margin-top:0.35rem;font-size:0.88rem;">
                        broad: {{ data_get($broad, 'historical.trades', 'n/a') }} trades, PF {{ $this->fmt(data_get($broad, 'historical.pf')) }};
                        short_v9: {{ data_get($short, 'historical.trades', 'n/a') }} trades, PF {{ $this->fmt(data_get($short, 'historical.pf')) }}.
                    </div>
                </div>

                <div style="{{ $subpanel }}">
                    <span style="{{ $okBadge }}">running</span>
                    <div style="margin-top:0.45rem;font-weight:700;color:#f8fafc;">Paper + prospective</div>
                    <div style="margin-top:0.35rem;font-size:0.88rem;">
                        signal-chain {{ strtoupper((string) ($signalChain['state'] ?? 'n/a')) }},
                        prospective {{ $prospectiveSummary['prospective_unique_signals'] ?? 0 }} new,
                        paper trades {{ $paperSummary['trades'] ?? 0 }}.
                    </div>
                </div>

                <div style="{{ $subpanel }}">
                    <span style="{{ $warnBadge }}">quarantine</span>
                    <div style="margin-top:0.45rem;font-weight:700;color:#f8fafc;">v7/v8 напряму не промотимо</div>
                    <div style="margin-top:0.35rem;font-size:0.88rem;">
                        Promotion audit: {{ $quarantinePromotion['overall_state'] ?? 'n/a' }}.
                        v7/v8 лишаються в quarantine.
                    </div>
                </div>

                <div style="{{ $subpanel }}">
                    <span style="{{ ($v7GateWalkForward['decision'] ?? '') === 'shadow_lane_candidate' ? $okBadge : $nextBadge }}">
                        {{ ($v7GateWalkForward['decision'] ?? '') === 'shadow_lane_candidate' ? 'candidate' : 'next' }}
                    </span>
                    <div style="margin-top:0.45rem;font-weight:700;color:#f8fafc;">v7+gate walk-forward</div>
                    <div style="margin-top:0.35rem;font-size:0.88rem;">
                        @if(!empty($topV7WfGate))
                            filtered PF {{ $this->fmt($topV7WfGate['pf'] ?? null) }},
                            WF net {{ $this->fmt($v7WfSummary['rank1_test_net_pct'] ?? null, 3) }}%,
                            months {{ $v7WfSummary['rank1_positive_months'] ?? 0 }}/{{ $v7WfSummary['rank1_months_with_trades'] ?? 0 }}.
                        @elseif(!empty($topV7Gate))
                            search PF {{ $this->fmt($topV7Gate['pf'] ?? null) }},
                            LOMO {{ $this->fmt($topV7Gate['lomo_min_pf'] ?? null) }}.
                        @else
                            Немає актуальної gate-гіпотези.
                        @endif
                    </div>
                </div>

                @if(!empty($longGap))
                    <div style="{{ $subpanel }}">
                        <span style="{{ $nextBadge }}">watch</span>
                        <div style="margin-top:0.45rem;font-weight:700;color:#f8fafc;">Long/gap lane</div>
                        <div style="margin-top:0.35rem;font-size:0.88rem;">
                            current-routed {{ $longGapTopRouted['current_routed'] ?? data_get($longGapCurrentPresent, 'current_signal_rows', 0) }},
                            PF {{ $this->fmt($longGapTopRouted['pf'] ?? data_get($longGapCurrentPresent, 'pf')) }},
                            trades {{ $longGapTopRouted['trades'] ?? data_get($longGapCurrentPresent, 'trades', 'n/a') }}.
                        </div>
                        <div style="margin-top:0.25rem;font-size:0.8rem;{{ $muted }}">
                            historical best PF {{ $this->fmt(data_get($longGapHistorical, 'pf')) }};
                            status: shadow watch, not paper.
                        </div>
                    </div>
                @endif

                @if(!empty($eventFamilyTop))
                    <div style="{{ $subpanel }}">
                        <span style="{{ $nextBadge }}">event lead</span>
                        <div style="margin-top:0.45rem;font-weight:700;color:#f8fafc;">Event long leads</div>
                        <div style="margin-top:0.35rem;display:grid;gap:0.35rem;font-size:0.86rem;">
                            @foreach($eventFamilyTop as $lead)
                                @php($pf = (float) ($lead['smoke_pf'] ?? 0))
                                @php($net = (float) ($lead['smoke_net_pct'] ?? 0))
                                <div>
                                    <span style="color:#bfdbfe;">{{ $lead['rule_id'] ?? 'rule' }} {{ $lead['profile'] ?? '' }}</span>
                                    <span style="{{ $muted }}">
                                        | {{ $lead['side'] ?? '' }},
                                        {{ $lead['smoke_trades'] ?? 0 }}t,
                                        PF {{ $this->fmt($pf, 2) }},
                                        net {{ $net >= 0 ? '+' : '' }}{{ $this->fmt($net, 2) }}%,
                                        current {{ $lead['current_rows'] ?? 0 }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                        <div style="margin-top:0.35rem;font-size:0.8rem;{{ $muted }}">
                            Shadow/regime lead only; не live promotion.
                        </div>
                    </div>
                @endif
            </div>
        </div>

        @if(!empty($topV7WfGate) || !empty($topV7Gate))
            <div style="{{ $panel }}">
                <div style="font-weight:700;color:#f8fafc;margin-bottom:0.6rem;">Legacy v7/gate test (archive)</div>
                <div style="font-size:0.92rem;line-height:1.45;">
                    <strong>short_v7 + regime gate</strong>: raw v7 слабкий, але gated-версія вже має окрему WF-перевірку.
                </div>
                <div style="margin-top:0.55rem;padding:0.65rem;border:1px solid #14532d;border-radius:0.45rem;background:#052e16;color:#bbf7d0;font-size:0.86rem;">
                    {{ $topV7WfGate['filter'] ?? ($topV7Gate['filter'] ?? 'n/a') }}
                </div>
                <div style="margin-top:0.55rem;font-size:0.88rem;color:{{ ($derivedV7Gate['decision'] ?? '') === 'derived_shadow_candidate' ? '#86efac' : '#fde68a' }};">
                    Promotion audit: {{ $quarantinePromotion['overall_state'] ?? 'n/a' }};
                    derived: {{ $derivedV7Gate['decision'] ?? 'n/a' }}.
                </div>
                <div style="margin-top:0.55rem;font-size:0.88rem;{{ $muted }}">
                    Це ще не live. Наступний крок: додати gated lane тільки в shadow/prospective і збирати out-of-sample сигнали.
                </div>
            </div>
        @endif

        <div style="{{ $panel }}">
            <div style="font-weight:700;color:#f8fafc;margin-bottom:0.35rem;">Legacy paper launch diagnostics</div>
            <div style="margin-bottom:0.75rem;font-size:0.82rem;{{ $muted }}">
                Це старий paper/prospective контроль. Поточний live-test маршрут ведеться через 7 пунктів вище.
            </div>
            <div style="display:grid;gap:0.7rem;font-size:0.92rem;line-height:1.45;">
                <div>
                    <span style="{{ $warnBadge }}">blocking</span>
                    <span style="margin-left:0.5rem;font-weight:700;color:#f8fafc;">Prospective shadow evidence</span>
                    <div style="margin-top:0.3rem;">
                        Треба зібрати сигнали після фіксації правил: broad {{ $broadSignals }}/{{ $broadMin }}, short {{ $shortSignals }}/{{ $shortMin }}.
                    </div>
                </div>

                <div>
                    <span style="{{ $nextBadge }}">next</span>
                    <span style="margin-left:0.5rem;font-weight:700;color:#f8fafc;">v7+gate promotion gate</span>
                    <div style="margin-top:0.3rem;">
                        {{ $componentPromotion['decision'] ?? 'not_ready' }}:
                        signals {{ $componentPromotionProspective['signals'] ?? 0 }}/{{ data_get($componentPromotion, 'gate.min_prospective_signals', 15) }},
                        PF {{ $this->fmt($componentPromotionProspective['pf'] ?? null, 3) }},
                        days {{ is_array($componentPromotionProspective['signal_days'] ?? null) ? count($componentPromotionProspective['signal_days']) : 0 }}/{{ data_get($componentPromotion, 'gate.min_signal_days', 3) }}.
                    </div>
                    @if(!empty($componentPromotion['blockers']) && is_array($componentPromotion['blockers']))
                        <div style="margin-top:0.25rem;font-size:0.84rem;color:#fde68a;">{{ implode(', ', $componentPromotion['blockers']) }}</div>
                    @endif
                </div>

                <div>
                    <span style="{{ $nextBadge }}">next</span>
                    <span style="margin-left:0.5rem;font-weight:700;color:#f8fafc;">Paper enable plan</span>
                    <div style="margin-top:0.3rem;">
                        {{ $paperEnablePlan['state'] ?? 'not_generated' }}:
                        applied {{ !empty($paperEnablePlan['applied']) ? 'yes' : 'no' }},
                        component {{ $paperEnablePlan['component'] ?? 'n/a' }}.
                    </div>
                    @if(!empty($paperEnablePlan['blockers']) && is_array($paperEnablePlan['blockers']))
                        <div style="margin-top:0.25rem;font-size:0.84rem;color:#fde68a;">{{ implode(', ', $paperEnablePlan['blockers']) }}</div>
                    @endif
                    @if(!empty($paperEnablePlannedChanges['config_priority_after']) && is_array($paperEnablePlannedChanges['config_priority_after']))
                        <div style="margin-top:0.25rem;font-size:0.84rem;{{ $muted }}">priority after: {{ implode(', ', $paperEnablePlannedChanges['config_priority_after']) }}</div>
                    @endif
                </div>

                <div>
                    <span style="{{ $nextBadge }}">next</span>
                    <span style="margin-left:0.5rem;font-weight:700;color:#f8fafc;">Paper validation sample</span>
                    <div style="margin-top:0.3rem;">
                        Мінімум {{ data_get($paperGate, 'thresholds.min_paper_trades', 20) }} paper trades, signal-chain PASS, no-real-orders, fee/slippage/TP-limit/SL-market контроль.
                    </div>
                </div>
            </div>
        </div>

        @if(!empty($noSignalLanes))
            <div style="{{ $panel }}">
                <div style="font-weight:700;color:#f8fafc;margin-bottom:0.75rem;">Чому зараз немає fresh signal</div>
                <div style="display:grid;gap:0.45rem;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));">
                    @foreach($noSignalLanes as $lane)
                        <div style="{{ $subpanel }}">
                            <div style="font-size:0.82rem;color:#bfdbfe;">{{ $lane['lane'] ?? 'lane' }}</div>
                            <div style="margin-top:0.25rem;color:#f8fafc;">{{ $lane['reason'] ?? 'n/a' }}</div>
                            <div style="margin-top:0.25rem;font-size:0.8rem;{{ $muted }}">
                                fresh {{ $this->fmt($lane['fresh_top_score'] ?? null, 4) }} /
                                thr {{ $this->fmt($lane['threshold'] ?? null, 4) }},
                                last {{ isset($lane['last_signal_age_min']) ? $this->fmt($lane['last_signal_age_min'], 0) . 'm' : 'n/a' }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if(!empty($quarantinePromotionLanes))
            <div style="{{ $panel }}">
                <div style="font-weight:700;color:#f8fafc;margin-bottom:0.75rem;">Quarantine decisions</div>
                <div style="display:grid;gap:0.45rem;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));">
                    @foreach($quarantinePromotionLanes as $lane)
                        @php($hist = $lane['historical'] ?? [])
                        <div style="{{ $subpanel }}">
                            <div style="font-size:0.82rem;color:#bfdbfe;">{{ $lane['lane'] ?? 'lane' }}</div>
                            <div style="margin-top:0.25rem;color:#f8fafc;">{{ $lane['decision'] ?? 'n/a' }}</div>
                            <div style="margin-top:0.25rem;font-size:0.8rem;{{ $muted }}">
                                PF {{ $this->fmt($hist['pf'] ?? null, 3) }},
                                LOMO {{ $this->fmt($hist['lomo_min_pf'] ?? null, 3) }},
                                trades {{ $hist['trades'] ?? 'n/a' }}
                            </div>
                            @if(!empty($lane['blockers']) && is_array($lane['blockers']))
                                <div style="margin-top:0.25rem;font-size:0.8rem;color:#fde68a;">{{ implode(', ', $lane['blockers']) }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div style="{{ $panel }}">
            <div style="font-weight:700;color:#f8fafc;margin-bottom:0.75rem;">Активний фон</div>
            <div style="display:grid;gap:0.45rem;">
                @forelse($runningJobs as $job)
                    <div style="{{ $subpanel }}">
                        <div style="display:flex;justify-content:space-between;gap:1rem;">
                            <div style="font-weight:700;color:#f8fafc;">{{ $job['title'] ?? $job['label'] ?? 'job' }}</div>
                            <div style="font-size:0.82rem;{{ $muted }}">{{ $job['progress'] ?? 0 }}%</div>
                        </div>
                        <div style="margin-top:0.25rem;font-size:0.84rem;{{ $muted }}">
                            {{ $job['details'] ?? '' }} {{ !empty($job['result']) ? ' / ' . $job['result'] : '' }}
                        </div>
                        @if(!empty($job['eta']) && is_array($job['eta']))
                            <div style="margin-top:0.25rem;font-size:0.8rem;{{ $muted }}">
                                left {{ $job['eta']['remaining'] ?? 'n/a' }}
                                @if(!empty($job['eta']['eta_kyiv']) || !empty($job['eta']['eta_utc']))
                                    / ETA {{ $job['eta']['eta_kyiv'] ?? $job['eta']['eta_utc'] }}
                                @endif
                            </div>
                        @endif
                    </div>
                @empty
                    <div style="{{ $muted }}">Активного фону немає.</div>
                @endforelse
            </div>
        </div>

        <div style="{{ $panel }}">
            <div style="font-weight:700;color:#f8fafc;margin-bottom:0.6rem;">Quality gate</div>
            <ul style="margin:0;padding-left:1.1rem;display:grid;gap:0.35rem;font-size:0.92rem;line-height:1.45;">
                <li>Не приймаємо PF 999 або 1-2 красиві угоди як edge.</li>
                <li>Не додаємо lane, якщо current/period edge є тільки в raw signals, без live-style potential entries.</li>
                <li>Historical PF / LOMO / months - sanity/context, не головна причина promotion.</li>
                <li>Backtest/paper має враховувати TP-limit, SL-market, fees, spread, delay, cooldown і realistic exit.</li>
                <li>Головне для promotion: active-regime support, potential entries/day, W/L, DD, safe exit і fresh/forward availability.</li>
            </ul>
        </div>
    </div>
</x-filament-panels::page>
