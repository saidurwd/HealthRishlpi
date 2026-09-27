<?php

namespace App\Http\Controllers;

use App\Models\Acl;
use App\Models\AclAction;
use App\Models\AclController;
use App\Models\UserGroup;
use App\Support\Grid;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class UserGroupController extends CrudController
{
    protected string $model = UserGroup::class;

    protected string $route = 'userGroup';

    protected string $plural = 'User Groups';

    protected string $singular = 'Group';

    protected string $savedMessage = 'Group was saved successfully';

    protected function grid(): Grid
    {
        return Grid::for(UserGroup::query())
            ->compare('id')
            ->compare('title', partial: true)
            ->compare('details', partial: true);
    }

    /**
     * The access matrix. Opening it first makes sure the group has an
     * `os_acl` row (access off) for every `os_acl_action`, and refreshes the
     * titles of existing rows.
     */
    public function access(int $id): View
    {
        $group = $this->find($id);

        foreach (AclAction::query()->with('controller0')->get() as $action) {
            $controller = $action->controller0?->controller;
            $existing = Acl::query()->where('group_id', $group->id)->where('controller', $controller)->where('actions', $action->action);

            if ($existing->exists()) {
                $existing->update(['action_title' => $action->title]);
            } else {
                Acl::create(['group_id' => $group->id, 'controller' => $controller, 'actions' => $action->action, 'action_title' => $action->title, 'access' => 0]);
            }
        }

        return view('user-group.access', [
            'group' => $group,
            'controllers' => AclController::query()->where('status', 1)->orderBy('title')->get(),
            'acl' => Acl::query()->where('group_id', $group->id)->orderBy('controller')->orderBy('actions')->get()->groupBy('controller'),
        ]);
    }

    public function turnon(int $id): Response
    {
        Acl::query()->whereKey($id)->update(['access' => 1]);

        return response('ok');
    }

    public function turnoff(int $id): Response
    {
        Acl::query()->whereKey($id)->update(['access' => 0]);

        return response('ok');
    }

    /**
     * "Access all" (id 2) or "Deny all" (any other id) for a whole group.
     */
    public function accessall(Request $request): Response
    {
        Acl::query()->where('group_id', (int) $request->input('group_id'))
            ->update(['access' => (int) $request->input('id') === 2 ? 1 : 0]);

        return response('ok');
    }

    /**
     * "Access all" (id 2) or "Deny all" for one controller of a group.
     */
    public function accessallc(Request $request): Response
    {
        Acl::query()->where('group_id', (int) $request->input('group_id'))
            ->where('controller', (string) $request->input('cntrl'))
            ->update(['access' => (int) $request->input('id') === 2 ? 1 : 0]);

        return response($request->input('id').', '.$request->input('group_id').', '.$request->input('cntrl'));
    }
}
