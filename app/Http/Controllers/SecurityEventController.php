<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\User;
use App\Support\Grid;
use App\Support\SecurityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Security Events: sign-ins and failures, lockouts, refused access,
 * password, account and permission changes, database exports.
 */
class SecurityEventController extends Controller
{
    public function admin(): View
    {
        $security = fn () => Activity::query()->where('log_name', SecurityLog::LOG);

        $grid = Grid::for($security()->with('causer'))
            ->compare('created_at', partial: true)
            ->compare('causer_id')
            ->compare('event')
            ->filter('ip', fn ($query, $value) => $query->where('properties->ip', 'like', "$value%"))
            ->filter('detail', fn ($query, $value) => $query->where('properties', 'like', "%$value%"))
            ->defaultOrder('id', 'desc')
            ->paginate(config('legacy.pageSize50'));

        $day = now()->subDay();
        $week = now()->subWeek();

        return view('security-event.admin', [
            'grid' => $grid,
            'page' => ['route' => 'securityEvent', 'plural' => 'Security Events', 'singular' => 'Event'],
            'users' => User::query()->orderBy('full_name')->pluck('full_name', 'id'),
            'events' => collect(SecurityLog::EVENTS)->map(fn ($event) => $event[0])->all(),
            'summary' => [
                'signins' => $security()->where('event', 'login')->where('created_at', '>=', $day)->count(),
                'failed' => $security()->where('event', 'login.failed')->where('created_at', '>=', $day)->count(),
                'lockouts' => $security()->where('event', 'login.lockout')->where('created_at', '>=', $day)->count(),
                'denied' => $security()->where('event', 'access.denied')->where('created_at', '>=', $day)->count(),
            ],
            // Where failed sign-ins came from this week
            'failedSources' => $security()->where('event', 'login.failed')->where('created_at', '>=', $week)
                ->select([
                    DB::raw("JSON_UNQUOTE(JSON_EXTRACT(properties, '$.ip')) AS ip"),
                    DB::raw("JSON_UNQUOTE(JSON_EXTRACT(properties, '$.username')) AS username"),
                    DB::raw('COUNT(*) AS attempts'),
                    DB::raw('MAX(created_at) AS last_at'),
                ])
                ->groupBy('ip', 'username')
                ->orderByDesc('attempts')
                ->limit(8)
                ->toBase()->get(),
        ]);
    }
}
