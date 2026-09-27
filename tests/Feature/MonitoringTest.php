<?php

namespace Tests\Feature;

use App\Http\Controllers\SiteController;
use App\Http\Middleware\RecordActivity;
use App\Models\Activity;
use App\Models\AuditTrail;
use App\Models\User;
use App\Support\SecurityLog;
use App\Support\SystemHealth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Monitoring: security events, activity log, login history, system health
 * and the About page.
 */
class MonitoringTest extends TestCase
{
    private function events(string $event): int
    {
        return Activity::query()->where('log_name', SecurityLog::LOG)->where('event', $event)->count();
    }

    public function test_sign_ins_failures_and_sign_outs_are_security_events(): void
    {
        $user = $this->makeUser();

        $this->post('/site/login', ['username' => 'tester', 'password' => 'wrong'])->assertSessionHasErrors(['password' => 'Incorrect username or password.']);
        $failure = Activity::query()->where('event', 'login.failed')->sole();
        $this->assertSame('tester', $failure->properties['username']);
        $this->assertSame('wrong password', $failure->properties['reason']);
        $this->assertSame('127.0.0.1', $failure->properties['ip']);

        $this->post('/site/login', ['username' => 'tester', 'password' => 'secret'])->assertRedirect('/dashboard/index');
        $this->assertSame(1, $this->events('login'));
        $this->assertSame($user->id, Activity::query()->where('event', 'login')->value('causer_id'));

        $this->post('/site/logout');
        $this->assertSame(1, $this->events('logout'));
    }

    public function test_repeated_failures_lock_the_username_out_for_a_minute(): void
    {
        $this->makeUser();
        RateLimiter::clear('login|tester|127.0.0.1');

        foreach (range(1, SiteController::LOGIN_ATTEMPTS) as $attempt) {
            $this->post('/site/login', ['username' => 'tester', 'password' => 'wrong']);
        }
        $this->assertSame(1, $this->events('login.lockout'));

        // Even the right password waits now
        $this->post('/site/login', ['username' => 'tester', 'password' => 'secret'])
            ->assertSessionHasErrors('password');
        $this->assertStringStartsWith('Too many sign-in attempts.', session('errors')->first('password'));
        $this->assertGuest();

        RateLimiter::clear('login|tester|127.0.0.1');
    }

    public function test_refused_access_and_account_changes_are_recorded(): void
    {
        $user = $this->makeUser();
        $this->group(2)->revokePermissionTo('unit.admin');
        $this->actingAs($user)->get('/unit/admin')->assertRedirect('/site/noaccess');
        $this->assertSame('unit.admin', Activity::query()->where('event', 'access.denied')->sole()->properties['route']);

        $user->forceFill(['status' => User::STATUS_BANNED])->save();
        $this->assertSame(1, $this->events('user.status_changed'));
    }

    public function test_pages_and_actions_go_to_the_activity_log_but_background_requests_do_not(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user);

        $this->get('/unit/admin')->assertOk();
        $this->get('/unit/admin', ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();
        $this->post('/unit/create', ['full_name' => 'Litre', 'formal_name' => 'L', 'decimal_place' => '1']);

        $log = Activity::query()->where('log_name', RecordActivity::LOG)->orderBy('id')->get();
        $this->assertSame(['get', 'post'], $log->pluck('event')->all());
        $this->assertSame('/unit/admin', $log[0]->properties['path']);
        $this->assertSame($user->id, $log[0]->causer_id);
        $this->assertStringContainsString('Unit', $log[0]->description);

        $this->get('/activityLog/admin')->assertOk()->assertSee('/unit/create');
    }

    public function test_monitoring_pages_render(): void
    {
        $this->actingAs($this->makeUser(['group_id' => User::SUPER_GROUP]));
        AuditTrail::recordLogin(auth()->id());
        SecurityLog::record('login.failed', ['username' => 'mallory', 'reason' => 'unknown user']);

        $this->get('/securityEvent/admin')->assertOk()->assertSee('Sign-in failed')->assertSee('mallory');
        $this->get('/auditTrail/admin')->assertOk()->assertSee('Login History')->assertSee('No sign-out')->assertSee('mallory');
        $this->get('/auditLog/admin')->assertOk();
        $this->get('/activityLog/admin')->assertOk();
    }

    public function test_system_health_reports_problems_and_the_scheduler(): void
    {
        $this->actingAs($this->makeUser(['group_id' => User::SUPER_GROUP]));
        Cache::forget(SystemHealth::HEARTBEAT_KEY);

        $this->get('/systemHealth/admin')->assertOk()
            ->assertSee('Database')
            ->assertSee('Pending migrations')
            ->assertSee('never ran')
            ->assertSee('Something is wrong and needs fixing.');

        // Backup status comes from the package's own monitor configuration
        $this->assertStringStartsWith('Disk backup_local', SystemHealth::checks()['Backups'][0]['label']);

        Cache::forever(SystemHealth::HEARTBEAT_KEY, now());
        $this->assertStringContainsString('last ran', collect(SystemHealth::checks()['Scheduled jobs'])->firstWhere('label', 'Scheduler')['value']);
    }

    public function test_about_is_open_to_every_signed_in_user(): void
    {
        $user = $this->makeUser();
        $this->group(2)->syncPermissions([]);

        $this->actingAs($user)->get('/site/about')->assertOk()
            ->assertSee('Version '.config('app.version'))
            ->assertSee("What's new", false)
            ->assertSee('Monitoring menu');
    }
}
