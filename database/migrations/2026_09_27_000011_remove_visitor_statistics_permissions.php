<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Visitor Statistics was removed (page views are in the Activity Log), so
 * its switches go from the access matrix; grants go with them (the pivot
 * cascades). The os_visitor table itself is dropped after the cutover
 * (database/migrations-after-cutover), since the Yii app still reads it.
 */
return new class extends Migration
{
    private const PERMISSIONS = ['visitor.admin' => 'Manage', 'visitor.delete' => 'Delete', 'visitor.truncate' => 'Truncate'];

    public function up(): void
    {
        DB::table(config('permission.table_names.permissions'))->whereIn('name', array_keys(self::PERMISSIONS))->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        foreach (self::PERMISSIONS as $name => $title) {
            DB::table(config('permission.table_names.permissions'))->insertOrIgnore([
                'name' => $name, 'guard_name' => 'web', 'title' => $title, 'group' => 'Visitor Statistics',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
