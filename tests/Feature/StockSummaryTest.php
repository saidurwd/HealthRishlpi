<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Product;
use App\Models\StockSummary;
use App\Models\Store;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * StockSummary::receive() / issue(): one atomic statement per change.
 */
class StockSummaryTest extends TestCase
{
    public function test_changes_add_up_and_the_amount_follows_the_latest_rate(): void
    {
        [$store, $item, $batch] = $this->stockKeys();
        $this->approvedReceive($item, rate: 2.5);

        StockSummary::receive($store, $item, $batch, 10);
        StockSummary::issue($store, $item, $batch, 4);
        // A change written meanwhile by someone else is kept
        DB::table('stock_summary')->where(['store' => $store, 'item' => $item, 'batch' => $batch])->update(['quantity' => DB::raw('quantity + 1')]);
        StockSummary::issue($store, $item, $batch, 2);

        $row = StockSummary::query()->where(['store' => $store, 'item' => $item, 'batch' => $batch])->sole();
        $this->assertEquals(5, $row->quantity);
        $this->assertEquals(2.5, $row->rate);
        $this->assertEquals(12.5, $row->amount);
    }

    public function test_it_keeps_the_yii_quirks(): void
    {
        [$store, $item, $batch] = $this->stockKeys();

        // Issuing from a missing row creates it with the quantity as given, and
        // no received rate (LIFO) is stored as 0
        StockSummary::issue($store, $item, $batch, 3);

        $row = StockSummary::query()->where(['store' => $store, 'item' => $item, 'batch' => $batch])->sole();
        $this->assertEquals(3, $row->quantity);
        $this->assertEquals(0, $row->rate);
        $this->assertEquals(0, $row->amount);
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private function stockKeys(): array
    {
        return [Store::factory()->create()->id, Product::factory()->create()->id, Batch::factory()->create()->id];
    }

    private function approvedReceive(int $item, float $rate): void
    {
        $parent = DB::table('purchase_receive_parent')->insertGetId(['receive_date' => now(), 'receive_number' => 'MRR/26/00001', 'receive_by' => 1, 'supplier' => Vendor::factory()->create()->id, 'status' => 1]);
        DB::table('purchase_receive')->insert(['parent' => $parent, 'item' => $item, 'quantity' => 1, 'rate' => $rate, 'total_amount' => $rate, 'store' => 1, 'batch' => 1]);
    }
}
