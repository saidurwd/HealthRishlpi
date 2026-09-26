<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Per-group permission for one controller action (`os_acl`).
 *
 * @property int $id
 * @property int $group_id
 * @property string $controller
 * @property string $actions
 * @property string $action_title
 * @property int $access
 */
#[Table('acl', timestamps: false)]
#[Fillable(['group_id', 'controller', 'actions', 'action_title', 'access'])]
class Acl extends Model
{
    /**
     * Port of Controller::checkAccess(): access is granted unless an `os_acl`
     * row for the user's group explicitly says otherwise. The decision is
     * cached per user for an hour, under the same key the Yii app used.
     */
    public static function allows(User $user, string $controller, string $action): bool
    {
        $key = 'Acl_'.$user->id.'_'.$controller.'_'.$action;

        $access = Cache::remember($key, config('legacy.aclCacheSeconds'), function () use ($user, $controller, $action) {
            $row = static::query()
                ->where('controller', $controller)
                ->where('actions', $action)
                ->where('group_id', $user->group_id)
                ->first();

            return $row?->access ?? 1;
        });

        return (int) $access === 1;
    }
}
