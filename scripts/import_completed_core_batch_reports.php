<?php

declare(strict_types=1);

use App\Models\SystemReport;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$sourcePath = '/home/ubuntu/Freqtrade/core_batch_controller/core_batch_controller.sqlite';
if (! is_file($sourcePath)) {
    fwrite(STDERR, "CoreBatchController ledger not found.\n");
    exit(1);
}

$source = new SQLite3($sourcePath, SQLITE3_OPEN_READONLY);
$options = getopt('', ['dry-run', 'repair-existing', 'scalp-only', 'renumber-core-circles']);
$dryRun = array_key_exists('dry-run', $options);
$repairExisting = array_key_exists('repair-existing', $options);
$scalpOnly = array_key_exists('scalp-only', $options);
$renumberCoreCircles = array_key_exists('renumber-core-circles', $options);
if ($renumberCoreCircles && ($repairExisting || $scalpOnly)) {
    fwrite(STDERR, "--renumber-core-circles cannot be combined with --repair-existing or --scalp-only.\n");
    exit(2);
}
$launch = $source->querySingle("SELECT occurred_at_utc FROM events WHERE stage = 'controller' AND status = 'continuous_full_circle_started' ORDER BY id DESC LIMIT 1");
if (! is_string($launch) || $launch === '') {
    fwrite(STDERR, "No continuous controller launch found.\n");
    exit(1);
}

$batches = $source->query("SELECT batch_id, created_at_utc FROM batches WHERE status = 'complete' AND created_at_utc >= '".$source->escapeString($launch)."' ORDER BY created_at_utc");
$imported = 0;
$updated = 0;
$skipped = 0;
$circleNumber = 0;

function metrics(?array $value): ?array
{
    if (! is_array($value) || ! array_key_exists('total_trades', $value)) {
        return null;
    }

    return [
        'trades' => (int) $value['total_trades'],
        'wins' => (int) ($value['wins'] ?? 0),
        'losses' => (int) ($value['losses'] ?? 0),
        // Policy gates use frozen source precision; display stays rounded.
        'policy_pnl_pct' => ((float) ($value['profit_total'] ?? 0)) * 100,
        'policy_pf' => (float) ($value['profit_factor'] ?? 0),
        'policy_dd_pct' => ((float) ($value['max_drawdown_account'] ?? 0)) * 100,
        'pnl_pct' => round(((float) ($value['profit_total'] ?? 0)) * 100, 2),
        'pf' => round((float) ($value['profit_factor'] ?? 0), 2),
        'dd_pct' => round(((float) ($value['max_drawdown_account'] ?? 0)) * 100, 2),
    ];
}

function branchLabel(array $branch): string
{
    if (($branch['kind'] ?? '') === 'baseline') {
        return 'baseline';
    }

    return trim((string) ($branch['filter'] ?? 'HTF').' '.(string) ($branch['timeframe'] ?? ''));
}

function branchStageSlug(array $branch): string
{
    if (($branch['kind'] ?? null) === 'baseline') {
        return 'baseline';
    }

    return (string) ($branch['filter'] ?? '').'__'.(string) ($branch['timeframe'] ?? '');
}

function reportingBranch(array $payload): ?array
{
    $selected = $payload['selected_variant'] ?? null;
    if (is_array($selected)) {
        return $selected;
    }

    // A validation-rejected candidate has no winner, but its completed
    // replay/validation/holdout branches are still valid evidence.  Pick one
    // deterministic representative without ever using holdout to choose it.
    $branches = array_values(array_filter(
        $payload['branch_results'] ?? [],
        fn ($branch): bool => is_array($branch)
            && ($branch['status'] ?? null) === 'validation_rejected'
            && is_array($branch['branch'] ?? null)
            && is_array($branch['validation'] ?? null)
    ));
    if ($branches === []) {
        return null;
    }

    usort($branches, function (array $left, array $right): int {
        $leftMetrics = $left['validation'];
        $rightMetrics = $right['validation'];
        $byPf = ((float) ($rightMetrics['profit_factor'] ?? -INF)) <=> ((float) ($leftMetrics['profit_factor'] ?? -INF));
        if ($byPf !== 0) {
            return $byPf;
        }
        $byPnl = ((float) ($rightMetrics['profit_total'] ?? -INF)) <=> ((float) ($leftMetrics['profit_total'] ?? -INF));
        if ($byPnl !== 0) {
            return $byPnl;
        }
        $byDd = ((float) ($leftMetrics['max_drawdown_account'] ?? INF)) <=> ((float) ($rightMetrics['max_drawdown_account'] ?? INF));

        return $byDd !== 0
            ? $byDd
            : strcmp(branchStageSlug($left['branch']), branchStageSlug($right['branch']));
    });

    return $branches[0]['branch'];
}

