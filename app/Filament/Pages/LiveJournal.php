<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Arr;

class LiveJournal extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Live Journal';
    protected static ?string $title = 'Live Journal';
    protected static ?int $navigationSort = 42;
    protected static string $view = 'filament.pages.live-journal';

    public string $sideFilter = 'all';
    public string $liveStateFilter = 'all';
    public string $signalStatusFilter = 'fresh';
    public string $decisionModeFilter = 'all';
    public string $botFilter = 'active';
    public string $candidateSearch = '';

    public static function canAccess(): bool
    {
        $tenant = app(\App\Services\TenantService::class);

        return $tenant->canSwitch()
            && $tenant->viewingTenantId() === \App\Services\TenantService::PRIMARY_USER_ID;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function getJournal(): array
    {
        $path = '/home/ubuntu/SafaryEngine/data/strategy_search/live_signal_journal_latest.json';

        if (! is_file($path)) {
            return [
                'available' => false,
                'path' => $path,
                'state' => 'missing',
                'generated_at' => '',
                'summary' => [],
                'bot_summary' => [],
                'candidate_summary' => [],
                'exact_decision_snapshots' => [],
                'decision_journal' => [],
                'signal_journal' => [],
                'ml_dataset' => [],
                'live_journal_ml_shadow' => [],
                'entry_preview' => $this->getEntryPreview(),
                'ml_shadow_view' => [],
                'feed_status' => [],
                'known_limitations' => [],
            ];
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data)) {
            return [
                'available' => false,
                'path' => $path,
                'state' => 'invalid_json',
                'generated_at' => '',
                'summary' => [],
                'bot_summary' => [],
                'candidate_summary' => [],
                'exact_decision_snapshots' => [],
                'decision_journal' => [],
                'signal_journal' => [],
                'ml_dataset' => $this->getMlDataset(),
                'live_journal_ml_shadow' => $this->getLiveJournalMlShadow(),
                'entry_preview' => $this->getEntryPreview(),
                'ml_shadow_view' => [],
                'feed_status' => [],
                'known_limitations' => [],
            ];
        }

        return [
            'available' => true,
            'path' => $path,
            'state' => (string) Arr::get($data, 'state', ''),
            'generated_at' => (string) Arr::get($data, 'generated_at_kyiv', Arr::get($data, 'generated_at_utc', '')),
            'summary' => is_array(Arr::get($data, 'summary')) ? Arr::get($data, 'summary') : [],
            'bot_summary' => is_array(Arr::get($data, 'bot_summary')) ? Arr::get($data, 'bot_summary') : [],
            'candidate_summary' => is_array(Arr::get($data, 'candidate_summary')) ? Arr::get($data, 'candidate_summary') : [],
            'exact_decision_snapshots' => is_array(Arr::get($data, 'exact_decision_snapshots')) ? Arr::get($data, 'exact_decision_snapshots') : [],
            'decision_journal' => is_array(Arr::get($data, 'decision_journal')) ? Arr::get($data, 'decision_journal') : [],
            'signal_journal' => is_array(Arr::get($data, 'signal_journal')) ? Arr::get($data, 'signal_journal') : [],
            'ml_dataset' => $this->getMlDataset(),
            'live_journal_ml_shadow' => $this->getLiveJournalMlShadow(),
            'entry_preview' => $this->getEntryPreview(),
            'ml_shadow_view' => is_array(Arr::get($data, 'ml_shadow_view')) ? Arr::get($data, 'ml_shadow_view') : [],
            'feed_status' => is_array(Arr::get($data, 'feed_status')) ? Arr::get($data, 'feed_status') : [],
            'known_limitations' => is_array(Arr::get($data, 'known_limitations')) ? Arr::get($data, 'known_limitations') : [],
        ];
    }

    public function setSideFilter(string $value): void
    {
        $this->sideFilter = in_array($value, ['all', 'long', 'short'], true) ? $value : 'all';
    }

    public function setLiveStateFilter(string $value): void
    {
        $this->liveStateFilter = in_array($value, ['all', 'live_enabled', 'not_in_live_config'], true) ? $value : 'all';
    }

    public function setSignalStatusFilter(string $value): void
    {
        $this->signalStatusFilter = in_array($value, ['all', 'fresh', 'executed', 'replay'], true) ? $value : 'all';
    }

    public function setDecisionModeFilter(string $value): void
    {
        $this->decisionModeFilter = in_array($value, ['all', 'exact', 'reconstructed'], true) ? $value : 'all';
    }

    public function setBotFilter(string $value): void
    {
        $this->botFilter = in_array($value, ['active', 'all'], true) ? $value : 'active';
    }

    public function getAnalysis(): array
    {
        $journal = $this->getJournal();
        $signals = is_array($journal['signal_journal'] ?? null) ? $journal['signal_journal'] : [];
        $candidates = is_array($journal['candidate_summary'] ?? null) ? $journal['candidate_summary'] : [];
        $decisions = is_array($journal['decision_journal'] ?? null) ? $journal['decision_journal'] : [];
        $exact = is_array($journal['exact_decision_snapshots'] ?? null) ? $journal['exact_decision_snapshots'] : [];
        $bots = is_array($journal['bot_summary'] ?? null) ? $journal['bot_summary'] : [];

        $health = $this->candidateHealthRows($candidates);
        $missed = $this->missedOpportunityRows($signals);
        $filteredSignals = array_values(array_filter($signals, fn (array $signal) => $this->signalMatchesFilters($signal)));
        $filteredDecisions = array_values(array_filter($decisions, fn (array $decision) => $this->decisionMatchesFilters($decision)));

        return [
            'journal' => $journal,
            'bots' => $this->filteredBots($bots),
            'candidate_health' => array_values(array_filter($health, fn (array $row) => $this->candidateMatchesFilters($row))),
            'missed_opportunities' => $missed,
            'signals' => array_slice($filteredSignals, 0, 80),
            'decisions' => array_slice($filteredDecisions, 0, 40),
            'exact_decision_snapshots' => array_slice($exact, 0, 20),
            'entry_preview_rows' => $this->entryPreviewRows($journal),
            'timeline' => array_slice($this->timelineRows($filteredSignals, $filteredDecisions, $exact, $bots), 0, 80),
            'actionable' => $this->actionableSummary($journal, $signals, $health, $missed),
        ];
    }

    private function actionableSummary(array $journal, array $signals, array $health, array $missed): array
    {
        $freshLive = 0;
        $freshNotLive = 0;
        $executedLive = 0;
        foreach ($signals as $signal) {
            if (! is_array($signal)) {
                continue;
            }
            if ($this->signalIsCurrentlyFresh($signal) && ($signal['live_state'] ?? '') === 'live_enabled') {
                $freshLive++;
            }
            if ($this->signalIsCurrentlyFresh($signal) && ($signal['live_state'] ?? '') === 'not_in_live_config') {
                $freshNotLive++;
            }
            if (($signal['live_state'] ?? '') === 'live_enabled' && str_contains((string) ($signal['status'] ?? ''), 'executed')) {
                $executedLive++;
            }
        }

        $silent = array_values(array_filter($health, fn (array $row) => ($row['health'] ?? '') === 'silent'));
        $losing = array_values(array_filter($health, fn (array $row) => ($row['health'] ?? '') === 'losing'));
        $dataset = is_array($journal['live_journal_ml_shadow']['counts'] ?? null) ? $journal['live_journal_ml_shadow']['counts'] : [];
        $entryPreview = is_array($journal['entry_preview'] ?? null) ? $journal['entry_preview'] : [];
        $entrySummary = is_array($entryPreview['summary'] ?? null) ? $entryPreview['summary'] : [];

        return [
            'fresh_live' => $freshLive,
            'fresh_not_live' => $freshNotLive,
            'executed_live' => $executedLive,
            'entry_would_allow' => (int) Arr::get($entrySummary, 'would_allow_entry', 0),
            'entry_waiting_fresh' => (int) Arr::get($entrySummary, 'waiting_fresh_signal', 0),
            'entry_monitoring' => (int) Arr::get($entrySummary, 'position_open_monitoring', 0),
            'entry_blocked' => (int) Arr::get($entrySummary, 'blocked', 0),
            'entry_fresh_or_open' => (int) Arr::get($entrySummary, 'event_fresh_or_open', 0),
            'silent_live_candidates' => count($silent),
            'losing_live_candidates' => count($losing),
            'missed_candidates' => count($missed),
            'open_positions' => (int) Arr::get($journal, 'summary.open_positions', 0),
            'open_hedge_overlaps' => (int) Arr::get($journal, 'summary.open_hedge_overlap_positions', 0),
            'exact_snapshots' => (int) Arr::get($journal, 'summary.exact_decision_snapshots_loaded', 0),
            'ml_labeled' => (int) Arr::get($dataset, 'labeled_rows', 0),
            'ml_min' => (int) Arr::get($dataset, 'min_labeled_for_model', 0),
            'top_silent' => array_slice($silent, 0, 4),
            'top_losing' => array_slice($losing, 0, 4),
        ];
    }

    private function candidateHealthRows(array $candidates): array
    {
        $rows = [];
        foreach ($candidates as $candidate) {
            if (! is_array($candidate)) {
                continue;
            }
            $w24 = is_array(Arr::get($candidate, 'windows.24h')) ? Arr::get($candidate, 'windows.24h') : [];
            $w120 = is_array(Arr::get($candidate, 'windows.120h')) ? Arr::get($candidate, 'windows.120h') : [];
            $e24 = is_array(Arr::get($w24, 'executed')) ? Arr::get($w24, 'executed') : [];
            $e120 = is_array(Arr::get($w120, 'executed')) ? Arr::get($w120, 'executed') : [];
            $ml = is_array($candidate['ml'] ?? null) ? $candidate['ml'] : [];
            $potential24 = (int) Arr::get($w24, 'potential_entries_live_seen', 0);
            $potential120 = (int) Arr::get($w120, 'potential_entries_live_seen', 0);
            $net120 = (float) Arr::get($e120, 'net_usdt', 0);
            $trades120 = (int) Arr::get($e120, 'trades', 0);
            $losses120 = (int) Arr::get($e120, 'losses', 0);

            $health = 'watch';
            if ($potential120 === 0 && ! $trades120) {
                $health = 'silent';
            } elseif ($trades120 > 0 && $losses120 >= max(1, (int) Arr::get($e120, 'wins', 0)) && $net120 < 0) {
                $health = 'losing';
            } elseif ($potential24 > 0 || $trades120 > 0) {
                $health = 'active';
            }

            $rows[] = [
                'side' => (string) ($candidate['side'] ?? ''),
                'candidate_id' => (string) ($candidate['candidate_id'] ?? ''),
                'component' => (string) ($candidate['component'] ?? ''),
                'profile' => (string) ($candidate['profile'] ?? ''),
                'role' => (string) ($candidate['role'] ?? ''),
                'live_enabled' => (bool) ($candidate['live_enabled'] ?? false),
                'health' => $health,
                'potential_24h' => $potential24,
                'potential_120h' => $potential120,
                'signals_24h' => (int) Arr::get($w24, 'signals_live_seen', 0),
                'signals_120h' => (int) Arr::get($w120, 'signals_live_seen', 0),
                'trades_24h' => (int) Arr::get($e24, 'trades', 0),
                'wins_24h' => (int) Arr::get($e24, 'wins', 0),
                'losses_24h' => (int) Arr::get($e24, 'losses', 0),
                'net_24h' => (float) Arr::get($e24, 'net_usdt', 0),
                'trades_120h' => $trades120,
                'wins_120h' => (int) Arr::get($e120, 'wins', 0),
                'losses_120h' => $losses120,
                'net_120h' => $net120,
                'last_signal' => (string) (Arr::get($w24, 'last_signal_kyiv') ?: Arr::get($w120, 'last_signal_kyiv', '')),
                'ml_score' => Arr::get($ml, 'ml_score'),
                'ml_decision' => (string) Arr::get($ml, 'decision', 'unknown'),
                'why' => is_array(Arr::get($ml, 'why')) ? Arr::get($ml, 'why') : [],
                'why_not' => is_array(Arr::get($ml, 'why_not')) ? Arr::get($ml, 'why_not') : [],
            ];
        }

        usort($rows, function (array $a, array $b): int {
            $rank = ['active' => 0, 'losing' => 1, 'watch' => 2, 'silent' => 3];
            return [$a['side'], $rank[$a['health']] ?? 9, -$a['potential_24h'], -$a['potential_120h']]
                <=> [$b['side'], $rank[$b['health']] ?? 9, -$b['potential_24h'], -$b['potential_120h']];
        });

        return $rows;
    }

    private function missedOpportunityRows(array $signals): array
    {
        $grouped = [];
        foreach ($signals as $signal) {
            if (! is_array($signal) || ! $this->sideMatches((string) ($signal['side'] ?? ''))) {
                continue;
            }
            if (($signal['live_state'] ?? '') !== 'not_in_live_config' || ! $this->signalIsCurrentlyFresh($signal)) {
                continue;
            }
            $key = implode('|', [
                $signal['side'] ?? '',
                $signal['candidate_id'] ?? '',
                $signal['component'] ?? '',
                $signal['profile'] ?? '',
            ]);
            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'side' => (string) ($signal['side'] ?? ''),
                    'candidate_id' => (string) ($signal['candidate_id'] ?? ''),
                    'component' => (string) ($signal['component'] ?? ''),
                    'profile' => (string) ($signal['profile'] ?? ''),
                    'count' => 0,
                    'latest_signal' => (string) ($signal['open_kyiv'] ?? ''),
                    'latest_price' => Arr::get($signal, 'signal_price'),
                    'quality_score' => Arr::get($signal, 'quality_score'),
                    'expires_at' => (string) ($signal['expires_at_utc'] ?? ''),
                ];
            }
            $grouped[$key]['count']++;
        }

        $rows = array_values($grouped);
        usort($rows, fn (array $a, array $b): int => [$b['count'], (float) $b['quality_score']] <=> [$a['count'], (float) $a['quality_score']]);
        return array_slice($rows, 0, 12);
    }

    private function entryPreviewRows(array $journal): array
    {
        $preview = is_array($journal['entry_preview'] ?? null) ? $journal['entry_preview'] : [];
        $decisions = is_array($preview['decisions'] ?? null) ? $preview['decisions'] : [];
        $accountPositions = is_array(Arr::get($preview, 'okx_position_check.positions.items'))
            ? Arr::get($preview, 'okx_position_check.positions.items')
            : [];
        $rows = [];

        foreach ($decisions as $decision) {
            if (! is_array($decision)) {
                continue;
            }
            $side = (string) ($decision['side'] ?? '');
            if (! $this->sideMatches($side)) {
                continue;
            }
            $searchText = implode(' ', [
                $decision['bot_slug'] ?? '',
                $decision['component'] ?? '',
                $decision['lane'] ?? '',
                $decision['profile'] ?? '',
                Arr::get($decision, 'event_driven.candidate_id', ''),
            ]);
            if (! $this->textMatchesSearch($searchText)) {
                continue;
            }

            $event = is_array($decision['event_driven'] ?? null) ? $decision['event_driven'] : [];
            $feed = is_array($decision['feed'] ?? null) ? $decision['feed'] : [];
            $feedCounts = is_array($feed['counts'] ?? null) ? $feed['counts'] : [];
            $generatedAt = (string) ($feed['generated_at_kyiv'] ?? $feed['generated_at'] ?? '');
            $ageMin = $this->ageMinutes((string) ($feed['generated_at'] ?? ''));
            $opposite = $side === 'long' ? 'short' : ($side === 'short' ? 'long' : '');
            $oppositeOpen = array_values(array_filter($accountPositions, function (array $position) use ($opposite): bool {
                return $opposite !== '' && (string) ($position['side'] ?? '') === $opposite;
            }));

            $rows[] = [
                'bot_slug' => (string) ($decision['bot_slug'] ?? ''),
                'side' => $side,
                'decision' => (string) ($decision['decision'] ?? ''),
                'cycle_state' => (string) ($decision['cycle_state'] ?? ''),
                'has_open_position' => (bool) ($decision['has_open_position'] ?? false),
                'component' => (string) ($decision['component'] ?? ''),
                'lane' => (string) ($decision['lane'] ?? ''),
                'profile' => (string) ($decision['profile'] ?? ''),
                'fresh_signal_count' => (int) ($decision['fresh_signal_count'] ?? 0),
                'potential_entries' => (int) Arr::get($event, 'potential_entries', 0),
                'entries_per_day' => Arr::get($event, 'entries_per_day'),
                'fresh_or_open' => (int) Arr::get($event, 'fresh_or_open', 0),
                'live_wins' => (int) Arr::get($event, 'live_wins', 0),
                'live_losses' => (int) Arr::get($event, 'live_losses', 0),
                'live_pnl_usdt' => (float) Arr::get($event, 'live_pnl_usdt', 0),
                'last_potential_kyiv' => (string) Arr::get($event, 'last_potential_kyiv', ''),
                'last_potential_age_min' => Arr::get($event, 'last_potential_age_min'),
                'ml_score' => Arr::get($decision, 'ml_score'),
                'ml_decision' => (string) ($decision['ml_decision'] ?? ''),
                'router_score' => Arr::get($decision, 'router_score'),
                'portfolio_state' => count($oppositeOpen) > 0 ? 'hedge_if_entry' : 'solo_if_entry',
                'opposite_open_count' => count($oppositeOpen),
                'opposite_open_notional' => round(array_sum(array_map(fn (array $item): float => (float) ($item['notional'] ?? 0), $oppositeOpen)), 4),
                'feed_generated_at' => $generatedAt,
                'feed_age_min' => $ageMin,
                'feed_fresh_signals' => (int) Arr::get($feedCounts, 'fresh_signals', 0),
                'feed_age_window_signals' => (int) Arr::get($feedCounts, 'age_window_signals', 0),
                'reasons' => array_slice(is_array($decision['reasons'] ?? null) ? $decision['reasons'] : [], 0, 5),
            ];
        }

        usort($rows, fn (array $a, array $b): int => [$a['side'], $a['bot_slug']] <=> [$b['side'], $b['bot_slug']]);

        return $rows;
    }

    private function filteredBots(array $bots): array
    {
        return array_values(array_filter($bots, function (array $bot): bool {
            if ($this->botFilter === 'all') {
                return true;
            }
            $status = (string) ($bot['status'] ?? '');
            $slug = (string) ($bot['slug'] ?? '');
            return in_array($status, ['running', 'paused'], true) || str_starts_with($slug, 'HYPE_USDT_USDT_');
        }));
    }

    private function timelineRows(array $signals, array $decisions, array $exact, array $bots): array
    {
        $rows = [];
        foreach ($bots as $bot) {
            if (! is_array($bot) || empty($bot['has_position']) || ! is_array($bot['position'] ?? null)) {
                continue;
            }
            $position = $bot['position'];
            $entrySignal = is_array($position['entry_signal'] ?? null) ? $position['entry_signal'] : [];
            $side = (string) ($position['side_str'] ?? $bot['side'] ?? '');
            if (! $this->sideMatches($side)) {
                continue;
            }
            $candidate = (string) ($entrySignal['candidate_id'] ?? '');
            if (! $this->textMatchesSearch($candidate . ' ' . ($entrySignal['component'] ?? '') . ' ' . ($entrySignal['profile'] ?? ''))) {
                continue;
            }
            $ts = (int) (($position['opened_at_ms'] ?? 0) / 1000);
            $rows[] = [
                'ts' => $ts,
                'time' => $this->kyivFromTs($ts),
                'type' => 'open_position',
                'side' => $side,
                'candidate_id' => $candidate,
                'component' => (string) ($entrySignal['component'] ?? ''),
                'profile' => (string) ($entrySignal['profile'] ?? ''),
                'status' => 'open',
                'live_state' => 'live_enabled',
                'pnl' => (float) ($position['pnl_usdt'] ?? 0),
                'detail' => 'bars=' . (int) ($position['bars_held'] ?? 0)
                    . '; margin=' . round((float) ($position['pnl_pct'] ?? 0), 3) . '%'
                    . '; portfolio=' . (string) Arr::get($bot, 'position_portfolio_context.overlap_kind', 'solo'),
            ];
        }

        foreach ($exact as $snapshot) {
            if (! is_array($snapshot)) {
                continue;
            }
            $selected = is_array($snapshot['selected_signal'] ?? null) ? $snapshot['selected_signal'] : [];
            $bot = is_array($snapshot['bot'] ?? null) ? $snapshot['bot'] : [];
            $side = (string) ($selected['side'] ?? $bot['side'] ?? '');
            if (! $this->sideMatches($side)) {
                continue;
            }
            $candidate = (string) ($selected['candidate_id'] ?? $selected['id'] ?? '');
            if (! $this->textMatchesSearch($candidate . ' ' . ($selected['component'] ?? '') . ' ' . ($selected['profile'] ?? ''))) {
                continue;
            }
            $ts = $this->timestampFromUtc((string) ($snapshot['created_at_utc'] ?? ''));
            $rows[] = [
                'ts' => $ts,
                'time' => $this->kyivFromTs($ts),
                'type' => 'exact_decision',
                'side' => $side,
                'candidate_id' => $candidate,
                'component' => (string) ($selected['component'] ?? ''),
                'profile' => (string) ($selected['profile'] ?? ''),
                'status' => ! empty($snapshot['entry_allowed']) ? 'allowed' : 'blocked',
                'live_state' => 'live_enabled',
                'pnl' => null,
                'detail' => 'pool=' . count($snapshot['candidate_pool'] ?? []) . '; ML observe; ' . (string) ($snapshot['selected_reason'] ?? ''),
            ];
        }

        foreach ($decisions as $decision) {
            if (! is_array($decision)) {
                continue;
            }
            $trade = is_array($decision['trade'] ?? null) ? $decision['trade'] : [];
            $side = (string) ($trade['side'] ?? '');
            if (! $this->sideMatches($side)) {
                continue;
            }
            $candidate = (string) ($trade['entry_candidate_id'] ?? Arr::get($decision, 'selected_signal.candidate_id', ''));
            if (! $this->textMatchesSearch($candidate . ' ' . ($trade['entry_component'] ?? '') . ' ' . ($trade['entry_profile'] ?? ''))) {
                continue;
            }
            $ts = $this->timestampFromUtc((string) ($trade['exit_time'] ?? $trade['entry_time'] ?? ''));
            $rows[] = [
                'ts' => $ts,
                'time' => $this->kyivFromTs($ts),
                'type' => empty($trade['exit_time']) ? 'trade_open' : 'trade_closed',
                'side' => $side,
                'candidate_id' => $candidate,
                'component' => (string) ($trade['entry_component'] ?? ''),
                'profile' => (string) ($trade['entry_profile'] ?? ''),
                'status' => (string) ($trade['exit_reason'] ?? 'open'),
                'live_state' => 'live_enabled',
                'pnl' => isset($trade['pnl_usdt']) ? (float) $trade['pnl_usdt'] : null,
                'detail' => 'MFE=' . Arr::get($trade, 'path_context.mfe_pct', 'n/a')
                    . '%; MAE=' . Arr::get($trade, 'path_context.mae_pct', 'n/a')
                    . '%; score=' . Arr::get($trade, 'scores.total_score', 'n/a')
                    . '; portfolio=' . Arr::get($trade, 'portfolio_context.overlap_kind', 'solo'),
            ];
        }

        foreach ($signals as $signal) {
            if (! is_array($signal)) {
                continue;
            }
            $ts = $this->timestampFromUtc((string) ($signal['open_utc'] ?? ''));
            $rows[] = [
                'ts' => $ts,
                'time' => (string) ($signal['open_kyiv'] ?? $this->kyivFromTs($ts)),
                'type' => 'signal',
                'side' => (string) ($signal['side'] ?? ''),
                'candidate_id' => (string) ($signal['candidate_id'] ?? ''),
                'component' => (string) ($signal['component'] ?? ''),
                'profile' => (string) ($signal['profile'] ?? ''),
                'status' => (string) ($signal['status'] ?? ''),
                'live_state' => (string) ($signal['live_state'] ?? ''),
                'pnl' => null,
                'detail' => 'quality=' . (is_numeric($signal['quality_score'] ?? null) ? round((float) $signal['quality_score'], 3) : 'n/a'),
            ];
        }

        usort($rows, fn (array $a, array $b): int => ($b['ts'] ?? 0) <=> ($a['ts'] ?? 0));
        return $rows;
    }

    private function candidateMatchesFilters(array $candidate): bool
    {
        if (! $this->sideMatches((string) ($candidate['side'] ?? ''))) {
            return false;
        }
        $needle = trim(mb_strtolower($this->candidateSearch));
        if ($needle !== '') {
            if (! $this->textMatchesSearch(implode(' ', [
                $candidate['candidate_id'] ?? '',
                $candidate['component'] ?? '',
                $candidate['profile'] ?? '',
                $candidate['role'] ?? '',
            ]))) {
                return false;
            }
        }
        return true;
    }

    private function signalMatchesFilters(array $signal): bool
    {
        if (! $this->sideMatches((string) ($signal['side'] ?? ''))) {
            return false;
        }
        if ($this->liveStateFilter !== 'all' && ($signal['live_state'] ?? '') !== $this->liveStateFilter) {
            return false;
        }
        $status = (string) ($signal['status'] ?? '');
        if ($this->signalStatusFilter === 'fresh' && ! $this->signalIsCurrentlyFresh($signal)) {
            return false;
        }
        if ($this->signalStatusFilter === 'executed' && ! str_contains($status, 'executed')) {
            return false;
        }
        if ($this->signalStatusFilter === 'replay' && ! str_contains($status, 'replay')) {
            return false;
        }
        $needle = trim(mb_strtolower($this->candidateSearch));
        if ($needle !== '') {
            if (! $this->textMatchesSearch(implode(' ', [
                $signal['candidate_id'] ?? '',
                $signal['component'] ?? '',
                $signal['profile'] ?? '',
            ]))) {
                return false;
            }
        }
        return true;
    }

    private function decisionMatchesFilters(array $decision): bool
    {
        $trade = is_array($decision['trade'] ?? null) ? $decision['trade'] : [];
        if (! $this->sideMatches((string) ($trade['side'] ?? ''))) {
            return false;
        }
        if ($this->decisionModeFilter === 'exact' && ! empty($decision['reconstructed'])) {
            return false;
        }
        if ($this->decisionModeFilter === 'reconstructed' && empty($decision['reconstructed'])) {
            return false;
        }
        $needle = trim(mb_strtolower($this->candidateSearch));
        if ($needle !== '') {
            if (! $this->textMatchesSearch(implode(' ', [
                $trade['entry_candidate_id'] ?? '',
                $trade['entry_component'] ?? '',
                $trade['entry_profile'] ?? '',
                Arr::get($decision, 'selected_signal.candidate_id', ''),
            ]))) {
                return false;
            }
        }
        return true;
    }

    private function sideMatches(string $side): bool
    {
        return $this->sideFilter === 'all' || $side === $this->sideFilter;
    }

    private function textMatchesSearch(string $text): bool
    {
        $needle = trim(mb_strtolower($this->candidateSearch));
        return $needle === '' || str_contains(mb_strtolower($text), $needle);
    }

    private function timestampFromUtc(string $text): int
    {
        if ($text === '') {
            return 0;
        }
        $ts = strtotime($text);
        return $ts === false ? 0 : $ts;
    }

    private function kyivFromTs(int $ts): string
    {
        if ($ts <= 0) {
            return '';
        }
        return \Carbon\Carbon::createFromTimestamp($ts, 'UTC')->setTimezone('Europe/Kyiv')->format('Y-m-d H:i') . ' Kyiv';
    }

    private function ageMinutes(string $utcText): ?int
    {
        if ($utcText === '') {
            return null;
        }
        $ts = strtotime($utcText);
        if ($ts === false) {
            return null;
        }
        return max(0, (int) floor((time() - $ts) / 60));
    }

    private function signalIsCurrentlyFresh(array $signal): bool
    {
        if (($signal['status'] ?? '') !== 'fresh' || ! ($signal['is_fresh'] ?? false)) {
            return false;
        }
        $expiresAt = (string) ($signal['expires_at_utc'] ?? '');
        if ($expiresAt === '') {
            return true;
        }
        $ts = strtotime($expiresAt);
        return $ts === false || $ts >= time();
    }

    private function getMlDataset(): array
    {
        $path = '/home/ubuntu/SafaryEngine/data/strategy_search/ml_shadow/live_journal_ml_dataset_latest.json';
        if (! is_file($path)) {
            return [
                'available' => false,
                'path' => $path,
                'state' => 'missing',
                'generated_at' => '',
                'counts' => [],
                'rules' => [],
            ];
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data)) {
            return [
                'available' => false,
                'path' => $path,
                'state' => 'invalid_json',
                'generated_at' => '',
                'counts' => [],
                'rules' => [],
            ];
        }

        return [
            'available' => true,
            'path' => $path,
            'state' => (string) Arr::get($data, 'state', ''),
            'generated_at' => (string) Arr::get($data, 'generated_at_kyiv', Arr::get($data, 'generated_at_utc', '')),
            'counts' => is_array(Arr::get($data, 'counts')) ? Arr::get($data, 'counts') : [],
            'rules' => is_array(Arr::get($data, 'rules')) ? Arr::get($data, 'rules') : [],
        ];
    }

    private function getEntryPreview(): array
    {
        $path = '/home/ubuntu/SafaryEngine/data/strategy_search/live_entry_decision_preview_latest.json';
        if (! is_file($path)) {
            return [
                'available' => false,
                'state' => 'missing',
                'generated_at' => '',
                'summary' => [],
                'decisions' => [],
            ];
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data)) {
            return [
                'available' => false,
                'state' => 'invalid_json',
                'generated_at' => '',
                'summary' => [],
                'decisions' => [],
            ];
        }

        return [
            'available' => true,
            'state' => (string) Arr::get($data, 'state', ''),
            'generated_at' => (string) Arr::get($data, 'generated_at_kyiv', Arr::get($data, 'generated_at_utc', '')),
            'summary' => is_array(Arr::get($data, 'summary')) ? Arr::get($data, 'summary') : [],
            'decisions' => is_array(Arr::get($data, 'decisions')) ? Arr::get($data, 'decisions') : [],
        ];
    }

    private function getLiveJournalMlShadow(): array
    {
        $path = '/home/ubuntu/SafaryEngine/data/strategy_search/ml_shadow/live_journal_ml_shadow_latest.json';
        if (! is_file($path)) {
            return [
                'available' => false,
                'state' => 'missing',
                'generated_at' => '',
                'counts' => [],
            ];
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data)) {
            return [
                'available' => false,
                'state' => 'invalid_json',
                'generated_at' => '',
                'counts' => [],
            ];
        }

        return [
            'available' => true,
            'state' => (string) Arr::get($data, 'state', ''),
            'generated_at' => (string) Arr::get($data, 'generated_at_kyiv', Arr::get($data, 'generated_at_utc', '')),
            'mode' => (string) Arr::get($data, 'mode', ''),
            'counts' => is_array(Arr::get($data, 'counts')) ? Arr::get($data, 'counts') : [],
        ];
    }
}
