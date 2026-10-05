<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Services\DocumentService;
use App\Services\RegistrationService;
use App\Support\Summit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class RegistrationController extends Controller
{
    public function __construct(private Summit $summit) {}

    public function show(Request $request): View
    {
        $edition = $this->summit->edition();
        $registration = $request->user()->registrationFor($edition)?->load('category', 'payments.reviewer');

        return view('registration.show', [
            'edition' => $edition,
            'registration' => $registration,
            'categories' => $edition?->categories->filter->hasFee() ?? collect(),
            'bankAccounts' => config('payments.bank_accounts'),
            'mobileProviders' => config('payments.mobile_money.providers'),
            'methods' => PaymentMethod::cases(),
        ]);
    }

    public function store(Request $request, RegistrationService $service): RedirectResponse
    {
        $edition = $this->summit->edition();
        abort_unless($edition?->registration_open, 403, 'Registration is not open.');
        abort_if($request->user()->registrationFor($edition), 409, 'You are already registered.');

        $data = $request->validate([
            'category' => ['required', Rule::exists('registration_categories', 'id')->where('edition_id', $edition->id)->whereNotNull('amount')],
            'institution' => ['required', 'string', 'max:255'],
            'profession' => ['required', 'string', 'max:255'],
            'badge_name' => ['nullable', 'string', 'max:60'],
            'needs_invitation_letter' => ['boolean'],
            'passport_number' => ['nullable', 'required_if:needs_invitation_letter,1', 'string', 'max:30'],
            'dietary_needs' => ['nullable', 'string', 'max:255'],
            'accessibility_needs' => ['nullable', 'string', 'max:255'],
            'confirm' => ['accepted'],
        ], [
            'passport_number.required_if' => 'Enter your passport number for the invitation letter.',
            'confirm.accepted' => 'Please confirm the registration details.',
        ]);

        $request->user()->update(['institution' => $data['institution'], 'profession' => $data['profession']]);

        $service->register(
            $request->user(),
            $edition,
            $edition->categories->firstWhere('id', (int) $data['category']),
            $data,
        );

        return redirect()->route('registration.show')
            ->with('status', 'You are registered. Pay the fee below to confirm your place.');
    }

    public function badgePage(Request $request): View
    {
        return view('registration.badge', [
            'registration' => $request->user()->registrationFor($this->summit->edition())?->load('category'),
        ]);
    }

    public function badge(Request $request, DocumentService $documents): Response
    {
        $registration = $request->user()->registrationFor($this->summit->edition());
        abort_unless($registration?->isConfirmed(), 403, 'Your badge is available once your payment is verified.');

        return $documents->badge($registration);
    }

    public function letter(Request $request, DocumentService $documents): Response
    {
        $registration = $request->user()->registrationFor($this->summit->edition());
        abort_unless($registration?->isConfirmed() && $registration->needs_invitation_letter, 403, 'The invitation letter is available once your payment is verified.');

        return $documents->invitationLetter($registration);
    }
}
