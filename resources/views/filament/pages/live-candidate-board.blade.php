<x-filament-panels::page>
    @php($data = $this->getBoard())
    @php($market = is_array($data['market'] ?? null) ? $data['market'] : [])
    @php($current = is_array($market['current_period'] ?? null) ? $market['current_period'] : [])
    @php($dominance = is_array($market['dominance'] ?? null) ? $market['dominance'] : [])
    @php($periodCoverage = is_array($data['period_coverage'] ?? null) ? $data['period_coverage'] : [])
    @php($opportunityLab = is_array($data['opportunity_lab'] ?? null) ? $data['opportunity_lab'] : [])
    @php($opportunitySides = is_array($opportunityLab['side_summaries'] ?? null) ? $opportunityLab['side_summaries'] : [])
    @php($opportunityPeriods = is_array($opportunityLab['period_summaries'] ?? null) ? $opportunityLab['period_summaries'] : [])
    @php($opportunityRanker = is_array($opportunityLab['ranker'] ?? null) ? $opportunityLab['ranker'] : [])
    @php($interestingPreview = is_array($data['interesting_unused_preview'] ?? null) ? $data['interesting_unused_preview'] : [])
    @php($interestingItems = is_array($interestingPreview['items'] ?? null) ? $interestingPreview['items'] : [])

    <div wire:poll.300s style="display:grid; gap:1rem;">
        @if (empty($data['available']))
            <div style="padding:1rem; border:1px solid #1f2937; border-radius:0.5rem; background:#020617; color:#cbd5e1;">
                Decision Core ще не згенерований.
            </div>
        @else
            <div style="padding:1rem; border:1px solid #1f2937; border-radius:0.5rem; background:#0f172a; color:#cbd5e1;">
                <div style="display:flex; justify-content:space-between; gap:1rem; align-items:baseline; margin-bottom:0.7rem;">
                    <div>
                        <div style="font-size:0.8rem; color:#94a3b8;">Decision Core</div>
                        <div style="font-size:1.05rem; font-weight:700; color:#f8fafc;">
                            {{ $current['major_key'] ?? 'unknown regime' }}
                        </div>
                    </div>
                    <div style="font-size:0.8rem; color:#94a3b8;">{{ $data['generated_at'] }}</div>
                </div>
                <div style="display:flex; flex-wrap:wrap; gap:0.45rem; font-size:0.78rem;">
                    @php($state = $data['state'] ?? '')
                    <span style="padding:0.22rem 0.5rem; border-radius:0.35rem; background:{{ $state === 'attention' ? '#78350f' : '#064e3b' }}; color:{{ $state === 'attention' ? '#fde68a' : '#bbf7d0' }}; font-weight:700;">state={{ $state }}</span>
                    <span style="padding:0.22rem 0.5rem; border-radius:0.35rem; background:#020617; color:#94a3b8;">live={{ $data['summary']['live_candidates'] ?? 0 }}</span>
                    <span style="padding:0.22rem 0.5rem; border-radius:0.35rem; background:#020617; color:#94a3b8;">attention={{ $data['summary']['attention_candidates'] ?? 0 }}</span>
                    <span style="padding:0.22rem 0.5rem; border-radius:0.35rem; background:#020617; color:#94a3b8;">read-only</span>
                </div>
            </div>

            @if (! empty($interestingPreview))
                <div style="padding:0.85rem 1rem; border:1px solid #334155; border-radius:0.5rem; background:#020617; color:#cbd5e1;">
                    <div style="display:flex; justify-content:space-between; gap:1rem; align-items:baseline; margin-bottom:0.55rem;">
                        <div>
                            <div style="font-size:0.9rem; font-weight:800; color:#f8fafc;">Unused Candidates Preview</div>
                            <div style="font-size:0.76rem; color:#94a3b8; margin-top:0.12rem;">НЕ live: кандидати для review/add/replace без слабших дублів live-логіки</div>
                        </div>
                        <div style="text-align:right; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;">
                            <div style="font-size:0.85rem; color:{{ ((int) ($interestingPreview['count'] ?? 0)) > 0 ? '#86efac' : '#94a3b8' }}; font-weight:800;">
                                {{ $interestingPreview['count'] ?? 0 }} не в live
                            </div>
                            <div style="font-size:0.74rem; color:#94a3b8;">
                                L {{ $interestingPreview['by_side']['long'] ?? 0 }} · S {{ $interestingPreview['by_side']['short'] ?? 0 }}
                            </div>
                        </div>
                    </div>
                    @if (! empty($interestingItems))
                        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(18rem,1fr)); gap:0.55rem; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.76rem; line-height:1.45;">
                            @foreach (array_slice($interestingItems, 0, 6) as $item)
                                @php($item24 = is_array($item['last24h'] ?? null) ? $item['last24h'] : [])
                                @php($item120 = is_array($item['last120h'] ?? null) ? $item['last120h'] : [])
                                @php($itemOwn = is_array($item['own_period'] ?? null) ? $item['own_period'] : [])
                                <div style="border:1px solid #1f2937; border-radius:0.45rem; padding:0.65rem; background:#0f172a;">
                                    <div style="display:flex; justify-content:space-between; gap:0.65rem; margin-bottom:0.28rem;">
                                        <span style="color:#e2e8f0; font-weight:800;">{{ strtoupper($item['side'] ?? '') }} · {{ $this->fmtRegimeAbbr($item['abbr'] ?? ($item['major_key'] ?? '')) }}</span>
                                        <span style="color:#fbbf24;">NOT LIVE</span>
                                    </div>
                                    <div style="display:flex; justify-content:space-between; gap:0.65rem; margin-bottom:0.22rem;">
                                        <span style="color:#86efac;">score {{ $this->fmtNumber($item['score'] ?? null, 1) }}</span>
                                        @if (! empty($item['same_logic_live']['candidate_id'] ?? null))
                                            <span style="color:#94a3b8;">same live: {{ $item['same_logic_live']['candidate_id'] }}</span>
                                        @else
                                            <span style="color:#94a3b8;">new/gap logic</span>
                                        @endif
                                    </div>
                                    <div style="color:#cbd5e1; overflow-wrap:anywhere;">{{ $item['candidate_id'] ?? '' }}</div>
                                    <div style="color:#64748b;">{{ $item['logic_root'] ?? '' }} · {{ $item['profile'] ?? '' }}</div>
                                    <div style="margin-top:0.25rem; color:#fbbf24;">120h · {{ $this->fmtPeriodCompact($item120) }}</div>
                                    <div style="color:#cbd5e1;">own · {{ $this->fmtPeriodCompact($itemOwn) }}</div>
                                    @if (($item24['entries'] ?? 0) > 0)
                                        <div style="color:#a7f3d0;">24h · {{ $this->fmtPeriodCompact($item24) }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div style="font-size:0.8rem; color:#94a3b8;">цікавих незадіяних кандидатів зараз немає</div>
                    @endif
                </div>
            @endif

            @if (! empty($opportunityLab))
                <div style="padding:1rem; border:1px solid #1f2937; border-radius:0.5rem; background:#020617; color:#cbd5e1;">
                    <div style="display:flex; justify-content:space-between; gap:1rem; align-items:baseline; margin-bottom:0.75rem;">
                        <div>
                            <div style="font-size:0.9rem; font-weight:800; color:#f8fafc;">Opportunity Research Lab</div>
                            <div style="font-size:0.76rem; color:#94a3b8; margin-top:0.15rem;">read-only candidate discovery · no live authority</div>
                        </div>
                        <div style="text-align:right; display:grid; gap:0.2rem;">
                            <span style="font-size:0.76rem; color:{{ ($opportunityLab['state'] ?? '') === 'ready' ? '#86efac' : '#fca5a5' }}; font-weight:800;">
                                {{ $opportunityLab['state'] ?? 'missing' }}
                            </span>
                            <span style="font-size:0.74rem; color:#94a3b8;">
                                ranker {{ $opportunityRanker['state'] ?? 'missing' }} · labels {{ $opportunityRanker['labeled_rows'] ?? 0 }}/{{ $opportunityRanker['min_ranker_labels'] ?? '?' }}
                            </span>
                        </div>
                    </div>
                    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(21rem,1fr)); gap:0.75rem;">
                        @foreach (['long' => 'LONG', 'short' => 'SHORT'] as $labSide => $labTitle)
                            @php($summary = is_array($opportunitySides[$labSide] ?? null) ? $opportunitySides[$labSide] : [])
                            @php($scores = is_array($summary['top_scores'] ?? null) ? $summary['top_scores'] : [])
                            <div style="border:1px solid #334155; border-radius:0.5rem; padding:0.8rem; background:#0f172a;">
                                <div style="display:flex; justify-content:space-between; gap:0.75rem; margin-bottom:0.55rem;">
                                    <div style="font-weight:800; color:#e2e8f0;">{{ $labTitle }} · {{ $this->fmtRegimeAbbr($summary['period_major_key'] ?? '') }}</div>
                                    <div style="font-size:0.76rem; color:{{ ($summary['state'] ?? '') === 'ready' ? '#86efac' : '#fca5a5' }}; font-weight:700;">
                                        {{ $summary['state'] ?? 'missing' }} · {{ $summary['candidate_count'] ?? 0 }} candidates
                                    </div>
                                </div>
                                <div style="display:grid; gap:0.35rem; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.76rem; line-height:1.45;">
                                    @forelse (array_slice($scores, 0, 3) as $scoreRow)
                                        <div style="color:{{ ($scoreRow['decision'] ?? '') === 'candidate_proposal_ready' ? '#86efac' : '#fbbf24' }};">
                                            {{ $this->fmtOpportunityScore($scoreRow) }}
                                        </div>
                                        @if (! empty($scoreRow['reasons']) && is_array($scoreRow['reasons']))
                                            <div style="color:#64748b;">{{ implode('; ', array_slice($scoreRow['reasons'], 0, 3)) }}</div>
                                        @endif
                                    @empty
                                        <div style="color:#94a3b8;">no proposals</div>
                                    @endforelse
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if (! empty($opportunityPeriods))
                        <div style="margin-top:0.8rem; border-top:1px solid #1f2937; padding-top:0.75rem;">
                            <div style="font-size:0.78rem; color:#94a3b8; font-weight:800; margin-bottom:0.45rem;">Period coverage</div>
                            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(14rem,1fr)); gap:0.5rem; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.75rem;">
                                @foreach (['flt' => 'FLT', 'dlt' => 'DLT', 'ult' => 'ULT'] as $periodSlug => $periodTitle)
                                    @php($periodRow = is_array($opportunityPeriods[$periodSlug] ?? null) ? $opportunityPeriods[$periodSlug] : [])
                                    @php($longRow = is_array($periodRow['long'] ?? null) ? $periodRow['long'] : [])
                                    @php($shortRow = is_array($periodRow['short'] ?? null) ? $periodRow['short'] : [])
                                    <div style="border:1px solid #1f2937; border-radius:0.4rem; padding:0.5rem; background:#020617;">
                                        <div style="color:#e2e8f0; font-weight:800; margin-bottom:0.25rem;">{{ $periodTitle }}</div>
                                        <div style="color:{{ ((int) ($longRow['candidate_count'] ?? 0)) > 0 ? '#86efac' : '#fca5a5' }};">
                                            long {{ $longRow['candidate_count'] ?? 0 }} · {{ $longRow['state'] ?? 'missing' }}
                                        </div>
                                        <div style="color:{{ ((int) ($shortRow['candidate_count'] ?? 0)) > 0 ? '#86efac' : '#fca5a5' }};">
                                            short {{ $shortRow['candidate_count'] ?? 0 }} · {{ $shortRow['state'] ?? 'missing' }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <div style="padding:1rem; border:1px solid #1f2937; border-radius:0.5rem; background:#020617; color:#cbd5e1;">
                <div style="font-size:0.9rem; font-weight:700; color:#f8fafc; margin-bottom:0.65rem;">Домінація періодів</div>
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(14rem,1fr)); gap:0.75rem;">
                    @foreach (['24h', '48h', '120h', '30d'] as $window)
                        @php($row = is_array($dominance[$window] ?? null) ? $dominance[$window] : [])
                        @php($top = is_array($row['top'] ?? null) ? array_slice($row['top'], 0, 3) : [])
                        <div style="border:1px solid #1f2937; border-radius:0.45rem; padding:0.75rem; background:#0f172a;">
                            <div style="display:flex; justify-content:space-between; gap:0.75rem; margin-bottom:0.45rem;">
                                <div style="font-weight:700; color:#e2e8f0;">{{ $window }}</div>
                                <div style="font-size:0.76rem; color:#64748b;">gap {{ $this->fmtNumber($row['gap_hours'] ?? 0, 1) }}h</div>
                            </div>
                            <div style="display:grid; gap:0.35rem; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.78rem;">
                                @foreach ($top as $item)
                                    <div>
                                        <span style="color:#cbd5e1;">{{ $item['major_key'] ?? '' }}</span>
                                        <span style="color:#64748b;"> · </span>
                                        <span style="color:#e2e8f0;">{{ $this->fmtPct($item['pct'] ?? 0, 1) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            @if (! empty($periodCoverage['targets']))
                <div style="padding:1rem; border:1px solid #1f2937; border-radius:0.5rem; background:#020617; color:#cbd5e1;">
                    <div style="display:flex; justify-content:space-between; gap:1rem; align-items:baseline; margin-bottom:0.65rem;">
                        <div style="font-size:0.9rem; font-weight:700; color:#f8fafc;">Покриття періодів</div>
                        <div style="font-size:0.76rem; color:{{ ($periodCoverage['state'] ?? '') === 'ok' ? '#86efac' : '#fca5a5' }}; font-weight:700;">
                            {{ $periodCoverage['state'] ?? 'unknown' }}
                        </div>
                    </div>
                    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(18rem,1fr)); gap:0.75rem;">
                        @foreach ($periodCoverage['targets'] as $target)
                            @php($cells = is_array($target['cells'] ?? null) ? $target['cells'] : [])
                            <div style="border:1px solid #1f2937; border-radius:0.45rem; padding:0.75rem; background:#0f172a; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.78rem; line-height:1.45;">
                                <div style="display:flex; justify-content:space-between; gap:0.75rem; margin-bottom:0.4rem;">
                                    <span style="color:#e2e8f0; font-weight:700;">{{ $target['major_key'] ?? '' }}</span>
                                    <span style="color:{{ ($target['state'] ?? '') === 'ok' ? '#86efac' : '#fca5a5' }};">{{ $target['state'] ?? '' }}</span>
                                </div>
                                @foreach (['long', 'short'] as $matrixSide)
                                    @php($cell = is_array($cells[$matrixSide] ?? null) ? $cells[$matrixSide] : [])
                                    @php($accepted = is_array($cell['accepted'] ?? null) ? $cell['accepted'] : [])
                                    @php($review = is_array($cell['review'] ?? null) ? $cell['review'] : [])
                                    <div style="color:{{ ($cell['state'] ?? '') === 'ok' ? '#86efac' : (($cell['state'] ?? '') === 'review' ? '#fde68a' : '#fca5a5') }};">
                                        {{ $matrixSide }}={{ $cell['state'] ?? 'gap' }}
                                        @if (! empty($accepted))
                                            · {{ implode(', ', array_map(fn ($row) => $row['candidate_id'] ?? '', $accepted)) }}
                                        @elseif (! empty($review))
                                            · review {{ implode(', ', array_map(fn ($row) => $row['candidate_id'] ?? '', $review)) }}
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>

                <div style="padding:1rem; border:1px solid #334155; border-radius:0.5rem; background:#020617; color:#cbd5e1;">
                    <div style="display:flex; justify-content:space-between; gap:1rem; align-items:baseline; margin-bottom:0.75rem;">
                        <div>
                            <div style="font-size:0.9rem; font-weight:700; color:#f8fafc;">Live Candidate Board · Periods</div>
                            <div style="font-size:0.76rem; color:#94a3b8; margin-top:0.15rem;">combined period slots + live/replay/ML details</div>
                        </div>
                        <div style="font-size:0.76rem; color:#64748b;">combined</div>
                    </div>

                    @php($sideDetails = [
                        'long' => is_array($data['sides']['long']['live_candidates'] ?? null) ? $data['sides']['long']['live_candidates'] : [],
                        'short' => is_array($data['sides']['short']['live_candidates'] ?? null) ? $data['sides']['short']['live_candidates'] : [],
                    ])

                    <div style="display:grid; gap:0.8rem;">
                        @foreach ($periodCoverage['targets'] as $target)
                            @php($cells = is_array($target['cells'] ?? null) ? $target['cells'] : [])
                            @php($dominanceRow = is_array($target['dominance'] ?? null) ? $target['dominance'] : [])
                            @php($periodState = (string) ($target['state'] ?? 'unknown'))
                            @php($periodKey = (string) ($target['major_key'] ?? 'unknown'))
                            @php($periodAbbr = $this->fmtRegimeAbbr($target['abbr'] ?? $periodKey))

                            @foreach (['long' => 'LONG', 'short' => 'SHORT'] as $matrixSide => $matrixTitle)
                                @php($cell = is_array($cells[$matrixSide] ?? null) ? $cells[$matrixSide] : [])
                                @php($accepted = is_array($cell['accepted'] ?? null) ? $cell['accepted'] : [])
                                @php($review = is_array($cell['review'] ?? null) ? $cell['review'] : [])
                                @php($acceptedRoots = collect($accepted)->map(fn ($row) => $row['logic_root'] ?? '')->filter()->unique()->values()->all())
                                @php($cellState = (string) ($cell['state'] ?? 'gap'))

                                <div style="padding:1rem; border:1px solid #475569; border-radius:0.5rem; background:#020617; color:#cbd5e1;">
                                    <div style="display:flex; justify-content:space-between; gap:1rem; align-items:baseline; margin-bottom:0.8rem;">
                                        <div>
                                            <div style="font-size:0.9rem; font-weight:800; color:#f8fafc;">Combined · {{ $matrixTitle }} · {{ $periodAbbr }}</div>
                                            <div style="font-size:0.76rem; color:#94a3b8; margin-top:0.15rem;">
                                                {{ $periodKey }} · {{ $target['reason'] ?? '' }}{{ ! empty($target['current']) ? ' · current' : '' }}
                                            </div>
                                        </div>
                                        <div style="text-align:right; display:grid; gap:0.2rem;">
                                            <span style="font-size:0.76rem; color:{{ $cellState === 'ok' ? '#86efac' : ($cellState === 'review' ? '#fde68a' : '#fca5a5') }}; font-weight:800;">
                                                {{ $cellState }} · {{ $periodState }}
                                            </span>
                                            <span style="font-size:0.74rem; color:#94a3b8;">
                                                live {{ $cell['accepted_count'] ?? count($accepted) }} · review {{ $cell['review_count'] ?? count($review) }}
                                                · 24h {{ $this->fmtPct($dominanceRow['24h']['pct'] ?? null, 1) }}
                                                · 120h {{ $this->fmtPct($dominanceRow['120h']['pct'] ?? null, 1) }}
                                            </span>
                                        </div>
                                    </div>

                                    <div style="display:grid; gap:0.75rem;">
                                        @foreach (['accepted' => 'LIVE', 'review' => 'REVIEW'] as $bucket => $bucketTitle)
                                            @php($bucketRows = is_array($cell[$bucket] ?? null) ? $cell[$bucket] : [])
                                            @foreach (array_slice($bucketRows, 0, $bucket === 'accepted' ? 3 : 2) as $idx => $periodCandidate)
                                                @php($parentId = (string) ($periodCandidate['parent_candidate_id'] ?? $periodCandidate['candidate_id'] ?? ''))
                                                @php($detail = collect($sideDetails[$matrixSide] ?? [])->first(fn ($row) => ($row['candidate_id'] ?? '') === $parentId || ($row['candidate_id'] ?? '') === ($periodCandidate['candidate_id'] ?? '')))
                                                @php($stats = is_array($periodCandidate['live_trade_stats'] ?? null) ? $periodCandidate['live_trade_stats'] : [])
                                                @php($liveWindows = is_array($detail['live_windows'] ?? null) ? $detail['live_windows'] : [])
                                                @php($live24 = is_array($liveWindows['24h'] ?? null) ? $liveWindows['24h'] : [])
                                                @php($live120 = is_array($liveWindows['120h'] ?? null) ? $liveWindows['120h'] : [])
                                                @php($selection = is_array($detail['selection'] ?? null) ? $detail['selection'] : [])
                                                @php($selCurrent = is_array($selection['current'] ?? null) ? $selection['current'] : [])
                                                @php($sel24 = is_array($selection['last24h'] ?? null) ? $selection['last24h'] : [])
                                                @php($sel120 = is_array($selection['last120h'] ?? null) ? $selection['last120h'] : [])
                                                @php($own = is_array($selection['own_period'] ?? null) ? $selection['own_period'] : [])
                                                @php($ml = is_array($detail['ml'] ?? null) ? $detail['ml'] : [])
                                                @php($latest = is_array($detail['latest_trade'] ?? null) ? $detail['latest_trade'] : [])

                                                <div style="border:1px solid {{ $bucket === 'accepted' ? '#14532d' : '#92400e' }}; border-radius:0.5rem; background:{{ $bucket === 'accepted' ? '#052e1a' : '#3b2506' }}; overflow:hidden;">
                                                    <div style="display:flex; justify-content:space-between; gap:1rem; align-items:flex-start; padding:0.75rem 0.85rem; border-bottom:1px solid rgba(148,163,184,0.22);">
                                                        <div>
                                                            <div style="font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.82rem; font-weight:800; color:#f8fafc;">
                                                                {{ $bucketTitle }} {{ $idx + 1 }} · {{ $periodCandidate['candidate_id'] ?? '' }}
                                                            </div>
                                                            <div style="font-size:0.76rem; color:#cbd5e1; margin-top:0.18rem;">
                                                                {{ $periodCandidate['logic_root'] ?? '' }} · {{ $periodCandidate['binding_status'] ?? '' }} · {{ $periodCandidate['health'] ?? '' }}
                                                            </div>
                                                        </div>
                                                        <div style="text-align:right; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.74rem; color:#e2e8f0;">
                                                            {{ $this->fmtTradeStats($stats) }}
                                                        </div>
                                                    </div>

                                                    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(15rem,1fr)); gap:0.65rem; padding:0.75rem 0.85rem; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.76rem; line-height:1.45;">
                                                        <div>
                                                            <div style="color:#94a3b8; font-weight:800;">LIVE</div>
                                                            <div style="color:#a7f3d0;">24h · {{ $this->fmtLiveWindowCompact($live24) }}</div>
                                                            <div style="color:#fbbf24;">120h · {{ $this->fmtLiveWindowCompact($live120) }}</div>
                                                        </div>
                                                        <div>
                                                            <div style="color:#94a3b8; font-weight:800;">REPLAY</div>
                                                            <div style="color:#a7f3d0;">current · {{ $this->fmtPeriodCompact($selCurrent) }}</div>
                                                            <div style="color:#86efac;">24h · {{ $this->fmtPeriodCompact($sel24) }}</div>
                                                            <div style="color:#fbbf24;">120h · {{ $this->fmtPeriodCompact($sel120) }}</div>
                                                            <div style="color:#cbd5e1;">own · {{ $this->fmtPeriodCompact($own) }}</div>
                                                        </div>
                                                        <div>
                                                            <div style="color:#94a3b8; font-weight:800;">ML / LAST</div>
                                                            <div style="color:#cbd5e1;">ML {{ $this->fmtNumber($ml['score'] ?? null, 3) }} · {{ $ml['decision'] ?? '' }}</div>
                                                            @if (! empty($latest['trade_id']))
                                                                <div style="color:#e2e8f0;">#{{ $latest['trade_id'] }} · {{ $this->fmtNumber($latest['pnl_usdt'] ?? null, 4) }} USDT · {{ $latest['exit_reason'] ?? '' }}</div>
                                                            @else
                                                                <div style="color:#64748b;">latest trade: n/a</div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @endforeach

                                        @if (empty($accepted))
                                            <div style="border:1px dashed #475569; border-radius:0.45rem; padding:0.65rem 0.75rem; background:#1e293b; color:#cbd5e1; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.76rem;">
                                                1. live · empty
                                            </div>
                                        @endif

                                        @if (count($acceptedRoots) < 2)
                                            <div style="border:1px dashed #92400e; border-radius:0.45rem; padding:0.65rem 0.75rem; background:#3b2506; color:#fde68a; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.76rem;">
                                                need: another {{ $matrixTitle }} logic for {{ $periodKey }}
                                                @if (! empty($review))
                                                    · review candidates above
                                                @else
                                                    · search target
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        @endforeach
                    </div>
                </div>

                @if (false)
                @php($flatTarget = collect($periodCoverage['targets'])->firstWhere('major_key', 'flat|low|tight'))
                @php($flatShortCell = is_array($flatTarget['cells']['short'] ?? null) ? $flatTarget['cells']['short'] : [])
                @php($shortDetails = is_array($data['sides']['short']['live_candidates'] ?? null) ? $data['sides']['short']['live_candidates'] : [])
                @if (! empty($flatShortCell))
                    <div style="padding:1rem; border:1px solid #475569; border-radius:0.5rem; background:#020617; color:#cbd5e1;">
                        <div style="display:flex; justify-content:space-between; gap:1rem; align-items:baseline; margin-bottom:0.8rem;">
                            <div>
                                <div style="font-size:0.9rem; font-weight:800; color:#f8fafc;">Combined Test · SHORT · FLT</div>
                                <div style="font-size:0.76rem; color:#94a3b8; margin-top:0.15rem;">flat|low|tight · matrix + candidate details in one block</div>
                            </div>
                            <div style="text-align:right; display:grid; gap:0.2rem;">
                                <span style="font-size:0.76rem; color:{{ ($flatShortCell['state'] ?? '') === 'ok' ? '#86efac' : '#fde68a' }}; font-weight:800;">
                                    {{ $flatShortCell['state'] ?? 'unknown' }}
                                </span>
                                <span style="font-size:0.74rem; color:#94a3b8;">
                                    live {{ $flatShortCell['accepted_count'] ?? 0 }} · review {{ $flatShortCell['review_count'] ?? 0 }}
                                </span>
                            </div>
                        </div>

                        <div style="display:grid; gap:0.75rem;">
                            @foreach (['accepted' => 'LIVE', 'review' => 'REVIEW'] as $bucket => $bucketTitle)
                                @php($bucketRows = is_array($flatShortCell[$bucket] ?? null) ? $flatShortCell[$bucket] : [])
                                @if (! empty($bucketRows))
                                    @foreach ($bucketRows as $idx => $periodCandidate)
                                        @php($parentId = (string) ($periodCandidate['parent_candidate_id'] ?? $periodCandidate['candidate_id'] ?? ''))
                                        @php($detail = collect($shortDetails)->first(fn ($row) => ($row['candidate_id'] ?? '') === $parentId || ($row['candidate_id'] ?? '') === ($periodCandidate['candidate_id'] ?? '')))
                                        @php($stats = is_array($periodCandidate['live_trade_stats'] ?? null) ? $periodCandidate['live_trade_stats'] : [])
                                        @php($liveWindows = is_array($detail['live_windows'] ?? null) ? $detail['live_windows'] : [])
                                        @php($live24 = is_array($liveWindows['24h'] ?? null) ? $liveWindows['24h'] : [])
                                        @php($live120 = is_array($liveWindows['120h'] ?? null) ? $liveWindows['120h'] : [])
                                        @php($selection = is_array($detail['selection'] ?? null) ? $detail['selection'] : [])
                                        @php($selCurrent = is_array($selection['current'] ?? null) ? $selection['current'] : [])
                                        @php($sel120 = is_array($selection['last120h'] ?? null) ? $selection['last120h'] : [])
                                        @php($own = is_array($selection['own_period'] ?? null) ? $selection['own_period'] : [])
                                        @php($ml = is_array($detail['ml'] ?? null) ? $detail['ml'] : [])
                                        @php($latest = is_array($detail['latest_trade'] ?? null) ? $detail['latest_trade'] : [])

                                        <div style="border:1px solid {{ $bucket === 'accepted' ? '#14532d' : '#92400e' }}; border-radius:0.5rem; background:{{ $bucket === 'accepted' ? '#052e1a' : '#3b2506' }}; overflow:hidden;">
                                            <div style="display:flex; justify-content:space-between; gap:1rem; align-items:flex-start; padding:0.75rem 0.85rem; border-bottom:1px solid rgba(148,163,184,0.22);">
                                                <div>
                                                    <div style="font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.82rem; font-weight:800; color:#f8fafc;">
                                                        {{ $bucketTitle }} {{ $idx + 1 }} · {{ $periodCandidate['candidate_id'] ?? '' }}
                                                    </div>
                                                    <div style="font-size:0.76rem; color:#cbd5e1; margin-top:0.18rem;">
                                                        {{ $periodCandidate['logic_root'] ?? '' }} · {{ $periodCandidate['binding_status'] ?? '' }} · {{ $periodCandidate['health'] ?? '' }}
                                                    </div>
                                                </div>
                                                <div style="text-align:right; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.74rem; color:#e2e8f0;">
                                                    {{ $this->fmtTradeStats($stats) }}
                                                </div>
                                            </div>

                                            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(15rem,1fr)); gap:0.65rem; padding:0.75rem 0.85rem; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.76rem; line-height:1.45;">
                                                <div>
                                                    <div style="color:#94a3b8; font-weight:800;">LIVE</div>
                                                    <div style="color:#a7f3d0;">24h · {{ $this->fmtLiveWindowCompact($live24) }}</div>
                                                    <div style="color:#fbbf24;">120h · {{ $this->fmtLiveWindowCompact($live120) }}</div>
                                                </div>
                                                <div>
                                                    <div style="color:#94a3b8; font-weight:800;">REPLAY</div>
                                                    <div style="color:#a7f3d0;">current · {{ $this->fmtPeriodCompact($selCurrent) }}</div>
                                                    <div style="color:#fbbf24;">120h · {{ $this->fmtPeriodCompact($sel120) }}</div>
                                                    <div style="color:#cbd5e1;">own · {{ $this->fmtPeriodCompact($own) }}</div>
                                                </div>
                                                <div>
                                                    <div style="color:#94a3b8; font-weight:800;">ML / LAST</div>
                                                    <div style="color:#cbd5e1;">ML {{ $this->fmtNumber($ml['score'] ?? null, 3) }} · {{ $ml['decision'] ?? '' }}</div>
                                                    @if (! empty($latest['trade_id']))
                                                        <div style="color:#e2e8f0;">#{{ $latest['trade_id'] }} · {{ $this->fmtNumber($latest['pnl_usdt'] ?? null, 4) }} USDT · {{ $latest['exit_reason'] ?? '' }}</div>
                                                    @else
                                                        <div style="color:#64748b;">latest trade: n/a</div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            @endforeach

                            @if (($flatShortCell['accepted_count'] ?? 0) < 2)
                                <div style="border:1px dashed #92400e; border-radius:0.45rem; padding:0.65rem 0.75rem; background:#3b2506; color:#fde68a; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.76rem;">
                                    need: another SHORT logic for flat|low|tight
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
                @endif
            @endif

            @if (! empty($data['actions']))
                <div style="padding:1rem; border:1px solid #1f2937; border-radius:0.5rem; background:#020617; color:#cbd5e1;">
                    <div style="font-size:0.9rem; font-weight:700; color:#f8fafc; margin-bottom:0.65rem;">Найближчі рішення</div>
                    <ol style="margin:0; padding-left:1.25rem; display:grid; gap:0.45rem; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.8rem; line-height:1.45;">
                        @foreach (array_slice($data['actions'], 0, 8) as $action)
                            @php($priority = $action['priority'] ?? 'P3')
                            @php($color = $priority === 'P0' ? '#fca5a5' : ($priority === 'P1' ? '#fde68a' : '#cbd5e1'))
                            <li>
                                <span style="color:{{ $color }}; font-weight:700;">{{ $priority }}</span>
                                <span style="color:#64748b;"> | </span>
                                <span style="color:#e2e8f0;">{{ $action['title'] ?? '' }}</span>
                                <span style="color:#64748b;"> | {{ $action['mode'] ?? '' }}</span>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif

            <div style="display:grid; gap:1rem;">
                @foreach (['long' => 'LONG', 'short' => 'SHORT'] as $sideKey => $sideTitle)
                    @php($side = is_array($data['sides'][$sideKey] ?? null) ? $data['sides'][$sideKey] : [])
                    @php($coverage = is_array($data['coverage'][$sideKey] ?? null) ? $data['coverage'][$sideKey] : [])
                    @php($policy = is_array($side['policy'] ?? null) ? $side['policy'] : [])
                    @php($policyPriority = (string) ($policy['priority'] ?? 'P3'))
                    @php($policyHits = is_array($policy['rule_hits'] ?? null) ? array_slice($policy['rule_hits'], 0, 4) : [])
                    <div style="padding:1rem; border:1px solid #1f2937; border-radius:0.5rem; background:#020617; color:#cbd5e1;">
                        <div style="display:flex; justify-content:space-between; gap:1rem; align-items:baseline; margin-bottom:0.7rem;">
                            <div>
                                <div style="font-size:0.9rem; font-weight:700; color:#f8fafc;">{{ $sideTitle }}</div>
                                <div style="font-size:0.78rem; color:#94a3b8; margin-top:0.18rem;">
                                    live={{ $side['live_count'] ?? 0 }};
                                    logic={{ $side['logic_family_count'] ?? 0 }};
                                    coverage={{ $coverage['logic_family_count'] ?? 0 }}/{{ $coverage['target_logic_families'] ?? 4 }};
                                    raw={{ $policy['raw_replacement_decision'] ?? ($side['replacement_watch_decision'] ?? '') }}
                                </div>
                            </div>
                            <div style="text-align:right; display:grid; gap:0.22rem;">
                                <div style="font-size:0.78rem; color:{{ $this->priorityColor($policyPriority) }}; font-weight:700;">
                                    {{ $policyPriority }} · {{ $policy['verdict'] ?? 'unknown' }}
                                </div>
                                <div style="font-size:0.74rem; color:#94a3b8;">
                                    {{ $policy['decision'] ?? ($side['replacement_decision'] ?? '') }}
                                </div>
                            </div>
                        </div>

                        @if (! empty($policyHits))
                            <div style="margin-bottom:0.8rem; border:1px solid #334155; border-radius:0.45rem; padding:0.65rem; background:#0f172a; display:grid; gap:0.35rem; font-size:0.76rem; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;">
                                @foreach ($policyHits as $hit)
                                    @php($hitPriority = (string) ($hit['priority'] ?? 'P3'))
                                    <div>
                                        <span style="color:{{ $this->priorityColor($hitPriority) }}; font-weight:700;">{{ $hitPriority }}</span>
                                        <span style="color:#64748b;"> · {{ $hit['rule_id'] ?? '' }} · </span>
                                        <span style="color:#e2e8f0;">{{ $hit['title'] ?? '' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if (! empty($side['live_candidates']))
                            <div style="display:grid; gap:0.8rem;">
                                @foreach ($side['live_candidates'] as $candidate)
                                    @php($health = (string) ($candidate['health'] ?? 'unknown'))
                                    @php($live24 = is_array($candidate['live_windows']['24h'] ?? null) ? $candidate['live_windows']['24h'] : [])
                                    @php($live120 = is_array($candidate['live_windows']['120h'] ?? null) ? $candidate['live_windows']['120h'] : [])
                                    @php($liveAll = is_array($candidate['live_windows']['all_live_test'] ?? null) ? $candidate['live_windows']['all_live_test'] : [])
                                    @php($selection = is_array($candidate['selection'] ?? null) ? $candidate['selection'] : [])
                                    @php($selCurrent = is_array($selection['current'] ?? null) ? $selection['current'] : [])
                                    @php($sel24 = is_array($selection['last24h'] ?? null) ? $selection['last24h'] : [])
                                    @php($sel120 = is_array($selection['last120h'] ?? null) ? $selection['last120h'] : [])
                                    @php($own = is_array($selection['own_period'] ?? null) ? $selection['own_period'] : [])
                                    @php($ml = is_array($candidate['ml'] ?? null) ? $candidate['ml'] : [])
                                    @php($binding = is_array($candidate['period_binding'] ?? null) ? $candidate['period_binding'] : [])
                                    @php($periodChildren = is_array($candidate['period_children'] ?? null) ? $candidate['period_children'] : [])
                                    @php($rec = is_array($candidate['recommended_action'] ?? null) ? $candidate['recommended_action'] : [])
                                    @php($latest = is_array($candidate['latest_trade'] ?? null) ? $candidate['latest_trade'] : [])
                                    <div style="border:1px solid #1f2937; border-radius:0.45rem; padding:0.85rem; background:#0f172a;">
                                        <div style="display:flex; justify-content:space-between; gap:1rem; align-items:flex-start; margin-bottom:0.55rem;">
                                            <div>
                                                <div style="font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.82rem; font-weight:700; color:#e2e8f0;">
                                                    {{ $candidate['candidate_id'] ?? '' }}
                                                </div>
                                                <div style="font-size:0.76rem; color:#94a3b8; margin-top:0.18rem;">
                                                    {{ $candidate['profile'] ?? '' }} · {{ $candidate['logic_root'] ?? '' }} · {{ $candidate['role'] ?? '' }}
                                                </div>
                                            </div>
                                            <div style="text-align:right; display:grid; gap:0.25rem;">
                                                <span style="font-size:0.76rem; color:{{ $this->healthColor($health) }}; font-weight:700;">{{ $health }}</span>
                                                <span style="font-size:0.74rem; color:#94a3b8;">{{ $rec['action'] ?? '' }}</span>
                                            </div>
                                        </div>

                                        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(20rem,1fr)); gap:0.7rem; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:0.78rem; line-height:1.45;">
                                            <div>
                                                <div style="color:#94a3b8; font-weight:700;">LIVE ACTUAL</div>
                                                <div style="color:#86efac;">24h · {{ $this->fmtLiveWindowCompact($live24) }}</div>
                                                <div style="color:#fbbf24;">120h · {{ $this->fmtLiveWindowCompact($live120) }}</div>
                                                <div style="color:#cbd5e1;">all live-test · {{ $this->fmtLiveWindowCompact($liveAll) }}</div>
                                            </div>
                                            <div>
                                                <div style="color:#94a3b8; font-weight:700;">SELECTION REPLAY</div>
                                                <div style="color:#a7f3d0;">current · {{ $this->fmtPeriodCompact($selCurrent) }}</div>
                                                <div style="color:#86efac;">24h · {{ $this->fmtPeriodCompact($sel24) }}</div>
                                                <div style="color:#fbbf24;">120h · {{ $this->fmtPeriodCompact($sel120) }}</div>
                                                <div style="color:#cbd5e1;">own_period · {{ $this->fmtPeriodCompact($own) }}</div>
                                            </div>
                                            <div>
                                                <div style="color:#94a3b8; font-weight:700;">ML / LAST</div>
                                                <div style="color:#cbd5e1;">ML {{ $this->fmtNumber($ml['score'] ?? null, 3) }} · {{ $ml['decision'] ?? '' }} · {{ $ml['revalidation_decision'] ?? '' }}</div>
                                                <div style="color:#e2e8f0;">period · {{ $this->fmtPeriodBinding($binding) }}</div>
                                                @if ($this->fmtBindingTop($binding) !== '')
                                                    <div style="color:#64748b;">events · {{ $this->fmtBindingTop($binding) }}</div>
                                                @endif
                                                @if ($this->fmtPeriodChildren($periodChildren) !== '')
                                                    <div style="color:#94a3b8;">periods · {{ $this->fmtPeriodChildren($periodChildren) }}</div>
                                                @endif
                                                @if (! empty($binding['warnings']) && is_array($binding['warnings']))
                                                    <div style="color:#fbbf24;">period warning · {{ implode(' · ', array_slice($binding['warnings'], 0, 2)) }}</div>
                                                @endif
                                                <div style="color:#64748b;">last 120h potential: {{ $live120['last_potential_kyiv'] ?? '' }}</div>
                                                @if (! empty($latest['trade_id']))
                                                    <div style="color:#94a3b8;">
                                                        trade #{{ $latest['trade_id'] }} · {{ $this->fmtNumber($latest['pnl_usdt'] ?? null, 4) }} USDT · {{ $latest['exit_reason'] ?? '' }}
                                                    </div>
                                                    <div style="color:#64748b;">
                                                        fill {{ $latest['exit_fill_provenance'] ?? ($latest['exit_price_source'] ?? 'n/a') }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        @if (! empty($candidate['reasons']))
                                            <div style="margin-top:0.65rem; padding-top:0.55rem; border-top:1px solid #1f2937; color:#fde68a; font-size:0.76rem; line-height:1.45;">
                                                {{ implode('; ', array_slice($candidate['reasons'], 0, 5)) }}
                                            </div>
                                        @endif

                                        @if (! empty($candidate['replacement_options']))
                                            <div style="margin-top:0.65rem; display:grid; gap:0.5rem; font-size:0.76rem; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;">
                                                @foreach ($candidate['replacement_options'] as $option)
                                                    @php($optCurrent = is_array($option['current'] ?? null) ? $option['current'] : [])
                                                    @php($opt24 = is_array($option['last24h'] ?? null) ? $option['last24h'] : [])
                                                    @php($opt120 = is_array($option['last120h'] ?? null) ? $option['last120h'] : [])
                                                    @php($optOwn = is_array($option['own_period'] ?? null) ? $option['own_period'] : [])
                                                    <div style="border-top:1px solid #1f2937; padding-top:0.45rem; color:#cbd5e1;">
                                                        <div>
                                                            <span style="color:#94a3b8;">option {{ $option['option_type'] ?? '' }}:</span>
                                                            <span style="color:#e2e8f0;">{{ $option['candidate_id'] ?? '' }}</span>
                                                            <span style="color:#64748b;"> · {{ $option['logic_root'] ?? '' }} · score {{ $this->fmtNumber($option['score'] ?? null) }}</span>
                                                        </div>
                                                        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(14rem,1fr)); gap:0.25rem 0.7rem; margin-top:0.25rem;">
                                                            <span style="color:#a7f3d0;">current · {{ $this->fmtPeriodCompact($optCurrent) }}</span>
                                                            <span style="color:#86efac;">24h · {{ $this->fmtPeriodCompact($opt24) }}</span>
                                                            <span style="color:#fbbf24;">120h · {{ $this->fmtPeriodCompact($opt120) }}</span>
                                                            <span style="color:#cbd5e1;">own_period · {{ $this->fmtPeriodCompact($optOwn) }}</span>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div style="font-size:0.85rem; color:#94a3b8;">Немає live-кандидатів для цього напрямку.</div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-panels::page>
