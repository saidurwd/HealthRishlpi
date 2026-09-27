<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Goods receive line (`os_purchase_receive`). Lines added before the
 * receive is saved have parent 0 and belong to their creator until then;
 * `reference` is the purchase order line it came from.
 *
 * @property int $id
 * @property int $parent
 * @property int|null $reference
 * @property int $item
 * @property string|float $quantity
 * @property string|float|null $rate sale rate
 * @property string|float|null $total_amount sale amount
 * @property string|float|null $buy_rate
 * @property string|float|null $buy_amount
 * @property int|null $store
 * @property int|null $batch
 * @property int|null $created_by
 * @property string|null $created_on
 */
#[Table('purchase_receive', timestamps: false)]
#[Fillable(['parent', 'item', 'quantity', 'rate', 'buy_rate', 'store'])]
class PurchaseReceive extends LegacyModel
{
    public static function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'parent' => 'Parent',
            'reference' => 'Reference',
            'item' => 'Product',
            'quantity' => 'Quantity',
            'rate' => 'Sale Rate',
            'total_amount' => 'Sale Amount',
            'buy_rate' => 'Buy Rate',
            'buy_amount' => 'Buy Amount',
            'store' => 'Store',
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
            'store' => ['required', 'integer'],
            'parent' => ['integer'],
            'rate' => ['max:18'],
            'buy_rate' => ['max:18'],
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function item0(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'item');
    }

    /** @return BelongsTo<Batch, $this> */
    public function batch0(): BelongsTo
    {
        return $this->belongsTo(Batch::class, 'batch');
    }

    /** @return BelongsTo<Store, $this> */
    public function store0(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store');
    }

    /** @return BelongsTo<PurchaseOrder, $this> */
    public function order0(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'reference');
    }

    /** @return HasMany<PurchaseReceiveDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(PurchaseReceiveDocument::class, 'receive_number');
    }

    /**
     * Order number of the purchase order the line came from
     * (PurchaseReceiveParent::getReferenceOrderNo()).
     */
    public function referenceOrderNumber(): ?string
    {
        return $this->reference > 0 ? $this->order0?->parent0?->order_number : null;
    }

    public function uom(): string
    {
        return $this->item0?->unit0?->formal_name ?: 'N/A';
    }

    /**
     * Keep the purchase order history and the order line's converted flag
     * in step with this line's quantity.
     */
    public function syncOrderHistory(): void
    {
        if (! $this->reference) {
            return;
        }

        PurchaseOrderHistory::query()->where('po_number', $this->reference)->where('pr_number', $this->id)->update(['quantity' => $this->quantity]);
        $this->order0?->refreshConverted();
    }
}
