<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stock requisition line (`os_stock_requisition`). Lines added before the
 * requisition is saved have parent 0 and belong to their creator until
 * then. `converted` is 1 once issues cover the line.
 *
 * @property int $id
 * @property int $parent
 * @property int $item
 * @property string $quantity
 * @property string|null $rate
 * @property string|null $amount
 * @property int|null $store
 * @property int|null $batch
 * @property int|null $converted
 */
#[Table('stock_requisition', timestamps: false)]
#[Fillable(['parent', 'item', 'quantity', 'store', 'batch'])]
class StockRequisition extends LegacyModel
{
    use Concerns\IsStockLine;

    public static function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'parent' => 'Parent',
            'reference' => 'Reference',
            'item' => 'Product',
            'quantity' => 'Quantity',
            'rate' => 'Rate',
            'amount' => 'Amount',
            'store' => 'Store',
            'batch' => 'Batch',
            'converted' => 'Converted',
            'created_by' => 'Created By',
            'created_on' => 'Created On',
        ];
    }

    public static function rules(?LegacyModel $model = null): array
    {
        return [
            'store' => ['required', 'integer'],
            'item' => ['required', 'integer'],
            'quantity' => ['required', 'max:18'],
            'parent' => ['integer'],
            'batch' => ['integer'],
        ];
    }

    /** @return BelongsTo<StockRequisitionParent, $this> */
    public function parent0(): BelongsTo
    {
        return $this->belongsTo(StockRequisitionParent::class, 'parent');
    }

    /**
     * Requested quantity not yet taken into issues (StockRequisition::getAvailableQuantity()).
     */
    public function availableQuantity(): float
    {
        return (float) $this->quantity - (float) StockRequisitionHistory::query()->where('requisition_number', $this->id)->sum('quantity');
    }

    /**
     * Mark the line converted when its issues cover it; with $reset also
     * clear the mark when they no longer do.
     */
    public function refreshConverted(bool $reset = true): void
    {
        $taken = StockRequisitionHistory::query()->where('requisition_number', $this->id)->sum('quantity');

        if ((float) $this->quantity <= (float) $taken) {
            $this->converted = 1;
            $this->save();
        } elseif ($reset) {
            $this->converted = 0;
            $this->save();
        }
    }
}
