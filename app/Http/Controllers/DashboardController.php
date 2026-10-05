<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Services\DashboardService;
use App\Support\Summit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Each person lands on the dashboard of their main role. Admins land on the
 * executive summary and registration officers on the registry, which are
 * their dashboards.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request, Summit $summit, DashboardService $dashboards): View|RedirectResponse
    {
        $user = $request->user();
        $edition = $summit->edition();
        $is = fn (Role $role) => $user->hasRole($role->value);

        return match (true) {
            $is(Role::Admin) => redirect()->route('admin.overview'),
            $is(Role::ScientificAdmin) => view('dashboards.scientific', $dashboards->scientific($edition) + ['user' => $user]),
            $is(Role::FinanceOfficer) => view('dashboards.finance', $dashboards->finance() + ['user' => $user]),
            $is(Role::RegistrationOfficer) => redirect()->route('desk.index'),
            $is(Role::Reviewer) => view('dashboards.reviewer', $dashboards->reviewer($user, $edition) + ['user' => $user, 'edition' => $edition]),
            default => view('dashboards.participant', $dashboards->participant($user, $edition) + ['user' => $user, 'edition' => $edition]),
        };
    }
}
