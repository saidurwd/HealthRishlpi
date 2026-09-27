<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * Copy the Yii access control into spatie/laravel-permission, keeping every
 * user's effective access exactly as it was:
 *
 *   - each os_user_group becomes a role with the same id, name and details;
 *   - each ACL-checked route ("controller.action") becomes a permission;
 *   - a role is given a permission unless os_acl has a row for that group,
 *     controller and action with access 0. Yii allowed anything without a
 *     row (Controller::checkAccess()), so actions nobody ever set up stay
 *     open to every group. Controller ids are compared case-insensitively,
 *     as MySQL compared "Patient" and "patient" in os_acl;
 *   - each user gets the role of their os_user.group_id.
 *
 * The old tables are left untouched; a later migration drops them.
 */
return new class extends Migration
{
    /**
     * Route-name permissions at the time of the conversion, per controller
     * id, with the section title used when os_acl_controller has none.
     * Routes added later get their permissions in their own migrations.
     *
     * @var array<string, array{0: string, 1: list<string>}>
     */
    private const CATALOGUE = [
        'auditTrail' => ['Audit Trail', ['admin', 'delete']],
        'backup' => ['Backup', ['admin', 'cleanup', 'delete', 'download', 'exportdatabase', 'restore']],
        'batch' => ['Batch', ['admin', 'create', 'delete', 'update']],
        'city' => ['City', ['admin', 'create', 'delete', 'update']],
        'country' => ['Country', ['admin', 'create', 'delete', 'update']],
        'department' => ['Department', ['admin', 'create', 'delete', 'update']],
        'disease' => ['Diseases', ['admin', 'create', 'delete', 'update']],
        'district' => ['District', ['admin', 'create', 'delete', 'update']],
        'instruction' => ['Instruction', ['admin', 'create', 'delete', 'update']],
        'invoice' => ['Invoice', ['add', 'adjustment', 'adjustmentEdit', 'admin', 'create', 'delete', 'edit', 'print', 'remove', 'rollback', 'update', 'view']],
        'manufacturer' => ['Manufacturer', ['admin', 'create', 'delete', 'update']],
        'patient' => ['Patient Directory', ['addmedicine', 'admin', 'card', 'create', 'delete', 'editprescription', 'newprescription', 'preblank', 'prescription', 'registration', 'rehabilitation', 'remove', 'removemedicine', 'update', 'view']],
        'patientCategory' => ['Patient Sub Category', ['admin', 'create', 'delete', 'update']],
        'patientCategoryNew' => ['Patient Category', ['admin', 'create', 'delete', 'update']],
        'patientGrade' => ['Patient Grade', ['admin', 'create', 'delete', 'update']],
        'patientType' => ['Patient Type', ['admin', 'create', 'delete', 'update']],
        'product' => ['Product', ['admin', 'create', 'delete', 'update']],
        'productCategory' => ['Product Category', ['admin', 'create', 'delete', 'update']],
        'purchaseOrder' => ['Purchase Order', ['add', 'admin', 'create', 'delete', 'print', 'remove', 'update', 'view']],
        'purchaseReceive' => ['Purchase Receive', ['add', 'addpo', 'adjustment', 'adjustmentEdit', 'admin', 'create', 'delete', 'deletefile', 'docupload', 'download', 'downloadall', 'downloadfile', 'edit', 'price', 'print', 'remove', 'update', 'upload', 'view']],
        'report' => ['Reports', ['allserviceprint', 'category', 'categoryprint', 'contactregister', 'contactregisterprint', 'disease', 'diseaseprint', 'expiration', 'expirationprint', 'medicine', 'medicineprint', 'mincome', 'mincomeprint', 'patinvoice', 'patinvoiceprint', 'periodstock', 'periodstockprint', 'prescription', 'prescriptionprint', 'register', 'registerphysio', 'registerphysioprint', 'registerprint', 'sales', 'salesprint', 'service', 'serviceprint', 'stockreceive', 'stockreceiveprint', 'stocksummary', 'stocksummaryprint']],
        'service' => ['Services', ['admin', 'create', 'delete', 'update']],
        'state' => ['State', ['admin', 'create', 'delete', 'update']],
        'stockIssue' => ['Stock Issue', ['add', 'addsr', 'adjustment', 'adjustmentEdit', 'admin', 'create', 'delete', 'edit', 'print', 'remove', 'update', 'view']],
        'stockRequisition' => ['Stock Requisition', ['add', 'admin', 'convertissue', 'create', 'delete', 'print', 'remove', 'update', 'view']],
        'stockTransfer' => ['Stock Transfer', ['add', 'admin', 'create', 'delete', 'print', 'remove', 'update', 'view']],
        'store' => ['Store', ['admin', 'create', 'delete', 'update']],
        'thana' => ['Thana', ['admin', 'create', 'delete', 'update']],
        'unit' => ['Unit', ['admin', 'create', 'delete', 'update']],
        'user' => ['User', ['admin', 'create', 'delete', 'edit', 'update', 'view']],
        'userGroup' => ['User Group', ['access', 'accessall', 'accessallc', 'admin', 'create', 'delete', 'turnoff', 'turnon', 'update']],
        'userStatus' => ['User Status', ['admin', 'create', 'delete', 'update']],
        'vendor' => ['Vendor', ['admin', 'create', 'delete', 'update']],
        'visitor' => ['Visitor Statistics', ['admin', 'delete', 'truncate']],
    ];

    private const GUARD = 'web';

    private const USER_MORPH_TYPE = 'App\Models\User';

    public function up(): void
    {
        $tables = config('permission.table_names');
        $now = now();

        DB::transaction(function () use ($tables, $now) {
            // Roles keep the group ids, so os_user.group_id still names the role
            $roleIds = [];
            foreach (DB::table('user_group')->orderBy('id')->get() as $group) {
                DB::table($tables['roles'])->insert([
                    'id' => $group->id,
                    'name' => $group->title,
                    'guard_name' => self::GUARD,
                    'details' => $group->details,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $roleIds[] = (int) $group->id;
            }

            // Labels as the Yii access manager showed them
            $sectionTitles = DB::table('acl_controller')->pluck('title', 'controller')
                ->mapWithKeys(fn ($title, $controller) => [strtolower((string) $controller) => $title]);
            $actionTitles = DB::table('acl_action as a')
                ->join('acl_controller as c', 'c.id', '=', 'a.controller_id')
                ->get(['c.controller', 'a.action', 'a.title'])
                ->mapWithKeys(fn ($row) => [strtolower($row->controller.'.'.$row->action) => $row->title]);

            $permissionIds = [];
            foreach (self::CATALOGUE as $controller => [$section, $actions]) {
                foreach ($actions as $action) {
                    $name = $controller.'.'.$action;
                    $permissionIds[$name] = DB::table($tables['permissions'])->insertGetId([
                        'name' => $name,
                        'guard_name' => self::GUARD,
                        'title' => $actionTitles[strtolower($name)] ?? Str::headline($action),
                        'group' => $sectionTitles[strtolower($controller)] ?? $section,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            // Denials only; everything else was allowed
            $denied = DB::table('acl')->where('access', 0)->get(['group_id', 'controller', 'actions'])
                ->mapWithKeys(fn ($row) => [strtolower($row->group_id.'|'.$row->controller.'.'.$row->actions) => true]);

            foreach ($roleIds as $roleId) {
                $grants = [];
                foreach ($permissionIds as $name => $permissionId) {
                    if (! isset($denied[strtolower($roleId.'|'.$name)])) {
                        $grants[] = ['permission_id' => $permissionId, 'role_id' => $roleId];
                    }
                }
                DB::table($tables['role_has_permissions'])->insert($grants);
            }

            DB::table('user')->whereIn('group_id', $roleIds)->orderBy('id')->get(['id', 'group_id'])
                ->chunk(500)
                ->each(fn ($users) => DB::table($tables['model_has_roles'])->insert($users->map(fn ($user) => [
                    'role_id' => $user->group_id,
                    'model_type' => self::USER_MORPH_TYPE,
                    'model_id' => $user->id,
                ])->all()));
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Only safe while the old tables still exist (before the drop migration).
     */
    public function down(): void
    {
        $tables = config('permission.table_names');

        DB::table($tables['model_has_roles'])->delete();
        DB::table($tables['role_has_permissions'])->delete();
        DB::table($tables['permissions'])->delete();
        DB::table($tables['roles'])->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
