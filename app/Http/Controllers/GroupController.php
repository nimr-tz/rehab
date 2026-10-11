<?php

namespace App\Http\Controllers;

use App\Actions\Fortify\CreateNewUser;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\GroupRegistration;
use App\Models\Registration;
use App\Services\GroupRegistrationService;
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

/** A leader registers a group, changes its members until paying, and pays once for everyone. */
class GroupController extends Controller
{
    public function __construct(private Summit $summit) {}

    public function index(Request $request): View
    {
        $edition = $this->summit->edition();

        return view('groups.index', [
            'edition' => $edition,
            'groups' => $edition
                ? GroupRegistration::where('edition_id', $edition->id)->where('leader_id', $request->user()->id)->withCount('members')->latest()->get()
                : collect(),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $edition = $this->summit->edition();

        if (! $edition?->registration_open) {
            return redirect()->route('groups.index')->with('status', 'Registration is not open yet.');
        }

        return view('groups.create', $this->formData() + [
            'leaderRegistered' => (bool) $request->user()->registrationFor($edition),
        ]);
    }

    public function store(Request $request, GroupRegistrationService $groups): RedirectResponse
    {
        $edition = $this->summit->edition();
        abort_unless($edition?->registration_open, 403, 'Registration is not open.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'leader_category' => ['nullable', 'integer'],
            'members' => ['nullable', 'array', 'max:'.GroupRegistrationService::MAX_MEMBERS],
            ...$this->memberRules('members.*.'),
            'members.*.email' => ['required', 'email', 'max:255', 'distinct:ignore_case'],
            'confirm' => ['accepted'],
        ], $this->messages() + ['confirm.accepted' => 'Confirm that you will pay the fee for everyone in the group.']);

        try {
            $group = $groups->create($request->user(), $edition, $data['name'], array_values($data['members'] ?? []), $data['leader_category'] ? (int) $data['leader_category'] : null);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['members' => $e->getMessage()]);
        }

        return redirect()->route('groups.show', $group)
            ->with('status', 'The group is registered. Each member has been emailed. Pay '.$group->formattedDue().' below to confirm everyone.');
    }

    public function show(Request $request, GroupRegistration $group): View
    {
        $this->authorizeLeader($request, $group);
        $group->load('members.user', 'members.category', 'payments.reviewer');

        return view('groups.show', $this->formData() + [
            'group' => $group,
            'pendingMpesa' => $group->pendingGatewayPayment(),
        ]);
    }

    public function addMember(Request $request, GroupRegistration $group, GroupRegistrationService $groups): RedirectResponse
    {
        $this->authorizeLeader($request, $group);
        $data = $request->validate([...$this->memberRules(''), 'email' => ['required', 'email', 'max:255']], $this->messages());

        try {
            $registration = $groups->addMember($group, $data);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['member' => $e->getMessage()]);
        }

