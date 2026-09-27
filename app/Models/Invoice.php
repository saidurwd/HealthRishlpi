<?php

namespace App\Models;

use App\Support\Stock;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Invoice line (`os_invoice`): a medicine (item/store/batch) or a service.
 * Lines added before the invoice is saved have no parent and belong to
 * their creator until then.
 *
 * @property int $id
 * @property int|null $parent
 * @property string|null $servicetype Medicine|Service
 * @property int|null $service
 * @property int|null $item
 * @property string $quantity
 * @property string|null $rate
 * @property string|null $discount
 * @property string|null $amount
 * @property int|null $store
 * @property int|null $batch
 */
#[Table('invoice', timestamps: false)]
class Invoice extends LegacyModel
{
    public static function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'parent' => 'Parent',
            'service' => 'Service',
            'item' => 'Product',
            'quantity' => 'Quantity',
            'rate' => 'Rate',
            'discount' => 'Discount',
            'amount' => 'Amount',
            'store' => 'Store',
            'batch' => 'Batch',
            'note' => 'Note',
            'created_by' => 'Created By',
            'created_on' => 'Created On',
            'discountamount' => 'Discountamount',
        ];
    }

    public static function rules(?LegacyModel $model = null): array
    {
        return [
            'quantity' => ['required', 'max:18'],
            'parent' => ['integer'],
            'service' => ['integer'],
            'item' => ['integer'],
            'store' => ['integer'],
            'batch' => ['integer'],
            'discountamount' => ['integer'],
            'rate' => ['max:18'],
            'servicetype' => ['max:100'],
            'note' => ['max:400'],
            'discounttype' => ['nullable'],
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function item0(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'item');
    }

    /** @return BelongsTo<Service, $this> */
    public function service0(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service');
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

    /**
     * Product or service title (Product::getItemName() . Service::getData()).
     */
    public function title(): string
    {
        return $this->item0?->title.$this->service0?->title;
    }

    /**
     * Unit of the product, "N/A" for services (Product::getItemUOM()).
     */
    public function uom(): string
    {
        return $this->item0?->unit0?->formal_name ?: 'N/A';
    }

    /**
     * Quantity still free to invoice for this line's item/store/batch:
     * on hand minus draft and pending invoices, plus the line's own saved
     * quantity when it is being edited (checkAvailability[Edit]()).
     */
    public function availableQuantity(): float
    {
        $free = StockSummary::availableQty($this->store, $this->item, $this->batch)
            - Stock::qtyPendingBatch($this->item, $this->store, $this->batch);

        return $this->exists ? $free + (float) $this->getOriginal('quantity') : $free;
    }

    /**
     * Recompute total, discount and amount after a quantity change
     * (InvoiceController::actionAdjustment()): 10% off medicines, and off
     * services whose discount is "Yes".
     */
    public function applyQuantity(mixed $quantity): void
    {
        $this->quantity = $quantity;
        $total = (float) $this->quantity * (float) $this->rate;

        $percent = $this->item > 0
            ? (int) config('legacy.discountMedicine')
            : ($this->service0?->discount === 'Yes' ? (int) config('legacy.discountService') : 0);

        $this->discount = round($total * ($percent / 100), 6);
        $this->amount = round($total - $this->discount, 6);
    }

    /**
     * Sum of the line amounts of an invoice (Invoice::getTotalAmount()).
     */
    public static function totalAmount(int $parent): mixed
    {
        return DB::table('invoice')->where('parent', $parent)->value(DB::raw('ROUND((SUM(amount)),6)'));
    }
}
