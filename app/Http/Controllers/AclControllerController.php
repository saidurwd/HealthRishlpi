<?php

namespace App\Http\Controllers;

use App\Models\AclAction;
use App\Models\AclController;
use App\Models\LegacyModel;
use App\Support\Grid;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Controllers listed in the access matrix. Saving goes to the view page.
 */
class AclControllerController extends CrudController
{
    protected string $model = AclController::class;

    protected string $route = 'aclController';

    protected string $plural = 'Controllers';

    protected string $singular = 'Controller';

    protected string $savedMessage = 'Saved successfully';

    public function view(int $id): View
    {
        return view('acl-controller.view', ['record' => $this->find($id)]);
    }

    /**
     * Deletes the controller's actions first.
     */
    public function delete(Request $request, int $id): RedirectResponse|Response
    {
        AclAction::query()->where('controller_id', $this->find($id)->id)->delete();

        return parent::delete($request, $id);
    }

    protected function grid(): Grid
    {
        return Grid::for(AclController::query()->withCount('aclActions'))
            ->compare('id')
            ->compare('controller', partial: true)
            ->compare('title', partial: true)
            ->compare('status')
            ->defaultOrder('title');
    }

    protected function pageSize(): int
    {
        return config('legacy.pageSize20');
    }

    protected function newRecord(): LegacyModel
    {
        return new AclController(['status' => 1]);
    }

    protected function afterSave(LegacyModel $record): RedirectResponse
    {
        return redirect()->route('aclController.view', $record->id);
    }
}
