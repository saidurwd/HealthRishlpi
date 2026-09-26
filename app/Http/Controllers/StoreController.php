<?php

namespace App\Http\Controllers;

use App\Models\LegacyModel;
use App\Models\Store;
use App\Models\User;
use App\Support\Grid;

class StoreController extends CrudController
{
    protected string $model = Store::class;

    protected string $route = 'store';

    protected string $plural = 'Stores';

    protected string $singular = 'Store';

    protected function filterData(): array
    {
        return [
            'parents' => Store::roots(),
            'users' => User::query()->orderBy('full_name')->pluck('full_name', 'id'),
        ];
    }

    protected function grid(): Grid
    {
        return Grid::for(Store::query()->with('parentRow'))
            ->compare('id')
            ->compare('parent')
            ->compare('title', partial: true)
            ->compare('alias', partial: true)
            ->compare('location', partial: true)
            ->compare('incharge')
            ->compare('description', partial: true)
            ->compare('path', partial: true)
            ->defaultOrder('path');
    }

    protected function formData(LegacyModel $record): array
    {
        return [
            // The Yii form called Store::getStores(), which does not exist (the page crashed);
            // this is the tree dropdown ProductCategory uses
            'parents' => Store::treeOptions(),
            'users' => User::query()->orderBy('full_name')->pluck('full_name', 'id'),
        ];
    }

    /**
     * @param  Store  $record
     */
    protected function saved(LegacyModel $record): void
    {
        $record->updatePath();
    }
}
