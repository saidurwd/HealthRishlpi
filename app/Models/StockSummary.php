<?php

namespace App\Models;

use App\Support\Stock;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Support\Facades\DB;

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

    /**
     * One atomic statement, so changes made at the same moment add up instead
     * of the last save overwriting the others (the row is unique per
     * store/item/batch). MySQL applies the assignments in order, so the
     * amount uses the new quantity.
     */
    private static function adjust(mixed $store, mixed $item, mixed $batch, float $change, ?float $createWith = null): void
    {
        $rate = Stock::itemRate($item, $store, $batch);
        // Yii stored a missing LIFO/FIFO rate (false) as 0
        $storedRate = $rate === false ? 0 : $rate;
        $initial = $createWith ?? $change;

        DB::statement('
            INSERT INTO '.DB::getQueryGrammar()->wrapTable('stock_summary').' (store, item, batch, quantity, rate, amount)
            VALUES (?, ?, ?, ?, ?, ROUND(? * ?, 2))
            ON DUPLICATE KEY UPDATE quantity = quantity + ?, rate = ?, amount = ROUND(quantity * ?, 2)
        ', [(int) $store, (int) $item, (int) $batch, $initial, $storedRate, $initial, (float) $rate, $change, $storedRate, (float) $rate]);

        Stock::forgetOptionCaches();
    }
}
