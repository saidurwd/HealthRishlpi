<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockIssue;
use App\Models\StockIssueParent;
use App\Models\StockRequisition;
use App\Models\StockRequisitionHistory;
use App\Models\StockRequisitionParent;
use App\Models\StockSummary;
use App\Models\StockTransfer;
use App\Models\StockTransferParent;
use App\Models\Store;
use App\Models\Unit;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Stock requisitions, issues (typed in, loaded from a requisition or made
 * from one) and store transfers, with their stock movements.
 */
class StockTest extends TestCase
{
    private User $user;

    private Product $product;

    private Store $store;

    private Store $ward;

    private Batch $batch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->makeUser(['group_id' => User::SUPER_GROUP]);
        $this->actingAs($this->user)->withSession(['currency' => '৳']);
        $unit = Unit::create(['full_name' => 'Piece', 'formal_name' => 'pcs', 'decimal_place' => 0]);
        $this->product = Product::create(['category' => ProductCategory::create(['title' => 'Tablets'])->id, 'title' => 'Napa', 'unit' => $unit->id]);
        $this->store = Store::create(['title' => 'Pharmacy', 'alias' => 'Pharmacy']);
        $this->ward = Store::create(['title' => 'Ward', 'alias' => 'Ward']);
        $this->batch = Batch::create(['title' => 'LOT-1', 'expiry' => '2030-01-01']);

