<?php

namespace App\Models;

use App\Support\Stock;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;

/**
 * On-hand quantity per store, item and batch (`os_stock_summary`).
 *
 * @property int $store
 * @property int $item
 * @property int $batch
 * @property string|float $quantity
 * @property string|float|null $rate
 * @property string|float|null $amount
 * @property int $id
 */
#[Table('stock_summary', timestamps: false)]
#[Fillable(['store', 'item', 'batch', 'quantity', 'rate', 'amount'])]
class StockSummary extends LegacyModel
{
    /**
     * Add stock (StockSummary::receiveStockSummary()).
     */
    public static function receive(mixed $store, mixed $item, mixed $batch, mixed $quantity): void
    {
        self::adjust($store, $item, $batch, (float) $quantity);
    }

    /**
     * Remove stock (StockSummary::issueStockSummary()). A missing row is
     * created with the quantity as given, as in the Yii app.
     */
    public static function issue(mixed $store, mixed $item, mixed $batch, mixed $quantity): void
    {
        self::adjust($store, $item, $batch, -(float) $quantity, createWith: (float) $quantity);
    }

    /**
     * On-hand quantity of one store/item/batch (StockSummary::availableQty()).
     */
    public static function availableQty(mixed $store, mixed $item, mixed $batch): float
    {
        return (float) static::query()
            ->where('store', (int) $store)->where('batch', (int) $batch)->where('item', (int) $item)
            ->value('quantity');
    }

    private static function adjust(mixed $store, mixed $item, mixed $batch, float $change, ?float $createWith = null): void
    {
        $rate = Stock::itemRate($item, $store, $batch);
        $row = static::query()->where('store', (int) $store)->where('batch', (int) $batch)->where('item', (int) $item)->first();

        if ($row === null) {
            $row = new self(['store' => (int) $store, 'item' => (int) $item, 'batch' => (int) $batch]);
            $row->quantity = $createWith ?? $change;
        } else {
            $row->quantity = (float) $row->quantity + $change;
        }

        // Yii stored a missing LIFO/FIFO rate (false) as 0
        $row->rate = $rate === false ? 0 : $rate;
        $row->amount = round((float) $row->quantity * (float) $rate, 2);
        $row->save();

        Stock::forgetOptionCaches();
    }
}
