<?php

namespace Tests\Feature;

use App\Models\Backup;
use App\Models\Batch;
use App\Models\Disease;
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
use App\Support\BackupEngine;
use Tests\TestCase;

/**
 * Reports menu, dashboard and database backup.
 */
class ReportsAndToolsTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->makeUser(['group_id' => User::SUPER_GROUP]);
        $this->actingAs($this->user)->withSession(['currency' => '৳']);
    }

    /**
     * An approved invoice today for a patient of the given sex, age 30, with a 25 Taka medicine line.
     */
    private function sale(string $sex): Patient
    {
        $patient = Patient::create([
            'category_new' => PatientCategoryNew::query()->firstOrCreate(['title' => 'Adult'], ['alias' => 'Adult'])->id,
            'category' => PatientCategory::query()->firstOrCreate(['title' => 'General'], ['alias' => 'General'])->id,
            'name' => $sex.' patient', 'admission' => 'No', 'sex' => $sex, 'age' => 30,
        ]);
        $patient->forceFill(['created_on' => now()])->save();

        $invoice = InvoiceParent::create(['patient' => $patient->id, 'patient_category' => $patient->category]);
        $invoice->forceFill(['invoice_date' => now(), 'invoice_number' => 'INV#T-'.$patient->id, 'invoice_by' => $this->user->id, 'status' => 1, 'total_amount' => 25])->save();
        $unit = Unit::query()->firstOrCreate(['full_name' => 'Piece'], ['formal_name' => 'pcs', 'decimal_place' => 0]);
        $product = Product::query()->firstOrCreate(['title' => 'Napa'], ['category' => ProductCategory::create(['title' => 'Tablets'])->id, 'unit' => $unit->id]);
        (new Invoice)->forceFill(['parent' => $invoice->id, 'servicetype' => 'Medicine', 'item' => $product->id, 'quantity' => 5, 'rate' => 5, 'amount' => 25, 'created_on' => now()])->save();

        PatientPrescription::query()->create(['diagnosis' => Disease::query()->firstOrCreate(['title' => 'Fever'])->id])
            ->forceFill(['patient' => $patient->id, 'created_on' => now()])->save();

        return $patient;
    }

    public function test_every_report_and_its_print_page_render(): void
    {
        $this->sale('Male');

        foreach (['stocksummary', 'stockreceive', 'sales', 'expiration', 'register', 'disease', 'category', 'medicine', 'service', 'mincome', 'periodstock', 'patinvoice', 'registerphysio', 'prescription', 'contactregister'] as $report) {
            $this->get("/report/$report")->assertOk()->assertSee('Search');
            $this->get("/report/{$report}print?start_date=".date('Y-m-01').'&end_date='.date('Y-m-t'))->assertOk()->assertSee(config('legacy.adminName'));
        }
    }

    public function test_all_services_printout_is_linked_from_the_service_grid(): void
    {
        Service::create(['title' => 'Physio', 'rate' => 150, 'discount' => 'Yes', 'rate_status' => 'Auto', 'service_type' => 'Service']);

        $this->get('/service/admin')->assertSee(route('report.allserviceprint'));
        $this->get('/report/allserviceprint')->assertOk()->assertSee('All Services')->assertSee('Physio');
    }

    public function test_sales_report_totals(): void
    {
        $this->sale('Male');
        $this->sale('Female');

        $this->post('/report/sales', ['start_date' => date('Y-m-d'), 'end_date' => date('Y-m-d')])
            ->assertOk()->assertSee('Napa')->assertSee('৳50', false)->assertSee('Displaying 2 results.');

        $this->get('/report/salesprint?start_date='.date('Y-m-d').'&end_date='.date('Y-m-d').'&status=Paid')->assertOk()->assertSee('Status: Paid');
    }

    public function test_patient_category_report_puts_male_and_female_in_the_right_columns(): void
    {
        $this->sale('Male');
        $this->sale('Male');
        $this->sale('Female');

        // Row "Above 25 Years": 2 male, 1 female (Yii swapped these columns)
        $this->post('/report/category', ['start_date' => date('Y-m-01'), 'end_date' => date('Y-m-t')])
            ->assertOk()
            ->assertSeeInOrder(['Above 25 Years', '>2<', '>1<', '>3<'], false);
    }

    public function test_stock_reports_read_the_stock_summary(): void
    {
        $unit = Unit::create(['full_name' => 'Piece', 'formal_name' => 'pcs', 'decimal_place' => 0]);
        $product = Product::create(['category' => ProductCategory::create(['title' => 'Syrups'])->id, 'title' => 'Tusca', 'unit' => $unit->id]);
        $store = Store::create(['title' => 'Pharmacy', 'alias' => 'Pharmacy']);
        $batch = Batch::create(['title' => 'OLD', 'expiry' => date('Y-m-d', strtotime('-3 days'))]);
        StockSummary::create(['store' => $store->id, 'item' => $product->id, 'batch' => $batch->id, 'quantity' => 7, 'rate' => 2, 'amount' => 14]);

        $this->get('/report/stocksummary')->assertOk()->assertSee('Tusca')->assertSee('৳14.00', false);
        $this->get('/report/expiration')->assertOk()->assertSee('Tusca')->assertSee('OLD');
        $this->post('/report/periodstock', ['start_date' => date('Y-m-01'), 'end_date' => date('Y-m-d')])->assertOk()->assertSee('Tusca');
    }

    public function test_dashboard_filter_and_export(): void
    {
        $this->sale('Male');

        $this->get('/dashboard/index')->assertOk()->assertSee('Health Management Dashboard')->assertSee('Male patient');

        $this->postJson('/dashboard/ajaxFilter', ['start_date' => date('Y-m-01'), 'end_date' => date('Y-m-t'), 'category' => 'all', 'department' => 'all'])
            ->assertOk()
            ->assertJsonPath('totalPatients', 1)
            ->assertJsonStructure(['monthlyRevenue', 'trendWeek' => ['labels', 'patients', 'revenue'], 'demographics', 'heatmap', 'recentActivity']);

        $this->get('/dashboard/export')->assertOk()->assertHeader('Content-Type', 'text/csv; charset=utf-8')->assertSee('Total Patients,1');
    }

    public function test_backup_export_download_and_delete(): void
    {
        $this->post('/backup/exportdatabase', ['type' => 'sql'])->assertRedirect('/backup/admin')->assertSessionHas('success');

        $backup = Backup::query()->latest('id')->firstOrFail();
        try {
            $this->assertFileExists($backup->path());
            $this->assertStringContainsString('CREATE TABLE `'.config('database.connections.mariadb.prefix').'user`', (string) file_get_contents($backup->path()));
            $this->get('/backup/admin')->assertOk()->assertSee($backup->attachment);
            $this->get("/backup/download/$backup->id")->assertOk()->assertDownload($backup->attachment);

            $this->post("/backup/delete/$backup->id", ['ajax' => 'backup-grid'])->assertNoContent();
            $this->assertFileDoesNotExist($backup->path());
        } finally {
            @unlink($backup->path());
        }
    }

    public function test_restore_splits_statements_outside_quotes(): void
    {
        $this->assertSame(
            ["INSERT INTO t VALUES('a;b')", "INSERT INTO t VALUES('it\\'s; fine')", 'SELECT 1'],
            BackupEngine::statements("INSERT INTO t VALUES('a;b');\nINSERT INTO t VALUES('it\\'s; fine');\nSELECT 1")
        );
    }
}