        // Buy rate 4 (LIFO) and 10 on hand in the pharmacy
        $receive = DB::table('purchase_receive_parent')->insertGetId(['receive_date' => now(), 'receive_number' => 'MRR/26/00001', 'receive_by' => $this->user->id, 'supplier' => Vendor::create(['title' => 'Acme'])->id, 'status' => 1]);
        DB::table('purchase_receive')->insert(['parent' => $receive, 'item' => $this->product->id, 'quantity' => 10, 'rate' => 5, 'buy_rate' => 4, 'total_amount' => 50, 'store' => $this->store->id, 'batch' => $this->batch->id]);
        StockSummary::create(['store' => $this->store->id, 'item' => $this->product->id, 'batch' => $this->batch->id, 'quantity' => 10, 'rate' => 5, 'amount' => 50]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function line(array $overrides = []): array
    {
        return array_merge(['parent' => 0, 'item' => $this->product->id, 'store' => $this->store->id, 'batch' => $this->batch->id, 'quantity' => '3'], $overrides);
    }

    private function onHand(Store $store): float
    {
        return (float) StockSummary::query()->where('store', $store->id)->where('item', $this->product->id)->value('quantity');
    }

    private function approvedRequisition(): StockRequisitionParent
    {
        $this->post('/stockRequisition/add', $this->line())->assertOk();
        $this->post('/stockRequisition/create', ['comments' => 'Ward stock']);
        $requisition = StockRequisitionParent::query()->firstOrFail();
        $this->post("/stockRequisition/update/$requisition->id", ['status' => 1, 'comments' => 'Ward stock']);

        return $requisition->fresh();
    }

    public function test_requisition_lines_are_priced_at_the_buy_rate_and_need_stock(): void
    {
        $this->post('/stockRequisition/add', $this->line(['batch' => '']), ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonFragment(['line' => ['Please select an Item.', 'Please select a Store.', 'Please select a Batch.', 'Please enter Quantity.']]);
        $this->post('/stockRequisition/add', $this->line(['quantity' => '11']), ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonFragment(['quantity' => ['Sorry! Request quantity not available!']]);

        $this->post('/stockRequisition/add', $this->line())->assertOk();
        $line = StockRequisition::query()->firstOrFail();
        $this->assertEquals([4, 12], [$line->rate, $line->amount]);

        $this->post('/stockRequisition/create', ['comments' => ''])->assertSessionHas('success');
        $requisition = StockRequisitionParent::query()->firstOrFail();
        $this->assertSame('SR#TESTER-'.date('Y').'-1', $requisition->requisition_number);
        $this->get("/stockRequisition/view/$requisition->id")->assertOk()->assertSee('EDIT')->assertDontSee('MAKE ME ISSUE');
        $this->get("/stockRequisition/print/$requisition->id")->assertOk()->assertSee('৳12.00', false);
    }

    public function test_make_me_issue_turns_an_approved_requisition_into_a_pending_issue(): void
    {
        $requisition = $this->approvedRequisition();
        $this->get("/stockRequisition/view/$requisition->id")->assertSee('MAKE ME ISSUE');

        $this->post("/stockRequisition/convertissue/$requisition->id")->assertSessionHas('success');

        $issue = StockIssueParent::query()->firstOrFail();
        $this->assertSame(0, $issue->status);
        $this->assertEquals(12, $issue->total_amount);
        $this->assertSame(StockRequisition::query()->value('id'), StockIssue::query()->value('reference'));
        $this->assertSame(1, StockRequisition::query()->value('converted'));
        $this->get("/stockRequisition/view/$requisition->id")->assertDontSee('MAKE ME ISSUE');

        // Issuing takes the stock out
        $this->post("/stockIssue/update/$issue->id", ['status' => 1, 'comments' => ''])->assertRedirect('/stockIssue/admin');
        $this->assertSame(7.0, $this->onHand($this->store));
    }

    public function test_issue_loaded_from_a_requisition_line_tracks_the_remaining_quantity(): void
    {
        $this->approvedRequisition();
        $requisitionLine = StockRequisition::query()->firstOrFail();

        $this->get('/stockIssue/create')->assertOk()->assertSee('SR#TESTER');
        $this->post('/stockIssue/addsr', ['id' => $requisitionLine->id])->assertOk();

        $line = StockIssue::query()->firstOrFail();
        $this->assertEquals(3, $line->quantity);
        $this->assertSame(1, $requisitionLine->fresh()->converted);

        $this->post('/stockIssue/adjustment', ['id' => $line->id, 'adjustment' => '2', 'type' => 'quantity'])->assertOk();
        $this->assertEquals(2, StockRequisitionHistory::query()->value('quantity'));
        $this->assertSame(0, $requisitionLine->fresh()->converted);

        $this->post("/stockIssue/delete/$line->id", ['ajax' => 'stock-issue-grid'])->assertNoContent();
        $this->assertSame(0, StockRequisitionHistory::query()->count());
    }

    public function test_typed_issue_and_special_edit(): void
    {
        $this->post('/stockIssue/add', $this->line(['quantity' => '4']))->assertOk();
        $this->post('/stockIssue/create', ['comments' => ''])->assertRedirect('/stockIssue/admin');
        $issue = StockIssueParent::query()->firstOrFail();
        $this->assertSame('SI#TESTER-'.date('Y').'-1', $issue->issue_number);

        $line = StockIssue::query()->firstOrFail();
        $this->post('/stockIssue/adjustment', ['id' => $line->id, 'adjustment' => '5', 'type' => 'quantity'])->assertOk();
        $this->post("/stockIssue/update/$issue->id", ['status' => 1, 'comments' => '']);
        $this->assertEquals(20, $issue->fresh()->total_amount);
        $this->assertSame(5.0, $this->onHand($this->store));

        $this->post('/stockIssue/adjustmentEdit', ['id' => $line->id, 'adjustment' => '1', 'type' => 'quantity'])->assertOk();
        $this->assertSame(9.0, $this->onHand($this->store));
        $this->get("/stockIssue/edit/$issue->id")->assertOk();
        $this->get("/stockIssue/print/$issue->id")->assertOk()->assertSee('Grand Total');
    }

    public function test_store_transfer_moves_stock_between_stores(): void
    {
        $this->post('/stockTransfer/add', ['parent' => 0, 'item' => $this->product->id, 'store_from' => $this->store->id, 'batch' => $this->batch->id, 'quantity' => '6', 'store_to' => ''], ['Accept' => 'application/json'])
            ->assertStatus(422);
        $this->post('/stockTransfer/add', ['parent' => 0, 'item' => $this->product->id, 'store_from' => $this->store->id, 'batch' => $this->batch->id, 'quantity' => '6', 'store_to' => $this->ward->id])
            ->assertOk();
        $this->assertEquals([4, 24], [StockTransfer::query()->value('rate'), StockTransfer::query()->value('total_amount')]);

        $this->post('/stockTransfer/create', ['comments' => ''])->assertRedirect('/stockTransfer/admin');
        $transfer = StockTransferParent::query()->firstOrFail();
        $this->assertSame('ST/'.date('y').'/00001', $transfer->transfer_number);

        $this->post("/stockTransfer/update/$transfer->id", ['status' => 1, 'comments' => ''])->assertRedirect('/stockTransfer/admin');
        $this->assertSame(4.0, $this->onHand($this->store));
        $this->assertSame(6.0, $this->onHand($this->ward));

        $this->get('/stockTransfer/admin')->assertSee($transfer->transfer_number);
        $this->get("/stockTransfer/print/$transfer->id")->assertOk()->assertSee('Ward');
    }
}
