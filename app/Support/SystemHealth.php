<?php

namespace App\Support;

use App\Models\Activity;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Spatie\Backup\Config\Config;
use Spatie\Backup\Tasks\Monitor\BackupDestinationStatusFactory;
use Throwable;

/**
 * The checks on the System Health page. Each check is
 * ['label', 'status' => ok|warning|error|info, 'value', 'hint'].
 */
class SystemHealth
{
    public const HEARTBEAT_KEY = 'health.scheduler_heartbeat';

    public const STATUSES = ['ok' => 0, 'info' => 0, 'warning' => 1, 'error' => 2];

    /**
     * @return array<string, list<array{label: string, status: string, value: string, hint?: string}>>
     */
    public static function checks(): array
    {
        return [
            'Application' => self::application(),
            'Database' => self::database(),
            'Storage' => self::storage(),
            'Scheduled jobs' => self::scheduler(),
            'Backups' => self::backups(),
            'Monitoring' => self::monitoring(),
            'Security (last 24 h)' => self::security(),
            'Errors (today)' => self::errors(),
        ];
    }

    /**
     * The worst status of all checks.
     *
     * @param  array<string, list<array{status: string}>>  $checks
     */
    public static function overall(array $checks): string
    {
        $worst = 'ok';
        foreach ($checks as $group) {
            foreach ($group as $check) {
                if (self::STATUSES[$check['status']] > self::STATUSES[$worst]) {
                    $worst = $check['status'];
                }
            }
        }

        return $worst;
    }

    /**
     * @return list<array{label: string, status: string, value: string, hint?: string}>
     */
    private static function application(): array
    {
        $production = app()->environment('production');
        $missing = array_values(array_filter(['pdo_mysql', 'gd', 'zip', 'intl', 'mbstring', 'curl', 'xml'], fn ($ext) => ! extension_loaded($ext)));
        $release = About::release();

        return [
            ['label' => 'Version', 'status' => 'info', 'value' => About::version().($release['commit'] ? ' ('.substr($release['commit'], 0, 7).')' : '')],
            ['label' => 'Environment', 'status' => 'info', 'value' => app()->environment()],
            ['label' => 'Debug mode', 'status' => $production && config('app.debug') ? 'error' : 'ok', 'value' => config('app.debug') ? 'on' : 'off',
                'hint' => 'Must be off in production: error pages would show code and data.'],
            ['label' => 'Address', 'status' => $production && ! str_starts_with((string) config('app.url'), 'https://') ? 'warning' : 'ok', 'value' => (string) config('app.url'),
                'hint' => 'Serve production over HTTPS.'],
            ['label' => 'PHP', 'status' => $missing === [] ? 'ok' : 'error', 'value' => PHP_VERSION.($missing === [] ? '' : ' - missing '.implode(', ', $missing))],
            ['label' => 'Laravel', 'status' => 'info', 'value' => app()->version()],
            ['label' => 'Configuration cached', 'status' => $production && ! app()->configurationIsCached() ? 'warning' : 'ok', 'value' => app()->configurationIsCached() ? 'yes' : 'no',
                'hint' => 'Deploys run php artisan optimize.'],
            ['label' => 'Time zone', 'status' => 'info', 'value' => config('app.timezone').' (now '.now()->format('Y-m-d H:i').')'],
        ];
    }

    /**
     * @return list<array{label: string, status: string, value: string, hint?: string}>
     */
    private static function database(): array
    {
        try {
            $started = microtime(true);
            $version = (string) DB::selectOne('SELECT VERSION() AS v')->v;
            $ms = round((microtime(true) - $started) * 1000, 1);
        } catch (Throwable $e) {
            return [['label' => 'Connection', 'status' => 'error', 'value' => 'failed: '.$e->getMessage()]];
        }

        $size = (float) DB::selectOne('SELECT SUM(data_length + index_length) AS bytes FROM information_schema.tables WHERE table_schema = DATABASE()')->bytes;
        $ran = DB::table('migrations')->pluck('migration')->all();
        $pending = fn (string $dir) => collect(File::glob(database_path("$dir/*.php")))
            ->map(fn ($file) => pathinfo($file, PATHINFO_FILENAME))
            ->diff($ran)->count();
        $pendingNow = $pending('migrations');
        $pendingCutover = $pending('migrations-after-cutover');

        return [
            ['label' => 'Connection', 'status' => $ms > 100 ? 'warning' : 'ok', 'value' => "$version, answered in $ms ms"],
            ['label' => 'Size', 'status' => 'info', 'value' => self::bytes($size)],
            ['label' => 'Pending migrations', 'status' => $pendingNow > 0 ? 'warning' : 'ok', 'value' => (string) $pendingNow,
                'hint' => 'The next deploy runs them (after a snapshot).'],
            ['label' => 'After-cutover migrations', 'status' => 'info', 'value' => $pendingCutover > 0 ? "$pendingCutover waiting for the Yii app's retirement" : 'done'],
        ];
    }

