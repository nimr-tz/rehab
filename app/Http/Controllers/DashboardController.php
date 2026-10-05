<?php

namespace App\Http\Controllers;

use App\Enums\AbstractStatus;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Models\AbstractSubmission;
use App\Models\Payment;
use App\Support\Summit;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, Summit $summit): View
    {
        $user = $request->user();
        $edition = $summit->edition();

        $registration = $user->registrationFor($edition)?->load('category', 'latestPayment');
        $abstracts = $edition
            ? $user->abstracts()->where('edition_id', $edition->id)->with('topic')->latest()->get()
            : collect();

        return view('dashboard', [
            'user' => $user,
            'edition' => $edition,
            'registration' => $registration,
            'abstracts' => $abstracts,
            'upcomingSessions' => $edition ? $edition->sessions()->where('kind', '!=', 'break')->limit(4)->get() : collect(),
            'staff' => [
                'reviews' => $user->hasRole(Role::Reviewer->value)
                    ? $user->reviewAssignments()->whereNull('completed_at')->count() : null,
                'payments' => $user->hasAnyRole([Role::FinanceOfficer->value, Role::Admin->value])
                    ? Payment::where('status', PaymentStatus::Submitted)->count() : null,
                'toAssign' => $user->hasAnyRole([Role::ScientificAdmin->value, Role::Admin->value])
                    ? AbstractSubmission::where('status', AbstractStatus::Submitted)->count() : null,
                'toDecide' => $user->hasAnyRole([Role::ScientificAdmin->value, Role::Admin->value])
                    ? AbstractSubmission::where('status', AbstractStatus::UnderReview)
                        ->whereDoesntHave('reviews', fn ($q) => $q->whereNull('completed_at'))->count() : null,
                'isAdmin' => $user->hasRole(Role::Admin->value),
                'isDesk' => $user->hasAnyRole([Role::RegistrationOfficer->value]),
            ],
        ]);
    }
}
