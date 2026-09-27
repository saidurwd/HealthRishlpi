<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Every route in the `route.permission` group needs the permission named
 * like the route ("patient.admin", the Yii controller.action ids). Users
 * without it go to the "no access" page, as they did under the Yii ACL.
 */
class AuthorizeRoute
{
    public function handle(Request $request, Closure $next): Response
    {
        $name = $request->route()?->getName();
        $user = $request->user();

        if ($user !== null && $name !== null && ! $user->can($name)) {
            return redirect()->route('site.noaccess')
                ->with('error', 'You are not authorized to perform this action!');
        }

        return $next($request);
    }
}
