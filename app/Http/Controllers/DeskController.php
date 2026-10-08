<?php

namespace App\Http\Controllers;

use App\Actions\Fortify\CreateNewUser;
use App\Enums\RegistrationStatus;
use App\Models\Registration;
use App\Services\AttendanceService;
use App\Services\DocumentService;
use App\Services\MpesaPaymentService;
use App\Services\RegistrationService;
use App\Support\Countries;
use App\Support\Summit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;
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

    public function index(Request $request): View|RedirectResponse
    {
        $edition = $this->summit->edition();
        $search = trim((string) $request->query('q'));
        $filter = array_key_exists($request->query('filter'), self::FILTERS) ? $request->query('filter') : 'all';
        $base = fn () => Registration::where('edition_id', $edition?->id);

        // A scanned badge, or a badge number typed in full, opens that person.
        if ($search !== '' && ($exact = $base()->where(fn ($q) => $q->where('qr_token', $search)->orWhere('reference', $search))->first())) {
            return redirect()->route('desk.show', $exact);
        }

        $registry = $base()->with('user', 'category', 'latestPayment')
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
                    ->orWhere('phone', 'like', "%{$search}%")
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

    /** One person at the desk: their badge, where they stand, and what the desk can do for them. */
    public function show(Registration $registration): View
    {
        abort_unless($registration->edition_id === $this->summit->edition()?->id, 404);

        $registration->load('user', 'category', 'payments.initiator', 'activeWaiver');

        return view('desk.show', [
            'registration' => $registration,
            'mpesa' => MpesaPaymentService::availableFor($registration),
            'pending' => $registration->pendingGatewayPayment(),
            'cpd' => app(AttendanceService::class)->summary($registration),
        ]);
    }

    /** Register someone who arrives without a registration. */
    public function create(): View
    {
        $edition = $this->summit->edition();
        abort_unless($edition, 404);

        return view('desk.register', [
            'categories' => $edition->categories->filter->hasFee(),
            'titles' => CreateNewUser::TITLES,
            'countries' => Countries::options(),
        ]);
    }

    public function store(Request $request, RegistrationService $service): RedirectResponse
    {
        $edition = $this->summit->edition();
        abort_unless($edition, 404);

        $data = $request->validate([
            'title' => ['nullable', Rule::in(CreateNewUser::TITLES)],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:32', 'regex:/^\+?[0-9 ()-]{7,20}$/'],
            'country' => ['required', Rule::in(Countries::codes())],
            'institution' => ['required', 'string', 'max:255'],
            'profession' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::exists('registration_categories', 'id')->where('edition_id', $edition->id)->whereNotNull('amount')],
            'dietary_needs' => ['nullable', 'string', 'max:255'],
            'accessibility_needs' => ['nullable', 'string', 'max:255'],
        ], [
            'phone.regex' => 'Enter a phone number with digits only, for example +255 712 345 678.',
        ]);

        try {
            $registration = $service->registerAtDesk($data, $edition, $edition->categories->firstWhere('id', (int) $data['category']));
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['email' => $e->getMessage()]);
        }

        return redirect()->route('desk.show', $registration)
            ->with('status', $registration->user->name.' is registered as '.$registration->reference.'. Take the payment below.');
    }

    /** Send an M-Pesa request to the participant's phone from the desk. */
    public function mpesa(Request $request, Registration $registration, MpesaPaymentService $service): JsonResponse
    {
        abort_unless(MpesaPaymentService::availableFor($registration), 404);

        $data = $request->validate([
            'mpesa_phone' => ['required', 'string', 'max:20', function ($attribute, $value, $fail) {
                if (! MpesaPaymentService::acceptsNumber($value)) {
                    $fail('Enter an M-Pesa number, for example 0754 123 456.');
                }
            }],
        ]);

        set_time_limit(180);

        try {
            $payment = $service->start($registration, $data['mpesa_phone'], $request->user());
        } catch (InvalidArgumentException $e) {
            return response()->json(['state' => 'failed', 'message' => $e->getMessage()], 422);
        }

        return response()->json(MpesaPaymentService::state($payment));
    }

    public function mpesaStatus(Registration $registration, MpesaPaymentService $service): JsonResponse
    {
        $payment = $registration->payments()->where('gateway', 'mpesa')->first();
        abort_unless($payment, 404);

        return response()->json(MpesaPaymentService::state($service->refresh($payment)));
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
