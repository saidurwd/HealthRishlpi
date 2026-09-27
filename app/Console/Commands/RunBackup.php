<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * The nightly full backup (spatie/laravel-backup's backup:run), refusing to
 * send patient data off site without BACKUP_ARCHIVE_PASSWORD.
 */
class RunBackup extends Command
{
    protected $signature = 'health:backup';

    protected $description = 'Back up the database and uploads to the configured backup disks';

    public function handle(): int
    {
        $remote = collect(config('backup.backup.destination.disks'))
            ->reject(fn (string $disk) => config("filesystems.disks.$disk.driver") === 'local');

        if ($remote->isNotEmpty() && blank(config('backup.backup.password'))) {
            $this->error('Set BACKUP_ARCHIVE_PASSWORD before backing up to '.$remote->implode(', ').': backups hold patient data.');

            return self::FAILURE;
        }

        return $this->call('backup:run');
    }
}
