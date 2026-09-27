<?php

use Spatie\Backup\Notifications\Notifiable;
use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification;
use Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification;
use Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes;
use Spatie\DbDumper\Compressors\GzipCompressor;

/*
|--------------------------------------------------------------------------
| Full backups (spatie/laravel-backup)
|--------------------------------------------------------------------------
|
| Nightly: the whole database (every table, the Yii app's too) and the
| uploads folder, zipped and encrypted with BACKUP_ARCHIVE_PASSWORD, kept on
| the disks in BACKUP_DISKS (backup_local, backup_offsite: see
| config/filesystems.php). health:backup refuses to send an unencrypted
| backup off site. Scheduled in routes/console.php; setup in
| deploy/README.md.
|
*/

return [

    'backup' => [
        // Folder name on the backup disks
        'name' => env('BACKUP_NAME', 'healthrishlpi'),

        'source' => [
            'files' => [
                // The code is in git; what cannot be recreated is the uploads
                // folder (user photos, store documents), Yii's while both apps run
                'include' => [
                    public_path('uploads'),
                ],
                'exclude' => [
                    // Old Yii SQL exports; the database is dumped below
                    public_path('uploads/backups'),
                ],
                // public/uploads is a symlink on the server
                'follow_links' => true,
                'ignore_unreadable_directories' => false,
                'relative_path' => public_path(),
            ],

            'databases' => [
                env('DB_CONNECTION', 'mariadb'),
            ],
        ],

        'database_dump_compressor' => GzipCompressor::class,
        'database_dump_file_timestamp_format' => null,
        'database_dump_filename_base' => 'database',
        'database_dump_file_extension' => '',

        'destination' => [
            'compression_method' => ZipArchive::CM_DEFAULT,
            'compression_level' => 9,
            'filename_prefix' => '',
            'disks' => explode(',', env('BACKUP_DISKS', 'backup_local')),
            // An unreachable off-site disk must not stop the local copy
            'continue_on_failure' => true,
        ],

        'temporary_directory' => storage_path('app/backup-temp'),

        // AES-256 zip encryption; required for backup_offsite
        'password' => env('BACKUP_ARCHIVE_PASSWORD'),
        'encryption' => 'default',

        'verify_backup' => true,
        'tries' => 1,
        'retry_delay' => 0,
    ],

    'notifications' => [
        // Failures only, by mail when BACKUP_NOTIFY_MAIL is set; failed
        // scheduled runs also show up in Nightwatch
        'notifications' => [
            BackupHasFailedNotification::class => env('BACKUP_NOTIFY_MAIL') ? ['mail'] : [],
            UnhealthyBackupWasFoundNotification::class => env('BACKUP_NOTIFY_MAIL') ? ['mail'] : [],
            CleanupHasFailedNotification::class => env('BACKUP_NOTIFY_MAIL') ? ['mail'] : [],
            BackupWasSuccessfulNotification::class => [],
            HealthyBackupWasFoundNotification::class => [],
            CleanupWasSuccessfulNotification::class => [],
        ],

        'notifiable' => Notifiable::class,

        'mail' => [
            // Must be a valid address even while mail is off (no BACKUP_NOTIFY_MAIL)
            'to' => env('BACKUP_NOTIFY_MAIL', 'backups@example.com'),
            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
                'name' => env('MAIL_FROM_NAME', 'Example'),
            ],
        ],

        'slack' => [
            'webhook_url' => '',
            'channel' => null,
            'username' => null,
            'icon' => null,
        ],

        'discord' => [
            'webhook_url' => '',
            'username' => '',
            'avatar_url' => '',
        ],

        'webhook' => [
            'url' => '',
        ],
    ],

    'log_channel' => null,

    // backup:monitor: every disk needs a backup less than a day old
    'monitor_backups' => [
        [
            'name' => env('BACKUP_NAME', 'healthrishlpi'),
            'disks' => explode(',', env('BACKUP_DISKS', 'backup_local')),
            'health_checks' => [
                MaximumAgeInDays::class => 1,
                MaximumStorageInMegabytes::class => 20000,
            ],
        ],
    ],

    'cleanup' => [
        'strategy' => DefaultStrategy::class,
        'default_strategy' => [
            'keep_all_backups_for_days' => 7,
            'keep_daily_backups_for_days' => 16,
            'keep_weekly_backups_for_weeks' => 8,
            'keep_monthly_backups_for_months' => 4,
            'keep_yearly_backups_for_years' => 2,
            'delete_oldest_backups_when_using_more_megabytes_than' => 20000,
        ],
        'tries' => 1,
        'retry_delay' => 0,
    ],

];
