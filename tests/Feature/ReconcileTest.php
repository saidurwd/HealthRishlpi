<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Product;
use App\Models\StockSummary;
use App\Models\Store;
use App\Models\Vendor;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * health:reconcile, the daily check while both apps share the database.
 */
class ReconcileTest extends TestCase
{
    private string $storage;

    protected function setUp(): void
    {
        parent::setUp();

        // Keep test baselines away from a real one
        $this->storage = sys_get_temp_dir().'/reconcile-test-'.uniqid();
        File::ensureDirectoryExists($this->storage.'/app');
        $this->app->useStoragePath($this->storage);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->storage);

        parent::tearDown();
    }

    public function test_it_needs_a_baseline(): void
    {
        $this->artisan('health:reconcile')->expectsOutputToContain('No baseline yet')->assertFailed();
    }

    public function test_it_reports_only_what_is_new_since_the_baseline(): void
    {
        // Already inconsistent before the baseline: stays quiet afterwards
        $this->approvedInvoice('INV#OLD-1', total: 100, lines: [90]);

        $this->assertSame(0, Artisan::call('health:reconcile', ['--baseline' => true]));
        $this->artisan('health:reconcile')->expectsOutputToContain('Nothing new since the baseline.')->assertSuccessful();

        $bad = $this->approvedInvoice('INV#NEW-1', total: 50, lines: [20]);
        $this->approvedInvoice('INV#NEW-1', total: 10, lines: [10]);
        $store = Store::factory()->create();
        $product = Product::factory()->create();
        $batch = Batch::factory()->create();
        StockSummary::create(['store' => $store->id, 'item' => $product->id, 'batch' => $batch->id, 'quantity' => 7, 'rate' => 1, 'amount' => 7]);
        DB::table('prescription_medicine')->insert(['parent' => 999999, 'created_by' => 1]);
        $user = $this->makeUser();
        DB::table('user')->where('id', $user->id)->update(['group_id' => $this->group(3)->id]);

        $this->assertSame(1, Artisan::call('health:reconcile'));
        $output = Artisan::output();

        $this->assertStringContainsString("invoice_totals new: $bad = 30", $output);
        $this->assertStringContainsString('duplicate_numbers new: invoice_parent:INV#NEW-1 = 2', $output);
        $this->assertStringContainsString("stock new: $store->id|$product->id|$batch->id = 7", $output);
        $this->assertStringContainsString('orphan_lines new: prescription_medicine:', $output);
        $this->assertStringContainsString("user_roles new: $user->id = 3 -> 2", $output);
        $this->assertStringNotContainsString('INV#OLD-1', $output);
    }

    public function test_approved_movements_balance_the_stock_summary(): void
    {
        $this->assertSame(0, Artisan::call('health:reconcile', ['--baseline' => true]));

        $store = Store::factory()->create();
        $product = Product::factory()->create();
        $batch = Batch::factory()->create();
        $receive = DB::table('purchase_receive_parent')->insertGetId(['receive_date' => now(), 'receive_number' => 'MRR/26/99999', 'receive_by' => 1, 'supplier' => Vendor::factory()->create()->id, 'status' => 1]);
        DB::table('purchase_receive')->insert(['parent' => $receive, 'item' => $product->id, 'quantity' => 10, 'rate' => 1, 'total_amount' => 10, 'store' => $store->id, 'batch' => $batch->id]);
        $invoice = $this->approvedInvoice('INV#T-2', total: 3, lines: []);
        DB::table('invoice')->insert(['parent' => $invoice, 'servicetype' => 'Medicine', 'item' => $product->id, 'quantity' => 3, 'rate' => 1, 'amount' => 3, 'store' => $store->id, 'batch' => $batch->id]);
        StockSummary::create(['store' => $store->id, 'item' => $product->id, 'batch' => $batch->id, 'quantity' => 7, 'rate' => 1, 'amount' => 7]);

        $this->artisan('health:reconcile')->expectsOutputToContain('Nothing new since the baseline.')->assertSuccessful();
    }

    /**
     * @param  array<int, float>  $lines  service line amounts
     */
    private function approvedInvoice(string $number, float $total, array $lines): int
    {
        $id = DB::table('invoice_parent')->insertGetId(['patient' => 1, 'invoice_date' => now(), 'invoice_number' => $number, 'invoice_by' => 1, 'total_amount' => $total, 'status' => 1]);
        foreach ($lines as $amount) {
            DB::table('invoice')->insert(['parent' => $id, 'servicetype' => 'Service', 'item' => 0, 'quantity' => 1, 'rate' => $amount, 'amount' => $amount]);
        }

        return $id;
    }
}
