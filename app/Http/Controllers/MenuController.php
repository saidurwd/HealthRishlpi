<?php

namespace App\Http\Controllers;

use App\Models\LegacyModel;
use App\Models\Menu;
use App\Models\UserGroup;
use App\Support\Grid;
use Illuminate\Http\Request;

class MenuController extends CrudController
{
    protected string $model = Menu::class;

    protected string $route = 'menu';

    protected string $plural = 'Menus';

    protected string $singular = 'Menu';

    protected string $savedMessage = 'Menu was saved successfully';

    protected function grid(): Grid
    {
        return Grid::for(Menu::query()->with('parentRow'))
            ->compare('id', partial: true)
            ->compare('parent')
            ->compare('title', partial: true)
            ->compare('controller', partial: true)
            ->compare('url', partial: true)
            ->compare('icon', partial: true)
            ->compare('ordering')
            ->compare('status')
            ->compare('group');
    }

    protected function formData(LegacyModel $record): array
    {
        return [
            'parents' => Menu::parentOptions(),
            'groups' => UserGroup::query()->pluck('title', 'id'),
        ];
    }

    /**
     * The Groups multi-select posts an array; the column keeps the ids
     * comma-separated (see Menu::get_groups() in the Yii app).
     */
    protected function validated(Request $request, ?LegacyModel $record = null): array
    {
        if (is_array($request->input('group'))) {
            $request->merge(['group' => implode(',', $request->input('group'))]);
        }

        return parent::validated($request, $record);
    }
}
