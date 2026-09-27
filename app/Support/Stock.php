<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Stock helpers of the Yii app's StockRequisition model: item rates from
 * approved purchase receives, quantities held by draft/pending invoices,
 * and the Product/Store/Batch option lists of the invoice form.
 */
class Stock
{
    public const STORE_LIST_CACHE_KEY = 'StockRequisition_StoreList';

    public const BATCH_LIST_CACHE_KEY = 'StockRequisition_BatchList';

    /**
     * Sale rate of an item (StockRequisition::genarateItemRate()), by the
     * RATEMETHODE setting. False when nothing was received (LIFO/FIFO),
     * which Yii stored as 0.
     */
    public static function itemRate(mixed $item, mixed $store, mixed $batch): mixed
    {
        return self::rate('total_amount', 'rate', $item, $store, $batch);
    }

    /**
     * Buy rate of an item (StockRequisition::genarateItemBuyRate()).
     */
    public static function itemBuyRate(mixed $item, mixed $store, mixed $batch): mixed
    {
        return self::rate('buy_amount', 'buy_rate', $item, $store, $batch);
    }

    /**
     * Quantity of an item/store/batch in draft invoices (no parent yet) plus
     * pending invoices (StockRequisition::getQtyPendingBatch()).
     */
    public static function qtyPendingBatch(mixed $item, mixed $store, mixed $batch): float
    {
        $draft = DB::table('invoice')
            ->where(fn ($query) => $query->whereNull('parent')->orWhere('parent', 0))
            ->where('item', (int) $item)->where('store', (int) $store)->where('batch', (int) $batch)
            ->sum('quantity');

        $pending = DB::table('invoice as inv')
            ->leftJoin('invoice_parent as invp', 'inv.parent', '=', 'invp.id')
            ->where('invp.status', 0)
            ->where('inv.item', (int) $item)->where('inv.store', (int) $store)->where('inv.batch', (int) $batch)
            ->sum('inv.quantity');

        return (float) $draft + (float) $pending;
    }

    /**
     * Product dropdown options, "Title [unit]" (StockRequisition::getItemList()),
     * cached for ten minutes and cleared when a product is saved.
     *
     * @return array<int, string>
     */
    public static function itemOptions(): array
    {
        return Cache::remember(Product::ITEM_LIST_CACHE_KEY, 600, function () {
            return DB::table('product as p')
                ->leftJoin('unit as u', 'u.id', '=', 'p.unit')
                ->orderBy('p.title')
                ->get(['p.id', 'p.title', 'u.formal_name'])
                ->mapWithKeys(fn ($row) => [$row->id => $row->title.' ['.($row->formal_name ?: 'N/A').']'])
                ->all();
        });
    }

