<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Drop the Yii menu and access control tables, now replaced by
 * config/menu.php and the permission tables.
 *
 * Nothing is dropped unless the conversion is complete: every user group
 * must have its role and every grouped user their role. The tables are
 * first written to storage/app/migration-archive/ as an SQL file that can
 * be loaded back with the mysql client.
 *
 * The Yii app needs these tables, so this lives outside database/migrations
 * (deploys never run it). Once Yii is retired:
 *   php artisan migrate --force --path=database/migrations-after-cutover
 */
return new class extends Migration
{
    private const TABLES = ['menu', 'acl', 'acl_action', 'acl_controller', 'user_group'];

    public function up(): void
    {
        $this->assertConverted();
        $this->archive();

        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function down(): void
    {
        throw new RuntimeException('The legacy menu/ACL tables were dropped; load them back from storage/app/migration-archive/ if needed.');
    }

    private function assertConverted(): void
    {
        $tables = config('permission.table_names');

        $groupsWithoutRole = DB::table('user_group as g')
            ->leftJoin($tables['roles'].' as r', 'r.id', '=', 'g.id')
            ->whereNull('r.id')
            ->count();

        $usersWithoutRole = DB::table('user as u')
            ->join('user_group as g', 'g.id', '=', 'u.group_id')
            ->leftJoin($tables['model_has_roles'].' as m', function ($join) {
                $join->on('m.model_id', '=', 'u.id')->where('m.model_type', 'App\Models\User');
            })
            ->whereNull('m.role_id')
            ->count();

        if ($groupsWithoutRole > 0 || $usersWithoutRole > 0) {
            throw new RuntimeException("Access control conversion incomplete ($groupsWithoutRole group(s) without a role, $usersWithoutRole user(s) without a role); nothing was dropped.");
        }
    }

    private function archive(): void
    {
        $directory = storage_path('app/migration-archive');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $path = $directory.'/legacy-menu-acl-'.date('Ymd-His').'.sql';
        $file = fopen($path, 'w');
        $pdo = DB::connection()->getPdo();

        fwrite($file, "-- Yii menu/ACL tables archived before migration 2026_09_27_000004\n\n");
        foreach (self::TABLES as $table) {
            $prefixed = DB::getTablePrefix().$table;
            if (! Schema::hasTable($table)) {
                continue;
            }

            $create = (array) DB::selectOne("SHOW CREATE TABLE `$prefixed`");
            fwrite($file, "DROP TABLE IF EXISTS `$prefixed`;\n".$create['Create Table'].";\n");
            foreach (DB::connection()->cursor("SELECT * FROM `$prefixed`") as $row) {
                $values = array_map(fn ($value) => $value === null ? 'NULL' : $pdo->quote((string) $value), (array) $row);
                fwrite($file, "INSERT INTO `$prefixed` VALUES(".implode(',', $values).");\n");
            }
            fwrite($file, "\n");
        }

        if (fclose($file) === false || filesize($path) === 0) {
            throw new RuntimeException("Could not write the archive $path; nothing was dropped.");
        }
    }
};
