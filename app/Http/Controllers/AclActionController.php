<?php

namespace App\Http\Controllers;

use App\Models\AclAction;
use App\Models\AclController;
use App\Support\Grid;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Actions of one ACL controller. Pages carry the controller as ?cid=.
 */
class AclActionController extends Controller
{
    public function actions(Request $request): View
    {
        $controller = $this->controller($request);
        $query = AclAction::query()->where('acl_action.controller_id', $controller->id);

        $grid = Grid::for($query)
            ->compare('id')
            ->compare('controller_id')
            ->compare('title', partial: true)
            ->compare('action', partial: true)
            ->defaultOrder('action')
            ->paginate(20);

        return view('acl-action.actions', ['grid' => $grid, 'controller' => $controller]);
    }

    public function view(Request $request, int $id): View
    {
        return view('acl-action.view', ['record' => $this->find($id), 'controller' => $this->controller($request)]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $controller = $this->controller($request);
        $record = new AclAction(['controller_id' => $controller->id]);

        if ($request->isMethod('post')) {
            $record->fill($this->validated($request))->save();

            return redirect()->route('aclAction.view', ['id' => $record->id, 'cid' => $record->controller_id])
                ->with('success', 'ACL action has been created successfully');
        }

        return view('acl-action.form', ['record' => $record, 'controller' => $controller]);
    }

    public function update(Request $request, int $id): View|RedirectResponse
    {
        $record = $this->find($id);

        if ($request->isMethod('post')) {
            $record->fill($this->validated($request))->save();

            return redirect()->route('aclAction.view', ['id' => $record->id, 'cid' => $record->controller_id])
                ->with('success', 'ACL action has been updated successfully');
        }

        return view('acl-action.form', ['record' => $record, 'controller' => $this->controller($request)]);
    }

    public function delete(Request $request, int $id): RedirectResponse|Response
    {
        $record = $this->find($id);

        try {
            $record->delete();
        } catch (QueryException $e) {
            return $this->deleteFailed($e);
        }

        if ($request->has('ajax')) {
            return response()->noContent();
        }

        return redirect($request->input('returnUrl', route('aclAction.actions', ['cid' => $record->controller_id])));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $rules = AclAction::rules();

        return $request->validate($rules, [], AclAction::labelsFor(array_keys($rules)));
    }

    private function find(int $id): AclAction
    {
        return AclAction::query()->find($id) ?? abort(404, 'The requested page does not exist.');
    }

    private function controller(Request $request): AclController
    {
        return AclController::query()->find((int) $request->query('cid')) ?? abort(404, 'The requested page does not exist.');
    }
}