    /**
     * Store options per item with the quantity still free to invoice
     * (StockRequisition::getStoreList()). Each row: value, label, and the
     * item id the option belongs to.
     *
     * @return array<int, array{value: int, label: string, chain: string}>
     */
    public static function storeOptions(): array
    {
        return Cache::remember(self::STORE_LIST_CACHE_KEY, 300, function () {
            $rows = DB::select('
                SELECT stock.store, stock.item, stock.total_quantity,
                       IFNULL(draft.total_quantity, 0) AS draft_quantity,
                       IFNULL(pending.total_quantity, 0) AS pending_quantity,
                       st.alias AS store_name
                FROM (SELECT store, item, SUM(quantity) AS total_quantity FROM '.self::table('stock_summary').' GROUP BY store, item) stock
                LEFT JOIN '.self::table('store').' st ON st.id = stock.store
                LEFT JOIN (
                    SELECT item, store, SUM(quantity) AS total_quantity FROM '.self::table('invoice').'
                    WHERE (parent IS NULL OR parent=0) GROUP BY item, store
                ) draft ON draft.item = stock.item AND draft.store = stock.store
                LEFT JOIN (
                    SELECT inv.item, inv.store, SUM(inv.quantity) AS total_quantity
                    FROM '.self::table('invoice').' inv INNER JOIN '.self::table('invoice_parent').' invp ON invp.id = inv.parent
                    WHERE invp.status = 0 GROUP BY inv.item, inv.store
                ) pending ON pending.item = stock.item AND pending.store = stock.store
            ');

            return array_map(fn ($row) => [
                'value' => (int) $row->store,
                'label' => $row->store_name.' ['.YiiFormat::number($row->total_quantity - $row->draft_quantity - $row->pending_quantity).']',
                'chain' => (string) (int) $row->item,
            ], $rows);
        });
    }

    /**
     * Batch options per item and store with expiry and free quantity
     * (StockRequisition::getBatchList()). Expired batches are red.
     *
     * @return array<int, array{value: int, label: string, chain: string, expired: bool}>
     */
    public static function batchOptions(): array
    {
        return Cache::remember(self::BATCH_LIST_CACHE_KEY, 300, function () {
            $rows = DB::select('
                SELECT stock.batch, stock.item, stock.store, stock.total_quantity,
                       IFNULL(draft.total_quantity, 0) AS draft_quantity,
                       IFNULL(pending.total_quantity, 0) AS pending_quantity,
                       bt.title AS batch_title, bt.expiry, u.formal_name AS uom
                FROM (
                    SELECT store, item, batch, SUM(quantity) AS total_quantity FROM '.self::table('stock_summary').'
                    WHERE quantity > 0 GROUP BY store, item, batch
                ) stock
                LEFT JOIN '.self::table('batch').' bt ON bt.id = stock.batch
                LEFT JOIN '.self::table('product').' p ON p.id = stock.item
                LEFT JOIN '.self::table('unit').' u ON u.id = p.unit
                LEFT JOIN (
                    SELECT item, store, batch, SUM(quantity) AS total_quantity FROM '.self::table('invoice').'
                    WHERE (parent IS NULL OR parent=0) GROUP BY item, store, batch
                ) draft ON draft.item = stock.item AND draft.store = stock.store AND draft.batch = stock.batch
                LEFT JOIN (
                    SELECT inv.item, inv.store, inv.batch, SUM(inv.quantity) AS total_quantity
                    FROM '.self::table('invoice').' inv INNER JOIN '.self::table('invoice_parent').' invp ON invp.id = inv.parent
                    WHERE invp.status = 0 GROUP BY inv.item, inv.store, inv.batch
                ) pending ON pending.item = stock.item AND pending.store = stock.store AND pending.batch = stock.batch
            ');

            return array_map(fn ($row) => [
                'value' => (int) $row->batch,
                'label' => $row->batch_title.' ['.$row->expiry.']  ['.YiiFormat::number($row->total_quantity - $row->draft_quantity - $row->pending_quantity).($row->uom ?: 'N/A').']',
                'chain' => (int) $row->item.'\\'.(int) $row->store,
                'expired' => ! ($row->expiry !== null && strtotime($row->expiry) > time() - 86400),
            ], $rows);
        });
    }

    /**
     * Forget the store/batch option lists (done whenever stock changes).
     */
    public static function forgetOptionCaches(): void
    {
        Cache::forget(self::STORE_LIST_CACHE_KEY);
        Cache::forget(self::BATCH_LIST_CACHE_KEY);
    }

    private static function rate(string $amountColumn, string $rateColumn, mixed $item, mixed $store, mixed $batch): mixed
    {
        $query = DB::table('purchase_receive_parent as prp')
            ->join('purchase_receive as pr', 'pr.parent', '=', 'prp.id')
            ->where('prp.status', 1)
            ->where('pr.item', (int) $item);

        // Aliases are prefixed too ("purchase_receive as pr" is `os_pr`), so wrap them for raw SQL
        $average = DB::raw('ROUND((SUM('.DB::getQueryGrammar()->wrap("pr.$amountColumn").')/SUM('.DB::getQueryGrammar()->wrap('pr.quantity').')),6)');

        return match (config('legacy.RATEMETHODE')) {
            'ACTUAL' => $query->where('pr.store', (int) $store)->where('pr.batch', (int) $batch)
                ->value($average),
            'LIFO' => $query->orderByDesc('pr.id')->value("pr.$rateColumn") ?? false,
            'FIFO' => $query->orderBy('pr.id')->value("pr.$rateColumn") ?? false,
            'AVERAGE' => $query->value($average),
            default => null,
        };
    }

    /**
     * Prefixed table name for raw SQL.
     */
    private static function table(string $name): string
    {
        return DB::getTablePrefix().$name;
    }
}
