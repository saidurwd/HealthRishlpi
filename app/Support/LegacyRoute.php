<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Maps Laravel routes back to Yii's controller and action ids.
 *
 * Routes are named "<controllerId>.<actionId>" using the exact ids the Yii
 * app used (e.g. "purchaseReceive.adjustmentEdit"), because those ids are
 * what `os_acl` rows and `os_menu` urls refer to.
 */
class LegacyRoute
{
    /**
     * @return array{0: ?string, 1: ?string}
     */
    public static function current(?Request $request = null): array
    {
        $name = ($request ?? request())->route()?->getName();

        if ($name === null || ! str_contains($name, '.')) {
            return [null, null];
        }

        [$controller, $action] = explode('.', $name, 2);

        return [$controller, $action];
    }

    /**
     * Turn a Yii GET-format URL (index.php?r=controller/action&id=5&...) into
     * this app's path ("/controller/action/5?..."). Returns null when the
     * request carries no Yii route.
     */
    public static function fromYiiQuery(Request $request): ?string
    {
        $route = trim((string) $request->query('r', ''), '/');

        if ($route === '' || preg_match('#^[A-Za-z0-9_]+(/[A-Za-z0-9_]+)?$#', $route) !== 1) {
            return null;
        }

        $query = $request->except('r');
        $path = '/'.$route;

        if (isset($query['id']) && is_scalar($query['id'])) {
            $path .= '/'.rawurlencode((string) $query['id']);
            unset($query['id']);
        }

        return $path.($query === [] ? '' : '?'.http_build_query($query));
    }
}
