<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * The Activity Log: every page a signed-in user opens and every action
 * they submit, written after the response has been sent. Background
 * requests (grid refreshes, searches, lookups) are left out.
 */
class RecordActivity
{
    public const LOG = 'activity';

    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('activity_started', microtime(true));

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $user = $request->user();
        $route = $request->route();
        $name = $route?->getName();

        if (! $user instanceof User || $name === null || ($request->isMethod('GET') && $request->ajax())) {
            return;
        }

        $started = (float) $request->attributes->get('activity_started', microtime(true));
        $id = $route->parameter('id');

        activity(self::LOG)
            ->causedBy($user)
            ->event(Str::lower($request->method()))
            ->withProperties(array_filter([
                'route' => $name,
                'record' => is_scalar($id) ? (string) $id : null,
                'method' => $request->method(),
                'path' => '/'.ltrim($request->path(), '/'),
                'status' => $response->getStatusCode(),
                'ms' => (int) round((microtime(true) - $started) * 1000),
                'ip' => $request->ip(),
                'agent' => Str::limit((string) $request->userAgent(), 200, ''),
            ], fn ($value) => $value !== null))
            ->log(self::title($name, is_scalar($id) ? (string) $id : null));
    }

    /**
     * "Patient Directory · View #135" from the permission's section and title.
     */
    public static function title(string $routeName, ?string $id = null): string
    {
        $permission = app(PermissionRegistrar::class)->getPermissions(['name' => $routeName])->first();
        [$controller, $action] = array_pad(explode('.', $routeName, 2), 2, '');

        $title = $permission
            ? $permission->getAttribute('group').' · '.$permission->getAttribute('title')
            : Str::headline($controller).' · '.Str::headline($action);

        return $title.($id !== null ? ' #'.$id : '');
    }
}
