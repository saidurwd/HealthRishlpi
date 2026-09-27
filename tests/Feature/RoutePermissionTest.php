<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Routes in the `route.permission` group need the permission named like the
 * route; the sidebar only shows what the user may open.
 */
class RoutePermissionTest extends TestCase
{
    public function test_every_protected_route_has_its_permission(): void
    {
        $names = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => in_array('route.permission', $route->gatherMiddleware(), true))
            ->map(fn ($route) => $route->getName());

        $this->assertNotContains(null, $names->all(), 'Every protected route needs a name');
        $this->assertSame([], $names->diff(Permission::query()->pluck('name'))->values()->all(), 'Add these permissions in a migration');
    }

    public function test_group_with_the_permission_gets_in(): void
    {
        $this->actingAs($this->makeUser())->get('/unit/admin')->assertOk();
    }

    public function test_missing_permission_redirects_to_noaccess(): void
    {
        $user = $this->makeUser();
        $this->group(2)->revokePermissionTo('unit.admin');

        $this->actingAs($user)
            ->get('/unit/admin')
            ->assertRedirect('/site/noaccess')
            ->assertSessionHas('error', 'You are not authorized to perform this action!');

        $this->actingAs($user)->get('/site/noaccess')->assertOk()->assertSee('You are not authorized to perform this action.');
    }

    public function test_user_without_a_group_gets_nowhere(): void
    {
        $this->actingAs($this->makeUser(['group_id' => null]))->get('/unit/admin')->assertRedirect('/site/noaccess');
    }

    public function test_super_users_pass_every_check(): void
    {
        $user = $this->makeUser(['group_id' => User::SUPER_GROUP]);
        $this->group(User::SUPER_GROUP)->syncPermissions([]);

        $this->actingAs($user)->get('/unit/admin')->assertOk();
    }

    public function test_dashboard_needs_no_permission(): void
    {
        $user = $this->makeUser();
        $this->group(2)->syncPermissions([]);

        $this->actingAs($user)->get('/dashboard/index')->assertOk();
    }

    public function test_changing_the_group_moves_the_user_to_its_role(): void
    {
        $user = $this->makeUser();
        $this->group(3, 'Front office')->syncPermissions(['patient.admin']);

        $user->forceFill(['group_id' => 3])->save();

        $this->assertSame(['Front office'], $user->fresh()->getRoleNames()->all());
        $this->actingAs($user->fresh())->get('/unit/admin')->assertRedirect('/site/noaccess');
    }

    public function test_sidebar_shows_permitted_items_and_marks_the_current_page(): void
    {
        $user = $this->makeUser();
        $this->group(2)->syncPermissions(['unit.admin', 'patient.admin']);

        $this->actingAs($user)->get('/unit/admin')->assertOk()
            ->assertSee('nav-item menu-open', false)
            ->assertSee('href="'.route('unit.admin').'" class="nav-link active"', false)
            ->assertSee('Patients')
            ->assertSee('<li class="nav-header">CLINIC</li>', false)
            ->assertDontSee('Products')
            // A parent without visible children is hidden, and so is a section left empty
            ->assertDontSee('Clinical Setup')
            ->assertDontSee('<li class="nav-header">MONITORING</li>', false)
            // About is open to everyone
            ->assertSee(route('site.about'), false);
    }
}
