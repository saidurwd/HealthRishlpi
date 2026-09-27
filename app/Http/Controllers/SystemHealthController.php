<?php

namespace App\Http\Controllers;

use App\Support\SystemHealth;
use Illuminate\View\View;

/**
 * System Health: database, storage, scheduled jobs, backups, monitoring,
 * security and errors at a glance.
 */
class SystemHealthController extends Controller
{
    public function admin(): View
    {
        $checks = SystemHealth::checks();

        return view('system-health.admin', ['checks' => $checks, 'overall' => SystemHealth::overall($checks)]);
    }
}
