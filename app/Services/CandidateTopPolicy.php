<?php

namespace App\Services;

/**
 * Read-only display policy for frozen CoreBatchController report metrics.
 * It never changes controller decisions or historical ledger records.
 */
final class CandidateTopPolicy
{
    private const MAX_DD_PCT = 8.0;
    private const TRAIN_MIN_TRADES = 30;
    private const TRAIN_MIN_PF = 1.15;
    private const VALIDATION_MIN_PF = 1.0;

    /**
     * The 2026-08-05 failure-filters top12+top12 run bypassed the required
     * 27-HTF matrix through fixed_rule and loaded ambiguous strategy sources.
     * Its historical ledger rows stay intact, but none is a valid Top result.
     */
    private const INVALID_PIPELINE_CANDIDATE_IDS = [
        'aff_long_03_v1',
        'aff_long_11_v1',
        'aff_long_12_v1',
        'aff_long_04_v1',
        'aff_long_01_v1',
        'aff_long_17_v1',
        'aff_long_06_v1',
        'aff_long_02_v1',
        'aff_long_14_v1',
        'aff_long_20_v1',
        'aff_long_07_v1',
        'aff_long_10_v1',
        'aff_short_18_v1',
        'aff_short_11_v1',
        'aff_short_19_v1',
        'aff_short_16_v1',
        'aff_short_12_v1',
        'aff_short_17_v1',
        'aff_short_15_v1',
        'aff_short_03_v1',
        'aff_short_08_v1',
        'aff_short_07_v1',
        'aff_short_13_v1',
        'aff_short_09_v1',
    ];

    /**
     * Returns an annotated candidate for the site top, or null when the
     * completed train / validation / holdout chain is not acceptable.
     */
    public static function eligible(array $candidate): ?array
    {
        $candidate = self::forDisplay($candidate);
        if ($candidate === null || self::isInvalidPipelineCandidate($candidate)) {
            return null;
        }

        $train = $candidate['train'] ?? null;
        $validation = $candidate['validation'] ?? null;
        $holdout = $candidate['holdout'] ?? null;

        if (! self::passesTrain($train) || ! self::passesOosGate($holdout)) {
            return null;
        }

        $validationTrades = self::trades($validation);
        $holdoutTrades = self::trades($holdout);

        if ($validationTrades >= 20 && self::passesOosGate($validation)) {
            return [...$candidate, 'policy_tier' => 'full'];
        }

        if (
            $validationTrades >= 8
            && $validationTrades <= 19
            && $holdoutTrades >= 15
            && ($validationTrades + $holdoutTrades) >= 27
            && self::passesOosGate($validation)
        ) {
            return [...$candidate, 'policy_tier' => 'OOS'];
        }

        return null;
    }

    private static function isInvalidPipelineCandidate(array $candidate): bool
    {
        return in_array((string) ($candidate['id'] ?? ''), self::INVALID_PIPELINE_CANDIDATE_IDS, true);
    }

    /**
     * Normalizes the one verified legacy duplicate of the exact live DMI
     * candidate.  Its AO 2h branch has an identical frozen train,
     * validation, holdout and TP/SL result to baseline; AO was not an input
     * to that strategy.  Other external HTF variants remain hidden.
     */
    public static function forDisplay(array $candidate): ?array
    {
        if (($candidate['id'] ?? null) !== 'live_dmi_long_exact_v1') {
            return $candidate;
        }

        $label = (string) (($candidate['selected_branch']['label'] ?? ''));
        if (! in_array($label, ['baseline', 'ao 2h'], true)) {
            return null;
        }

        return [
            ...$candidate,
            'selected_branch' => ['label' => 'DMI + Supertrend 4h'],
        ];
    }

    private static function passesTrain(mixed $metrics): bool
    {
        return self::trades($metrics) >= self::TRAIN_MIN_TRADES
            && self::pnl($metrics) > 0.0
            && self::pf($metrics) >= self::TRAIN_MIN_PF
            && self::dd($metrics) <= self::MAX_DD_PCT;
    }

    private static function passesOosGate(mixed $metrics): bool
    {
        return is_array($metrics)
            && self::pnl($metrics) > 0.0
            && self::pf($metrics) > self::VALIDATION_MIN_PF
            && self::dd($metrics) <= self::MAX_DD_PCT;
    }

    private static function trades(mixed $metrics): int
    {
        return is_array($metrics) ? (int) ($metrics['trades'] ?? 0) : 0;
    }

    private static function pnl(mixed $metrics): float
    {
        return is_array($metrics) ? (float) ($metrics['policy_pnl_pct'] ?? $metrics['pnl_pct'] ?? 0.0) : 0.0;
    }

    private static function pf(mixed $metrics): float
    {
        return is_array($metrics) ? (float) ($metrics['policy_pf'] ?? $metrics['pf'] ?? 0.0) : 0.0;
    }

    private static function dd(mixed $metrics): float
    {
        return is_array($metrics) ? (float) ($metrics['policy_dd_pct'] ?? $metrics['dd_pct'] ?? INF) : INF;
    }
}
