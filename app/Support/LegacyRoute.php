<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Old Yii URLs. Routes keep the Yii paths and are named
 * "<controllerId>.<actionId>" with the exact Yii ids
 * (e.g. "purchaseReceive.adjustmentEdit"), which are also the permission names.
 */
class LegacyRoute
{
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
