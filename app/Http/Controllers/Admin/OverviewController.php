<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AbstractStatus;
use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Models\AbstractSubmission;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\ReviewAssignment;
use App\Support\Summit;
use Illuminate\View\View;

class OverviewController extends Controller
{
    public function __invoke(Summit $summit): View
    {
        $edition = $summit->edition();
        $editionId = $edition?->id;

        $registrations = Registration::where('edition_id', $editionId)
            ->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

        $byCategory = Registration::where('registrations.edition_id', $editionId)
            ->join('registration_categories', 'registration_categories.id', '=', 'registrations.registration_category_id')
            ->selectRaw('registration_categories.name as name, count(*) as n')
            ->groupBy('registration_categories.name')->orderByDesc('n')->pluck('n', 'name');

        $revenue = Payment::where('status', PaymentStatus::Verified)
            ->whereHas('registration', fn ($q) => $q->where('edition_id', $editionId))
            ->selectRaw('currency, sum(amount) as total')->groupBy('currency')->pluck('total', 'currency');

        $abstracts = AbstractSubmission::where('edition_id', $editionId)
            ->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

        $byTopic = $edition
            ? $edition->topics()->withCount(['abstracts' => fn ($q) => $q->where('status', '!=', AbstractStatus::Draft)])->get()
            : collect();

        $reviews = ReviewAssignment::whereHas('abstract', fn ($q) => $q->where('edition_id', $editionId));

        return view('admin.overview', [
            'edition' => $edition,
            'registrations' => $registrations,
            'registrationTotal' => $registrations->sum(),
            'byCategory' => $byCategory,
            'revenue' => $revenue,
            'pendingPayments' => Payment::where('status', PaymentStatus::Submitted)->count(),
            'abstracts' => $abstracts,
            'byTopic' => $byTopic,
            'reviewsDone' => (clone $reviews)->whereNotNull('completed_at')->count(),
            'reviewsTotal' => (clone $reviews)->count(),
            'countries' => Registration::where('edition_id', $editionId)
                ->join('users', 'users.id', '=', 'registrations.user_id')
                ->distinct()->count('users.country'),
            'recent' => Registration::where('edition_id', $editionId)->with('user', 'category')->latest()->limit(6)->get(),
            'statuses' => RegistrationStatus::cases(),
            'abstractStatuses' => AbstractStatus::cases(),
        ]);
    }
}
