<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderHistory;
use App\Models\PurchaseOrderParent;
use App\Models\PurchaseReceive;
use App\Models\PurchaseReceiveDocument;
use App\Models\PurchaseReceiveParent;
use App\Models\StockSummary;
use App\Models\Store;
use App\Models\Unit;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Purchase orders, goods receives (typed in or loaded from orders), stock
 * on receipt, special edit and receive documents.
 */
class PurchaseTest extends TestCase
{
    private User $user;

    private Product $product;

    private Store $store;

    private Vendor $vendor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->makeUser(['group_id' => User::SUPER_GROUP]);
        $this->actingAs($this->user)->withSession(['currency' => '৳']);
        $unit = Unit::create(['full_name' => 'Piece', 'formal_name' => 'pcs', 'decimal_place' => 0]);
        $this->product = Product::create(['category' => ProductCategory::create(['title' => 'Tablets'])->id, 'title' => 'Napa', 'product_code' => 'N5', 'unit' => $unit->id]);
        $this->store = Store::create(['title' => 'Pharmacy', 'alias' => 'Pharmacy']);
        $this->vendor = Vendor::create(['title' => 'Acme', 'address' => 'Dhaka']);
    }

    private function approvedOrder(int $quantity = 10): PurchaseOrder
    {
        $parent = PurchaseOrderParent::create(['supplier' => $this->vendor->id]);
        $parent->forceFill(['order_date' => now(), 'order_number' => 'PO/26/00001', 'order_by' => $this->user->id, 'status' => 1])->save();

        $line = new PurchaseOrder(['parent' => $parent->id, 'item' => $this->product->id, 'quantity' => $quantity]);
        $line->forceFill(['converted' => 0])->save();

        return $line;
    }

    public function test_purchase_order_lifecycle(): void
    {
        $this->post('/purchaseOrder/add', ['parent' => 0, 'item' => '', 'quantity' => ''], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonFragment(['line' => ['Please select an Item.', 'Please enter Quantity.']]);

        $this->post('/purchaseOrder/add', ['parent' => 0, 'item' => $this->product->id, 'quantity' => '12'])->assertOk();
        $this->get('/purchaseOrder/create')->assertOk()->assertSee('Napa - N5 (pcs)')->assertSee('12.00 pcs');

        $this->post('/purchaseOrder/create', ['supplier' => $this->vendor->id, 'comments' => 'Urgent'])
            ->assertRedirect('/purchaseOrder/admin')->assertSessionHas('success', 'Data was saved successfully');

        $order = PurchaseOrderParent::query()->firstOrFail();
        $this->assertSame('PO/'.date('y').'/00001', $order->order_number);
        $this->assertSame($order->id, PurchaseOrder::query()->value('parent'));

        $this->post("/purchaseOrder/update/$order->id", ['status' => 1, 'supplier' => $this->vendor->id])->assertRedirect('/purchaseOrder/admin');
        $this->get("/purchaseOrder/update/$order->id")->assertRedirect('/purchaseOrder/admin')->assertSessionHas('error');
        $this->get("/purchaseOrder/print/$order->id")->assertOk()->assertSee('Acme')->assertSee('Napa');

        $this->post("/purchaseOrder/remove/$order->id")->assertRedirect('/purchaseOrder/admin');
        $this->assertSame(2, $order->fresh()->status);
    }

    public function test_typed_receive_line_finds_or_creates_the_batch_and_receiving_adds_stock(): void
    {
        $existing = Batch::create(['title' => 'OLD', 'expiry' => '2031-05-31']);

        $this->post('/purchaseReceive/add', ['parent' => 0, 'item' => $this->product->id, 'store' => $this->store->id, 'quantity' => '10', 'buy_rate' => '4', 'rate' => '5', 'expiry' => '2030-01-31'])->assertOk();
        $this->post('/purchaseReceive/add', ['parent' => 0, 'item' => $this->product->id, 'store' => $this->store->id, 'quantity' => '2', 'buy_rate' => '4', 'rate' => '5', 'expiry' => '2031-05-31'])->assertOk();
        $this->post('/purchaseReceive/add', ['parent' => 0, 'item' => $this->product->id, 'store' => '', 'quantity' => '2', 'buy_rate' => '4', 'rate' => '5', 'expiry' => ''], ['Accept' => 'application/json'])->assertStatus(422);

        [$first, $second] = PurchaseReceive::query()->orderBy('id')->get()->all();
        $this->assertSame('20300131', $first->batch0->title);
        $this->assertSame($existing->id, $second->batch);
        $this->assertEquals([50, 40], [$first->total_amount, $first->buy_amount]);

        $this->post('/purchaseReceive/create', ['supplier' => $this->vendor->id, 'comments' => '', 'doc_file' => [UploadedFile::fake()->create('Bill.pdf', 10)]])
            ->assertRedirect('/purchaseReceive/admin');
        $receive = PurchaseReceiveParent::query()->firstOrFail();
        $this->assertSame('MRR/'.date('y').'/00001', $receive->receive_number);
        $this->assertSame('Bill.pdf', $receive->documents()->value('doc_title'));
        @unlink(public_path('uploads/store/'.$receive->documents()->value('doc_file')));

        $this->post("/purchaseReceive/update/$receive->id", ['status' => 1, 'supplier' => $this->vendor->id])->assertRedirect('/purchaseReceive/admin');
        $this->assertEquals(12, StockSummary::query()->where('item', $this->product->id)->sum('quantity'));

        $this->get('/purchaseReceive/admin')->assertSee($receive->receive_number)->assertSee('৳60.00', false);
        $this->get("/purchaseReceive/view/$receive->id")->assertOk()->assertSee('20300131');
        $this->get('/purchaseReceive/price')->assertOk()->assertSee('Napa');

        // Special edit: 10 → 7 takes 3 out of stock at once
        $this->post('/purchaseReceive/adjustmentEdit', ['id' => $first->id, 'adjustment' => '7', 'type' => 'quantity'])->assertOk();
        $this->assertEquals(9, StockSummary::query()->where('item', $this->product->id)->sum('quantity'));
        $this->get("/purchaseReceive/edit/$receive->id")->assertOk();
    }

    public function test_lines_without_store_or_batch_block_saving(): void
    {
        $order = $this->approvedOrder();
        $this->post('/purchaseReceive/addpo', ['ids' => (string) $order->id, 'prp' => 0])->assertOk();

        $this->post('/purchaseReceive/create', ['supplier' => $this->vendor->id])
            ->assertRedirect('/purchaseReceive/create')
            ->assertSessionHas('error', 'Store/Batch cannot be blank!');
    }

    public function test_loading_from_a_purchase_order_tracks_what_is_still_to_receive(): void
    {
        $order = $this->approvedOrder(10);

        $this->get('/purchaseReceive/create')->assertOk()->assertSee('PO/26/00001');

        $this->post('/purchaseReceive/addpo', ['ids' => (string) $order->id, 'prp' => 0])->assertOk();
        $line = PurchaseReceive::query()->firstOrFail();
        $this->assertEquals(10, $line->quantity);
        $this->assertSame(1, $order->fresh()->converted);
        $this->assertSame('PO/26/00001', $line->referenceOrderNumber());

        // Receiving only 6 puts the order back on the list
        $this->post('/purchaseReceive/adjustment', ['id' => $line->id, 'adjustment' => '6', 'type' => 'quantity'])->assertOk();
        $this->assertEquals(6, PurchaseOrderHistory::query()->value('quantity'));
        $this->assertSame(0, $order->fresh()->converted);
        $this->assertEquals(4, $order->fresh()->availableQuantity());

        $this->post('/purchaseReceive/adjustment', ['id' => $line->id, 'adjustment' => (string) $this->store->id, 'type' => 'store'])->assertOk();
        $this->assertSame($this->store->id, $line->fresh()->store);

        // Deleting the line gives the whole quantity back
        $this->post("/purchaseReceive/delete/$line->id", ['ajax' => 'purchase-receive-grid'])->assertNoContent();
        $this->assertSame(0, PurchaseOrderHistory::query()->count());
        $this->assertEquals(10, $order->fresh()->availableQuantity());
    }

    public function test_line_documents(): void
    {
        $this->post('/purchaseReceive/add', ['parent' => 0, 'item' => $this->product->id, 'store' => $this->store->id, 'quantity' => '1', 'buy_rate' => '1', 'rate' => '1', 'expiry' => '2030-01-31']);
        $line = PurchaseReceive::query()->firstOrFail();

        $this->post('/purchaseReceive/docupload', ['receive_number' => $line->id, 'doc_title' => 'Invoice scan', 'doc_file' => UploadedFile::fake()->create('scan.pdf', 5)])->assertOk();
        $document = PurchaseReceiveDocument::query()->firstOrFail();

        $this->get("/purchaseReceive/upload/$line->id")->assertOk()->assertSee('Invoice scan');
        $this->get("/purchaseReceive/downloadfile/$document->id")->assertOk()->assertDownload($document->doc_file);
        $this->get("/purchaseReceive/downloadall/$line->id")->assertOk()->assertDownload("$line->id.zip");

        $this->post("/purchaseReceive/deletefile/$document->id", ['ajax' => 'purchase-receive-document-grid'])->assertNoContent();
        $this->assertFileDoesNotExist(public_path('uploads/store/'.$document->doc_file));
    }
}
