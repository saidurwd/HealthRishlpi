<?php

namespace Tests\Feature;

use App\Models\Acl;
use App\Models\AclAction;
use App\Models\AclController;
use App\Models\AuditTrail;
use App\Models\Menu;
use App\Models\User;
use App\Models\UserGroup;
use App\Models\UserStatus;
use App\Models\Visitor;
use App\Rules\YiiEmail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Access Control section of the menu: users, groups and their access
 * matrix, user statuses, menus, ACL controllers/actions, audit trail and
 * visitor statistics.
 */
class AccessControlTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        YiiEmail::$mxLookup = fn (string $domain) => $domain !== 'no-mx.test';
        $this->admin = $this->makeUser();
        $this->actingAs($this->admin);
    }

    protected function tearDown(): void
    {
        YiiEmail::$mxLookup = null;

        parent::tearDown();
    }

    public function test_menu_crud_stores_groups_comma_separated(): void
    {
        $parent = Menu::create(['parent' => 0, 'title' => 'Reports', 'url' => '#', 'status' => 1]);
        $groups = [UserGroup::create(['title' => 'Doctors'])->id, UserGroup::create(['title' => 'Nurses'])->id];

        $this->get('/menu/create')->assertOk()->assertSee('--please select--')->assertSee('Reports');

        $this->from('/menu/create')->post('/menu/create', ['parent' => $parent->id, 'title' => '', 'url' => ''])
            ->assertSessionHasErrors(['title' => 'Title cannot be blank.', 'url' => 'Url cannot be blank.']);

        $this->post('/menu/create', ['parent' => $parent->id, 'title' => 'Sales', 'controller' => 'report', 'url' => '/report/sales', 'icon' => '', 'ordering' => '2', 'status' => '1', 'group' => $groups])
            ->assertRedirect('/menu/admin')
            ->assertSessionHas('success', 'Menu was saved successfully');

        $menu = Menu::query()->where('title', 'Sales')->firstOrFail();
        $this->assertSame(implode(',', $groups), $menu->group);
        $this->assertSame($parent->id, $menu->parent);

        $this->get('/menu/admin?Menu[title]=Sal')->assertSee('/report/sales')->assertSee('Reports');

        // Clearing every group empties the column
        $this->post("/menu/update/$menu->id", ['parent' => $parent->id, 'title' => 'Sales', 'url' => '/report/sales', 'status' => '0', 'group' => ''])
            ->assertRedirect('/menu/admin');
        $this->assertSame('', $menu->fresh()->group);
        $this->assertSame(0, $menu->fresh()->status);
    }

    public function test_user_status_crud(): void
    {
        $this->post('/userStatus/create', ['title' => 'Suspended'])
            ->assertRedirect('/userStatus/admin')
            ->assertSessionHas('success', 'Status was saved successfully');

        $status = UserStatus::query()->where('title', 'Suspended')->firstOrFail();
        $this->get('/userStatus/admin')->assertSee('Suspended');
        $this->post("/userStatus/update/$status->id", ['title' => 'On hold'])->assertRedirect('/userStatus/admin');
        $this->assertSame('On hold', $status->fresh()->title);
        $this->post("/userStatus/delete/$status->id", ['ajax' => 'user-status-grid'])->assertNoContent();
        $this->assertNull($status->fresh());
    }

    public function test_group_access_page_creates_missing_acl_rows_and_toggles_them(): void
    {
        $group = UserGroup::create(['title' => 'Pharmacists']);
        $controller = AclController::create(['controller' => 'unit', 'title' => 'Units', 'status' => 1]);
        AclAction::create(['controller_id' => $controller->id, 'action' => 'admin', 'title' => 'Manage']);
        AclAction::create(['controller_id' => $controller->id, 'action' => 'create', 'title' => 'Create']);
        Acl::create(['group_id' => $group->id, 'controller' => 'unit', 'actions' => 'admin', 'action_title' => 'Old title', 'access' => 1]);

        $this->get("/userGroup/access/$group->id")->assertOk()->assertSee('Pharmacists')->assertSee('Units')->assertSee('Manage');

        $rows = Acl::query()->where('group_id', $group->id)->orderBy('actions')->get();
        $this->assertSame(['admin' => 1, 'create' => 0], $rows->pluck('access', 'actions')->all());
        $this->assertSame('Manage', $rows[0]->action_title);

        $this->post("/userGroup/turnon/{$rows[1]->id}")->assertOk()->assertSee('ok');
        $this->assertSame(1, $rows[1]->fresh()->access);
        $this->post("/userGroup/turnoff/{$rows[0]->id}")->assertOk();
        $this->assertSame(0, $rows[0]->fresh()->access);

        $this->post('/userGroup/accessall', ['id' => 2, 'group_id' => $group->id])->assertOk();
        $this->assertSame(2, Acl::query()->where('group_id', $group->id)->where('access', 1)->count());
        $this->post('/userGroup/accessallc', ['id' => 1, 'group_id' => $group->id, 'cntrl' => 'unit'])->assertOk();
        $this->assertSame(0, Acl::query()->where('group_id', $group->id)->where('access', 1)->count());

        // The switches change data, so they only answer POST
        $this->get("/userGroup/turnon/{$rows[0]->id}")->assertStatus(405);
    }

    public function test_acl_controller_saves_to_its_view_page_and_deletes_its_actions(): void
    {
        $response = $this->post('/aclController/create', ['controller' => 'patient', 'title' => 'Patients', 'status' => '1'])
            ->assertSessionHas('success', 'Saved successfully');
        $controller = AclController::query()->where('controller', 'patient')->firstOrFail();
        $response->assertRedirect("/aclController/view/$controller->id");

        $this->from('/aclController/create')->post('/aclController/create', ['controller' => 'patient', 'title' => 'Again'])
            ->assertSessionHasErrors(['controller' => 'Controller "patient" has already been taken.']);

        $this->post('/aclAction/create?cid='.$controller->id, ['controller_id' => $controller->id, 'title' => 'Manage', 'action' => 'admin'])
            ->assertSessionHas('success', 'ACL action has been created successfully');
        $action = AclAction::query()->where('controller_id', $controller->id)->firstOrFail();

        $this->get("/aclAction/actions?cid=$controller->id")->assertOk()->assertSee('Controller Actions (patient)')->assertSee('Manage');
        $this->get("/aclAction/view/$action->id?cid=$controller->id")->assertOk()->assertSee('Action Details (admin)');
        $this->get('/aclController/admin')->assertSee('Actions (1)');

        $this->post("/aclController/delete/$controller->id", ['ajax' => 'acl-controller-grid'])->assertNoContent();
        $this->assertNull($controller->fresh());
        $this->assertNull($action->fresh());
    }

    public function test_audit_trail_lists_sessions_with_duration(): void
    {
        AuditTrail::create(['user_id' => $this->admin->id, 'login_time' => '2026-09-01 10:00:00', 'logout_time' => '2026-09-01 11:05:03']);

        $this->get('/auditTrail/admin')->assertOk()
            ->assertSee('Test User')
            ->assertSee('Sep 1, 2026, 10:00:00 AM')
            ->assertSee('1 hour, 5 minutes and 3 seconds')
            ->assertDontSee('NEW');

        $this->assertSame('5 minutes', AuditTrail::interval('2026-09-01 10:00:00', '2026-09-01 10:05:00'));
    }

    public function test_visitor_grid_prunes_old_rows_and_truncates(): void
    {
        Visitor::query()->insert([
            ['user_id' => $this->admin->id, 'page_title' => 'Old page', 'server_time' => now()->subDays(8)],
            ['user_id' => $this->admin->id, 'page_title' => 'Recent page', 'server_time' => now()->subDay()],
        ]);

        $this->get('/visitor/admin')->assertOk()->assertSee('Recent page')->assertDontSee('Old page');
        $this->assertSame(1, Visitor::query()->count());

        $this->post('/visitor/truncate')->assertRedirect('/visitor/admin')->assertSessionHas('success', 'TRUNCATE all visitors statistics data!');
        $this->assertSame(0, Visitor::query()->count());
    }

    public function test_user_create_hashes_password_and_validates_like_yii(): void
    {
        $group = UserGroup::create(['title' => 'Front desk']);
        $this->makeUser(['username' => 'outsider', 'email' => 'outsider@clinic.test']);

        $this->from('/user/create')->post('/user/create', ['full_name' => 'Rana', 'username' => 'tester', 'email' => 'rana@no-mx.test', 'password' => ''])
            ->assertSessionHasErrors([
                'username' => 'Username "tester" has already been taken.',
                'email' => 'Email is not a valid email address.',
                'password' => 'Password cannot be blank.',
            ]);

        $this->post('/user/create', ['full_name' => 'Rana', 'username' => 'rana', 'email' => 'rana@clinic.test', 'password' => 'pass123', 'group_id' => $group->id, 'department' => '', 'status' => '1'])
            ->assertRedirect('/user/admin')
            ->assertSessionHas('success', 'User was saved successfully');

        $user = User::query()->where('username', 'rana')->firstOrFail();
        $this->assertSame(sha1('pass123'), $user->password);
        $this->assertNotEmpty($user->activation);
        $this->assertNull($user->department);
        $this->assertSame('', $user->photo);

        $this->get('/user/admin?User[group_id]='.$group->id)->assertSee('rana@clinic.test')->assertDontSee('outsider@clinic.test');
        $this->get("/user/view/$user->id")->assertOk()->assertSee('Front desk');
    }

    public function test_user_update_keeps_password_and_replaces_photo(): void
    {
        $user = $this->makeUser(['username' => 'nurse', 'email' => 'nurse@clinic.test']);
        $photo = UploadedFile::fake()->image('My Photo.jpg', 800, 400);

        try {
            $this->post("/user/update/$user->id", ['full_name' => 'Head Nurse', 'username' => 'nurse', 'email' => 'nurse@clinic.test', 'group_id' => '', 'department' => '', 'status' => '1', 'photo' => $photo])
                ->assertRedirect('/user/admin');

            $user->refresh();
            $this->assertSame('Head Nurse', $user->full_name);
            $this->assertSame(sha1('secret'), $user->password);
            $this->assertStringEndsWith('_my_photo.jpg', $user->photo);
            $this->assertSame([200, 100], array_slice(getimagesize(public_path('uploads/user/thumb/'.$user->photo)), 0, 2));
        } finally {
            foreach (['uploads/user/', 'uploads/user/thumb/'] as $dir) {
                if ($user->photo !== '' && is_file(public_path($dir.$user->photo))) {
                    unlink(public_path($dir.$user->photo));
                }
            }
        }
    }

    public function test_change_password(): void
    {
        $user = $this->makeUser(['username' => 'clerk', 'email' => 'clerk@clinic.test']);

        $this->from("/user/edit/$user->id")->post("/user/edit/$user->id", ['password' => ''])
            ->assertSessionHasErrors(['password' => 'Password cannot be blank.']);

        $this->post("/user/edit/$user->id", ['password' => 'n3w'])
            ->assertRedirect('/user/admin')
            ->assertSessionHas('success', 'Password was changed successfully');
        $this->assertSame(sha1('n3w'), DB::table('user')->where('id', $user->id)->value('password'));
    }

    public function test_menu_items_of_access_control_all_resolve(): void
    {
        foreach (['/menu/admin', '/userGroup/admin', '/userStatus/admin', '/user/admin', '/aclController/admin', '/auditTrail/admin', '/visitor/admin', '/user/create', "/user/view/{$this->admin->id}"] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
