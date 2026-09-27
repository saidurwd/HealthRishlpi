<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Invoice;
use App\Models\InvoiceParent;
use App\Models\Patient;
use App\Models\PatientCategory;
use App\Models\PatientCategoryNew;
use App\Models\PatientPrescription;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Service;
use App\Models\StockSummary;
use App\Models\Store;
use App\Models\Unit;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Invoices: line pricing, stock availability, saving, approval (stock
 * issue), quantity adjustments, delete / rollback and printing.
 */
class InvoiceTest extends TestCase
{
    private User $user;

    private Product $product;

    private Store $store;

    private Batch $batch;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->makeUser(['group_id' => User::SUPER_GROUP]);
        $this->actingAs($this->user)->withSession(['currency' => '৳']);
        // Explicit ids: the id is a tinyint, and rolled-back inserts would still use up the counter
        DB::table('transection_status')->insert([
            ['id' => 50, 'status_id' => 0, 'status_title' => 'Pending', 'user_view' => 1, 'transection_type' => 5],
            ['id' => 51, 'status_id' => 1, 'status_title' => 'Approved', 'user_view' => 1, 'transection_type' => 5],
            ['id' => 52, 'status_id' => 2, 'status_title' => 'Deleted', 'user_view' => 1, 'transection_type' => 5],
        ]);

        $unit = Unit::create(['full_name' => 'Piece', 'formal_name' => 'pcs', 'decimal_place' => 0]);
        $this->product = Product::create(['category' => ProductCategory::create(['title' => 'Tablets'])->id, 'title' => 'Napa', 'unit' => $unit->id]);
        $this->store = Store::create(['title' => 'Pharmacy', 'alias' => 'Pharmacy']);
        $this->batch = Batch::create(['title' => 'LOT-1', 'expiry' => '2030-01-01']);

        // An approved receive gives the item its LIFO rate of 5; 10 are on hand
        $receive = DB::table('purchase_receive_parent')->insertGetId(['receive_date' => now(), 'receive_number' => 'PR-1', 'receive_by' => $this->user->id, 'supplier' => Vendor::create(['title' => 'Acme'])->id, 'status' => 1]);
        DB::table('purchase_receive')->insert(['parent' => $receive, 'item' => $this->product->id, 'quantity' => 10, 'rate' => 5, 'total_amount' => 50, 'store' => $this->store->id, 'batch' => $this->batch->id]);
        StockSummary::create(['store' => $this->store->id, 'item' => $this->product->id, 'batch' => $this->batch->id, 'quantity' => 10, 'rate' => 5, 'amount' => 50]);

