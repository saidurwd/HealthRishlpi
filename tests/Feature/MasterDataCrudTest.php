<?php

namespace Tests\Feature;

use App\Models\ProductCategory;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Walks every Gii-style master data module through the same flow the Yii
 * controllers supported: manage grid, create (with validation), update, delete.
 */
class MasterDataCrudTest extends TestCase
{
    /**
     * route id => [table, valid input, a required field with its Yii message]
     *
     * @return array<string, array{0: string, 1: string, 2: array<string, mixed>, 3: string, 4: string}>
     */
    public static function modules(): array
    {
        return [
            'country' => ['country', 'country', ['title' => 'Testland', 'country_2_code' => 'TL', 'country_3_code' => 'TLD', 'status' => 'Active'], 'title', 'Country cannot be blank.'],
            'state' => ['state', 'state', ['country' => 18, 'title' => 'Test State', 'state_2_code' => 'TS', 'status' => 'Active'], 'country', 'Country cannot be blank.'],
            'city' => ['city', 'city', ['country' => 18, 'state' => 1, 'title' => 'Test City', 'status' => 'Inactive'], 'state', 'State cannot be blank.'],
            'district' => ['district', 'district', ['country' => 18, 'state' => 1, 'city' => 1, 'title' => 'Test District', 'status' => 'Active'], 'city', 'City cannot be blank.'],
            'thana' => ['thana', 'thana', ['country' => 18, 'state' => 1, 'city' => 1, 'district' => 1, 'title' => 'Test Thana', 'status' => 'Active'], 'district', 'District cannot be blank.'],
            'disease' => ['disease', 'disease', ['title' => 'Test Fever', 'status' => 'Active'], 'title', 'Disease cannot be blank.'],
            'instruction' => ['instruction', 'instruction', ['title' => 'After meal', 'status' => 'Active'], 'title', 'Instruction cannot be blank.'],
            'manufacturer' => ['manufacturer', 'manufacturer', ['title' => 'Test Pharma', 'email' => 'a@b.c', 'phone' => '1', 'mobile' => '2', 'address' => 'Dhaka'], 'title', 'Manufacturer cannot be blank.'],
            'vendor' => ['vendor', 'vendor', ['title' => 'Test Supplier', 'email' => 'a@b.c', 'phone' => '1', 'mobile' => '2', 'address' => 'Khulna'], 'title', 'Vendor cannot be blank.'],
            'batch' => ['batch', 'batch', ['title' => 'LOT-1', 'manufacturing' => '2026-01-01', 'expiry' => '2028-01-01'], 'expiry', 'Expiry cannot be blank.'],
            'patientGrade' => ['patientGrade', 'patient_grade', ['title' => 'Grade Z', 'remarks' => 'r', 'status' => 'Active'], 'title', 'Patient Grade cannot be blank.'],
            'patientType' => ['patientType', 'patient_type', ['title' => 'Type Z', 'remarks' => 'r', 'status' => 'Active'], 'title', 'Patient Type cannot be blank.'],
            'patientCategory' => ['patientCategory', 'patient_category', ['parent' => '', 'title' => 'Sub Z', 'status' => 'Active'], 'title', 'Sub Category cannot be blank.'],
            'patientCategoryNew' => ['patientCategoryNew', 'patient_category_new', ['parent' => '', 'title' => 'Cat Z', 'status' => 'Active'], 'title', 'Category cannot be blank.'],
            'department' => ['department', 'department', ['parent' => '', 'title' => 'Dept Z', 'code' => 'DZ', 'description' => 'd'], 'title', 'Department cannot be blank.'],
            'productCategory' => ['productCategory', 'product_category', ['parent' => '', 'title' => 'Cat Q', 'description' => 'd'], 'title', 'Category cannot be blank.'],
            'store' => ['store', 'store', ['parent' => '', 'title' => 'Store Z', 'location' => 'L1', 'incharge' => '', 'description' => 'd'], 'title', 'Store cannot be blank.'],
            'service' => ['service', 'service', ['parent' => '', 'title' => 'Physio Z', 'rate' => '150', 'rate_status' => 'Auto', 'discount' => 'No', 'service_type' => 'Service', 'service_grade' => '', 'ordering' => '1', 'status' => 'Active'], 'rate', 'Rate cannot be blank.'],
            'unit' => ['unit', 'unit', ['full_name' => 'Test Unit Z', 'formal_name' => 'tuz', 'decimal_place' => '2'], 'full_name', 'Full Name cannot be blank.'],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->makeUser());
    }

    /**
     * @param  array<string, mixed>  $input
     */
    #[DataProvider('modules')]
    public function test_module_crud(string $route, string $table, array $input, string $requiredField, string $requiredMessage): void
    {
        $gridId = Str::kebab($route).'-grid';

        $this->get("/$route/admin")->assertOk()->assertSee('href="'.route("$route.create").'"', false)->assertSee("id=\"$gridId\"", false)
            ->assertSee('No result found.');
        $this->get("/$route/create")->assertOk()->assertSee('Fields with');

        $this->from("/$route/create")
            ->post("/$route/create", array_merge($input, [$requiredField => '']))
            ->assertRedirect("/$route/create")
            ->assertSessionHasErrors([$requiredField => $requiredMessage]);

        $this->post("/$route/create", $input)
            ->assertRedirect("/$route/admin")
            ->assertSessionHas('success', 'Data was saved successfully');

        $label = $input['title'] ?? $input['full_name'];
        $id = DB::table($table)->where(isset($input['title']) ? 'title' : 'full_name', $label)->value('id');
        $this->assertNotNull($id, "$route row was not saved");
        $this->get("/$route/admin")->assertSee($label);

        $this->get("/$route/update/$id")->assertOk()->assertSee('value="'.e($label).'"', false);
        $field = isset($input['title']) ? 'title' : 'full_name';
        $this->post("/$route/update/$id", array_merge($input, [$field => $label.' 2']))->assertRedirect("/$route/admin");
        $this->assertSame($label.' 2', DB::table($table)->where('id', $id)->value($field));

        $this->post("/$route/delete/$id", ['ajax' => $gridId])->assertNoContent();
        $deleted = DB::table($table)->where('id', $id)->doesntExist();
        $this->assertSame(! in_array($route, ['patientCategory', 'patientCategoryNew'], true), $deleted);
    }

    public function test_product_crud(): void
    {
        $category = ProductCategory::create(['title' => 'Tablets']);
        $unit = Unit::create(['full_name' => 'Strip', 'formal_name' => 'str', 'decimal_place' => 0]);

        $this->get('/product/create')->assertOk()->assertSee('Tablets')->assertSee('Strip');

        $this->post('/product/create', ['category' => $category->id, 'title' => 'Napa 500', 'product_code' => 'N5', 'unit' => $unit->id, 'threshold_value' => '10', 'minimum_storage_limit' => '5', 'description' => ''])
            ->assertRedirect('/product/admin');

        $product = DB::table('product')->where('title', 'Napa 500')->first();
        $this->assertSame(auth()->id(), $product->created_by);
        $this->assertNotNull($product->created_on);

        // Category and unit filters match the related names, like Yii's joined search
        $this->get('/product/admin?Product[category]=Tabl&Product[unit]=str')->assertSee('Napa 500');
        $this->get('/product/admin?Product[category]=Syrup')->assertDontSee('Napa 500');
        $this->get('/product/admin?Product_sort=category.desc')->assertOk()->assertSee('Napa 500');
    }
}