function reportSource(array $candidates): string
{
    $ids = array_column($candidates, 'id');

    return $ids !== [] && count(array_filter($ids, fn (string $id): bool => str_starts_with($id, 'mi_'))) === count($ids)
        ? 'core_batch_controller_scalp'
        : 'core_batch_controller';
}

function stageMetrics(SQLite3 $source, string $batchId, string $candidateId, string $stage): ?array
{
    $statement = $source->prepare(
        "SELECT payload_json FROM events
         WHERE batch_id = :batch_id AND candidate_id = :candidate_id
           AND stage = :stage AND status = 'complete'
         ORDER BY id DESC LIMIT 1"
    );
    $statement->bindValue(':batch_id', $batchId, SQLITE3_TEXT);
    $statement->bindValue(':candidate_id', $candidateId, SQLITE3_TEXT);
    $statement->bindValue(':stage', $stage, SQLITE3_TEXT);
    $row = $statement->execute()->fetchArray(SQLITE3_ASSOC);
    if (! is_array($row)) {
        return null;
    }

    $payload = json_decode((string) $row['payload_json'], true, 512, JSON_THROW_ON_ERROR);

    return metrics(is_array($payload['metrics'] ?? null) ? $payload['metrics'] : null);
}

function stageRisk(SQLite3 $source, string $batchId, string $candidateId, string $stage): ?array
{
    $statement = $source->prepare(
        "SELECT payload_json FROM events
         WHERE batch_id = :batch_id AND candidate_id = :candidate_id
           AND stage = :stage AND status = 'complete'
         ORDER BY id DESC LIMIT 1"
    );
    $statement->bindValue(':batch_id', $batchId, SQLITE3_TEXT);
    $statement->bindValue(':candidate_id', $candidateId, SQLITE3_TEXT);
    $statement->bindValue(':stage', $stage, SQLITE3_TEXT);
    $row = $statement->execute()->fetchArray(SQLITE3_ASSOC);
    if (! is_array($row)) {
        return null;
    }

    $payload = json_decode((string) $row['payload_json'], true, 512, JSON_THROW_ON_ERROR);
    $params = $payload['selected']['params_dict'] ?? null;
    if (! is_array($params) || ! is_numeric($params['roi_target'] ?? null) || ! is_numeric($params['stoploss'] ?? null)) {
        return null;
    }

    // Controller research is fixed at x2.  Hyperopt stores leveraged PnL
    // ratios, while reports display the underlying price movement.
    return [
        'tp_pct' => round(abs((float) $params['roi_target']) * 50, 2),
        'sl_pct' => round(abs((float) $params['stoploss']) * 50, 2),
    ];
}

