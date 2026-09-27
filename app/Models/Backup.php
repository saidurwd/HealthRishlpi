<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A database backup file (`os_backup`). The files are kept in
 * storage/app/backups (not under public/, since they hold the whole
 * database including password hashes).
 *
 * @property int $id
 * @property string $attachment file name
 * @property int $file_size
 * @property string|null $checksum
 * @property string $type gzip|zip|sql
 * @property string $status success|failed
 */
#[Table('backup', timestamps: false)]
class Backup extends LegacyModel
{
    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    public const TYPE_SQL = 'sql';

    public const TYPE_ZIP = 'zip';

    public const TYPE_GZIP = 'gzip';

    public static function attributeLabels(): array
    {
        return ['id' => 'ID', 'attachment' => 'Attachment', 'created_on' => 'Created On', 'created_by' => 'Created By'];
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function path(): string
    {
        return self::directory().'/'.$this->attachment;
    }

    public static function directory(): string
    {
        return storage_path('app/backups');
    }

    /**
     * "1.5 MB" (Backup::formatBytes()).
     */
    public static function formatBytes(mixed $bytes): string
    {
        if ($bytes === null || $bytes < 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max((float) $bytes, 0);
        $pow = min((int) floor(($bytes ? log($bytes) : 0) / log(1024)), count($units) - 1);

        return round($bytes / pow(1024, $pow), 2).' '.$units[$pow];
    }

    /**
     * Delete backups (files and rows) older than $days days; returns how many.
     */
    public static function cleanOld(int $days): int
    {
        $old = static::query()->whereRaw('created_on < DATE_SUB(NOW(), INTERVAL ? DAY)', [$days])->get();

        foreach ($old as $backup) {
            if (is_file($backup->path())) {
                @unlink($backup->path());
            }
            $backup->delete();
        }

        return $old->count();
    }
}
