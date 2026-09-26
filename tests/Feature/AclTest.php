<?php

namespace Tests\Feature;

use App\Models\Acl;
use Tests\TestCase;

class AclTest extends TestCase
{
    public function test_access_is_allowed_when_no_acl_row_exists(): void
    {
        $this->actingAs($this->makeUser())->get('/unit/admin')->assertOk();
    }

    public function test_access_zero_redirects_to_noaccess(): void
    {
        $user = $this->makeUser();
        Acl::create(['group_id' => $user->group_id, 'controller' => 'unit', 'actions' => 'admin', 'action_title' => 'Manage', 'access' => 0]);

        $this->actingAs($user)
            ->get('/unit/admin')
            ->assertRedirect('/site/noaccess')
            ->assertSessionHas('error', 'You are not authorized to perform this action!');

        $this->actingAs($user)->get('/site/noaccess')->assertOk()->assertSee('You are not authorized to perform this action.');
    }

    public function test_controller_names_match_case_insensitively(): void
    {
        // Live os_acl rows spell controllers "Unit", "PurchaseReceive", ... while
        // Yii's controller ids are "unit", "purchaseReceive". The column collation is _ci.
        $user = $this->makeUser();
        Acl::create(['group_id' => $user->group_id, 'controller' => 'Unit', 'actions' => 'admin', 'action_title' => 'Manage', 'access' => 0]);

        $this->actingAs($user)->get('/unit/admin')->assertRedirect('/site/noaccess');
    }

    public function test_acl_rows_for_other_groups_do_not_apply(): void
    {
        $user = $this->makeUser();
        Acl::create(['group_id' => 99, 'controller' => 'unit', 'actions' => 'admin', 'action_title' => 'Manage', 'access' => 0]);

        $this->actingAs($user)->get('/unit/admin')->assertOk();
    }

    public function test_dashboard_skips_acl(): void
    {
        $user = $this->makeUser();
        Acl::create(['group_id' => $user->group_id, 'controller' => 'dashboard', 'actions' => 'index', 'action_title' => 'Index', 'access' => 0]);

        $this->actingAs($user)->get('/dashboard/index')->assertOk();
    }
}
