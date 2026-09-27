<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\User;
use App\Support\Grid;
use Illuminate\View\View;

/**
 * Audit Log: who created, changed or deleted which record, with the old and
 * new values (activity log "data" and "access" entries).
 */
class AuditLogController extends Controller
{
    public const LOGS = ['data' => 'Records', 'access' => 'Access rights'];

    public function admin(): View
    {
        $grid = Grid::for(Activity::query()->whereIn('log_name', array_keys(self::LOGS))->with('causer'), 'Audit')
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

        return view('audit-log.admin', [
            'grid' => $grid,
            'users' => User::query()->orderBy('full_name')->pluck('full_name', 'id'),
            'types' => Activity::query()->whereIn('log_name', array_keys(self::LOGS))->distinct()->orderBy('subject_type')->pluck('subject_type')
                ->filter()->mapWithKeys(fn (string $type) => [$type => Activity::typeLabel($type)]),
        ]);
    }

    /**
     * One entry and the whole history of its record.
     */
    public function view(int $id): View
    {
        $entry = Activity::query()->whereIn('log_name', array_keys(self::LOGS))->with('causer')->find($id) ?? abort(404, 'The requested page does not exist.');

        return view('audit-log.view', [
            'entry' => $entry,
            'history' => $entry->subject_type === null ? collect() : Activity::query()
                ->whereIn('log_name', array_keys(self::LOGS))
                ->where('subject_type', $entry->subject_type)
                ->where('subject_id', $entry->subject_id)
                ->with('causer')
                ->orderByDesc('id')
                ->limit(200)
                ->get(),
        ]);
    }
}
