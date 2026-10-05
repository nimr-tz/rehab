<?php

namespace App\Http\Controllers;

use App\Enums\RegistrationStatus;
use App\Models\Registration;
use App\Services\DocumentService;
use App\Support\Summit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/** The registration desk at the venue: find a participant, check them in, print a badge. */
class DeskController extends Controller
{
    public function index(Request $request, Summit $summit): View
    {
        $search = trim((string) $request->query('q'));
        $edition = $summit->edition();

        $results = $search === '' ? collect() : Registration::query()
            ->where('edition_id', $edition?->id)
            ->with('user', 'category')
            ->where(fn ($q) => $q
                ->where('reference', 'like', "%{$search}%")
                ->orWhere('qr_token', $search)
                ->orWhereHas('user', fn ($q) => $q
                    ->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")))
            ->limit(20)
            ->get();

        $base = Registration::where('edition_id', $edition?->id);

        return view('desk.index', [
            'search' => $search,
            'results' => $results,
            'confirmed' => (clone $base)->where('status', RegistrationStatus::Confirmed)->count(),
            'checkedIn' => (clone $base)->whereNotNull('checked_in_at')->count(),
        ]);
    }

    public function checkIn(Request $request, Registration $registration): RedirectResponse
    {
        if (! $registration->isConfirmed()) {
            return back()->withErrors(['check_in' => $registration->user->name.' has not completed payment. Send them to the finance desk.']);
        }

        if (! $registration->checked_in_at) {
            $registration->update(['checked_in_at' => now()]);
        }

        return back()->with('status', $registration->user->name.' is checked in.');
    }

    public function badge(Registration $registration, DocumentService $documents): Response
    {
        abort_unless($registration->isConfirmed(), 403, 'Badges are printed for confirmed registrations only.');

        return $documents->badge($registration);
    }
}
