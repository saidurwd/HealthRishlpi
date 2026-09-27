<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\User;
use App\Support\Grid;
use Illuminate\View\View;

/**
 * Who changed what: the activity log, newest first, read only.
 */
class ActivityLogController extends Controller
{
    public function admin(): View
    {
        $grid = Grid::for(Activity::query()->with('causer'))
            ->compare('id')
            ->compare('created_at', partial: true)
            ->compare('causer_id')
            ->compare('log_name')
            ->compare('event')
            ->compare('subject_type')
            ->compare('subject_id')
            ->compare('description', partial: true)
            ->defaultOrder('id', 'desc')
            ->paginate(config('legacy.pageSize50'));

        return view('activity-log.admin', [
            'grid' => $grid,
            'page' => ['route' => 'activityLog', 'plural' => 'Activity Log', 'singular' => 'Activity'],
            'users' => User::query()->orderBy('full_name')->pluck('full_name', 'id'),
            'types' => Activity::query()->distinct()->orderBy('subject_type')->pluck('subject_type')
                ->filter()->mapWithKeys(fn (string $type) => [$type => Activity::typeLabel($type)]),
        ]);
    }
}
