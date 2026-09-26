<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\PatientCategory;
use App\Models\ProductCategory;
use App\Models\Service;
use App\Models\Store;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MasterDataBehaviourTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->makeUser());
    }

    public function test_tree_path_and_alias_follow_the_parent(): void
    {
        $this->post('/department/create', ['parent' => '', 'title' => 'Medical', 'code' => '', 'description' => '']);
        $root = Department::query()->where('title', 'Medical')->firstOrFail();

        $this->assertNull($root->parent, "Yii stored '' in a nullable int column as NULL");
        $this->assertSame('0.'.$root->id, $root->path);
        $this->assertSame('Medical', $root->alias);

        $this->post('/department/create', ['parent' => $root->id, 'title' => 'Physio', 'code' => '', 'description' => '']);
        $child = Department::query()->where('title', 'Physio')->firstOrFail();

        $this->assertSame("0.{$root->id}.{$child->id}", $child->path);
        $this->assertSame('Medical/Physio', $child->alias);
        $this->assertSame('Medical <i class="fa fa-angle-double-right text-info"></i> Physio', $child->fullPath());
    }

    public function test_tree_dropdown_nests_children_under_parents(): void
    {
        $root = ProductCategory::create(['title' => 'Medicine']);
        $root->updatePath();
        $child = ProductCategory::create(['title' => 'Tablet', 'parent' => $root->id]);
        $child->updatePath();

        $options = ProductCategory::treeOptions();
        $keys = array_keys($options);

        $this->assertSame($root->id, $keys[array_search($child->id, $keys, true) - 1]);
        $this->assertSame(str_repeat("\u{00A0}", 6).'Tablet', $options[$child->id]);
    }

    public function test_service_grid_shows_full_path(): void
    {
        $this->post('/service/create', ['parent' => '', 'title' => 'Therapy', 'rate' => '0', 'rate_status' => 'Auto', 'discount' => 'No', 'status' => 'Active']);
        $root = Service::query()->where('title', 'Therapy')->firstOrFail();
        $this->post('/service/create', ['parent' => $root->id, 'title' => 'Massage', 'rate' => '100', 'rate_status' => 'Manual', 'discount' => 'Yes', 'status' => 'Active']);

        $this->get('/service/admin?Service[title]=Massage')
            ->assertSee('Therapy <i class="fa fa-angle-double-right text-info"></i> Massage', false);
    }

    public function test_new_records_start_with_column_defaults(): void
    {
        $this->get('/service/create')->assertSee('<option value="Auto" selected>', false);
        $this->get('/district/create')->assertSee('<option value="Active" selected>', false);
        $this->get('/unit/create')->assertSee('value="2"', false);
    }

    public function test_empty_numeric_input_is_typecast_like_yii(): void
    {
        // NOT NULL tinyint: '' => 0
        $this->post('/unit/create', ['full_name' => 'Box', 'formal_name' => 'box', 'decimal_place' => '']);
        $this->assertSame(0, DB::table('unit')->where('full_name', 'Box')->value('decimal_place'));

        // nullable int: '' => NULL
        $this->post('/store/create', ['parent' => '', 'title' => 'Main', 'location' => '', 'incharge' => '', 'description' => '']);
        $store = DB::table('store')->where('title', 'Main')->first();
        $this->assertNull($store->incharge);
        $this->assertNull($store->parent);
        // nullable varchar keeps ''
        $this->assertSame('', $store->location);
    }

    public function test_patient_category_delete_is_a_no_op(): void
    {
        $category = PatientCategory::create(['title' => 'Adult']);

        $this->post("/patientCategory/delete/{$category->id}")->assertRedirect('/patientCategory/admin');
        $this->assertDatabaseHas('patient_category', ['id' => $category->id]);
    }

    public function test_store_form_renders_with_tree_parent_dropdown(): void
    {
        $store = Store::create(['title' => 'Central']);
        $store->updatePath();

        $this->get('/store/create')->assertOk()->assertSee('Central')->assertSee('Select a User');
    }

    public function test_unit_in_use_cannot_be_deleted(): void
    {
        $unit = Unit::create(['full_name' => 'A', 'formal_name' => 'a', 'decimal_place' => 0]);
        $category = ProductCategory::create(['title' => 'X']);
        DB::table('product')->insert(['title' => 'P', 'category' => $category->id, 'unit' => $unit->id]);

        $this->post("/unit/delete/{$unit->id}", ['ajax' => 'unit-grid'])->assertStatus(409);
        $this->post("/productCategory/delete/{$category->id}", ['ajax' => 'product-category-grid'])->assertStatus(409);
    }
}