        $this->patient = Patient::create([
            'category_new' => PatientCategoryNew::create(['title' => 'Adult', 'alias' => 'Adult'])->id,
            'category' => PatientCategory::create(['title' => 'General', 'alias' => 'General'])->id,
            'name' => 'Rahim', 'admission' => 'No',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function addMedicine(array $overrides = []): TestResponse
    {
        return $this->post('/invoice/add', array_merge([
            'servicetype' => 'Medicine', 'item' => $this->product->id, 'store' => $this->store->id, 'batch' => $this->batch->id,
            'discounttype' => 'Percentage', 'discountamount' => '', 'quantity' => '4',
        ], $overrides), ['Accept' => 'application/json']);
    }

    private function stock(): float
    {
        return (float) StockSummary::query()->where('item', $this->product->id)->value('quantity');
    }

    public function test_medicine_line_is_priced_at_the_item_rate_less_ten_percent(): void
    {
        $this->addMedicine()->assertOk();

        $line = Invoice::query()->firstOrFail();
        $this->assertNull($line->parent);
        $this->assertEquals(5, $line->rate);
        $this->assertEquals(2, $line->discount);
        $this->assertEquals(18, $line->amount);

        $this->get('/invoice/create')->assertOk()->assertSee('Napa')->assertSee('৳18.00', false);
    }

    public function test_medicine_line_needs_free_stock_and_every_field(): void
    {
        $this->addMedicine(['quantity' => '7'])->assertOk();

        // 10 on hand, 7 already on a draft invoice
        $this->addMedicine(['quantity' => '4'])->assertStatus(422)->assertJsonFragment(['quantity' => ['Sorry! Request quantity not available!']]);

        $this->addMedicine(['batch' => ''])->assertStatus(422)->assertJsonFragment(['line' => ['Please select an Item.', 'Please select a Store.', 'Please select a Batch.', 'Please enter Quantity.']]);
    }

    public function test_service_lines_take_the_service_rate_and_the_typed_discount(): void
    {
        $service = Service::create(['title' => 'Physio', 'rate' => 200, 'discount' => 'No', 'rate_status' => 'Auto']);
        $manual = Service::create(['title' => 'Dressing', 'rate' => 0, 'discount' => 'No', 'rate_status' => 'Manual']);

        $this->post('/invoice/add', ['servicetype' => 'Service', 'service' => $service->id, 'discounttype' => 'Percentage', 'discountamount' => '10', 'quantity' => '2', 'rate' => '999'])->assertOk();
        $this->post('/invoice/add', ['servicetype' => 'Service', 'service' => $manual->id, 'discounttype' => 'Cash', 'discountamount' => '5', 'quantity' => '1', 'rate' => '50', 'note' => 'Large'])->assertOk();

        [$auto, $typed] = Invoice::query()->orderBy('id')->get()->all();
        $this->assertEquals([200, 40, 360], [$auto->rate, $auto->discount, $auto->amount]);
        $this->assertEquals([50, 5, 45], [$typed->rate, $typed->discount, $typed->amount]);
        // NULL is left out of the insert, so the NOT NULL column takes 0 (as under Yii)
        $this->assertSame(0, $auto->fresh()->item);
    }

    public function test_saving_an_invoice_and_approving_it_issues_stock(): void
    {
        $this->from('/invoice/create')->post('/invoice/create', ['patient' => $this->patient->id, 'payment_status' => 'Paid'])
            ->assertSessionHasErrors(['error_message' => 'Please add one or more items to the grid!']);

        $this->addMedicine();
        $response = $this->post('/invoice/create', ['patient' => $this->patient->id, 'prescription' => '', 'payment_status' => 'Paid', 'comments' => 'OK'])
            ->assertSessionHas('success', 'Invoice was CREATED successfully.');

        $invoice = InvoiceParent::query()->firstOrFail();
        $response->assertRedirect("/invoice/update/$invoice->id");
        $this->assertSame('INV#TESTER-'.date('Y').'-1', $invoice->invoice_number);
        $this->assertEquals(18, $invoice->total_amount);
        $this->assertSame($this->patient->category_new, $invoice->patient_category_new);
        $this->assertSame($invoice->id, Invoice::query()->value('parent'));
        $this->assertSame(10.0, $this->stock());

        $this->post("/invoice/update/$invoice->id", ['patient' => $this->patient->id, 'status' => 1, 'payment_status' => 'Paid', 'patient_category_new' => $invoice->patient_category_new, 'patient_category' => $invoice->patient_category])
            ->assertRedirect('/invoice/admin')
            ->assertSessionHas('success', 'Invoice was UPDATED successfully.');
        $this->assertSame(6.0, $this->stock());

        // Approved invoices can no longer be updated
        $this->get("/invoice/update/$invoice->id")->assertRedirect('/invoice/admin')->assertSessionHas('error', 'You are not authorized to perform this action!');

        $this->get('/invoice/admin')->assertSee($invoice->invoice_number)->assertSee('Approved');
        $this->get("/patient/view/{$this->patient->id}")->assertSee($invoice->invoice_number);
        $this->get("/invoice/view/$invoice->id")->assertOk()->assertSee('Napa');
        $this->get("/invoice/print/$invoice->id")->assertOk()->assertSee('Grand Total: ৳18', false);
    }

    public function test_quantity_adjustments(): void
    {
        $this->addMedicine();
        $line = Invoice::query()->firstOrFail();

        $this->post('/invoice/adjustment', ['id' => $line->id, 'adjustment' => '6', 'type' => 'quantity'])->assertOk();
        $this->assertEquals([6, 3, 27], [$line->fresh()->quantity, $line->fresh()->discount, $line->fresh()->amount]);

        // The line's own 6 count as free again: 10 on hand
        $this->post('/invoice/adjustment', ['id' => $line->id, 'adjustment' => '11', 'type' => 'quantity'], ['Accept' => 'application/json'])->assertStatus(422);

        // On an approved invoice the change moves stock straight away
        $parent = InvoiceParent::query()->create(['patient' => $this->patient->id]);
        $parent->forceFill(['invoice_date' => now(), 'invoice_number' => 'INV#X-2026-1', 'invoice_by' => $this->user->id, 'status' => 1])->save();
        $line->forceFill(['parent' => $parent->id])->save();
        StockSummary::query()->update(['quantity' => 4]);

        $this->post('/invoice/adjustmentEdit', ['id' => $line->id, 'adjustment' => '2', 'type' => 'quantity'])->assertOk();
        $this->assertSame(8.0, $this->stock());
        $this->get("/invoice/edit/$parent->id")->assertOk();
    }

    public function test_delete_and_rollback(): void
    {
        $parent = InvoiceParent::query()->create(['patient' => $this->patient->id]);
        $parent->forceFill(['invoice_date' => now(), 'invoice_number' => 'INV#X-2026-1', 'invoice_by' => $this->user->id, 'status' => 0])->save();
        Invoice::query()->insert(['parent' => $parent->id, 'servicetype' => 'Medicine', 'item' => $this->product->id, 'quantity' => 1, 'rate' => 5, 'amount' => 4.5, 'store' => $this->store->id, 'batch' => $this->batch->id]);

        $this->post("/invoice/remove/$parent->id")->assertRedirect('/invoice/admin')->assertSessionHas('success', 'Invoice was deleted successfully.');
        $this->assertSame(2, $parent->fresh()->status);

        $this->post("/invoice/rollback/$parent->id", ['patient' => $this->patient->id, 'status' => 0, 'payment_status' => 'Unpaid', 'patient_category' => $this->patient->category])
            ->assertRedirect('/invoice/admin');
        $this->assertSame(0, $parent->fresh()->status);

        // Only super users may roll back
        $this->actingAs($this->makeUser(['username' => 'clerk', 'email' => 'clerk@example.com']));
        $this->get("/invoice/rollback/$parent->id")->assertRedirect('/invoice/admin')->assertSessionHas('error');
    }

    public function test_the_screen_loads_without_embedding_every_patient_and_product(): void
    {
        Patient::factory()->count(3)->create();

        $page = $this->get('/invoice/create')->assertOk()->assertSee('invoice-workspace');

        // Only the chosen patient travels with the page; the rest is searched
        $this->assertStringNotContainsString('Rahim', $page->getContent());
        $this->assertLessThan(100 * 1024, strlen($page->getContent()));
    }

    public function test_lines_endpoint_returns_drafts_or_an_invoices_lines_with_totals(): void
    {
        $this->addMedicine()->assertOk();

        $this->getJson('/invoice/lines')->assertOk()
            ->assertJsonPath('lines.0.title', 'Napa')
            ->assertJsonPath('lines.0.quantity', '4')
            ->assertJsonPath('lines.0.batch', 'LOT-1')
            ->assertJsonPath('totals.count', 1)
            ->assertJsonPath('totals.gross', '৳20.00')
            ->assertJsonPath('totals.discount', '৳2.00')
            ->assertJsonPath('totals.amount', '৳18.00');

        // Another user's drafts are not mine
        $this->actingAs($this->makeUser(['username' => 'other', 'email' => 'other@example.com']));
        $this->getJson('/invoice/lines')->assertOk()->assertJsonPath('totals.count', 0);
    }

    public function test_patient_search_by_name_pat_id_or_mobile(): void
    {
        $this->patient->forceFill(['pat_id' => 'PAT#2026-SEP-77', 'mobile' => '01711000000', 'sex' => 'Male', 'age' => 30])->save();

        foreach (['rahi', '2026-SEP-77', '017110'] as $term) {
            $this->getJson('/invoice/patients?q='.urlencode($term))->assertOk()
                ->assertJsonPath('0.id', $this->patient->id)
                ->assertJsonPath('0.text', 'Rahim [PAT#2026-SEP-77]');
        }

        $this->getJson('/invoice/patients?q=nobody')->assertOk()->assertExactJson([]);
    }

    public function test_product_search_and_stock_show_what_is_free_to_sell(): void
    {
        // 3 of the 10 on hand are held by a draft line
        $this->addMedicine(['quantity' => '3'])->assertOk();

        $this->getJson('/invoice/items?q=nap')->assertOk()
            ->assertJsonPath('0.text', 'Napa [pcs]')
            ->assertJsonPath('0.free', 7);

        $this->getJson('/invoice/stock?item='.$this->product->id)->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.store_name', 'Pharmacy')
            ->assertJsonPath('0.batch_title', 'LOT-1')
            ->assertJsonPath('0.expired', false)
            ->assertJsonPath('0.free', 7)
            ->assertJsonPath('0.rate', 5);
    }

    public function test_prescriptions_of_the_chosen_patient(): void
    {
        $prescription = PatientPrescription::query()->create([]);
        $prescription->forceFill(['patient' => $this->patient->id, 'pre_number' => 'PRE#T-2026-1', 'created_on' => now()])->save();

        $this->getJson('/invoice/prescriptions?patient='.$this->patient->id)->assertOk()
            ->assertJsonPath('0.id', $prescription->id)
            ->assertJsonPath('0.text', fn ($text) => str_starts_with($text, 'PRE#T-2026-1'));
    }

    public function test_the_list_filters_by_patient_name(): void
    {
        $this->addMedicine()->assertOk();
        $this->post('/invoice/create', ['patient' => $this->patient->id, 'payment_status' => 'Paid'])->assertRedirect();
        $number = InvoiceParent::query()->value('invoice_number');

        $this->get('/invoice/admin?InvoiceParent[patient]=rahi')->assertOk()->assertSee($number);
        $this->get('/invoice/admin?InvoiceParent[patient]=nobody')->assertOk()->assertDontSee($number);
    }

    public function test_the_lookups_need_their_permissions(): void
    {
        $user = $this->makeUser(['group_id' => 4, 'username' => 'desk', 'email' => 'desk@example.com']);
        $this->group(4)->revokePermissionTo('invoice.items');

        $this->actingAs($user)->get('/invoice/items?q=nap')->assertRedirect('/site/noaccess');
    }
}
