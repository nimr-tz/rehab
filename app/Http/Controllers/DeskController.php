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

/** The registration desk: the registry, badge printing and check-in at the venue. */
class DeskController extends Controller
{
    public const FILTERS = [
        'all' => 'All',
        'confirmed' => 'Confirmed',
        'printed' => 'Printed',
        'checked_in' => 'Checked in',
        'payment_submitted' => 'Under verification',
        'pending_payment' => 'Awaiting payment',
    ];

    public function __construct(private Summit $summit) {}

    public function index(Request $request): View
    {
        $edition = $this->summit->edition();
        $search = trim((string) $request->query('q'));
        $filter = array_key_exists($request->query('filter'), self::FILTERS) ? $request->query('filter') : 'all';
        $base = fn () => Registration::where('edition_id', $edition?->id);

        $registry = $base()->with('user', 'category')
            ->when($filter === 'confirmed', fn ($q) => $q->where('status', RegistrationStatus::Confirmed))
            ->when($filter === 'printed', fn ($q) => $q->whereNotNull('badge_printed_at'))
            ->when($filter === 'checked_in', fn ($q) => $q->whereNotNull('checked_in_at'))
            ->when(in_array($filter, ['payment_submitted', 'pending_payment'], true), fn ($q) => $q->where('status', $filter))
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('reference', 'like', "%{$search}%")
                ->orWhere('qr_token', $search)
                ->orWhereHas('user', fn ($q) => $q
                    ->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('institution', 'like', "%{$search}%"))))
            ->orderBy('reference')
            ->paginate(12)
            ->withQueryString();

        $all = $base()->with('category')->get();
        $confirmed = $all->where('status', RegistrationStatus::Confirmed);

        return view('desk.index', [
            'registry' => $registry,
            'search' => $search,
            'filter' => $filter,
            'filters' => self::FILTERS,
            'stats' => [
                'registered' => $all->count(),
                'confirmed' => $confirmed->count(),
                'printed' => $all->whereNotNull('badge_printed_at')->count(),
                'ready' => $confirmed->whereNull('badge_printed_at')->count(),
                'checkedIn' => $all->whereNotNull('checked_in_at')->count(),
                'awaiting' => $all->whereIn('status', [RegistrationStatus::PendingPayment, RegistrationStatus::PaymentSubmitted])->count(),
            ],
            'byCategory' => $all->groupBy(fn ($r) => $r->category->name)->map->count()->sortDesc(),
            'readiness' => $this->readiness($edition, $confirmed->count(), $all->whereNotNull('badge_printed_at')->count()),
        ]);
    }

    /** Badges waiting to be printed, in batches. */
    public function queue(): View
    {
        $edition = $this->summit->edition();
        $confirmed = Registration::where('edition_id', $edition?->id)->where('status', RegistrationStatus::Confirmed)->with('user', 'category');

        return view('desk.queue', [
            'unprinted' => (clone $confirmed)->whereNull('badge_printed_at')->orderBy('reference')->get(),
            'printed' => (clone $confirmed)->whereNotNull('badge_printed_at')->latest('badge_printed_at')->limit(12)->get(),
            'batchSize' => 40,
        ]);
    }

    /** The next batch of unprinted badges as one PDF; they are marked as printed. */
    public function printBatch(DocumentService $documents): Response
    {
        $batch = Registration::where('edition_id', $this->summit->edition()?->id)
            ->where('status', RegistrationStatus::Confirmed)->whereNull('badge_printed_at')
            ->orderBy('reference')->limit(40)->get();

        abort_if($batch->isEmpty(), 404, 'Every confirmed badge has been printed.');

        Registration::whereIn('id', $batch->pluck('id'))->update(['badge_printed_at' => now()]);

        return $documents->badges($batch, 'badges-'.now()->format('Y-m-d-His').'.pdf');
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

        $registration->update(['badge_printed_at' => $registration->badge_printed_at ?? now()]);

        return $documents->badge($registration);
    }

    /** What the desk needs before doors open, computed from the data. */
    private function readiness($edition, int $confirmed, int $printed): array
    {
        return [
            ['Summit dates and venue published', (bool) ($edition?->start_date && $edition?->venue), $edition?->start_date ? $edition->start_date->format('j M Y') : 'Set in Summit settings'],
            ['Programme published', $edition && $edition->sessions()->exists(), $edition ? $edition->sessions()->count().' sessions' : '—'],
            ['Badges printed for confirmed participants', $confirmed > 0 && $printed >= $confirmed, $printed.' of '.$confirmed],
            ['Payments verified before arrival', ! Registration::where('edition_id', $edition?->id)->where('status', RegistrationStatus::PaymentSubmitted)->exists(), Registration::where('edition_id', $edition?->id)->where('status', RegistrationStatus::PaymentSubmitted)->count().' still under verification'],
        ];
    }
}
