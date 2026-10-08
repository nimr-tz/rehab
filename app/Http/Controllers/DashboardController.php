<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Services\DashboardService;
use App\Support\Summit;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Each person lands on the dashboard of the workspace they are in. Admins
 * land on the executive summary, registration officers on the registry,
 * photographers on their albums and judges on their scoring, which are their
 * dashboards.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request, Summit $summit, DashboardService $dashboards): View|RedirectResponse
    {
        $user = $request->user();
        $edition = $summit->edition();

        return match (Workspace::current($user)) {
            Role::Admin, Role::Executive => redirect()->route('admin.overview'),
            Role::ScientificAdmin => view('dashboards.scientific', $dashboards->scientific($edition) + ['user' => $user]),
            Role::FinanceOfficer => view('dashboards.finance', $dashboards->finance() + ['user' => $user]),
            Role::RegistrationOfficer => redirect()->route('desk.index'),
            Role::Reviewer => view('dashboards.reviewer', $dashboards->reviewer($user, $edition) + ['user' => $user, 'edition' => $edition]),
            Role::Photographer => redirect()->route('media.albums.index'),
            Role::Judge => redirect()->route('judging.index'),
            default => view('dashboards.participant', $dashboards->participant($user, $edition) + ['user' => $user, 'edition' => $edition]),
        };
    }
}
