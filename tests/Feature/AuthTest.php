<?php

namespace Tests\Feature;

use App\Models\AuditTrail;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuthTest extends TestCase
{
    public function test_guest_is_sent_to_login(): void
    {
        $this->get('/unit/admin')->assertRedirect('/site/login');
        $this->get('/dashboard/index')->assertRedirect('/site/login');
    }

    public function test_login_page_renders(): void
    {
        $this->get('/site/login')
            ->assertOk()
            ->assertSee('APPLICATION SIGN IN')
            ->assertSee(config('legacy.adminName'));
    }

    public function test_login_with_username_and_sha1_password(): void
    {
        $user = $this->makeUser();

        $this->post('/site/login', ['username' => 'tester', 'password' => 'secret', 'rememberMe' => '1'])
            ->assertRedirect('/dashboard/index')
            ->assertSessionHas('success')
            ->assertSessionHas('currency', '৳');

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull(AuditTrail::query()->where('user_id', $user->id)->value('login_time'));
        $this->assertNotEquals('0000-00-00 00:00:00', User::query()->find($user->id)->lastvisit);
    }

    public function test_remember_me_signs_in_a_new_session_without_a_remember_token_column(): void
    {
        $user = $this->makeUser();
        DB::enableQueryLog();

        $cookie = $this->rememberMeCookie();
        $this->post('/site/logout');

        $this->assertStringNotContainsString('remember_token', implode("\n", array_column(DB::getQueryLog(), 'query')));

        $this->withCookie(Auth::guard()->getRecallerName(), $cookie)
            ->get('/dashboard/index')
            ->assertOk();
        $this->assertAuthenticatedAs($user);
    }

    public function test_remember_me_cookie_stops_working_after_a_password_change(): void
    {
        $user = $this->makeUser();
        $cookie = $this->rememberMeCookie();
        $user->forceFill(['password' => User::hashPassword('changed')])->save();

        $this->withCookie(Auth::guard()->getRecallerName(), $cookie)
            ->get('/dashboard/index')
            ->assertRedirect('/site/login');
        $this->assertGuest();
    }

    /**
     * Sign in as "tester" with "Remember me" ticked and return the remember
     * cookie's value, leaving a fresh browser session (no session, no login).
     */
    private function rememberMeCookie(): string
    {
        $recaller = Auth::guard()->getRecallerName();

        $response = $this->post('/site/login', ['username' => 'tester', 'password' => 'secret', 'rememberMe' => '1'])
            ->assertCookie($recaller);

        $this->app['auth']->forgetGuards();
        $this->flushSession();

        return $response->getCookie($recaller)->getValue();
    }

    public function test_login_with_email(): void
    {
        $user = $this->makeUser();

        $this->post('/site/login', ['username' => 'tester@example.com', 'password' => 'secret'])
            ->assertRedirect('/dashboard/index');

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_and_blocked_statuses_share_one_message(): void
    {
        $this->makeUser();
        $this->makeUser(['username' => 'banned', 'email' => 'banned@example.com', 'status' => User::STATUS_BANNED]);

        foreach ([['tester', 'wrong'], ['banned', 'secret'], ['nobody', 'secret']] as [$username, $password]) {
            $this->from('/site/login')
                ->post('/site/login', ['username' => $username, 'password' => $password])
                ->assertRedirect('/site/login')
                ->assertSessionHasErrors(['password' => 'Incorrect username or password.']);
        }

        $this->assertGuest();
    }

    public function test_blank_fields_use_yii_messages(): void
    {
        $this->from('/site/login')
            ->post('/site/login', ['username' => '', 'password' => ''])
            ->assertSessionHasErrors([
                'username' => 'Username cannot be blank.',
                'password' => 'Password cannot be blank.',
            ]);
    }

    public function test_logout_stamps_audit_trail(): void
    {
        $user = $this->makeUser();
        AuditTrail::recordLogin($user->id);

        $this->actingAs($user)
            ->post('/site/logout')
            ->assertRedirect('/site/login')
            ->assertSessionHas('success');

        $this->assertGuest();
        $this->assertNotNull(AuditTrail::query()->where('user_id', $user->id)->value('logout_time'));
    }

    public function test_old_yii_urls_redirect(): void
    {
        $this->get('/?r=unit/update&id=5&x=1')->assertRedirect('/unit/update/5?x=1');
        $this->get('/?r=unit/admin')->assertRedirect('/unit/admin');
        $this->get('/')->assertRedirect('/dashboard/index');
    }
}
