<?php

namespace App\Http\Controllers;

use App\Models\AuditTrail;
use App\Models\User;
use App\Support\About;
use App\Support\SecurityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SiteController extends Controller
{
    /** Failed sign-ins allowed per username and IP address in a minute */
    public const LOGIN_ATTEMPTS = 5;

    public function login(Request $request): View|RedirectResponse
    {
        if ($request->isMethod('get')) {
            return view('site.login');
        }

        $input = $request->validate(
            ['username' => ['required'], 'password' => ['required'], 'rememberMe' => ['nullable', 'boolean']],
            [],
            ['username' => 'Username', 'password' => 'Password', 'rememberMe' => 'Remember me next time'],
        );

        // Slow down password guessing: 5 failures a minute per username and IP
        $throttleKey = 'login|'.Str::lower($input['username']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, self::LOGIN_ATTEMPTS)) {
            return back()
                ->withErrors(['password' => 'Too many sign-in attempts. Please try again in '.RateLimiter::availableIn($throttleKey).' seconds.'])
                ->onlyInput('username', 'rememberMe');
        }

        [$user, $failure] = $this->authenticate($input['username'], $input['password']);

        if ($user === null) {
            $attempts = RateLimiter::hit($throttleKey, 60);
            SecurityLog::record('login.failed', ['username' => Str::limit($input['username'], 100, ''), 'reason' => $failure]);
            if ($attempts >= self::LOGIN_ATTEMPTS) {
                SecurityLog::record('login.lockout', ['username' => Str::limit($input['username'], 100, '')]);
            }

            return back()
                ->withErrors(['password' => 'Incorrect username or password.'])
                ->onlyInput('username', 'rememberMe');
        }

        RateLimiter::clear($throttleKey);

        Auth::login($user, $request->boolean('rememberMe'));
        $request->session()->regenerate();

        User::query()->whereKey($user->id)->update(['lastvisit' => now()]);
        $request->session()->put('currency', config('legacy.currency'));
        $request->session()->put('login_name', $input['username']);
        AuditTrail::recordLogin($user->id);

        return redirect('/dashboard/index')->with(
            'success',
            'Welcome to the wonderful world of <strong>'.e(config('app.name')).'</strong>. With advanced features you will definitely have a great experience of using <strong>'.e(config('app.name')).'</strong>.',
        );
    }

    /**
     * Port of UserIdentity::authenticate(). Every failure shows the same
     * "Incorrect username or password." message, as in the Yii app; the
     * reason only goes to the security log.
     *
     * @return array{0: ?User, 1: ?string} the user, or null and why
     */
    private function authenticate(string $username, string $password): array
    {
        // Yii used strpos(), so an "@" in the first position still means username
        $column = strpos($username, '@') ? 'email' : 'username';
        $user = User::query()->where($column, $username)->first();

        if ($user === null) {
            return [null, 'unknown user'];
        }
        if (! $user->passwordMatches($password)) {
            return [null, 'wrong password'];
        }

        $blocked = [User::STATUS_NOT_ACTIVE => 'account not active', User::STATUS_BANNED => 'account banned', User::STATUS_EXPIRED => 'account expired'];

        return isset($blocked[(int) $user->status]) ? [null, $blocked[(int) $user->status]] : [$user, null];
    }

    /**
     * Version, build and what's new; open to every signed-in user.
     */
    public function about(): View
    {
        return view('site.about', [
            'version' => About::version(),
            'release' => About::release(),
            'changes' => About::changes(),
            'database' => 'MariaDB '.explode('-', (string) DB::selectOne('SELECT VERSION() AS v')->v)[0],
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        if ($id = Auth::id()) {
            AuditTrail::recordLogout($id);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/site/login')->with(
            'success',
            'Logout Successful! You can improve your security further after logging out by closing this opened browser.',
        );
    }

    public function noaccess(): View
    {
        return view('site.noaccess');
    }
}