function buildCandidates(SQLite3 $source, string $batchId, string $createdAt, bool $flattenBranches = false): array
{
    $statement = $source->prepare(
        "SELECT candidate_id, status, payload_json, occurred_at_utc
         FROM events WHERE batch_id = :batch_id AND stage = 'full_candidate' ORDER BY id"
    );
    $statement->bindValue(':batch_id', $batchId, SQLITE3_TEXT);
    $result = $statement->execute();
    $candidates = [];
    $completedAt = $createdAt;

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $payload = json_decode((string) $row['payload_json'], true, 512, JSON_THROW_ON_ERROR);
        $candidateId = (string) $row['candidate_id'];
        $branchResults = $payload['branch_results'] ?? [];

        // Scalp research evaluates several independent branches per core.
        // Persist each actual branch as one normal report row instead of
        // inventing a winner when the candidate was not accepted.
        if ($flattenBranches && is_array($branchResults) && $branchResults !== []) {
            foreach ($branchResults as $branchResult) {
                $branch = $branchResult['branch'] ?? null;
                if (! is_array($branch)) {
                    continue;
                }
                $stagePrefix = 'branch:'.branchStageSlug($branch);
                $candidates[] = [
                    'id' => $candidateId,
                    'status' => (string) ($branchResult['status'] ?? $row['status']),
                    'selected_branch' => ['label' => branchLabel($branch)],
                    'risk' => stageRisk($source, $batchId, $candidateId, $stagePrefix.':hyperopt'),
                    'train' => stageMetrics($source, $batchId, $candidateId, $stagePrefix.':replay'),
                    'validation' => stageMetrics($source, $batchId, $candidateId, $stagePrefix.':validation'),
                    'holdout' => stageMetrics($source, $batchId, $candidateId, $stagePrefix.':holdout'),
                ];
            }
            $completedAt = max($completedAt, (string) $row['occurred_at_utc']);
            continue;
        }

        $selected = reportingBranch($payload);
        $stagePrefix = $selected === null ? null : 'branch:'.branchStageSlug($selected);

        $candidates[] = [
            'id' => $candidateId,
            'status' => (string) $row['status'],
            'selected_branch' => $selected === null ? null : ['label' => branchLabel($selected)],
            'risk' => $stagePrefix === null ? null : stageRisk($source, $batchId, $candidateId, $stagePrefix.':hyperopt'),
            // Final train metrics exist only after exact replay. Raw screen data
            // must never be presented as a frozen train result.
            'train' => $stagePrefix === null ? null : stageMetrics($source, $batchId, $candidateId, $stagePrefix.':replay'),
            'validation' => $stagePrefix === null ? null : stageMetrics($source, $batchId, $candidateId, $stagePrefix.':validation'),
            'holdout' => $stagePrefix === null ? null : stageMetrics($source, $batchId, $candidateId, $stagePrefix.':holdout'),
        ];
        $completedAt = max($completedAt, (string) $row['occurred_at_utc']);
    }

    return [$candidates, $completedAt];
}

$existingQuery = SystemReport::query()
    ->whereIn('payload->source', ['core_batch_controller', 'core_batch_controller_scalp']);
if ($scalpOnly) {
    $existingQuery->where('payload->source', 'core_batch_controller_scalp');
}
$existingReportRows = $existingQuery->get();
$existingReports = $existingReportRows
    ->keyBy(fn (SystemReport $report): string => (string) data_get($report->payload, 'source_batch_id'));

if ($renumberCoreCircles) {
    $coreReports = $existingReportRows
        ->filter(fn (SystemReport $report): bool => data_get($report->payload, 'source') === 'core_batch_controller')
        ->values();
    $batchIds = $coreReports
        ->map(fn (SystemReport $report): string => (string) data_get($report->payload, 'source_batch_id'))
        ->all();
    if (in_array('', $batchIds, true) || count($batchIds) !== count(array_unique($batchIds))) {
        fwrite(STDERR, "Core report batch ids must be present and unique before renumbering.\n");
        exit(2);
    }

    $plan = [];
    foreach ($coreReports as $report) {
        $batchId = (string) data_get($report->payload, 'source_batch_id');
        $statement = $source->prepare('SELECT created_at_utc FROM batches WHERE batch_id = :batch_id');
        $statement->bindValue(':batch_id', $batchId, SQLITE3_TEXT);
        $batch = $statement->execute()->fetchArray(SQLITE3_ASSOC);
        if (! is_array($batch) || ! is_string($batch['created_at_utc'] ?? null)) {
            fwrite(STDERR, "Missing CoreBatch ledger batch for report {$report->id}: {$batchId}.\n");
            exit(2);
        }
        $plan[] = [
            'report' => $report,
            'batch_id' => $batchId,
            'created_at_utc' => $batch['created_at_utc'],
            'old' => (int) data_get($report->payload, 'circle_number', 0),
        ];
    }
    usort($plan, fn (array $left, array $right): int => [$left['created_at_utc'], $left['batch_id']] <=> [$right['created_at_utc'], $right['batch_id']]);

    $changes = [];
    foreach ($plan as $index => &$item) {
        $item['new'] = $index + 1;
        if ($item['old'] !== $item['new']) {
            $changes[] = $item;
        }
    }
    unset($item);

    foreach ($changes as $item) {
        printf("batch=%s old=%d new=%d created_at_utc=%s\n", $item['batch_id'], $item['old'], $item['new'], $item['created_at_utc']);
    }
    if (! $dryRun && $changes !== []) {
        DB::transaction(function () use ($changes): void {
            foreach ($changes as $item) {
                /** @var SystemReport $report */
                $report = $item['report'];
                $payload = $report->payload ?? [];
                $payload['circle_number'] = $item['new'];
                $report->update(['payload' => $payload]);
            }
        });
    }
    printf("renumbered_core_circles=%d changed=%d dry_run=%s\n", count($plan), count($changes), $dryRun ? 'yes' : 'no');
    exit(0);
}
$circleNumber = (int) ($existingReports
    ->filter(fn (SystemReport $report): bool => data_get($report->payload, 'source') === 'core_batch_controller'
        && data_get($report->payload, 'controller_launch_at_utc') !== $launch)
    ->max(fn (SystemReport $report): int => (int) data_get($report->payload, 'circle_number', 0)) ?? 0);
