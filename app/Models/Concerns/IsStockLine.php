<?php

namespace App\Models\Concerns;

use App\Models\Batch;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Relations and display helpers shared by requisition, issue and transfer
 * lines (item / store / batch columns).
 *
 * @property int $item
 * @property int|null $store
 * @property int|null $batch
 */
trait IsStockLine
{
    /** @return BelongsTo<Product, $this> */
    public function item0(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'item');
    }

    /** @return BelongsTo<Store, $this> */
    public function store0(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store');
    }

    /** @return BelongsTo<Batch, $this> */
    public function batch0(): BelongsTo
    {
        return $this->belongsTo(Batch::class, 'batch');
    }

    public function uom(): string
    {
        return $this->item0?->unit0?->formal_name ?: 'N/A';
    }
}
