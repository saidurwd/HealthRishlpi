<?php

namespace Tests\Feature;

use App\Models\Unit;
use Tests\TestCase;

class UnitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->makeUser());
    }

    public function test_admin_lists_filters_and_sorts(): void
    {
        Unit::create(['full_name' => 'Kilogram', 'formal_name' => 'kg', 'decimal_place' => 2]);
        Unit::create(['full_name' => 'Milligram', 'formal_name' => 'mg', 'decimal_place' => 3]);
        Unit::create(['full_name' => 'Piece', 'formal_name' => 'pcs', 'decimal_place' => 0]);

        $this->get('/unit/admin')->assertOk()->assertSee('Kilogram')->assertSee('Piece');

        $this->get('/unit/admin?Unit[full_name]=gram')
            ->assertSee('Kilogram')->assertSee('Milligram')->assertDontSee('Piece');

        $this->get('/unit/admin?Unit[decimal_place]=>2')
            ->assertSee('Milligram')->assertDontSee('Kilogram');

        $this->get('/unit/admin?Unit_sort=full_name.desc')
            ->assertSeeInOrder(['Piece', 'Milligram', 'Kilogram']);

        $this->get('/unit/admin?Unit[full_name]=zzz')->assertSee('No result found.');
    }

    public function test_admin_pages_by_legacy_page_size(): void
    {
        foreach (range(1, config('legacy.pageSize') + 1) as $i) {
            Unit::create(['full_name' => sprintf('Unit %03d', $i), 'formal_name' => "u$i", 'decimal_place' => 0]);
        }

        $this->get('/unit/admin?Unit_sort=full_name')->assertSee('Unit 025')->assertDontSee('Unit 026')->assertSee('Next &gt;', false);
        $this->get('/unit/admin?Unit_sort=full_name&Unit_page=2')->assertSee('Unit 026')->assertDontSee('Unit 025');
    }

    public function test_create_saves_and_flashes(): void
    {
        $this->get('/unit/create')->assertOk()->assertSee('New Unit');

        $this->post('/unit/create', ['full_name' => 'Litre', 'formal_name' => 'L', 'decimal_place' => '1'])
            ->assertRedirect('/unit/admin')
            ->assertSessionHas('success', 'Data was saved successfully');

        $this->assertDatabaseHas('unit', ['full_name' => 'Litre', 'formal_name' => 'L', 'decimal_place' => 1]);
    }

    public function test_create_validates_with_yii_messages(): void
    {
        Unit::create(['full_name' => 'Litre', 'formal_name' => 'L', 'decimal_place' => 1]);

        $this->from('/unit/create')
            ->post('/unit/create', ['full_name' => 'Litre', 'formal_name' => '', 'decimal_place' => 'x'])
            ->assertRedirect('/unit/create')
            ->assertSessionHasErrors([
                'full_name' => 'Full Name "Litre" has already been taken.',
                'formal_name' => 'Formal Name cannot be blank.',
                'decimal_place' => 'Decimal Place must be an integer.',
            ]);
    }

    public function test_update_keeps_own_values_unique(): void
    {
        $unit = Unit::create(['full_name' => 'Litre', 'formal_name' => 'L', 'decimal_place' => 1]);

        $this->get("/unit/update/{$unit->id}")->assertOk()->assertSee('Edit Unit')->assertSee('value="Litre"', false);

        $this->post("/unit/update/{$unit->id}", ['full_name' => 'Litre', 'formal_name' => 'Ltr', 'decimal_place' => '2'])
            ->assertRedirect('/unit/admin');

        $this->assertDatabaseHas('unit', ['id' => $unit->id, 'formal_name' => 'Ltr', 'decimal_place' => 2]);
    }

    public function test_missing_unit_is_404(): void
    {
        $this->get('/unit/update/999999')->assertNotFound()->assertSee('The requested page does not exist.');
    }

    public function test_delete_from_grid_and_by_form(): void
    {
        $first = Unit::create(['full_name' => 'A', 'formal_name' => 'a', 'decimal_place' => 0]);
        $second = Unit::create(['full_name' => 'B', 'formal_name' => 'b', 'decimal_place' => 0]);

        $this->post("/unit/delete/{$first->id}", ['ajax' => 'unit-grid'])->assertNoContent();
        $this->post("/unit/delete/{$second->id}")->assertRedirect('/unit/admin');

        $this->assertDatabaseMissing('unit', ['id' => $first->id]);
        $this->assertDatabaseMissing('unit', ['id' => $second->id]);
        $this->get("/unit/delete/{$first->id}")->assertStatus(405);
    }
}
