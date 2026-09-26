<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Medicine / stock item (`os_product`).
 *
 * @property int $id
 * @property int $category
 * @property string $title
 * @property int $unit
 */
#[Table('product', timestamps: false)]
#[Fillable(['category', 'title', 'product_code', 'description', 'unit', 'threshold_value', 'minimum_storage_limit'])]
class Product extends LegacyModel
{
    // Cache key of StockRequisition::getItemList(), cleared whenever a product is saved
    public const ITEM_LIST_CACHE_KEY = 'StockRequisition_ItemList';

    public static function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'category' => 'Category',
            'title' => 'Product',
            'product_code' => 'Code',
            'description' => 'Description',
            'unit' => 'Unit',
            'threshold_value' => 'Threshold Value',
            'minimum_storage_limit' => 'Min Storage Limit',
            'created_by' => 'Created By',
            'created_on' => 'Created On',
        ];
    }

    public static function rules(?LegacyModel $model = null): array
    {
        return [
            'category' => ['required', 'integer'],
            'title' => ['required', 'max:255'],
            'unit' => ['required', 'integer'],
            'product_code' => ['max:100'],
            'threshold_value' => ['max:18'],
            'minimum_storage_limit' => ['max:18'],
            'description' => ['nullable'],
        ];
    }

    /** @return BelongsTo<ProductCategory, $this> */
    public function category0(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category');
    }

    /** @return BelongsTo<Unit, $this> */
    public function unit0(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit');
    }
}
