<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Monitoring pages: Audit Log, Security Events and System Health. Groups
 * that manage access rights (userGroup.access, i.e. administrators) get
 * them; Super Users see everything anyway. Login History is the former
 * Audit Trail page (same permissions, new section name).
 */
return new class extends Migration
{
    private const PERMISSIONS = [
        'auditLog.admin' => ['Manage', 'Audit Log'],
        'auditLog.view' => ['View', 'Audit Log'],
        'securityEvent.admin' => ['Manage', 'Security Events'],
        'systemHealth.admin' => ['Manage', 'System Health'],
    ];

    public function up(): void
    {
        $tables = config('permission.table_names');
        $roles = DB::table($tables['role_has_permissions'].' as rp')
            ->join($tables['permissions'].' as p', 'p.id', '=', 'rp.permission_id')
            ->where('p.name', 'userGroup.access')
            ->pluck('rp.role_id');

        DB::transaction(function () use ($tables, $roles) {
            foreach (self::PERMISSIONS as $name => [$title, $section]) {
                $id = DB::table($tables['permissions'])->insertGetId([
                    'name' => $name, 'guard_name' => 'web', 'title' => $title, 'group' => $section,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                DB::table($tables['role_has_permissions'])->insert($roles->map(fn ($role) => ['permission_id' => $id, 'role_id' => $role])->all());
            }

            DB::table($tables['permissions'])->where('name', 'like', 'auditTrail.%')->update(['group' => 'Login History']);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $tables = config('permission.table_names');
        DB::table($tables['permissions'])->whereIn('name', array_keys(self::PERMISSIONS))->delete();
        DB::table($tables['permissions'])->where('name', 'like', 'auditTrail.%')->update(['group' => 'Audit Trail']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
