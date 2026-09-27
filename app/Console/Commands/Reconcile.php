<?php

namespace App\Console\Commands;

use App\Support\Reconciliation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Daily consistency report while both apps write to the database. Findings
 * are compared with a baseline (saved once with --baseline) and the command
 * fails when anything is new or has changed, so the scheduled run shows up
 * as failed in Nightwatch.
 */
class Reconcile extends Command
{
    protected $signature = 'health:reconcile
        {--baseline : Save the current findings as the baseline to compare against}
        {--limit=20 : Findings listed per check}';

    protected $description = 'Check stock, totals, document numbers and roles for new inconsistencies';

    public function handle(): int
    {
        $findings = [];
        foreach (array_keys(Reconciliation::checks()) as $check) {
            $findings[$check] = Reconciliation::run($check);
        }

        File::ensureDirectoryExists(self::directory());
        File::put(self::directory().'/latest.json', json_encode(['taken_at' => now()->toDateTimeString(), 'findings' => $findings], JSON_PRETTY_PRINT));

        if ($this->option('baseline')) {
            File::put(self::baselinePath(), json_encode(['taken_at' => now()->toDateTimeString(), 'findings' => $findings], JSON_PRETTY_PRINT));
            $this->table(['Check', 'Findings'], collect($findings)->map(fn ($rows, $check) => [$check, count($rows)])->values()->all());
            $this->info('Baseline saved to '.self::baselinePath());

            return self::SUCCESS;
        }

        if (! File::exists(self::baselinePath())) {
            $this->error('No baseline yet: run "php artisan health:reconcile --baseline" once, on the data as it is before the parallel run.');

            return self::FAILURE;
        }

        $baseline = json_decode(File::get(self::baselinePath()), true);
        $this->line('Compared with the baseline of '.$baseline['taken_at']);

        $drift = 0;
        $summary = [];
        foreach ($findings as $check => $rows) {
            $before = $baseline['findings'][$check] ?? [];
            $new = array_diff_key($rows, $before);
            $changed = array_filter(array_intersect_key($rows, $before), fn ($value, $key) => (string) $value !== (string) $before[$key], ARRAY_FILTER_USE_BOTH);
            $resolved = array_diff_key($before, $rows);
            $drift += count($new) + count($changed);
            $summary[] = [$check, count($rows), count($new), count($changed), count($resolved)];

            foreach (['new' => $new, 'changed' => $changed] as $kind => $list) {
                foreach (array_slice($list, 0, (int) $this->option('limit'), true) as $key => $value) {
                    $was = $kind === 'changed' ? ' (was '.$before[$key].')' : '';
                    $this->line("  $check $kind: $key = $value$was");
                }
            }
        }

        $this->table(['Check', 'Findings', 'New', 'Changed', 'Resolved'], $summary);

        if ($drift > 0) {
            $this->error("$drift new or changed finding(s). Descriptions: ".collect(Reconciliation::checks())->map(fn ($d, $c) => "$c: $d")->implode('; '));

            return self::FAILURE;
        }

        $this->info('Nothing new since the baseline.');

        return self::SUCCESS;
    }

    private static function directory(): string
    {
        return storage_path('app/reconcile');
    }

    private static function baselinePath(): string
    {
        return self::directory().'/baseline.json';
    }
}
