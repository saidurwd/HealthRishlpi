<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Writes tables to storage/app/migration-archive/<name>-<time>.sql (CREATE
 * TABLE plus one INSERT per row) before a migration drops them, so they can
 * be loaded back with the mysql client. Throws unless the file was written.
 */
class TableArchive
{
    /**
     * @param  list<string>  $tables  unprefixed names; missing tables are skipped
     * @param  list<string>  $structureOnly  tables whose rows are not kept
     */
    public static function write(string $name, array $tables, array $structureOnly = []): string
    {
        $directory = storage_path('app/migration-archive');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $path = $directory.'/'.$name.'-'.date('Ymd-His').'.sql';
        $file = fopen($path, 'w');
        $pdo = DB::connection()->getPdo();

        fwrite($file, "-- $name: archived ".date('Y-m-d H:i:s')." before being dropped\n\n");
        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $prefixed = DB::getTablePrefix().$table;
            $create = (array) DB::selectOne("SHOW CREATE TABLE `$prefixed`");
            fwrite($file, "DROP TABLE IF EXISTS `$prefixed`;\n".$create['Create Table'].";\n");

            if (in_array($table, $structureOnly, true)) {
                fwrite($file, "-- rows of $prefixed not kept\n\n");

                continue;
            }

            foreach (DB::connection()->cursor("SELECT * FROM `$prefixed`") as $row) {
                $values = array_map(fn ($value) => $value === null ? 'NULL' : $pdo->quote((string) $value), (array) $row);
                fwrite($file, "INSERT INTO `$prefixed` VALUES(".implode(',', $values).");\n");
            }
            fwrite($file, "\n");
        }

        if (fclose($file) === false || filesize($path) === 0) {
            throw new RuntimeException("Could not write the archive $path; nothing was dropped.");
        }

        return $path;
    }
}
