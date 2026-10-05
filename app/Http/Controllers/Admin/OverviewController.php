<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Support\Summit;
use Illuminate\View\View;

/** The executive summary: registrations, money and science at a glance. */
class OverviewController extends Controller
{
    public function __invoke(Summit $summit, DashboardService $dashboards): View
    {
        return view('admin.overview', $dashboards->executive($summit->edition()) + ['edition' => $summit->edition()]);
    }
}
