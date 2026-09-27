<?php

namespace Tests\Feature;

use App\Models\AuditTrail;
use App\Models\Role;
use App\Models\User;
use App\Models\UserStatus;
use App\Rules\YiiEmail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Access Control section of the menu: users, groups (roles) and their
 * access matrix, user statuses and login history.
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

    public function test_group_crud(): void
    {
        $this->post('/userGroup/create', ['name' => 'Pharmacists', 'details' => 'Dispensary'])
            ->assertRedirect('/userGroup/admin')
            ->assertSessionHas('success', 'Group was saved successfully');

        $role = Role::query()->where('name', 'Pharmacists')->firstOrFail();
        $this->assertSame('web', $role->guard_name);
        $this->get('/userGroup/admin?Role[name]=Pharm')->assertOk()->assertSee('Dispensary');

        $this->from('/userGroup/create')->post('/userGroup/create', ['name' => 'Pharmacists'])
            ->assertSessionHasErrors(['name' => 'Group "Pharmacists" has already been taken.']);

        $this->post("/userGroup/update/$role->id", ['name' => 'Pharmacy', 'details' => ''])->assertRedirect('/userGroup/admin');
        $this->assertSame('Pharmacy', $role->fresh()->name);

        $this->post("/userGroup/delete/$role->id", ['ajax' => 'user-group-grid'])->assertNoContent();
        $this->assertNull($role->fresh());
    }

    public function test_group_access_matrix_toggles_permissions(): void
    {
        $role = $this->group(4, 'Front office');
        $role->syncPermissions(['unit.admin']);

        $this->get("/userGroup/access/$role->id")->assertOk()
            ->assertSee('Front office')
            ->assertSee('Unit')
            ->assertSee('data-acl-permission="unit.admin" checked', false)
            ->assertSee('data-acl-permission="unit.create" >', false);

        $this->post("/userGroup/turnon/$role->id", ['permission' => 'unit.create'])->assertOk()->assertSee('ok');
        $this->post("/userGroup/turnoff/$role->id", ['permission' => 'unit.admin'])->assertOk();
        $this->assertSame(['unit.create'], $role->fresh()->permissions->pluck('name')->all());

        $this->post("/userGroup/turnon/$role->id", ['permission' => 'no.such'])->assertSessionHasErrors('permission');

        $this->post('/userGroup/accessall', ['id' => 2, 'group_id' => $role->id])->assertOk();
        $this->assertSame(Permission::query()->count(), $role->fresh()->permissions->count());
        $this->post('/userGroup/accessallc', ['id' => 1, 'group_id' => $role->id, 'section' => 'Unit'])->assertOk();
        $this->assertFalse($role->fresh()->hasPermissionTo('unit.update'));
        $this->assertTrue($role->fresh()->hasPermissionTo('patient.admin'));
        $this->post('/userGroup/accessall', ['id' => 1, 'group_id' => $role->id])->assertOk();
        $this->assertSame(0, $role->fresh()->permissions->count());

        // The switches change data, so they only answer POST
        $this->get("/userGroup/turnon/$role->id")->assertStatus(405);
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

    public function test_user_create_hashes_password_and_validates_like_yii(): void
    {
        $group = $this->group(5, 'Front desk');
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
        $this->assertTrue($user->hasRole('Front desk'));
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
        foreach (['/userGroup/admin', '/userStatus/admin', '/user/admin', '/auditTrail/admin', '/user/create', "/user/view/{$this->admin->id}"] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
