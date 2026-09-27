<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Security events, kept in the activity log under the "security" log name
 * with the request's IP address and browser: sign-ins (and failures,
 * lockouts), sign-outs, refused access, password, account and permission
 * changes, and database exports/downloads.
 */
class SecurityLog
{
    public const LOG = 'security';

    /** Event => [title, severity (info|warning|danger)] for the Security Events page */
    public const EVENTS = [
        'login' => ['Signed in', 'info'],
        'login.failed' => ['Sign-in failed', 'warning'],
        'login.lockout' => ['Sign-in locked out', 'danger'],
        'logout' => ['Signed out', 'info'],
        'access.denied' => ['Access denied', 'warning'],
        'password.changed' => ['Password changed', 'warning'],
        'user.created' => ['User created', 'info'],
        'user.deleted' => ['User deleted', 'warning'],
        'user.group_changed' => ['User group changed', 'warning'],
        'user.status_changed' => ['User status changed', 'warning'],
        'permissions.changed' => ['Permissions changed', 'warning'],
        'backup.exported' => ['Database exported', 'warning'],
        'backup.downloaded' => ['Backup downloaded', 'danger'],
        'backup.deleted' => ['Backup deleted', 'warning'],
    ];

    /**
     * @param  array<string, mixed>  $properties
     */
    public static function record(string $event, array $properties = [], ?Model $subject = null, ?User $causer = null): void
    {
        $request = request();
        $causer ??= auth()->user();

        $log = activity(self::LOG)
            ->event($event)
            ->withProperties($properties + [
                'ip' => $request->ip(),
                'agent' => Str::limit((string) $request->userAgent(), 200, ''),
            ]);

        if ($causer instanceof User) {
            $log->causedBy($causer);
        }
        if ($subject !== null) {
            $log->performedOn($subject);
        }

        $log->log(self::EVENTS[$event][0] ?? $event);
    }

    public static function title(?string $event): string
    {
        return self::EVENTS[$event][0] ?? (string) $event;
    }

    public static function severity(?string $event): string
    {
        return self::EVENTS[$event][1] ?? 'info';
    }
}
