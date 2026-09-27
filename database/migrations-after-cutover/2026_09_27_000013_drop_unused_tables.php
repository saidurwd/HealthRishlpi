<?php

use App\Support\TableArchive;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Drop tables neither app needs once the Yii app is retired:
 *
 *   os_cache       Yii's database-cache schema, never used (Yii caches in
 *                  files, CFileCache; Laravel caches in files too); empty
 *   os_yiisession  Yii's login sessions (CDbHttpSession): needed by Yii until
 *                  the cutover, never used by Laravel
 *   os_sessions    left over from an early Laravel setup on development
 *                  databases (Laravel keeps sessions in files); not on live
 *
 * The other retired Yii tables go in 000004 (menu, ACL, user groups) and
 * 000012 (visitor statistics). Run after the cutover:
 *   php artisan migrate --force --path=database/migrations-after-cutover
 * Tables are archived to storage/app/migration-archive/ first; session rows
 * are not kept (short-lived sign-in data, useless once Yii is gone).
 */
return new class extends Migration
{
    private const TABLES = ['cache', 'yiisession', 'sessions'];

    private const STRUCTURE_ONLY = ['yiisession', 'sessions'];

    public function up(): void
    {
        $present = array_values(array_filter(self::TABLES, fn (string $table) => Schema::hasTable($table)));
        if ($present === []) {
            return;
        }

        TableArchive::write('unused-tables', $present, self::STRUCTURE_ONLY);

        foreach ($present as $table) {
            Schema::drop($table);
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Unused tables were dropped; their structure (and os_cache rows) are in storage/app/migration-archive/.');
    }
};
