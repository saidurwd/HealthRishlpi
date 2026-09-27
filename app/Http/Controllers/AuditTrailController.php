<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\AuditTrail;
use App\Models\User;
use App\Support\Grid;
use App\Support\SecurityLog;

/**
 * Login History: sign-in sessions (os_audit_trail, written by both apps)
 * and the failed sign-in attempts from the security log.
 */
class AuditTrailController extends CrudController
{
    protected string $model = AuditTrail::class;

    protected string $route = 'auditTrail';

    protected string $plural = 'Login History';

    protected string $singular = 'Session';

    protected function grid(): Grid
    {
        return Grid::for(AuditTrail::query()->with('user'))
            ->compare('id', partial: true)
            ->compare('user_id')
            ->compare('login_time', partial: true)
            ->compare('logout_time', partial: true)
            ->defaultOrder('login_time', 'desc');
    }

    protected function filterData(): array
    {
        return [
            'users' => User::query()->orderBy('full_name')->pluck('full_name', 'id'),
            'failed' => Activity::query()->where('log_name', SecurityLog::LOG)->whereIn('event', ['login.failed', 'login.lockout'])
                ->orderByDesc('id')->limit(50)->get(),
            'openSessions' => AuditTrail::query()->whereNull('logout_time')->where('login_time', '>=', now()->subHours(12))->count(),
        ];
    }
}
