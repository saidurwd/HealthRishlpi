<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

/**
 * Full gzipped dump of the application database (every table, including the
 * Yii app's), taken by the deploy script before it runs migrations. Fails
 * unless the dump finished, so a deploy never migrates without a backup.
 */
class DatabaseSnapshot extends Command
{
    protected $signature = 'db:snapshot {directory : Where to write the file} {--keep=10 : Snapshots to keep in the directory}';

    protected $description = 'Dump the whole database to a gzipped SQL file';

    public function handle(): int
    {
        $connection = config('database.connections.'.config('database.default'));
        $directory = rtrim((string) $this->argument('directory'), '/');
        File::ensureDirectoryExists($directory);

        $path = $directory.'/'.$connection['database'].'-'.date('Ymd-His').'.sql.gz';
        $credentials = tempnam(sys_get_temp_dir(), 'snapshot');
        chmod($credentials, 0600);
        file_put_contents($credentials, "[client]\nhost=\"{$connection['host']}\"\nport=\"{$connection['port']}\"\nuser=\"{$connection['username']}\"\npassword=\"{$connection['password']}\"\n");

        $binary = $this->dumpBinary();
        $options = '--single-transaction --routines --triggers --events';
        // MySQL 8's mysqldump queries COLUMN_STATISTICS, which MariaDB lacks
        if (preg_match('/Ver 8\.|Distrib 8\.|Ver 9\./', Process::run([$binary, '--version'])->output()) === 1) {
            $options .= ' --column-statistics=0';
        }

        try {
            $result = Process::timeout(3600)->run([
                'bash', '-o', 'pipefail', '-c',
                '"$0" --defaults-extra-file="$1" '.$options.' "$2" | gzip > "$3"',
                $binary, $credentials, $connection['database'], $path,
            ]);
        } finally {
            unlink($credentials);
        }

        $tail = Process::run(['bash', '-o', 'pipefail', '-c', 'gzip -dc "$0" | tail -n 1', $path])->output();

        if ($result->failed() || ! str_contains($tail, 'Dump completed')) {
            @unlink($path);
            $this->error('Database snapshot failed: '.trim($result->errorOutput() ?: 'the dump did not complete'));

            return self::FAILURE;
        }

        $this->prune($directory, (int) $this->option('keep'));
        $this->info($path);

        return self::SUCCESS;
    }

    private function dumpBinary(): string
    {
        $found = Process::run('command -v mariadb-dump || command -v mysqldump')->output();

        return trim(strtok($found, "\n") ?: 'mysqldump');
    }

    private function prune(string $directory, int $keep): void
    {
        $files = glob($directory.'/*.sql.gz') ?: [];
        usort($files, fn (string $a, string $b) => filemtime($b) <=> filemtime($a));

        foreach (array_slice($files, max(1, $keep)) as $old) {
            unlink($old);
        }
    }
}
