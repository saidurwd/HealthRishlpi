<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Store transfer line (`os_stock_transfer`): moves an item/batch from
 * store_from to store_to. Lines added before the transfer is saved have
 * parent 0 and belong to their creator until then.
 *
 * @property int $id
 * @property int $parent
 * @property int $item
 * @property string $quantity
 * @property string|null $rate
 * @property string|null $total_amount
 * @property int $store_from
 * @property int $store_to
 * @property int|null $batch
 */
#[Table('stock_transfer', timestamps: false)]
#[Fillable(['parent', 'item', 'quantity', 'store_from', 'store_to', 'batch'])]
class StockTransfer extends LegacyModel
{
    use Concerns\IsStockLine;

    public static function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'parent' => 'Parent',
            'reference' => 'Reference',
            'item' => 'Item',
            'quantity' => 'Quantity',
            'rate' => 'Rate',
            'total_amount' => 'Amount',
            'store_from' => 'From Store',
            'store_to' => 'To Store',
            'batch' => 'Batch',
            'created_by' => 'Created By',
            'created_on' => 'Created On',
        ];
    }

    public static function rules(?LegacyModel $model = null): array
    {
        return [
            'item' => ['required', 'integer'],
            'quantity' => ['required', 'max:18'],
            'store_from' => ['required', 'integer'],
            'store_to' => ['required', 'integer'],
            'parent' => ['integer'],
            'batch' => ['integer'],
        ];
    }

    /** @return BelongsTo<Store, $this> */
    public function storeFrom0(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_from');
    }

    /** @return BelongsTo<Store, $this> */
    public function storeTo0(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_to');
    }
}
