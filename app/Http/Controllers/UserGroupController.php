<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Support\Grid;
use App\Support\SecurityLog;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;

/**
 * User groups (roles) and their access matrix: one switch per permission,
 * listed under the permission's section.
 */
class UserGroupController extends Controller
{
    private const PAGE = ['route' => 'userGroup', 'plural' => 'User Groups', 'singular' => 'Group'];

    public function admin(): View
    {
        $grid = Grid::for(Role::query())
            ->compare('id')
            ->compare('name', partial: true)
            ->compare('details', partial: true)
            ->paginate(config('legacy.pageSize'));

        return view('user-group.admin', ['grid' => $grid, 'page' => self::PAGE]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        return $this->save($request, new Role, 'create');
    }

    public function update(Request $request, int $id): View|RedirectResponse
    {
        return $this->save($request, $this->find($id), 'update');
    }

    public function delete(Request $request, int $id): RedirectResponse|Response
    {
        try {
            $this->find($id)->delete();
        } catch (QueryException $e) {
            return $this->deleteFailed($e);
        }

        if ($request->has('ajax')) {
            return response()->noContent();
        }

        return redirect($request->input('returnUrl', route('userGroup.admin')));
    }

    public function access(int $id): View
    {
        $role = $this->find($id);

        return view('user-group.access', [
            'group' => $role,
            'sections' => Permission::query()->orderBy('group')->orderBy('id')->get()->groupBy('group'),
            'granted' => $role->permissions()->pluck('id')->flip(),
        ]);
    }

    public function turnon(Request $request, int $id): Response
    {
        $role = $this->find($id);
        $role->givePermissionTo($permission = $this->permission($request));
        self::logAccess($role, 'granted', [$permission]);

        return response('ok');
    }

    public function turnoff(Request $request, int $id): Response
    {
        $role = $this->find($id);
        $role->revokePermissionTo($permission = $this->permission($request));
        self::logAccess($role, 'revoked', [$permission]);

        return response('ok');
    }

    /**
     * "Access all" (id 2) or "Deny all" (any other id) for a whole group.
     */
    public function accessall(Request $request): Response
    {
        $role = $this->find((int) $request->input('group_id'));
        $grant = (int) $request->input('id') === 2;
        $role->syncPermissions($grant ? Permission::all() : []);
        self::logAccess($role, $grant ? 'granted' : 'revoked', ['all']);

        return response('ok');
    }

    /**
     * "Access all" (id 2) or "Deny all" for one section of a group.
     */
    public function accessallc(Request $request): Response
    {
        $role = $this->find((int) $request->input('group_id'));
        $permissions = Permission::query()->where('group', (string) $request->input('section'))->get();

        if ((int) $request->input('id') === 2) {
            $role->givePermissionTo($permissions);
            self::logAccess($role, 'granted', $permissions->pluck('name')->all());
        } else {
            $role->revokePermissionTo($permissions);
            self::logAccess($role, 'revoked', $permissions->pluck('name')->all());
        }

        return response('ok');
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private static function logAccess(Role $role, string $change, array $permissions): void
    {
        activity('access')->performedOn($role)->event('updated')
            ->withProperties(['permissions' => $permissions])
            ->log("permissions $change");
        SecurityLog::record('permissions.changed', ['group' => $role->name, 'change' => $change, 'permissions' => $permissions], $role);
    }

    private function save(Request $request, Role $role, string $action): View|RedirectResponse
    {
        if ($request->isMethod('post')) {
            $rules = Role::rules($role->exists ? $role : null);
            $role->fill($request->validate($rules, [], Role::labelsFor(array_keys($rules))))->save();

            return redirect()->route('userGroup.admin')->with('success', 'Group was saved successfully');
        }

        return view('crud.'.$action, ['record' => $role, 'page' => self::PAGE, 'form' => 'user-group._form']);
    }

    private function find(int $id): Role
    {
        return Role::query()->find($id) ?? abort(404, 'The requested page does not exist.');
    }

    private function permission(Request $request): string
    {
        return $request->validate([
            'permission' => ['required', 'string', Rule::exists(config('permission.table_names.permissions'), 'name')],
        ])['permission'];
    }
}
