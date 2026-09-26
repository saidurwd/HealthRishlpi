<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Login/logout history (`os_audit_trail`).
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $login_time
 * @property string|null $logout_time
 */
#[Table('audit_trail', timestamps: false)]
#[Fillable(['user_id', 'login_time', 'logout_time'])]
class AuditTrail extends Model
{
    public static function recordLogin(int $userId): void
    {
        static::create(['user_id' => $userId, 'login_time' => now()->format('Y-m-d G:i:s')]);
    }

    /**
     * Stamp the user's most recent login row, as SiteController::actionLogout() did.
     */
    public static function recordLogout(int $userId): void
    {
        static::query()
            ->where('user_id', $userId)
            ->orderByDesc('login_time')
            ->limit(1)
            ->update(['logout_time' => now()]);
    }
}
