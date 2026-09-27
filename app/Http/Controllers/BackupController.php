<?php

namespace App\Http\Controllers;

use App\Models\Backup;
use App\Support\BackupEngine;
use App\Support\Grid;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

/**
 * Database Backup: export (gzip / zip / plain SQL), download, delete and
 * clean up old backups. Export and cleanup were GET links in Yii; they
 * change data, so they are POSTs here. The one-click restore was removed:
 * restoring overwrites every table (also the Yii app's), so it is done from
 * the command line after checking the file (deploy/README.md).
 */
class BackupController extends Controller
{
    public const RETENTION_DAYS = 30;

    public function admin(): View
    {
        $grid = Grid::for(Backup::query()->with('createdBy'))
            ->compare('id')
            ->compare('attachment', partial: true)
            ->compare('created_on', partial: true)
            ->compare('created_by')
            ->defaultOrder('created_on', 'desc')
            ->defaultOrder('id', 'desc')
            ->paginate(config('legacy.pageSize'));

        return view('backup.admin', [
            'grid' => $grid,
            'stats' => [
                'total_backups' => Backup::query()->count(),
                'total_size' => (int) Backup::query()->sum('file_size'),
                'success_count' => Backup::query()->where('status', Backup::STATUS_SUCCESS)->count(),
                'failed_count' => Backup::query()->where('status', Backup::STATUS_FAILED)->count(),
            ],
        ]);
    }

    public function exportdatabase(Request $request, BackupEngine $engine): RedirectResponse
    {
        set_time_limit(0);
        $started = microtime(true);

        try {
            $backup = $engine->create((string) $request->input('type', 'gzip'), $request->user()->id);

            return redirect()->route('backup.admin')->with('success', 'Database backed up successfully! '
                .$backup->tables_count.' tables exported in '.round(microtime(true) - $started, 2).'s. File: '.Backup::formatBytes($backup->file_size));
        } catch (Throwable $e) {
            return redirect()->route('backup.admin')->with('error', 'Backup failed: '.e($e->getMessage()));
        }
    }

    public function cleanup(Request $request): RedirectResponse
    {
        $days = (int) $request->input('days') ?: self::RETENTION_DAYS;
        $deleted = Backup::cleanOld($days);

        return redirect()->route('backup.admin')->with('success', 'Cleanup completed. '.$deleted.' backup(s) older than '.$days.' days were deleted.');
    }

    public function download(int $id): BinaryFileResponse|RedirectResponse
    {
        $backup = $this->find($id);

        if (empty($backup->attachment) || ! is_file($backup->path())) {
            return redirect()->route('backup.admin')->with('error', 'The file <strong>'.e($backup->attachment).'</strong> does not exist');
        }

        return response()->download($backup->path(), basename($backup->attachment));
    }

    public function delete(Request $request, int $id): RedirectResponse|Response
    {
        $backup = $this->find($id);

        if (is_file($backup->path())) {
            @unlink($backup->path());
        }
        $backup->delete();

        if ($request->has('ajax')) {
            return response()->noContent();
        }

        return redirect($request->input('returnUrl', route('backup.admin')));
    }

    private function find(int $id): Backup
    {
        return Backup::query()->find($id) ?? abort(404, 'The requested page does not exist.');
    }
}
