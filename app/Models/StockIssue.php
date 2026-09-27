<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stock issue line (`os_stock_issue`). Lines added before the issue is
 * saved have parent 0 and belong to their creator until then;
 * `reference` is the requisition line it came from.
 *
 * @property int $id
 * @property int $parent
 * @property int|null $reference
 * @property int $item
 * @property string $quantity
 * @property string|null $rate
 * @property string|null $amount
 * @property int|null $store
 * @property int|null $batch
 */
#[Table('stock_issue', timestamps: false)]
#[Fillable(['parent', 'item', 'quantity', 'store', 'batch'])]
class StockIssue extends LegacyModel
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
            'store' => ['integer'],
            'batch' => ['integer'],
        ];
    }

    /** @return BelongsTo<StockRequisition, $this> */
    public function requisition0(): BelongsTo
    {
        return $this->belongsTo(StockRequisition::class, 'reference');
    }

    /**
     * Number of the requisition the line came from (StockIssueParent::getReferenceRequisitionNo()).
     */
    public function referenceNumber(): ?string
    {
        return $this->reference > 0 ? $this->requisition0?->parent0?->requisition_number : null;
    }

    /**
     * Keep the requisition history and the requisition line's converted
     * flag in step with this line's quantity.
     */
    public function syncRequisitionHistory(): void
    {
        if (! $this->reference) {
            return;
        }

        StockRequisitionHistory::query()->where('requisition_number', $this->reference)->where('issue_number', $this->id)->update(['quantity' => $this->quantity]);
        $this->requisition0?->refreshConverted();
    }
}
