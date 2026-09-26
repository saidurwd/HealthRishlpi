<?php

namespace App\Http\Controllers;

use App\Models\AuditTrail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SiteController extends Controller
{
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

        $user = $this->authenticate($input['username'], $input['password']);

        if ($user === null) {
            return back()
                ->withErrors(['password' => 'Incorrect username or password.'])
                ->onlyInput('username', 'rememberMe');
        }

        Auth::login($user, $request->boolean('rememberMe'));
        $request->session()->regenerate();

        User::query()->whereKey($user->id)->update(['lastvisit' => now()]);
        $request->session()->put('currency', config('legacy.currency'));
        AuditTrail::recordLogin($user->id);

        return redirect('/dashboard/index')->with(
            'success',
            'Welcome to the wonderful world of <strong>'.e(config('app.name')).'</strong>. With advanced features you will definitely have a great experience of using <strong>'.e(config('app.name')).'</strong>.',
        );
    }

    /**
     * Port of UserIdentity::authenticate(). Every failure shows the same
     * "Incorrect username or password." message, as in the Yii app.
     */
    private function authenticate(string $username, string $password): ?User
    {
        // Yii used strpos(), so an "@" in the first position still means username
        $column = strpos($username, '@') ? 'email' : 'username';
        $user = User::query()->where($column, $username)->first();

        if ($user === null || ! $user->passwordMatches($password)) {
            return null;
        }

        $blocked = [User::STATUS_NOT_ACTIVE, User::STATUS_BANNED, User::STATUS_EXPIRED];

        return in_array((int) $user->status, $blocked, true) ? null : $user;
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
