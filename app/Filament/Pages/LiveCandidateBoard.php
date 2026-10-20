<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Arr;

class LiveCandidateBoard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static ?string $navigationLabel = 'Live Board';
    protected static ?string $title = 'Live Candidate Board';
    protected static ?int $navigationSort = 41;
    protected static string $view = 'filament.pages.live-candidate-board';

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

    public function getBoard(): array
    {
        $corePath = '/home/ubuntu/SafaryEngine/data/decision_core/decision_core_latest.json';
        $boardPath = '/home/ubuntu/SafaryEngine/data/decision_core/live_candidate_board_latest.json';
        $opportunityLabPath = '/home/ubuntu/SafaryEngine/data/research/opportunity_research_lab_status.json';
        $core = $this->readJson($corePath);
        $board = $this->readJson($boardPath);
        $opportunityLab = $this->readJson($opportunityLabPath);

        if (empty($core) || empty($board)) {
            return [
                'available' => false,
                'core_path' => $corePath,
                'board_path' => $boardPath,
                'state' => 'missing',
                'generated_at' => '',
                'market' => [],
                'actions' => [],
                'summary' => [],
                'sides' => ['long' => [], 'short' => []],
                'coverage' => [],
                'opportunity_lab' => $opportunityLab,
            ];
        }

        return [
            'available' => true,
            'core_path' => $corePath,
            'board_path' => $boardPath,
            'state' => (string) Arr::get($core, 'state', Arr::get($board, 'state', '')),
            'generated_at' => (string) Arr::get($core, 'generated_at_kyiv', Arr::get($board, 'generated_at_kyiv', '')),
            'market' => is_array(Arr::get($core, 'market')) ? Arr::get($core, 'market') : [],
            'actions' => is_array(Arr::get($core, 'actions')) ? Arr::get($core, 'actions') : [],
            'summary' => is_array(Arr::get($board, 'summary')) ? Arr::get($board, 'summary') : [],
            'sides' => is_array(Arr::get($board, 'sides')) ? Arr::get($board, 'sides') : ['long' => [], 'short' => []],
            'coverage' => is_array(Arr::get($board, 'coverage')) ? Arr::get($board, 'coverage') : [],
            'period_coverage' => is_array(Arr::get($board, 'period_coverage')) ? Arr::get($board, 'period_coverage') : [],
            'interesting_unused_preview' => is_array(Arr::get($board, 'interesting_unused_preview')) ? Arr::get($board, 'interesting_unused_preview') : [],
            'opportunity_lab' => $opportunityLab,
            'rule' => (string) Arr::get($board, 'rule', ''),
        ];
    }

    private function readJson(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }

        $data = json_decode((string) file_get_contents($path), true);

        return is_array($data) ? $data : [];
    }

    public function fmtNumber($value, int $decimals = 2): string
    {
        if ($value === null || $value === '') {
            return 'n/a';
        }

        return number_format((float) $value, $decimals, '.', '');
    }

    public function fmtPct($value, int $decimals = 2): string
    {
        if ($value === null || $value === '') {
            return 'n/a';
        }

        return number_format((float) $value, $decimals, '.', '') . '%';
    }

    public function fmtRegimeAbbr($value): string
    {
        $text = (string) ($value ?? '');

        return match ($text) {
            'flat|low|tight' => 'FLT',
            'down|low|tight' => 'DLT',
            'up|low|tight' => 'ULT',
            'up|high|wide' => 'UHW',
            'up|high|breakout' => 'UHB',
            'up|high|normal' => 'UHN',
            default => $text,
        };
    }

    public function fmtPeriod(array $period): string
    {
        $entries = $period['entries'] ?? 0;
        $days = array_key_exists('active_days', $period) && $period['active_days'] !== null
            ? '/' . $period['active_days'] . 'd'
            : '';
        $perDay = array_key_exists('per_day', $period) && $period['per_day'] !== null
            ? '=' . $this->fmtNumber($period['per_day']) . '/d'
            : '';
        $pf = array_key_exists('pf', $period) ? $this->fmtNumber($period['pf']) : 'n/a';
        $net = array_key_exists('net_pct', $period) ? $this->fmtPct($period['net_pct']) : 'n/a';

        return $entries . $days . $perDay . ', PF ' . $pf . ', net ' . $net;
    }

    public function fmtPeriodCompact(array $period): string
    {
        if (empty($period)) {
            return 'n/a';
        }

        $entries = $period['entries'] ?? 0;
        $outcomes = '';
        if (
            ($period['tp'] ?? null) !== null
            || ($period['sl'] ?? null) !== null
            || ($period['time'] ?? null) !== null
            || ($period['open'] ?? null) !== null
        ) {
            $outcomes = ' TP/SL/T/Open '
                . (int) ($period['tp'] ?? 0)
                . '/' . (int) ($period['sl'] ?? 0)
                . '/' . (int) ($period['time'] ?? 0)
                . '/' . (int) ($period['open'] ?? 0);
        }
        $days = array_key_exists('active_days', $period) && $period['active_days'] !== null
            ? '/' . $this->fmtNumber($period['active_days'], 2) . 'd'
            : '';
        $perDay = array_key_exists('per_day', $period) && $period['per_day'] !== null
            ? '=' . $this->fmtNumber($period['per_day']) . '/d'
            : '';
        $pf = array_key_exists('pf', $period) && $period['pf'] !== null ? $this->fmtNumber($period['pf']) : 'not calculated';
        $net = array_key_exists('net_pct', $period) && $period['net_pct'] !== null ? $this->fmtPct($period['net_pct']) : 'n/a';

        return $entries . $outcomes . $days . $perDay . ' | PF ' . $pf . ' | net ' . $net;
    }

    public function fmtLiveWindow(array $window): string
    {
        $trades = (int) ($window['trades'] ?? 0);
        $wins = (int) ($window['wins'] ?? 0);
        $losses = (int) ($window['losses'] ?? 0);
        $potential = (int) ($window['potential_live_seen'] ?? 0);
        $net = $this->fmtNumber($window['net_usdt'] ?? 0, 4);

        return $potential . ' pot, ' . $trades . ' trades (' . $wins . '/' . $losses . '), net ' . $net . ' USDT';
    }

    public function fmtLiveWindowCompact(array $window): string
    {
        if (empty($window)) {
            return 'n/a';
        }

        $potential = (int) ($window['potential_live_seen'] ?? 0);
        $trades = (int) ($window['trades'] ?? 0);
        $wins = (int) ($window['wins'] ?? 0);
        $losses = (int) ($window['losses'] ?? 0);
        $net = $this->fmtNumber($window['net_usdt'] ?? 0, 4);

        return $potential . ' pot | ' . $trades . ' tr (' . $wins . '/' . $losses . ') | net ' . $net . ' USDT';
    }

    public function fmtTradeStats(array $stats): string
    {
        if (empty($stats)) {
            return 'no live trades';
        }

        $trades = (int) ($stats['trades'] ?? 0);
        $wins = (int) ($stats['wins'] ?? 0);
        $losses = (int) ($stats['losses'] ?? 0);
        $tp = (int) ($stats['tp'] ?? 0);
        $sl = (int) ($stats['sl'] ?? 0);
        $time = (int) ($stats['time'] ?? 0);
        $net = $this->fmtNumber($stats['net_usdt'] ?? 0, 4);

        return $trades . ' tr (' . $wins . '/' . $losses . ') · TP/SL/T ' . $tp . '/' . $sl . '/' . $time . ' · net ' . $net . ' USDT';
    }

    public function fmtOpportunityScore(array $row): string
    {
        if (empty($row)) {
            return 'n/a';
        }

        return ($row['candidate_id'] ?? 'n/a')
            . ' · ' . ($row['period_major_key'] ?? 'unknown')
            . ' · score ' . $this->fmtNumber($row['score'] ?? null, 2)
            . ' · ' . ($row['decision'] ?? 'watch');
    }

    public function fmtPeriodBinding(array $binding): string
    {
        if (empty($binding)) {
            return 'missing';
        }

        $operating = is_array($binding['operating_period'] ?? null) ? $binding['operating_period'] : $binding;
        $selection = is_array($binding['selection_period'] ?? null) ? $binding['selection_period'] : [];

        $major = (string) ($operating['major_key'] ?? $binding['major_key'] ?? 'missing');
        $status = (string) ($operating['status'] ?? $binding['status'] ?? 'unknown');
        $confidence = (string) ($operating['confidence'] ?? $binding['confidence'] ?? 'n/a');
        $source = (string) ($operating['source'] ?? $binding['source'] ?? 'n/a');
        $text = 'operating ' . $major . ' | ' . $status . '/' . $confidence . ' | ' . $source;

        if (! empty($selection['major_key'])) {
            $text .= ' · selected ' . $selection['major_key'];
        }

        return $text;
    }

    public function fmtBindingTop(array $binding): string
    {
        $top = Arr::get($binding, 'event_distribution.top');
        if (! is_array($top) || empty($top)) {
            return '';
        }

        $parts = [];
        foreach (array_slice($top, 0, 3) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $parts[] = ($row['major_key'] ?? '') . ' ' . $this->fmtPct($row['pct'] ?? 0, 1);
        }

        return implode(' · ', $parts);
    }

    public function fmtPeriodChildren(array $children): string
    {
        if (empty($children)) {
            return '';
        }

        $parts = [];
        foreach (array_slice($children, 0, 5) as $child) {
            if (! is_array($child)) {
                continue;
            }

            $abbr = (string) ($child['abbr'] ?? $child['major_key'] ?? '');
            $state = ! empty($child['coverage_accepted']) ? 'ok' : 'review';
            $events = (int) ($child['event_count'] ?? 0);
            $pct = $this->fmtPct($child['event_pct'] ?? 0, 1);
            $stats = is_array($child['live_trade_stats'] ?? null) ? $child['live_trade_stats'] : [];
            $trades = (int) ($stats['trades'] ?? 0);

            $tail = $events . 'e/' . $pct;
            if ($trades > 0) {
                $tail .= ', tr ' . $trades
                    . ' (' . (int) ($stats['wins'] ?? 0)
                    . '/' . (int) ($stats['losses'] ?? 0)
                    . '), net ' . $this->fmtNumber($stats['net_usdt'] ?? 0, 4);
            }

            $parts[] = trim($abbr . ' ' . $state . ' ' . $tail);
        }

        return implode(' | ', $parts);
    }

    public function healthColor(string $health): string
    {
        return match ($health) {
            'ok' => '#86efac',
            'new_live_waiting_fresh_signal' => '#93c5fd',
            'watch_loss', 'shadow_risk', 'replay_only', 'period_binding_review' => '#fde68a',
            'pause_review', 'replace_review' => '#fca5a5',
            default => '#cbd5e1',
        };
    }

    public function priorityColor(string $priority): string
    {
        return match ($priority) {
            'P0', 'P1' => '#fca5a5',
            'P2' => '#fde68a',
            default => '#86efac',
        };
    }
}
