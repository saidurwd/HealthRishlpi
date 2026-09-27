<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permission for the Activity Log page. No group gets it: Super Users see
 * it anyway; grant it to others in the User Group access matrix.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table(config('permission.table_names.permissions'))->insert([
            'name' => 'activityLog.admin',
            'guard_name' => 'web',
            'title' => 'Manage',
            'group' => 'Activity Log',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        DB::table(config('permission.table_names.permissions'))->where('name', 'activityLog.admin')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