        return back()->with('status', $registration->user->name.' was added to the group and emailed.');
    }

    public function removeMember(Request $request, GroupRegistration $group, Registration $registration, GroupRegistrationService $groups): RedirectResponse
    {
        $this->authorizeLeader($request, $group);

        try {
            $groups->removeMember($group, $registration);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['member' => $e->getMessage()]);
        }

        return back()->with('status', $registration->user->name.' was taken out of the group.');
    }

    /** Payment details and proof for the whole group, for finance to verify. */
    public function pay(Request $request, GroupRegistration $group, RegistrationService $service): RedirectResponse
    {
        $this->authorizeLeader($request, $group);
        abort_unless($group->canSubmitPayment(), 403, 'A payment cannot be submitted right now.');

        $data = PaymentController::validatePayment($request);
        if ($data['method'] === PaymentMethod::MobileMoney->value && $group->currency !== config('payments.mobile_money.currency')) {
            return back()->withInput()->withErrors(['method' => 'Mobile money is available for TZS fees only. Please pay by bank transfer.']);
        }

        $service->submitPayment($group, $data, $request->file('proof'));

        return redirect()->route('groups.show', $group)
            ->with('status', 'Thank you. The payment details were sent to our finance team for verification.');
    }

    public function mpesa(Request $request, GroupRegistration $group, MpesaPaymentService $service): RedirectResponse|JsonResponse
    {
        $this->authorizeLeader($request, $group);
        abort_unless(MpesaPaymentService::availableFor($group), 404);

        $data = $request->validate(['mpesa_phone' => ['required', 'string', 'max:20', function ($attribute, $value, $fail) {
            if (! MpesaPaymentService::acceptsNumber($value)) {
                $fail('Enter an M-Pesa number, for example 0754 123 456.');
            }
        }]], ['mpesa_phone.required' => 'Enter the M-Pesa number to pay from.']);

        set_time_limit(180);

        try {
            $payment = $service->start($group, $data['mpesa_phone']);
        } catch (InvalidArgumentException $e) {
            return $request->wantsJson()
                ? response()->json(['state' => 'failed', 'message' => $e->getMessage()], 422)
                : back()->withInput()->withErrors(['mpesa_phone' => $e->getMessage()]);
        }

        if ($request->wantsJson()) {
            return response()->json(MpesaPaymentService::state($payment));
        }

        return $payment->status === PaymentStatus::Failed
            ? redirect()->route('groups.show', $group)->withInput(['channel' => 'mpesa'])->withErrors(['mpesa_phone' => MpesaPaymentService::message($payment)])
            : redirect()->route('groups.show', $group);
    }

    public function mpesaStatus(Request $request, GroupRegistration $group, MpesaPaymentService $service): JsonResponse
    {
        $this->authorizeLeader($request, $group);
        $payment = $group->payments()->where('gateway', 'mpesa')->first();
        abort_unless($payment, 404);

        return response()->json(MpesaPaymentService::state($service->refresh($payment)));
    }

    public function mpesaCheck(Request $request, GroupRegistration $group, MpesaPaymentService $service): RedirectResponse
    {
        $this->authorizeLeader($request, $group);
        $payment = $group->pendingGatewayPayment();
        abort_unless($payment, 404);

        $service->reconcile($payment);

        return redirect()->route('groups.show', $group);
    }

    private function authorizeLeader(Request $request, GroupRegistration $group): void
    {
        abort_unless($group->leader_id === $request->user()->id && $group->edition_id === $this->summit->edition()?->id, 404);
    }

    private function formData(): array
    {
        $edition = $this->summit->edition();

        return [
            'categories' => $edition?->categories->filter(fn ($c) => $c->hasFee() && ! $c->is_student)->values() ?? collect(),
            'titles' => CreateNewUser::TITLES,
            'countries' => Countries::options(),
        ];
    }

    private function memberRules(string $prefix): array
    {
        $edition = $this->summit->edition();

        return [
            $prefix.'title' => ['nullable', Rule::in(CreateNewUser::TITLES)],
            $prefix.'first_name' => ['required', 'string', 'max:100'],
            $prefix.'last_name' => ['required', 'string', 'max:100'],
            $prefix.'phone' => ['required', 'string', 'max:32', 'regex:/^\+?[0-9 ()-]{7,20}$/'],
            $prefix.'country' => ['required', Rule::in(Countries::codes())],
            $prefix.'institution' => ['required', 'string', 'max:255'],
            $prefix.'profession' => ['required', 'string', 'max:255'],
            $prefix.'category' => ['required', Rule::exists('registration_categories', 'id')->where('edition_id', $edition?->id)->whereNotNull('amount')],
            $prefix.'dietary_needs' => ['nullable', 'string', 'max:255'],
            $prefix.'accessibility_needs' => ['nullable', 'string', 'max:255'],
        ];
    }

    private function messages(): array
    {
        return [
            '*.phone.regex' => 'Enter a phone number with digits only, for example +255 712 345 678.',
            'phone.regex' => 'Enter a phone number with digits only, for example +255 712 345 678.',
            'members.*.email.distinct' => 'Each member needs their own email address.',
        ];
    }
}
