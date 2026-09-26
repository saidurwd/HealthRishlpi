<?php

namespace App\Http\Middleware;

use App\Models\Acl;
use App\Support\LegacyRoute;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Port of the `beforeAction()` ACL check every Yii controller ran: look up
 * the user's group in `os_acl` for this controller/action and send them to
 * the "no access" page when access is 0.
 */
class CheckAcl
{
    public function handle(Request $request, Closure $next): Response
    {
        [$controller, $action] = LegacyRoute::current($request);
        $user = $request->user();

        if ($user !== null && $controller !== null && ! Acl::allows($user, $controller, $action)) {
            return redirect()->route('site.noaccess')
                ->with('error', 'You are not authorized to perform this action!');
        }

        return $next($request);
    }
}
