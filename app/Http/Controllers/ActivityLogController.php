<?php

namespace App\Http\Controllers;

use App\Http\Middleware\RecordActivity;
use App\Models\Activity;
use App\Models\User;
use App\Support\Grid;
use Illuminate\View\View;

/**
 * Activity Log: the pages users opened and the actions they submitted, with
 * time taken, IP address and browser (RecordActivity writes it; kept 180
 * days). Record changes themselves are in the Audit Log.
 */
class ActivityLogController extends Controller
{
    public function admin(): View
    {
        $grid = Grid::for(Activity::query()->where('log_name', RecordActivity::LOG)->with('causer'))
            ->compare('created_at', partial: true)
            ->compare('causer_id')
            ->compare('event')
            ->compare('description', partial: true)
            ->filter('path', fn ($query, $value) => $query->where('properties->path', 'like', "%$value%"))
            ->filter('ip', fn ($query, $value) => $query->where('properties->ip', 'like', "$value%"))
            ->defaultOrder('id', 'desc')
            ->paginate(config('legacy.pageSize50'));

        $today = Activity::query()->where('log_name', RecordActivity::LOG)->where('created_at', '>=', today());

        return view('activity-log.admin', [
            'grid' => $grid,
            'page' => ['route' => 'activityLog', 'plural' => 'Activity Log', 'singular' => 'Activity'],
            'users' => User::query()->orderBy('full_name')->pluck('full_name', 'id'),
            'today' => [
                'requests' => (clone $today)->count(),
                'users' => (clone $today)->distinct()->count('causer_id'),
                'actions' => (clone $today)->where('event', '!=', 'get')->count(),
            ],
        ]);
    }
}