    /**
     * @return list<array{label: string, status: string, value: string, hint?: string}>
     */
    private static function storage(): array
    {
        $free = @disk_free_space(storage_path()) ?: 0;
        $total = @disk_total_space(storage_path()) ?: 1;
        $share = $free / $total;
        $writable = fn (string $path) => is_dir($path) && is_writable($path);

        return [
            ['label' => 'Free disk space', 'status' => $share < 0.05 ? 'error' : ($share < 0.15 ? 'warning' : 'ok'),
                'value' => self::bytes($free).' of '.self::bytes($total).' ('.round($share * 100).'% free)'],
            ['label' => 'Storage folder writable', 'status' => $writable(storage_path('framework')) && $writable(storage_path('logs')) ? 'ok' : 'error', 'value' => storage_path()],
            ['label' => 'Uploads folder writable', 'status' => $writable(public_path('uploads')) ? 'ok' : 'error', 'value' => (string) realpath(public_path('uploads'))],
        ];
    }

    /**
     * @return list<array{label: string, status: string, value: string, hint?: string}>
     */
    private static function scheduler(): array
    {
        $beat = Cache::get(self::HEARTBEAT_KEY);
        $age = $beat ? now()->diffInMinutes($beat, true) : null;

        $checks = [
            ['label' => 'Scheduler', 'status' => $age === null ? 'error' : ($age > 5 ? 'error' : 'ok'),
                'value' => $age === null ? 'never ran' : 'last ran '.now()->subMinutes((int) $age)->diffForHumans(),
                'hint' => 'Needs the cron line from deploy/README.md; backups and checks depend on it.'],
        ];

        $latest = storage_path('app/reconcile/latest.json');
        if (is_file($latest)) {
            $run = json_decode((string) file_get_contents($latest), true);
            $drift = $run['drift'] ?? null;
            $checks[] = ['label' => 'Daily reconciliation', 'status' => $drift > 0 ? 'warning' : 'ok',
                'value' => 'last run '.$run['taken_at'].($drift === null ? '' : ", $drift new finding(s)"),
                'hint' => 'php artisan health:reconcile lists them.'];
        } else {
            $checks[] = ['label' => 'Daily reconciliation', 'status' => 'info', 'value' => 'not run yet'];
        }

        return $checks;
    }

    /**
     * @return list<array{label: string, status: string, value: string, hint?: string}>
     */
    private static function backups(): array
    {
        $checks = [];

        try {
            // Built from config/backup.php by the package, as backup:monitor does
            $statuses = BackupDestinationStatusFactory::createForMonitorConfig(Config::fromArray(config('backup'))->monitoredBackups);
            foreach ($statuses as $status) {
                $destination = $status->backupDestination();
                $newest = $destination->newestBackup();
                $failure = $status->getHealthCheckFailure();
                $checks[] = [
                    'label' => 'Disk '.$destination->diskName(),
                    'status' => $status->isHealthy() ? 'ok' : 'error',
                    'value' => $newest
                        ? 'newest '.$newest->date()->diffForHumans().', '.$destination->backups()->count().' kept, '.self::bytes((float) $destination->usedStorage())
                        : 'no backups yet',
                    'hint' => $failure?->exception()->getMessage() ?? 'php artisan health:backup runs nightly.',
                ];
            }
        } catch (Throwable $e) {
            $checks[] = ['label' => 'Backups', 'status' => 'error', 'value' => $e->getMessage()];
        }

        $remote = collect(config('backup.backup.destination.disks'))->reject(fn ($disk) => config("filesystems.disks.$disk.driver") === 'local');
        $checks[] = ['label' => 'Off-site copy', 'status' => $remote->isEmpty() ? 'warning' : 'ok',
            'value' => $remote->isEmpty() ? 'not configured' : $remote->implode(', '),
            'hint' => 'Set BACKUP_DISKS=backup_local,backup_offsite with the bucket settings.'];
        $checks[] = ['label' => 'Backup encryption', 'status' => blank(config('backup.backup.password')) ? ($remote->isEmpty() ? 'warning' : 'error') : 'ok',
            'value' => blank(config('backup.backup.password')) ? 'no password' : 'AES-256'];

        return $checks;
    }

