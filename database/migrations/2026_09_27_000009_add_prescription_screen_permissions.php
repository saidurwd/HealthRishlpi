<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * The redesigned prescription screen loads its medicines and searches
 * products on demand. Every group that can write or edit prescriptions gets
 * these lookups, so nobody's access changes.
 */
return new class extends Migration
{
    private const PERMISSIONS = [
        'patient.medicines' => 'Prescription Medicines',
        'patient.products' => 'Product Search',
    ];

    private const GRANTED_WITH = ['patient.newprescription', 'patient.editprescription'];

    public function up(): void
    {
        $tables = config('permission.table_names');
        $section = DB::table($tables['permissions'])->where('name', 'patient.newprescription')->value('group') ?? 'Patient Directory';
        $roles = DB::table($tables['role_has_permissions'].' as rp')
            ->join($tables['permissions'].' as p', 'p.id', '=', 'rp.permission_id')
            ->whereIn('p.name', self::GRANTED_WITH)
            ->distinct()->pluck('rp.role_id');

        DB::transaction(function () use ($tables, $section, $roles) {
            foreach (self::PERMISSIONS as $name => $title) {
                $id = DB::table($tables['permissions'])->insertGetId([
                    'name' => $name, 'guard_name' => 'web', 'title' => $title, 'group' => $section,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                DB::table($tables['role_has_permissions'])->insert($roles->map(fn ($role) => ['permission_id' => $id, 'role_id' => $role])->all());
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        DB::table(config('permission.table_names.permissions'))->whereIn('name', array_keys(self::PERMISSIONS))->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
