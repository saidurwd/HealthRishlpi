<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Drop os_visitor, the Yii app's page-view statistics: neither app writes it
 * any more (Yii's Controller::statistics() call is commented out) and the
 * Laravel app keeps page views in the Activity Log. Yii's Visitor page still
 * reads it, so this runs only after the cutover:
 *   php artisan migrate --force --path=database/migrations-after-cutover
 * The rows are first written to storage/app/migration-archive/*.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('visitor')) {
            return;
        }

        $this->archive();
        Schema::drop('visitor');
    }

    public function down(): void
    {
        throw new RuntimeException('os_visitor was dropped; load it back from storage/app/migration-archive/ if needed.');
    }

    private function archive(): void
    {
        $directory = storage_path('app/migration-archive');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $table = DB::getTablePrefix().'visitor';
        $path = $directory.'/legacy-visitor-'.date('Ymd-His').'.sql';
        $file = fopen($path, 'w');
        $pdo = DB::connection()->getPdo();

        $create = (array) DB::selectOne("SHOW CREATE TABLE `$table`");
        fwrite($file, "-- os_visitor archived before migration 2026_09_27_000012\n\nDROP TABLE IF EXISTS `$table`;\n".$create['Create Table'].";\n");
        foreach (DB::connection()->cursor("SELECT * FROM `$table`") as $row) {
            $values = array_map(fn ($value) => $value === null ? 'NULL' : $pdo->quote((string) $value), (array) $row);
            fwrite($file, "INSERT INTO `$table` VALUES(".implode(',', $values).");\n");
        }

        if (fclose($file) === false || filesize($path) === 0) {
            throw new RuntimeException("Could not write the archive $path; nothing was dropped.");
        }
    }
};
