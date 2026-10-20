<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Arr;

class Results extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Result';
    protected static ?string $title = 'Result';
    protected static ?int $navigationSort = 40;
    protected static string $view = 'filament.pages.results';

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

    public function getCodexResult(): array
    {
        $path = '/home/ubuntu/SafaryEngine/data/codex_result_status.json';
        $activeStrategy = $this->readActiveStrategyStatus();

        if (! is_file($path)) {
            if (! empty($activeStrategy)) {
                return $activeStrategy;
            }
            return [
                'title' => 'Очікування',
                'status' => 'Поки немає активної задачі.',
                'progress' => 0,
                'updated_at' => null,
            'details' => 'Тут буде виводитися статистика та результат по торговому боту.',
            'result' => '',
            'plan' => [],
            ];
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data)) {
            if (! empty($activeStrategy)) {
                return $activeStrategy;
            }
            return [
                'title' => 'Помилка читання',
                'status' => 'Файл статусу пошкоджений.',
                'progress' => 0,
                'updated_at' => null,
                'details' => '',
                'result' => '',
                'plan' => [],
            ];
        }

        if (! empty($activeStrategy)) {
            return $activeStrategy;
        }

        if ((string) Arr::get($data, 'title', '') === 'Background Supervisor') {
            return [
                'title' => 'Очікування',
                'status' => 'Основний Result вільний. Фони/CPU винесені в окремий блок нижче.',
                'progress' => 0,
                'updated_at' => Arr::get($data, 'updated_at'),
                'details' => '',
                'result' => '',
                'plan' => [],
            ];
        }

        return [
            'title' => (string) Arr::get($data, 'title', 'Codex status'),
            'status' => (string) Arr::get($data, 'status', ''),
            'progress' => max(0, min(100, (int) Arr::get($data, 'progress', 0))),
            'updated_at' => Arr::get($data, 'updated_at'),
            'details' => (string) Arr::get($data, 'details', ''),
            'result' => (string) Arr::get($data, 'result', ''),
            'plan' => is_array(Arr::get($data, 'plan')) ? Arr::get($data, 'plan') : [],
        ];
    }

    private function readActiveStrategyStatus(): array
    {
        $assetOrchestrators = [
            '/home/ubuntu/SafaryEngine/data/strategy_search/assets/sol/orchestrator/sol_research_orchestrator_latest.json',
        ];
        foreach ($assetOrchestrators as $candidatePath) {
            if (! is_file($candidatePath)) {
                continue;
            }
            $data = json_decode((string) file_get_contents($candidatePath), true);
            if (! is_array($data)) {
                continue;
            }
            $mtime = (int) filemtime($candidatePath);
            $state = (string) Arr::get($data, 'state', '');
            $isFresh = $mtime >= (time() - 3600);
            if (! in_array($state, ['running', 'done', 'candidate_review_hold', 'failed_audit', 'failed_autopilot'], true) || ! $isFresh) {
                continue;
            }
            $lastCycle = collect(Arr::get($data, 'cycles', []))->last();
            $top = is_array($lastCycle) ? Arr::get($lastCycle, 'top_ranker', []) : [];
            $own = is_array($top) ? Arr::get($top, 'own_period', []) : [];
            $w120 = is_array($top) ? Arr::get($top, 'w120', []) : [];
            $details = sprintf(
                'cycle=%s profile=%s top=%s own potential %s PF %s net %s%% DD %s%%; 120h %s net %s%%',
                (string) Arr::get($data, 'cycle', ''),
                is_array($lastCycle) ? (string) Arr::get($lastCycle, 'profile', '') : '',
                (string) Arr::get($top, 'top_ranker_id', ''),
                (string) Arr::get($own, 'potential_entries', 0),
                (string) Arr::get($own, 'pf', 0),
                (string) Arr::get($own, 'net_pct', 0),
                (string) Arr::get($own, 'max_dd_pct', 0),
                (string) Arr::get($w120, 'potential_entries', 0),
                (string) Arr::get($w120, 'net_pct', 0),
            );

            return [
                'title' => strtoupper((string) Arr::get($data, 'asset', 'asset')) . ' Research Orchestrator',
                'status' => $state,
                'progress' => $state === 'running' ? 50 : 100,
                'updated_at' => (string) Arr::get($data, 'generated_at', date('Y-m-d H:i:s', $mtime)),
                'details' => $details,
                'result' => (string) Arr::get($data, 'last_action', ''),
                'plan' => [
                    '1. read memory',
                    '2. choose next research profile',
                    '3. run autopilot',
                    '4. hard audit',
                    '5. save planner result',
                ],
            ];
        }

        $candidates = [
            '/home/ubuntu/SafaryEngine/data/strategy_search/strategy_module/strategy_v4_3_hybrid_stage0_queue_status.json',
            '/home/ubuntu/SafaryEngine/data/strategy_search/strategy_module/strategy_v4_3_night_autopilot_status.json',
        ];
        $best = [];
        $bestMtime = 0;
        foreach ($candidates as $candidatePath) {
            if (! is_file($candidatePath)) {
                continue;
            }
            $data = json_decode((string) file_get_contents($candidatePath), true);
            if (! is_array($data) || (string) Arr::get($data, 'status', '') !== 'running') {
                continue;
            }
            $mtime = (int) filemtime($candidatePath);
            if ($mtime >= $bestMtime) {
                $bestMtime = $mtime;
                $best = $data;
            }
        }
        if (empty($best)) {
            return [];
        }

        return [
            'title' => (string) Arr::get($best, 'title', 'Strategy status'),
            'status' => (string) Arr::get($best, 'status', ''),
            'progress' => max(0, min(100, (int) Arr::get($best, 'progress', 0))),
            'updated_at' => Arr::get($best, 'updated_at'),
            'details' => (string) Arr::get($best, 'details', ''),
            'result' => (string) Arr::get($best, 'result', ''),
            'plan' => is_array(Arr::get($best, 'plan')) ? Arr::get($best, 'plan') : [],
        ];
    }

    public function getControlTower(): array
    {
        $path = '/home/ubuntu/SafaryEngine/data/control_tower/control_tower_latest.json';

        if (! is_file($path)) {
            return [
                'available' => false,
                'generated_at' => '',
                'state' => '',
                'counts' => [],
                'current_period' => [],
                'top_risks' => [],
                'safe_auto_candidates' => [],
                'requires_user_go' => [],
            ];
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data)) {
            return [
                'available' => false,
                'generated_at' => '',
                'state' => 'read_error',
                'counts' => [],
                'current_period' => [],
                'top_risks' => [],
                'safe_auto_candidates' => [],
                'requires_user_go' => [],
            ];
        }

        return [
            'available' => true,
            'generated_at' => (string) Arr::get($data, 'generated_at_kyiv', Arr::get($data, 'generated_at_utc', '')),
            'state' => (string) Arr::get($data, 'state', ''),
            'mode' => (string) Arr::get($data, 'mode', ''),
            'counts' => is_array(Arr::get($data, 'counts')) ? Arr::get($data, 'counts') : [],
            'current_period' => is_array(Arr::get($data, 'current_period')) ? Arr::get($data, 'current_period') : [],
            'top_risks' => is_array(Arr::get($data, 'top_risks')) ? array_slice(Arr::get($data, 'top_risks'), 0, 6) : [],
            'safe_auto_candidates' => is_array(Arr::get($data, 'safe_auto_candidates')) ? array_slice(Arr::get($data, 'safe_auto_candidates'), 0, 5) : [],
            'requires_user_go' => is_array(Arr::get($data, 'requires_user_go')) ? array_slice(Arr::get($data, 'requires_user_go'), 0, 5) : [],
        ];
    }

    public function getBackgroundHealth(): array
    {
        $path = '/home/ubuntu/SafaryEngine/data/strategy_search/background_health_latest.json';

        if (! is_file($path)) {
            return [
                'available' => false,
                'updated_at' => null,
                'cpu' => '',
                'cpu_metrics' => [],
                'counts' => [],
                'running' => [],
                'canonical_candidate_refresh' => [],
            ];
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data)) {
            return [
                'available' => false,
                'updated_at' => null,
                'cpu' => '',
                'cpu_metrics' => [],
                'counts' => [],
                'running' => [],
                'canonical_candidate_refresh' => [],
            ];
        }

        $resources = Arr::get($data, 'resources', []);
        $cpu = is_array(Arr::get($resources, 'cpu')) ? Arr::get($resources, 'cpu') : [];
        $cores = (int) Arr::get($cpu, 'cpu_count', 0);
        $usage = Arr::get($cpu, 'usage_pct');
        $interval = Arr::get($cpu, 'interval_sec');
        $load5 = Arr::get($cpu, 'loadavg_5m');
        $load5Pct = Arr::get($cpu, 'loadavg_5m_pct_of_cores');

        $usageFloat = is_numeric($usage) ? (float) $usage : null;
        $load5Float = is_numeric($load5) ? (float) $load5 : null;
        $load5PctFloat = is_numeric($load5Pct) ? (float) $load5Pct : ($load5Float !== null && $cores > 0 ? $load5Float / $cores * 100.0 : null);

        $cpuText = 'CPU: немає даних';
        if ($usage !== null) {
            $cpuText = 'CPU avg ' . $this->formatDuration((int) $interval) . ': ' . $usage . '%';
            if ($load5 !== null && $cores > 0) {
                $cpuText .= '; load5=' . $load5 . '/' . $cores . ' (' . $load5Pct . '%)';
            }
        }

        $usageState = 'немає даних';
        $usageColor = '#94a3b8';
        if ($usageFloat !== null) {
            if ($usageFloat >= 90.0) {
                $usageState = 'зайнято';
                $usageColor = '#fca5a5';
            } elseif ($usageFloat >= 70.0) {
                $usageState = 'високе';
                $usageColor = '#fde68a';
            } else {
                $usageState = 'нормально';
                $usageColor = '#86efac';
            }
        }

        $loadState = 'немає даних';
        $loadColor = '#94a3b8';
        if ($load5PctFloat !== null) {
            if ($load5PctFloat >= 150.0) {
                $loadState = 'черга висока';
                $loadColor = '#fca5a5';
            } elseif ($load5PctFloat >= 100.0) {
                $loadState = 'ядра зайняті';
                $loadColor = '#fde68a';
            } else {
                $loadState = 'без черги';
                $loadColor = '#86efac';
            }
        }

        $running = [];
        $regimeLab = $this->readRegimeLabContinuous();
        foreach ((array) Arr::get($data, 'jobs', []) as $job) {
            if (! is_array($job) || Arr::get($job, 'derived_state') !== 'running') {
                continue;
            }
            $eta = is_array(Arr::get($job, 'eta')) ? Arr::get($job, 'eta') : [];
            $label = (string) Arr::get($job, 'label', '');
            $label = str_replace('hype_shadow_router_v2_', '', $label);
            $extra = '';
            if (str_contains($label, 'regime_lab') && ! empty($regimeLab['available'])) {
                $label = str_contains($label, 'stage') ? 'Regime Lab stage focus' : 'Regime Lab continuous';
                $extra = 'active=' . ($regimeLab['active_count'] ?? 0)
                    . '; done=' . ($regimeLab['done_count'] ?? 0)
                    . '; failed=' . ($regimeLab['failed_count'] ?? 0)
                    . '; best=' . ($regimeLab['best_score'] ?? 'n/a');
            }
            $running[] = [
                'label' => $label,
                'progress' => (int) Arr::get($eta, 'progress', Arr::get($job, 'progress', 0)),
                'remaining' => (string) Arr::get($eta, 'remaining', 'ETA unknown'),
                'eta' => (string) Arr::get($eta, 'eta_kyiv', Arr::get($eta, 'eta_utc', '')),
                'extra' => $extra,
            ];
        }
        $strategyQueue = $this->readStrategyV43Queue();

        return [
            'available' => true,
            'updated_at' => (string) Arr::get($data, 'generated_at', ''),
            'cpu' => $cpuText,
            'cpu_metrics' => [
                'usage_pct' => $usageFloat,
                'usage_text' => $usageFloat === null ? 'n/a' : $this->formatNumber($usageFloat, 1) . '%',
                'usage_bar' => $usageFloat === null ? 0 : max(0, min(100, (int) round($usageFloat))),
                'usage_state' => $usageState,
                'usage_color' => $usageColor,
                'interval' => $interval === null ? '' : $this->formatDuration((int) $interval),
                'load5' => $load5Float,
                'load5_text' => $load5Float === null ? 'n/a' : $this->formatNumber($load5Float, 2),
                'load5_pct' => $load5PctFloat,
                'load5_pct_text' => $load5PctFloat === null ? 'n/a' : $this->formatNumber($load5PctFloat, 1) . '%',
                'load5_bar' => $load5PctFloat === null ? 0 : max(0, min(100, (int) round($load5PctFloat))),
                'load_state' => $loadState,
                'load_color' => $loadColor,
                'cores' => $cores,
            ],
            'counts' => is_array(Arr::get($data, 'counts')) ? Arr::get($data, 'counts') : [],
            'child_counts' => [
                'running' => (int) Arr::get($strategyQueue, 'running', 0),
                'queued' => (int) Arr::get($strategyQueue, 'queued', 0),
                'done' => (int) Arr::get($strategyQueue, 'done', 0),
                'total' => (int) Arr::get($strategyQueue, 'total', 0),
                'label' => (string) Arr::get($strategyQueue, 'label', ''),
                'state' => (string) Arr::get($strategyQueue, 'state', ''),
            ],
            'running' => $running,
            'canonical_candidate_refresh' => is_array(Arr::get($data, 'canonical_candidate_refresh')) ? Arr::get($data, 'canonical_candidate_refresh') : [],
        ];
    }

    private function readStrategyV43Queue(): array
    {
        $path = '/home/ubuntu/SafaryEngine/data/strategy_search/strategy_module/strategy_v4_3_hybrid_stage0_queue_latest.json';
        if (! is_file($path)) {
            return [];
        }
        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data)) {
            return [];
        }
        $tasks = is_array(Arr::get($data, 'tasks')) ? Arr::get($data, 'tasks') : [];
        return [
            'label' => (string) Arr::get($data, 'label', ''),
            'state' => (string) Arr::get($data, 'state', ''),
            'total' => count($tasks),
            'running' => count(array_filter($tasks, fn ($task) => is_array($task) && Arr::get($task, 'state') === 'running')),
            'queued' => count(array_filter($tasks, fn ($task) => is_array($task) && Arr::get($task, 'state') === 'queued')),
            'done' => count(array_filter($tasks, fn ($task) => is_array($task) && Arr::get($task, 'state') === 'done')),
        ];
    }

    private function readRegimeLabContinuous(): array
    {
        $path = '/home/ubuntu/SafaryEngine/data/strategy_search/regime_lab/continuous_status.json';
        if (! is_file($path)) {
            return ['available' => false];
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data)) {
            return ['available' => false];
        }

        return [
            'available' => true,
            'active_count' => (int) Arr::get($data, 'active_count', 0),
            'done_count' => (int) Arr::get($data, 'done_count', 0),
            'failed_count' => (int) Arr::get($data, 'failed_count', 0),
            'best_score' => Arr::get($data, 'best_score', 'n/a'),
        ];
    }

    public function getExecutionPrioritySupervisor(): array
    {
        $path = '/home/ubuntu/SafaryEngine/data/strategy_search/execution_priority_supervisor_latest.json';

        if (! is_file($path)) {
            return [
                'available' => false,
                'generated_at' => '',
                'state' => '',
                'candidate_summary' => [],
                'job_summary' => [],
                'desired_running_mix' => [],
                'gaps' => [],
                'top_issue' => [],
                'issues' => [],
                'next_commands' => [],
            ];
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data)) {
            return [
                'available' => false,
                'generated_at' => '',
                'state' => '',
                'candidate_summary' => [],
                'job_summary' => [],
                'desired_running_mix' => [],
                'gaps' => [],
                'top_issue' => [],
                'issues' => [],
                'next_commands' => [],
            ];
        }

        return [
            'available' => true,
            'generated_at' => (string) Arr::get($data, 'generated_at_kyiv', Arr::get($data, 'generated_at_utc', '')),
            'state' => (string) Arr::get($data, 'state', ''),
            'candidate_summary' => is_array(Arr::get($data, 'candidate_summary')) ? Arr::get($data, 'candidate_summary') : [],
            'job_summary' => is_array(Arr::get($data, 'job_summary')) ? Arr::get($data, 'job_summary') : [],
            'desired_running_mix' => is_array(Arr::get($data, 'desired_running_mix')) ? Arr::get($data, 'desired_running_mix') : [],
            'gaps' => is_array(Arr::get($data, 'gaps')) ? Arr::get($data, 'gaps') : [],
            'top_issue' => is_array(Arr::get($data, 'top_issue')) ? Arr::get($data, 'top_issue') : [],
            'issues' => is_array(Arr::get($data, 'issues')) ? array_slice(Arr::get($data, 'issues'), 0, 4) : [],
            'next_commands' => is_array(Arr::get($data, 'next_commands')) ? array_slice(Arr::get($data, 'next_commands'), 0, 2) : [],
        ];
    }

    public function getBackgroundCompletionInbox(): array
    {
        $path = '/home/ubuntu/SafaryEngine/data/strategy_search/background_completion_inbox_latest.json';

        if (! is_file($path)) {
            return [
                'available' => false,
                'generated_at' => null,
                'counts' => [],
                'needs_analysis' => [],
                'recent_finished' => [],
            ];
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data)) {
            return [
                'available' => false,
                'generated_at' => null,
                'counts' => [],
                'needs_analysis' => [],
                'recent_finished' => [],
            ];
        }

        return [
            'available' => true,
            'generated_at' => (string) Arr::get($data, 'generated_at_kyiv', Arr::get($data, 'generated_at_utc', '')),
            'counts' => is_array(Arr::get($data, 'counts')) ? Arr::get($data, 'counts') : [],
            'needs_analysis' => is_array(Arr::get($data, 'needs_analysis')) ? Arr::get($data, 'needs_analysis') : [],
            'recent_finished' => is_array(Arr::get($data, 'recent_finished')) ? Arr::get($data, 'recent_finished') : [],
        ];
    }

    public function getSignalEventSummary(): array
    {
        $syncPath = '/home/ubuntu/SafaryEngine/data/strategy_search/signal_event_live_sync_latest.json';
        $syncData = is_file($syncPath) ? json_decode((string) file_get_contents($syncPath), true) : [];
        $syncSources = is_array($syncData) && is_array(Arr::get($syncData, 'sources')) ? Arr::get($syncData, 'sources') : [];
        $sources = [
            [
                'key' => 'long',
                'label' => 'Long feed',
                'path' => '/home/ubuntu/SafaryEngine/data/strategy_search/live_long_regime_feed_latest.json',
            ],
            [
                'key' => 'short',
                'label' => 'Short feed',
                'path' => '/home/ubuntu/SafaryEngine/data/strategy_search/edge_factory/executable_quality/hype_shadow_router_v2_live_now_latest.json',
            ],
        ];

        $items = [];
        foreach ($sources as $source) {
            $path = (string) $source['path'];
            if (! is_file($path)) {
                $items[] = [
                    'key' => $source['key'],
                    'label' => $source['label'],
                    'available' => false,
                    'path' => $path,
                ];
                continue;
            }

            $data = json_decode((string) file_get_contents($path), true);
            if (! is_array($data)) {
                $items[] = [
                    'key' => $source['key'],
                    'label' => $source['label'],
                    'available' => false,
                    'path' => $path,
                ];
                continue;
            }

            $eventLog = Arr::get($data, 'signal_event_log', []);
            $state = Arr::get($eventLog, 'source_state.state', []);
            $counts = Arr::get($eventLog, 'source_state.counts_by_status', []);
            $summary = Arr::get($eventLog, 'summary', []);
            $sourceKey = (string) Arr::get($summary, 'source_key', '');
            if ($sourceKey !== '' && is_array(Arr::get($syncSources, $sourceKey . '.counts_by_status'))) {
                $counts = Arr::get($syncSources, $sourceKey . '.counts_by_status');
            }

            $items[] = [
                'key' => $source['key'],
                'label' => $source['label'],
                'available' => ! empty($eventLog),
                'generated_at' => (string) Arr::get($data, 'generated_at', ''),
                'max_feature_utc' => (string) Arr::get($data, 'max_feature_utc', Arr::get($data, 'max_feature_open_time_utc', '')),
                'cursor_utc' => (string) Arr::get($state, 'updated_at_utc', ''),
                'last_processed_utc' => (string) Arr::get($summary, 'new_cursor_utc', ''),
                'snapshot_signals' => (int) Arr::get($summary, 'snapshot_signals', 0),
                'inserted' => (int) Arr::get($summary, 'inserted', 0),
                'fresh_now' => is_array(Arr::get($data, 'fresh_signals')) ? count(Arr::get($data, 'fresh_signals')) : 0,
                'age_window_now' => (int) Arr::get($data, 'counts.age_window_signals', is_array(Arr::get($data, 'age_window_signals')) ? count(Arr::get($data, 'age_window_signals')) : 0),
                'baseline_replay' => (int) Arr::get($counts, 'baseline_replay', 0),
                'fresh_total' => (int) Arr::get($counts, 'fresh', 0),
                'executed_open' => (int) Arr::get($counts, 'executed_open', 0),
                'executed_closed' => (int) Arr::get($counts, 'executed_closed', 0),
                'late_backfill' => (int) Arr::get($counts, 'late_backfill', 0),
                'replay_seen' => (int) Arr::get($counts, 'replay_seen', 0),
                'already_seen' => (int) Arr::get($summary, 'already_seen', 0),
                'source_initialized' => (bool) Arr::get($summary, 'source_initialized', false),
            ];
        }

        return [
            'available' => collect($items)->contains(fn ($item) => ! empty($item['available'])),
            'sources' => $items,
        ];
    }

    public function getEventDrivenSignalEngine(): array
    {
        $path = '/home/ubuntu/SafaryEngine/data/strategy_search/event_driven_signal_engine_latest.json';

        if (! is_file($path)) {
            return [
                'available' => false,
                'generated_at' => '',
                'summary' => [],
                'top_candidates' => [],
                'recent_router_entries' => [],
            ];
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data)) {
            return [
                'available' => false,
                'generated_at' => '',
                'summary' => [],
                'top_candidates' => [],
                'recent_router_entries' => [],
            ];
        }

        return [
            'available' => true,
            'generated_at' => (string) Arr::get($data, 'generated_at_kyiv', Arr::get($data, 'generated_at_utc', '')),
            'state' => (string) Arr::get($data, 'state', ''),
            'summary' => is_array(Arr::get($data, 'summary')) ? Arr::get($data, 'summary') : [],
            'top_candidates' => is_array(Arr::get($data, 'top_candidates')) ? array_slice(Arr::get($data, 'top_candidates'), 0, 8) : [],
            'recent_router_entries' => is_array(Arr::get($data, 'recent_router_potential_entries')) ? array_slice(Arr::get($data, 'recent_router_potential_entries'), 0, 8) : [],
        ];
    }

    public function getLiveCandidateStats(): array
    {
        $path = '/home/ubuntu/SafaryEngine/data/strategy_search/live_candidate_stats_latest.json';

        if (! is_file($path)) {
            return [
                'available' => false,
                'generated_at' => null,
                'candidates' => [],
            ];
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data)) {
            return [
                'available' => false,
                'generated_at' => null,
                'candidates' => [],
            ];
        }

        return [
            'available' => true,
            'generated_at' => (string) Arr::get($data, 'generated_at', ''),
            'source_generated_at' => (string) Arr::get($data, 'source_generated_at', ''),
            'last_month_definition' => (string) Arr::get($data, 'last_month_definition', ''),
            'candidates' => is_array(Arr::get($data, 'candidates')) ? Arr::get($data, 'candidates') : [],
        ];
    }

    public function getCandidatePromotionScorecards(): array
    {
        $path = '/home/ubuntu/SafaryEngine/data/strategy_search/candidate_promotion_scorecards_latest.json';

        if (! is_file($path)) {
            return [
                'available' => false,
                'generated_at' => null,
                'counts' => [],
                'by_side' => [],
                'top' => [],
            ];
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data)) {
            return [
                'available' => false,
                'generated_at' => null,
                'counts' => [],
                'by_side' => [],
                'top' => [],
            ];
        }

        return [
            'available' => true,
            'generated_at' => (string) Arr::get($data, 'generated_at_kyiv', Arr::get($data, 'generated_at_utc', '')),
            'counts' => is_array(Arr::get($data, 'counts')) ? Arr::get($data, 'counts') : [],
            'by_side' => is_array(Arr::get($data, 'by_side')) ? Arr::get($data, 'by_side') : [],
            'top' => is_array(Arr::get($data, 'top')) ? array_slice(Arr::get($data, 'top'), 0, 12) : [],
            'standard_doc' => (string) Arr::get($data, 'standard_doc', ''),
        ];
    }

    public function getLongCandidateRegistry(): array
    {
        $path = '/home/ubuntu/SafaryEngine/data/strategy_search/long_candidate_registry_latest.json';

        if (! is_file($path)) {
            return [
                'available' => false,
                'generated_at' => null,
                'counts' => [],
                'top' => [],
            ];
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data)) {
            return [
                'available' => false,
                'generated_at' => null,
                'counts' => [],
                'top' => [],
            ];
        }

        return [
            'available' => true,
            'generated_at' => (string) Arr::get($data, 'generated_at_kyiv', ''),
            'counts' => is_array(Arr::get($data, 'counts')) ? Arr::get($data, 'counts') : [],
            'top' => is_array(Arr::get($data, 'top')) ? array_slice(Arr::get($data, 'top'), 0, 6) : [],
        ];
    }

    public function getLongRouterPrep(): array
    {
        $path = '/home/ubuntu/SafaryEngine/data/strategy_search/long_router_prep_latest.json';
        $auditPath = '/home/ubuntu/SafaryEngine/data/strategy_search/long_router_prep_audit_latest.json';

        if (! is_file($path)) {
            return [
                'available' => false,
                'generated_at' => null,
                'state' => '',
                'live_enabled' => false,
                'counts' => [],
                'candidates' => [],
            ];
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data)) {
            return [
                'available' => false,
                'generated_at' => null,
                'state' => '',
                'live_enabled' => false,
                'counts' => [],
                'candidates' => [],
            ];
        }

        $audit = [];
        if (is_file($auditPath)) {
            $auditData = json_decode((string) file_get_contents($auditPath), true);
            $audit = is_array($auditData) ? $auditData : [];
        }

        return [
            'available' => true,
            'generated_at' => (string) Arr::get($data, 'generated_at_kyiv', ''),
            'state' => (string) Arr::get($data, 'state', ''),
            'live_enabled' => (bool) Arr::get($data, 'live_enabled', false),
            'audit_state' => (string) Arr::get($audit, 'state', ''),
            'audit_counts' => is_array(Arr::get($audit, 'counts')) ? Arr::get($audit, 'counts') : [],
            'audit_warnings' => is_array(Arr::get($audit, 'warnings')) ? array_slice(Arr::get($audit, 'warnings'), 0, 3) : [],
            'audit_blockers' => is_array(Arr::get($audit, 'blockers')) ? array_slice(Arr::get($audit, 'blockers'), 0, 3) : [],
            'counts' => is_array(Arr::get($data, 'counts')) ? Arr::get($data, 'counts') : [],
            'candidates' => is_array(Arr::get($data, 'candidates')) ? array_slice(Arr::get($data, 'candidates'), 0, 5) : [],
        ];
    }

    public function getLongShadowFeed(): array
    {
        $path = '/home/ubuntu/SafaryEngine/data/strategy_search/long_shadow_feed_latest.json';

        if (! is_file($path)) {
            return [
                'available' => false,
                'generated_at' => null,
                'counts' => [],
                'fresh_signals' => [],
                'candidates' => [],
            ];
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data)) {
            return [
                'available' => false,
                'generated_at' => null,
                'counts' => [],
                'fresh_signals' => [],
                'candidates' => [],
            ];
        }

        return [
            'available' => true,
            'generated_at' => (string) Arr::get($data, 'generated_at_kyiv', ''),
            'max_open' => (string) Arr::get($data, 'max_feature_open_time_utc', ''),
            'counts' => is_array(Arr::get($data, 'counts')) ? Arr::get($data, 'counts') : [],
            'fresh_signals' => is_array(Arr::get($data, 'fresh_signals')) ? Arr::get($data, 'fresh_signals') : [],
            'candidates' => is_array(Arr::get($data, 'candidates')) ? array_slice(Arr::get($data, 'candidates'), 0, 5) : [],
        ];
    }

    public function getMlShadow(): array
    {
        $path = '/home/ubuntu/SafaryEngine/data/strategy_search/ml_shadow/ml_shadow_latest.json';

        if (! is_file($path)) {
            return [
                'available' => false,
                'generated_at' => '',
                'mode' => '',
                'diagnostics' => [],
                'dataset_counts' => [],
                'regime_counts' => [],
                'candidates' => [],
            ];
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data)) {
            return [
                'available' => false,
                'generated_at' => '',
                'mode' => '',
                'diagnostics' => [],
                'dataset_counts' => [],
                'regime_counts' => [],
                'candidates' => [],
            ];
        }

        $dataset = Arr::get($data, 'dataset', []);

        return [
            'available' => true,
            'generated_at' => (string) Arr::get($data, 'generated_at_kyiv', ''),
            'mode' => (string) Arr::get($data, 'mode', ''),
            'diagnostics' => is_array(Arr::get($data, 'diagnostics')) ? Arr::get($data, 'diagnostics') : [],
            'dataset_counts' => is_array(Arr::get($dataset, 'counts')) ? Arr::get($dataset, 'counts') : [],
            'regime_counts' => is_array(Arr::get($data, 'regime_counts')) ? Arr::get($data, 'regime_counts') : [],
            'candidates' => is_array(Arr::get($data, 'candidates')) ? array_slice(Arr::get($data, 'candidates'), 0, 8) : [],
        ];
    }

    public function getMlDecisionLab(): array
    {
        $path = '/home/ubuntu/SafaryEngine/data/ml_decision_lab/ml_decision_lab_latest.json';

        if (! is_file($path)) {
            return [
                'available' => false,
                'generated_at' => '',
                'authority' => '',
                'model_version' => '',
                'dataset_counts' => [],
                'walk_forward' => [],
                'segments' => [],
                'segment_promotion' => [],
                'feature_importance' => [],
                'label_gap_plan' => [],
                'ml_next_action_plan' => [],
                'live_learning' => [],
                'promotion_readiness' => [],
                'promotion_trend' => [],
                'exit_optimizer' => [],
                'blockers' => [],
                'registry' => [],
                'memory' => [],
            ];
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data)) {
            return [
                'available' => false,
                'generated_at' => '',
                'authority' => '',
                'model_version' => '',
                'dataset_counts' => [],
                'walk_forward' => [],
                'segments' => [],
                'segment_promotion' => [],
                'feature_importance' => [],
                'label_gap_plan' => [],
                'ml_next_action_plan' => [],
                'live_learning' => [],
                'promotion_readiness' => [],
                'promotion_trend' => [],
                'exit_optimizer' => [],
                'blockers' => [],
                'registry' => [],
                'memory' => [],
            ];
        }

        return [
            'available' => true,
            'generated_at' => (string) Arr::get($data, 'generated_at_kyiv', ''),
            'authority' => (string) Arr::get($data, 'authority', ''),
            'model_version' => (string) Arr::get($data, 'model_version', ''),
            'dataset_counts' => is_array(Arr::get($data, 'dataset_counts')) ? Arr::get($data, 'dataset_counts') : [],
            'walk_forward' => is_array(Arr::get($data, 'walk_forward_replay')) ? Arr::get($data, 'walk_forward_replay') : [],
            'segments' => is_array(Arr::get($data, 'segment_replays')) ? Arr::get($data, 'segment_replays') : [],
            'segment_promotion' => is_array(Arr::get($data, 'segment_promotion_readiness')) ? Arr::get($data, 'segment_promotion_readiness') : [],
            'feature_importance' => is_array(Arr::get($data, 'feature_importance')) ? Arr::get($data, 'feature_importance') : [],
            'label_gap_plan' => is_array(Arr::get($data, 'label_gap_plan')) ? Arr::get($data, 'label_gap_plan') : [],
            'ml_next_action_plan' => is_array(Arr::get($data, 'ml_next_action_plan')) ? Arr::get($data, 'ml_next_action_plan') : [],
            'live_learning' => is_array(Arr::get($data, 'live_learning')) ? Arr::get($data, 'live_learning') : [],
            'promotion_readiness' => is_array(Arr::get($data, 'promotion_readiness')) ? Arr::get($data, 'promotion_readiness') : [],
            'promotion_trend' => is_array(Arr::get($data, 'promotion_trend')) ? Arr::get($data, 'promotion_trend') : [],
            'exit_optimizer' => is_array(Arr::get($data, 'exit_policy_optimizer')) ? Arr::get($data, 'exit_policy_optimizer') : [],
            'blockers' => is_array(Arr::get($data, 'authority_blockers')) ? Arr::get($data, 'authority_blockers') : [],
            'registry' => is_array(Arr::get($data, 'model_registry')) ? Arr::get($data, 'model_registry') : [],
            'memory' => is_array(Arr::get($data, 'decision_memory')) ? Arr::get($data, 'decision_memory') : [],
        ];
    }

    public function getMlReplay(): array
    {
        $path = '/home/ubuntu/SafaryEngine/data/strategy_search/ml_replay/ml_replay_latest.json';

        if (! is_file($path)) {
            return [
                'available' => false,
                'generated_at' => '',
                'decision' => '',
                'blockers' => [],
                'strengths' => [],
                'stream' => [],
                'baseline_all' => [],
                'best_variant' => [],
                'variants' => [],
                'leakage_audit' => [],
            ];
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data)) {
            return [
                'available' => false,
                'generated_at' => '',
                'decision' => '',
                'blockers' => [],
                'strengths' => [],
                'stream' => [],
                'baseline_all' => [],
                'best_variant' => [],
                'variants' => [],
                'leakage_audit' => [],
            ];
        }

        return [
            'available' => true,
            'generated_at' => (string) Arr::get($data, 'generated_at_kyiv', ''),
            'decision' => (string) Arr::get($data, 'decision', ''),
            'blockers' => is_array(Arr::get($data, 'blockers')) ? Arr::get($data, 'blockers') : [],
            'strengths' => is_array(Arr::get($data, 'strengths')) ? Arr::get($data, 'strengths') : [],
            'stream' => is_array(Arr::get($data, 'stream')) ? Arr::get($data, 'stream') : [],
            'baseline_all' => is_array(Arr::get($data, 'baseline_all')) ? Arr::get($data, 'baseline_all') : [],
            'best_variant' => is_array(Arr::get($data, 'best_variant')) ? Arr::get($data, 'best_variant') : [],
            'variants' => is_array(Arr::get($data, 'variants')) ? array_slice(Arr::get($data, 'variants'), 0, 6) : [],
            'leakage_audit' => is_array(Arr::get($data, 'leakage_audit')) ? Arr::get($data, 'leakage_audit') : [],
        ];
    }

    public function getShadowPreflight(): array
    {
        $path = '/home/ubuntu/SafaryEngine/data/strategy_search/shadow_preflight_latest.json';

        if (! is_file($path)) {
            return [
                'available' => false,
                'generated_at' => '',
                'state' => '',
                'counts' => [],
                'blockers' => [],
                'candidates' => [],
            ];
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data)) {
            return [
                'available' => false,
                'generated_at' => '',
                'state' => '',
                'counts' => [],
                'blockers' => [],
                'candidates' => [],
            ];
        }

        return [
            'available' => true,
            'generated_at' => (string) Arr::get($data, 'generated_at_kyiv', ''),
            'state' => (string) Arr::get($data, 'state', ''),
            'counts' => is_array(Arr::get($data, 'counts')) ? Arr::get($data, 'counts') : [],
            'blockers' => is_array(Arr::get($data, 'blockers')) ? Arr::get($data, 'blockers') : [],
            'candidates' => is_array(Arr::get($data, 'candidates')) ? Arr::get($data, 'candidates') : [],
        ];
    }

    public function getMarketplaceDecisionTrace(): array
    {
        $path = '/home/ubuntu/SafaryEngine/data/strategy_search/marketplace_decision_trace_latest.json';

        if (! is_file($path)) {
            return [
                'available' => false,
                'generated_at' => '',
                'summary' => [],
                'active_candidates' => [],
                'top_unique' => [],
                'blocked_focus' => [],
                'active_preflight' => [],
            ];
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data)) {
            return [
                'available' => false,
                'generated_at' => '',
                'summary' => [],
                'active_candidates' => [],
                'top_unique' => [],
                'blocked_focus' => [],
                'active_preflight' => [],
            ];
        }

        return [
            'available' => true,
            'generated_at' => (string) Arr::get($data, 'generated_at_kyiv', ''),
            'summary' => is_array(Arr::get($data, 'summary')) ? Arr::get($data, 'summary') : [],
            'active_candidates' => is_array(Arr::get($data, 'active_candidates')) ? Arr::get($data, 'active_candidates') : [],
            'top_unique' => is_array(Arr::get($data, 'top_unique')) ? array_slice(Arr::get($data, 'top_unique'), 0, 8) : [],
            'blocked_focus' => is_array(Arr::get($data, 'blocked_focus')) ? array_slice(Arr::get($data, 'blocked_focus'), 0, 4) : [],
            'active_preflight' => is_array(Arr::get($data, 'active_preflight')) ? Arr::get($data, 'active_preflight') : [],
        ];
    }

    public function getLiveEntryDecisionPreview(): array
    {
        $path = '/home/ubuntu/SafaryEngine/data/strategy_search/live_entry_decision_preview_latest.json';

        if (! is_file($path)) {
            return [
                'available' => false,
                'generated_at' => '',
                'summary' => [],
                'decisions' => [],
                'okx_position_check' => [],
            ];
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data)) {
            return [
                'available' => false,
                'generated_at' => '',
                'summary' => [],
                'decisions' => [],
                'okx_position_check' => [],
            ];
        }

        return [
            'available' => true,
            'generated_at' => (string) Arr::get($data, 'generated_at_kyiv', ''),
            'summary' => is_array(Arr::get($data, 'summary')) ? Arr::get($data, 'summary') : [],
            'decisions' => is_array(Arr::get($data, 'decisions')) ? Arr::get($data, 'decisions') : [],
            'okx_position_check' => is_array(Arr::get($data, 'okx_position_check')) ? Arr::get($data, 'okx_position_check') : [],
        ];
    }

    public function getCandidateRevalidationReport(): array
    {
        $path = '/home/ubuntu/SafaryEngine/data/strategy_search/candidate_revalidation_report_latest.json';

        if (! is_file($path)) {
            return [
                'available' => false,
                'generated_at' => '',
                'counts' => [],
                'live_test_allowed' => [],
                'candidate_next' => [],
                'blockers_focus' => [],
                'gaps' => [],
            ];
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data)) {
            return [
                'available' => false,
                'generated_at' => '',
                'counts' => [],
                'live_test_allowed' => [],
                'candidate_next' => [],
                'blockers_focus' => [],
                'gaps' => [],
            ];
        }

        return [
            'available' => true,
            'generated_at' => (string) Arr::get($data, 'generated_at_kyiv', Arr::get($data, 'generated_at_utc', '')),
            'counts' => is_array(Arr::get($data, 'counts')) ? Arr::get($data, 'counts') : [],
            'live_test_allowed' => is_array(Arr::get($data, 'live_test_allowed')) ? array_slice(Arr::get($data, 'live_test_allowed'), 0, 5) : [],
            'candidate_next' => is_array(Arr::get($data, 'candidate_next')) ? array_slice(Arr::get($data, 'candidate_next'), 0, 8) : [],
            'blockers_focus' => is_array(Arr::get($data, 'blockers_focus')) ? array_slice(Arr::get($data, 'blockers_focus'), 0, 5) : [],
            'gaps' => is_array(Arr::get($data, 'gaps')) ? array_slice(Arr::get($data, 'gaps'), 0, 4) : [],
        ];
    }

    public function getCandidateSelectionReport(): array
    {
        $path = '/home/ubuntu/SafaryEngine/data/strategy_search/candidate_selection_report_latest.json';

        if (! is_file($path)) {
            return [
                'available' => false,
                'generated_at' => '',
                'important_rule' => '',
                'metric_roles' => [],
                'top_candidates' => [],
                'top_non_live' => [],
                'gaps' => [],
            ];
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data)) {
            return [
                'available' => false,
                'generated_at' => '',
                'important_rule' => '',
                'metric_roles' => [],
                'top_candidates' => [],
                'top_non_live' => [],
                'gaps' => [],
            ];
        }

        return [
            'available' => true,
            'generated_at' => (string) Arr::get($data, 'generated_at_kyiv', Arr::get($data, 'generated_at_utc', '')),
            'important_rule' => (string) Arr::get($data, 'important_rule', ''),
            'metric_roles' => is_array(Arr::get($data, 'metric_roles')) ? Arr::get($data, 'metric_roles') : [],
            'top_candidates' => is_array(Arr::get($data, 'top_candidates')) ? Arr::get($data, 'top_candidates') : [],
            'top_non_live' => is_array(Arr::get($data, 'top_non_live')) ? Arr::get($data, 'top_non_live') : [],
            'gaps' => is_array(Arr::get($data, 'gaps')) ? array_slice(Arr::get($data, 'gaps'), 0, 6) : [],
        ];
    }

    public function getLiveCandidateReplacementWatch(): array
    {
        $path = '/home/ubuntu/SafaryEngine/data/strategy_search/live_candidate_replacement_watch_latest.json';

        if (! is_file($path)) {
            return [
                'available' => false,
                'generated_at' => '',
                'state' => '',
                'rule' => '',
                'actions' => [],
                'sides' => [],
            ];
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data)) {
            return [
                'available' => false,
                'generated_at' => '',
                'state' => '',
                'rule' => '',
                'actions' => [],
                'sides' => [],
            ];
        }

        return [
            'available' => true,
            'generated_at' => (string) Arr::get($data, 'generated_at_kyiv', Arr::get($data, 'generated_at_utc', '')),
            'state' => (string) Arr::get($data, 'state', ''),
            'rule' => (string) Arr::get($data, 'rule', 'manual review only; no automatic replacement'),
            'actions' => is_array(Arr::get($data, 'actions')) ? Arr::get($data, 'actions') : [],
            'sides' => is_array(Arr::get($data, 'sides')) ? Arr::get($data, 'sides') : [],
        ];
    }

    public function getDecisionCoreSummary(): array
    {
        $path = '/home/ubuntu/SafaryEngine/data/decision_core/decision_core_latest.json';

        if (! is_file($path)) {
            return [
                'available' => false,
                'generated_at' => '',
                'state' => '',
                'current_period' => [],
                'dominance' => [],
                'actions' => [],
                'board_summary' => [],
            ];
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data)) {
            return [
                'available' => false,
                'generated_at' => '',
                'state' => '',
                'current_period' => [],
                'dominance' => [],
                'actions' => [],
                'board_summary' => [],
            ];
        }

        return [
            'available' => true,
            'generated_at' => (string) Arr::get($data, 'generated_at_kyiv', Arr::get($data, 'generated_at_utc', '')),
            'state' => (string) Arr::get($data, 'state', ''),
            'current_period' => is_array(Arr::get($data, 'market.current_period')) ? Arr::get($data, 'market.current_period') : [],
            'dominance' => is_array(Arr::get($data, 'market.dominance')) ? Arr::get($data, 'market.dominance') : [],
            'actions' => is_array(Arr::get($data, 'actions')) ? array_slice(Arr::get($data, 'actions'), 0, 6) : [],
            'board_summary' => is_array(Arr::get($data, 'live_candidate_board.summary')) ? Arr::get($data, 'live_candidate_board.summary') : [],
        ];
    }

    public function getNormalizedCandidateReport(): array
    {
        $path = '/home/ubuntu/SafaryEngine/data/research/candidate_report_normalized_latest.json';

        if (! is_file($path)) {
            return [
                'available' => false,
                'generated_at' => '',
                'summary' => [],
                'by_side' => ['long' => [], 'short' => []],
            ];
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data)) {
            return [
                'available' => false,
                'generated_at' => '',
                'summary' => [],
                'by_side' => ['long' => [], 'short' => []],
            ];
        }

        $bySide = ['long' => [], 'short' => []];
        foreach ((array) Arr::get($data, 'candidates', []) as $candidate) {
            if (! is_array($candidate) || ($candidate['status'] ?? '') !== 'valid_normalized') {
                continue;
            }
            $side = (string) ($candidate['side'] ?? '');
            if (! array_key_exists($side, $bySide)) {
                continue;
            }
            $bySide[$side][] = $candidate;
        }

        foreach ($bySide as $side => $rows) {
            usort($rows, function (array $a, array $b): int {
                $aScore = ((float) Arr::get($a, 'periods.current_period.net_pct', -999))
                    + ((float) Arr::get($a, 'periods.last_120h.net_pct', -999) * 0.35)
                    + min(4.0, (float) Arr::get($a, 'periods.own_period.entries_per_day', 0));
                $bScore = ((float) Arr::get($b, 'periods.current_period.net_pct', -999))
                    + ((float) Arr::get($b, 'periods.last_120h.net_pct', -999) * 0.35)
                    + min(4.0, (float) Arr::get($b, 'periods.own_period.entries_per_day', 0));

                return $bScore <=> $aScore;
            });
            $bySide[$side] = array_slice($rows, 0, 7);
        }

        return [
            'available' => true,
            'generated_at' => (string) Arr::get($data, 'generated_at_kyiv', Arr::get($data, 'generated_at_utc', '')),
            'summary' => is_array(Arr::get($data, 'summary')) ? Arr::get($data, 'summary') : [],
            'by_side' => $bySide,
        ];
    }

    public function formatNormalizedPeriod(array $period): string
    {
        $entries = Arr::get($period, 'entries', 'n/a');
        $days = Arr::get($period, 'active_days');
        $perDay = Arr::get($period, 'entries_per_day');
        $pf = Arr::get($period, 'pf');
        $net = Arr::get($period, 'net_pct');
        $win = Arr::get($period, 'win_pct');

        $daysText = $days === null ? 'n/a' : number_format((float) $days, 2, '.', '') . 'd';
        $perDayText = $perDay === null ? 'n/a' : number_format((float) $perDay, 2, '.', '') . '/d';
        $pfText = $pf === null ? 'n/a' : number_format((float) $pf, 2, '.', '');
        $netText = $net === null ? 'n/a' : number_format((float) $net, 2, '.', '') . '%';
        $winText = $win === null ? 'n/a' : number_format((float) $win, 1, '.', '') . '%';

        return "{$entries}/{$daysText}={$perDayText}, PF={$pfText}, net={$netText}, win={$winText}";
    }

    public function getCandidateConsistencyAudit(): array
    {
        $path = '/home/ubuntu/SafaryEngine/data/strategy_search/candidate_consistency_audit_latest.json';

        if (! is_file($path)) {
            return [
                'available' => false,
                'generated_at' => '',
                'state' => '',
                'counts' => [],
                'issues' => [],
                'protected_long' => [],
            ];
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data)) {
            return [
                'available' => false,
                'generated_at' => '',
                'state' => '',
                'counts' => [],
                'issues' => [],
                'protected_long' => [],
            ];
        }

        $focused = is_array(Arr::get($data, 'focused')) ? Arr::get($data, 'focused') : [];
        $protectedLong = [];
        foreach ($focused as $row) {
            if (is_array($row) && (string) Arr::get($row, 'candidate_id', '') === 'direct_long_protected_cc5c1e93e9') {
                $protectedLong = $row;
                break;
            }
        }

        return [
            'available' => true,
            'generated_at' => (string) Arr::get($data, 'generated_at_kyiv', Arr::get($data, 'generated_at_utc', '')),
            'state' => (string) Arr::get($data, 'state', ''),
            'counts' => is_array(Arr::get($data, 'counts')) ? Arr::get($data, 'counts') : [],
            'issues' => is_array(Arr::get($data, 'issues')) ? array_slice(Arr::get($data, 'issues'), 0, 8) : [],
            'protected_long' => $protectedLong,
        ];
    }

    public function getLiveExitWhatif(): array
    {
        $path = '/home/ubuntu/SafaryEngine/data/strategy_search/live_exit_whatif_latest.json';

        if (! is_file($path)) {
            return [
                'available' => false,
                'generated_at' => '',
                'latest' => [],
            ];
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data)) {
            return [
                'available' => false,
                'generated_at' => '',
                'latest' => [],
            ];
        }

        $latest = is_array(Arr::get($data, 'latest')) ? Arr::get($data, 'latest') : [];
        $actual = is_array(Arr::get($latest, 'actual')) ? Arr::get($latest, 'actual') : [];
        if (is_array(Arr::get($actual, 'profile'))) {
            $profile = Arr::get($actual, 'profile');
            $actual['profile'] = trim(
                'tp' . Arr::get($profile, 'take_profit_pct', '') .
                ' sl' . Arr::get($profile, 'stop_loss_pct', '') .
                ' h' . Arr::get($profile, 'max_holding_bars', '') .
                ' ' . Arr::get($profile, 'source', '')
            );
            $latest['actual'] = $actual;
        }

        return [
            'available' => true,
            'generated_at' => (string) Arr::get($data, 'generated_at_kyiv', Arr::get($data, 'generated_at_utc', '')),
            'latest' => $latest,
        ];
    }

    public function getExitRobustnessDecision(): array
    {
        $path = '/home/ubuntu/SafaryEngine/data/strategy_search/exit_robustness_decision_latest.json';

        if (! is_file($path)) {
            return [
                'available' => false,
                'generated_at' => '',
                'decision' => [],
                'live_like' => [],
            ];
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data)) {
            return [
                'available' => false,
                'generated_at' => '',
                'decision' => [],
                'live_like' => [],
            ];
        }

        return [
            'available' => true,
            'generated_at' => (string) Arr::get($data, 'generated_at_kyiv', Arr::get($data, 'generated_at_utc', '')),
            'decision' => is_array(Arr::get($data, 'decision')) ? Arr::get($data, 'decision') : [],
            'live_like' => is_array(Arr::get($data, 'live_like')) ? array_slice(Arr::get($data, 'live_like'), 0, 6) : [],
            'weighted_regime_decision' => is_array(Arr::get($data, 'weighted_regime_decision')) ? Arr::get($data, 'weighted_regime_decision') : [],
        ];
    }

    public function getLongLiveDraftPreflight(): array
    {
        $path = '/home/ubuntu/SafaryEngine/data/strategy_search/long_live_draft_preflight_latest.json';

        if (! is_file($path)) {
            return [
                'available' => false,
                'generated_at' => '',
                'prep_state' => '',
                'launch_state' => '',
                'entry_state' => '',
                'counts' => [],
                'prep_blockers' => [],
                'launch_blockers' => [],
                'entry_blockers' => [],
                'target' => [],
                'metrics' => [],
                'ml' => [],
                'router_risk_decision' => [],
                'feed' => [],
                'next_actions' => [],
            ];
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data)) {
            return [
                'available' => false,
                'generated_at' => '',
                'prep_state' => '',
                'launch_state' => '',
                'entry_state' => '',
                'counts' => [],
                'prep_blockers' => [],
                'launch_blockers' => [],
                'entry_blockers' => [],
                'target' => [],
                'metrics' => [],
                'ml' => [],
                'router_risk_decision' => [],
                'feed' => [],
                'next_actions' => [],
            ];
        }

        return [
            'available' => true,
            'generated_at' => (string) Arr::get($data, 'generated_at_kyiv', ''),
            'prep_state' => (string) Arr::get($data, 'prep_state', ''),
            'launch_state' => (string) Arr::get($data, 'launch_state', ''),
            'entry_state' => (string) Arr::get($data, 'entry_state', ''),
            'counts' => is_array(Arr::get($data, 'counts')) ? Arr::get($data, 'counts') : [],
            'prep_blockers' => is_array(Arr::get($data, 'prep_blockers')) ? Arr::get($data, 'prep_blockers') : [],
            'launch_blockers' => is_array(Arr::get($data, 'launch_blockers')) ? Arr::get($data, 'launch_blockers') : [],
            'entry_blockers' => is_array(Arr::get($data, 'entry_blockers')) ? Arr::get($data, 'entry_blockers') : [],
            'target' => is_array(Arr::get($data, 'target')) ? Arr::get($data, 'target') : [],
            'metrics' => is_array(Arr::get($data, 'metrics')) ? Arr::get($data, 'metrics') : [],
            'ml' => is_array(Arr::get($data, 'ml')) ? Arr::get($data, 'ml') : [],
            'router_risk_decision' => is_array(Arr::get($data, 'router_risk_decision')) ? Arr::get($data, 'router_risk_decision') : [],
            'feed' => is_array(Arr::get($data, 'feed')) ? Arr::get($data, 'feed') : [],
            'next_actions' => is_array(Arr::get($data, 'next_actions')) ? Arr::get($data, 'next_actions') : [],
        ];
    }

    public function getDirectLongProtectedFeed(): array
    {
        $path = '/home/ubuntu/SafaryEngine/data/strategy_search/direct_long_protected_feed_latest.json';

        if (! is_file($path)) {
            return [
                'available' => false,
                'generated_at' => '',
                'state' => '',
                'counts' => [],
                'candidate' => [],
                'recent' => [],
            ];
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data)) {
            return [
                'available' => false,
                'generated_at' => '',
                'state' => '',
                'counts' => [],
                'candidate' => [],
                'recent' => [],
            ];
        }

        $recent = Arr::get($data, 'recent_signals');
        if (! is_array($recent)) {
            $recent = [];
        }

        return [
            'available' => true,
            'generated_at' => (string) Arr::get($data, 'generated_at_kyiv', Arr::get($data, 'generated_at_utc', '')),
            'state' => (string) Arr::get($data, 'state', ''),
            'max_feature_kyiv' => (string) Arr::get($data, 'max_feature_kyiv', ''),
            'counts' => is_array(Arr::get($data, 'counts')) ? Arr::get($data, 'counts') : [],
            'candidate' => is_array(Arr::get($data, 'candidate')) ? Arr::get($data, 'candidate') : [],
            'recent' => array_slice($recent, -6),
        ];
    }

    public function getCurrentRegimeLongSearch(): array
    {
        $paths = glob('/home/ubuntu/SafaryEngine/data/strategy_search/edge_factory/current_regime_long_search/hype_current_regime_long*_liveprep_report.json') ?: [];

        $paths = array_filter($paths, static fn (string $path): bool => is_file($path));
        usort($paths, static fn (string $left, string $right): int => filemtime($right) <=> filemtime($left));

        if (empty($paths)) {
            return [
                'available' => false,
                'generated_at' => '',
                'decision' => '',
                'current_major' => '',
                'regime_filter' => '',
                'regime_rows' => 0,
                'best' => [],
                'top' => [],
            ];
        }

        $selected = $paths[0];
        foreach ($paths as $path) {
            $candidate = json_decode((string) file_get_contents($path), true);
            if (! is_array($candidate)) {
                continue;
            }
            $bestCurrent = Arr::get($candidate, 'best_current_present');
            if (is_array($bestCurrent) && (int) Arr::get($bestCurrent, 'current_signal_rows', 0) > 0) {
                $selected = $path;
                break;
            }
        }

        $data = json_decode((string) file_get_contents($selected), true);
        if (! is_array($data)) {
            return [
                'available' => false,
                'generated_at' => '',
                'decision' => '',
                'current_major' => '',
                'regime_filter' => '',
                'regime_rows' => 0,
                'best' => [],
                'top' => [],
            ];
        }

        $best = is_array(Arr::get($data, 'best_current_present')) && ! empty(Arr::get($data, 'best_current_present'))
            ? Arr::get($data, 'best_current_present')
            : Arr::get($data, 'best', []);

        return [
            'available' => true,
            'generated_at' => (string) Arr::get($data, 'generated_at', ''),
            'decision' => (string) Arr::get($data, 'decision', ''),
            'current_major' => (string) Arr::get($data, 'current_major', ''),
            'regime_filter' => (string) Arr::get($data, 'regime_filter', ''),
            'regime_rows' => (int) Arr::get($data, 'regime_rows', 0),
            'best' => is_array($best) ? $best : [],
            'top' => is_array(Arr::get($data, 'top')) ? array_slice(Arr::get($data, 'top'), 0, 5) : [],
        ];
    }

    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . 's';
        }

        $minutes = intdiv($seconds, 60);
        if ($minutes < 60) {
            return $minutes . 'm';
        }

        $hours = intdiv($minutes, 60);
        $remMinutes = $minutes % 60;

        return $hours . 'h ' . str_pad((string) $remMinutes, 2, '0', STR_PAD_LEFT) . 'm';
    }

    private function formatNumber(float $value, int $precision): string
    {
        return number_format($value, $precision, '.', '');
    }
}
