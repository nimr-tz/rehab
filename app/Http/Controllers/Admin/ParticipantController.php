<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Support\Csv;
use App\Support\Summit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ParticipantController extends Controller
{
    public function index(Request $request, Summit $summit): View
    {
        $edition = $summit->edition();

        return view('admin.participants.index', [
            'registrations' => $this->query($request, $summit)->with('user', 'category')->latest()->paginate(20)->withQueryString(),
            'categories' => $edition?->categories ?? collect(),
            'statuses' => RegistrationStatus::cases(),
            'filters' => $this->filters($request),
        ]);
    }

    public function show(Registration $registration): View
    {
        $registration->load('user', 'category', 'payments.reviewer', 'edition');

        return view('admin.participants.show', [
            'registration' => $registration,
            'abstracts' => $registration->user->abstracts()->where('edition_id', $registration->edition_id)->with('topic')->get(),
        ]);
    }

    /**
     * Everyone matching the filters, as a spreadsheet. With needs=1, only those
     * with dietary or accessibility needs, for the caterers and the venue.
     */
    public function export(Request $request, Summit $summit): StreamedResponse
    {
        $needs = $request->boolean('needs');
        $registrations = $this->query($request, $summit)
            ->when($needs, fn ($q) => $q->where(fn ($q) => $q->whereNotNull('dietary_needs')->orWhereNotNull('accessibility_needs')))
            ->with('user', 'category')->orderBy('reference')->get();

        $headers = ['Reference', 'Title', 'First name', 'Last name', 'Name on badge', 'Email', 'Phone', 'Country', 'Institution', 'Profession', 'Category', 'Status', 'Dietary needs', 'Accessibility needs'];
        if (! $needs) {
            array_push($headers, 'Currency', 'Fee', 'Waived', 'Due', 'Student proof', 'Invitation letter', 'Registered', 'Confirmed', 'Badge printed', 'Checked in');
        }

        return Csv::download(($needs ? 'participant-needs-' : 'participants-').now()->format('Y-m-d').'.csv', $headers,
            $registrations->map(function (Registration $r) use ($needs) {
                $u = $r->user;
                $row = [$r->reference, $u->title, $u->first_name, $u->last_name, $r->displayName(), $u->email, $u->phone, $u->countryName(), $u->institution, $u->profession,
                    $r->category->name, $r->status->label(), $r->dietary_needs, $r->accessibility_needs];

                return $needs ? $row : [...$row, $r->currency, $r->amount, $r->waived_amount, $r->amountDue(), $r->student_status?->label(), $r->needs_invitation_letter,
                    $r->created_at->toDateTimeString(), $r->confirmed_at?->toDateTimeString(), $r->badge_printed_at?->toDateTimeString(), $r->checked_in_at?->toDateTimeString()];
            }));
    }

    private function query(Request $request, Summit $summit): Builder
    {
        ['status' => $status, 'category' => $category, 'search' => $search] = $this->filters($request);

        return Registration::query()
            ->where('edition_id', $summit->edition()?->id)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($category, fn ($q) => $q->where('registration_category_id', $category))
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('reference', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($q) => $q
                    ->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('institution', 'like', "%{$search}%"))));
    }

    /** @return array{status: ?string, category: ?string, search: string} */
    private function filters(Request $request): array
    {
        return [
            'status' => $request->query('status'),
            'category' => $request->query('category'),
            'search' => trim((string) $request->query('q')),
        ];
    }
}
