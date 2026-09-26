<?php

namespace App\Http\Controllers;

use App\Models\LegacyModel;
use App\Models\ProductCategory;
use App\Support\Grid;

class ProductCategoryController extends CrudController
{
    protected string $model = ProductCategory::class;

    protected string $route = 'productCategory';

    protected string $plural = 'Product Categories';

    protected string $singular = 'Category';

    protected function filterData(): array
    {
        return [
            'parents' => ProductCategory::roots(),
        ];
    }

    protected function grid(): Grid
    {
        return Grid::for(ProductCategory::query()->with('parentRow'))
            ->compare('id')
            ->compare('parent')
            ->compare('title', partial: true)
            ->compare('alias', partial: true)
            ->compare('description', partial: true)
            ->compare('path', partial: true)
            ->defaultOrder('path');
    }

    protected function formData(LegacyModel $record): array
    {
        return [
            'parents' => ProductCategory::treeOptions(),
        ];
    }

    /**
     * @param  ProductCategory  $record
     */
    protected function saved(LegacyModel $record): void
    {
        $record->updatePath();
    }
}
