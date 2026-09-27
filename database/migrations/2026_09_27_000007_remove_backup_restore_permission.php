<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * The one-click database restore was removed, so its switch goes from the
 * access matrix (grants go with it: the pivot cascades).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table(config('permission.table_names.permissions'))->where('name', 'backup.restore')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        DB::table(config('permission.table_names.permissions'))->insertOrIgnore([
            'name' => 'backup.restore',
            'guard_name' => 'web',
            'title' => 'Restore',
            'group' => 'Backup',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
