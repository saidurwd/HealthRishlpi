<?php

namespace App\Models;

use DateTime;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
class AuditTrail extends LegacyModel
{
    // Sign-in records are a log already
    protected static $recordEvents = [];

    public static function attributeLabels(): array
    {
        return ['id' => 'ID', 'user_id' => 'User', 'login_time' => 'Login Time', 'logout_time' => 'Logout Time'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Session length for the Duration column (AuditTrail::returnInterval()),
     * e.g. "1 hour, 5 minutes and 3 seconds". A missing time counts as now.
     */
    public static function interval(?string $from, ?string $to): string
    {
        $diff = (new DateTime((string) $from))->diff(new DateTime((string) $to));
        $parts = [];

        foreach (['y' => 'year', 'm' => 'month', 'd' => 'day', 'h' => 'hour', 'i' => 'minute', 's' => 'second'] as $field => $unit) {
            if ($diff->{$field}) {
                $parts[] = $diff->{$field}.' '.$unit.($diff->{$field} === 1 ? '' : 's');
            }
        }

        // The Yii version also replaced the first two characters when there
        // was only one part ("5 minutes" became " and minutes")
        $last = array_pop($parts);

        return $parts === [] ? (string) $last : implode(', ', $parts).' and '.$last;
    }

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
