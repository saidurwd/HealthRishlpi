<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DashboardController extends Controller
{
    // TODO(port): DashboardController::actionIndex/actionAjaxFilter/actionExport
    public function index(): View
    {
        return view('dashboard.index');
    }
}