$nextScalpCircle = ((int) ($existingReports
    ->filter(fn (SystemReport $report): bool => data_get($report->payload, 'source') === 'core_batch_controller_scalp')
    ->max(fn (SystemReport $report): int => (int) data_get($report->payload, 'circle_number', 0)) ?? 0)) + 1;

if ($repairExisting) {
    $batches = [];
    foreach ($existingReports as $batchId => $report) {
        if ($batchId === '') {
            continue;
        }
        $statement = $source->prepare('SELECT batch_id, created_at_utc FROM batches WHERE batch_id = :batch_id');
        $statement->bindValue(':batch_id', $batchId, SQLITE3_TEXT);
        $batch = $statement->execute()->fetchArray(SQLITE3_ASSOC);
        if (is_array($batch)) {
            $batches[] = $batch;
        }
    }
    usort($batches, fn (array $left, array $right): int => strcmp((string) $left['created_at_utc'], (string) $right['created_at_utc']));
} else {
    $batches = [];
    $result = $source->query("SELECT batch_id, created_at_utc FROM batches WHERE status = 'complete' AND created_at_utc >= '".$source->escapeString($launch)."' ORDER BY created_at_utc");
    while ($batch = $result->fetchArray(SQLITE3_ASSOC)) {
        $batches[] = $batch;
    }
}

foreach ($batches as $batch) {
    $circleNumber++;
    $batchId = (string) $batch['batch_id'];
    /** @var SystemReport|null $existing */
    $existing = $existingReports->get($batchId);
    if ($existing && ! $repairExisting) {
        $payload = $existing->payload ?? [];
        if (($payload['circle_number'] ?? null) !== $circleNumber && ! $dryRun) {
            $payload['circle_number'] = $circleNumber;
            $existing->update(['payload' => $payload]);
            $updated++;
        }
        $skipped++;
        continue;
    }

    [$candidates, $completedAt] = buildCandidates($source, $batchId, (string) $batch['created_at_utc'], $scalpOnly);
    $existingPayload = $existing?->payload ?? [];
    $sourceName = reportSource($candidates);
    if ($scalpOnly && $sourceName !== 'core_batch_controller_scalp') {
        $skipped++;
        continue;
    }
    $assignedCircle = $repairExisting
        ? ($existingPayload['circle_number'] ?? ($sourceName === 'core_batch_controller_scalp' ? $nextScalpCircle++ : $circleNumber))
        : ($sourceName === 'core_batch_controller_scalp' ? $nextScalpCircle++ : $circleNumber);
    $payload = [
        'source' => $sourceName,
        'source_batch_id' => $batchId,
        'circle_number' => $assignedCircle,
        'controller_launch_at_utc' => $launch,
        'completed_at_utc' => $completedAt,
        'candidates' => $candidates,
    ];

    if ($dryRun) {
        $existing ? $updated++ : $imported++;
        continue;
    }

    if ($existing) {
        $existing->update(['gateway_generated_at' => $completedAt, 'payload' => $payload]);
        $updated++;
    } else {
        SystemReport::create([
            'status' => 'published',
            'published_at' => now(),
            'gateway_generated_at' => $completedAt,
            'payload' => $payload,
        ]);
        $imported++;
    }
}

printf("imported=%d updated=%d skipped=%d dry_run=%s repair_existing=%s scalp_only=%s\n", $imported, $updated, $skipped, $dryRun ? 'yes' : 'no', $repairExisting ? 'yes' : 'no', $scalpOnly ? 'yes' : 'no');
