<?php

namespace App\Http\Controllers;

use App\Models\LegacyModel;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Support\Grid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProductController extends CrudController
{
    protected string $model = Product::class;

    protected string $route = 'product';

    protected string $plural = 'Products';

    protected string $singular = 'Product';

    /**
     * Category and unit filters match the related title / formal name, as
     * Product::search() did through its category0/unit0 joins.
     */
    protected function grid(): Grid
    {
        $query = Product::query()
            ->select('product.*')
            ->leftJoin('product_category as category0', 'category0.id', '=', 'product.category')
            ->leftJoin('unit as unit0', 'unit0.id', '=', 'product.unit')
            ->with('category0', 'unit0');

        return Grid::for($query)
            ->compare('id')
            ->compare('title', partial: true)
            ->compare('product_code', partial: true)
            ->compare('description', partial: true)
            ->compare('threshold_value', partial: true)
            ->compare('minimum_storage_limit', partial: true)
            ->compare('created_by')
            ->compare('created_on', partial: true)
            ->compare('category', partial: true, column: 'category0.title')
            ->compare('unit', partial: true, column: 'unit0.formal_name')
            // Sorting by these columns ordered by the id columns in Yii
            ->sortable('category', 'product.category')
            ->sortable('unit', 'product.unit');
    }

    protected function formData(LegacyModel $record): array
    {
        return [
            'categories' => ProductCategory::treeOptions(),
            'units' => Unit::query()->orderBy('full_name')->pluck('full_name', 'id'),
        ];
    }

    protected function saving(LegacyModel $record, Request $request): void
    {
        if (! $record->exists) {
            $record->forceFill(['created_by' => $request->user()->id, 'created_on' => now()->format('Y-m-d G:i:s')]);
        }
    }

    protected function saved(LegacyModel $record): void
    {
        Cache::forget(Product::ITEM_LIST_CACHE_KEY);
    }
}
