<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Purchase order line (`os_purchase_order`). Lines added before the order
 * is saved have parent 0 and belong to their creator until then.
 * `converted` is 1 once receives cover the ordered quantity.
 *
 * @property int $id
 * @property int $parent
 * @property int $item
 * @property string|float $quantity
 * @property int|null $converted
 * @property int|null $reference
 * @property string|float|null $rate
 * @property string|float|null $total_amount
 * @property int|null $project
 * @property int|null $assignment
 * @property int|null $created_by
 * @property string|null $created_on
 */
#[Table('purchase_order', timestamps: false)]
#[Fillable(['parent', 'item', 'quantity'])]
class PurchaseOrder extends LegacyModel
{
    public static function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'parent' => 'Parent',
            'reference' => 'Reference',
            'item' => 'Product',
            'quantity' => 'Quantity',
            'rate' => 'Rate',
            'total_amount' => 'Amount',
            'converted' => 'Converted',
            'created_by' => 'Created By',
            'created_on' => 'Created On',
        ];
    }

    public static function rules(?LegacyModel $model = null): array
    {
        return [
            'item' => ['required', 'integer'],
            'quantity' => ['required', 'max:18'],
            'parent' => ['integer'],
        ];
    }

    /** @return BelongsTo<PurchaseOrderParent, $this> */
    public function parent0(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderParent::class, 'parent');
    }

    /** @return BelongsTo<Product, $this> */
    public function item0(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'item');
    }

    /**
     * Ordered quantity not yet taken into receives (PurchaseOrder::getAvailableQuantity()).
     */
    public function availableQuantity(): float
    {
        return (float) $this->quantity - (float) PurchaseOrderHistory::query()->where('po_number', $this->id)->sum('quantity');
    }

    /**
     * Mark the line converted when its receives cover it; with $reset also
     * clear the mark when they no longer do.
     */
    public function refreshConverted(bool $reset = true): void
    {
        $taken = PurchaseOrderHistory::query()->where('po_number', $this->id)->sum('quantity');

        if ((float) $this->quantity <= (float) $taken) {
            $this->converted = 1;
            $this->save();
        } elseif ($reset) {
            $this->converted = 0;
            $this->save();
        }
    }
}