    /**
     * @return list<array{label: string, status: string, value: string, hint?: string}>
     */
    private static function monitoring(): array
    {
        $production = app()->environment('production');
        $nightwatch = config('nightwatch.enabled') && filled(config('nightwatch.token'));

        return [
            ['label' => 'Error tracking (Nightwatch)', 'status' => $nightwatch ? 'ok' : ($production ? 'warning' : 'info'), 'value' => $nightwatch ? 'on' : 'off'],
            ['label' => 'Mail', 'status' => in_array(config('mail.default'), ['log', 'array'], true) ? 'info' : 'ok', 'value' => (string) config('mail.default')],
            ['label' => 'Queue', 'status' => 'info', 'value' => (string) config('queue.default')],
            ['label' => 'Cache', 'status' => self::cacheWorks() ? 'ok' : 'error', 'value' => (string) config('cache.default')],
        ];
    }

    /**
     * @return list<array{label: string, status: string, value: string, hint?: string}>
     */
    private static function security(): array
    {
        $since = now()->subDay();
        $count = fn (string $event) => Activity::query()->where('log_name', SecurityLog::LOG)->where('event', $event)->where('created_at', '>=', $since)->count();
        $failed = $count('login.failed');
        $lockouts = $count('login.lockout');

        return [
            ['label' => 'Failed sign-ins', 'status' => $failed > 50 ? 'error' : ($failed > 10 ? 'warning' : 'ok'), 'value' => (string) $failed],
            ['label' => 'Lockouts', 'status' => $lockouts > 0 ? 'warning' : 'ok', 'value' => (string) $lockouts],
            ['label' => 'Access denied', 'status' => 'info', 'value' => (string) $count('access.denied')],
        ];
    }

    /**
     * Errors logged today (the last 2 MB of today's log).
     *
     * @return list<array{label: string, status: string, value: string, hint?: string}>
     */
    private static function errors(): array
    {
        $today = now()->format('Y-m-d');
        $file = collect([storage_path("logs/laravel-$today.log"), storage_path('logs/laravel.log')])->first(fn ($path) => is_file($path));

        if ($file === null) {
            return [['label' => 'Logged errors', 'status' => 'ok', 'value' => 'none']];
        }

        $handle = fopen($file, 'r');
        fseek($handle, max(0, filesize($file) - 2 * 1024 * 1024));
        $tail = (string) stream_get_contents($handle);
        fclose($handle);

        preg_match_all('/^\['.preg_quote($today, '/').' [^\]]+\] \w+\.(ERROR|CRITICAL|ALERT|EMERGENCY): (.*)$/m', $tail, $matches, PREG_SET_ORDER);
        $count = count($matches);

        $checks = [['label' => 'Logged errors', 'status' => $count > 20 ? 'error' : ($count > 0 ? 'warning' : 'ok'), 'value' => (string) $count,
            'hint' => 'Details in '.basename($file).' and in Nightwatch.']];
        foreach (array_slice(array_reverse($matches), 0, 5) as $match) {
            $checks[] = ['label' => $match[1], 'status' => 'info', 'value' => mb_strimwidth($match[2], 0, 160, '…')];
        }

        return $checks;
    }

    private static function cacheWorks(): bool
    {
        try {
            Cache::put('health.cache_check', 1, 10);

            return Cache::get('health.cache_check') === 1;
        } catch (Throwable) {
            return false;
        }
    }

    public static function bytes(float $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, $i === 0 ? 0 : 1).' '.$units[$i];
    }
}
