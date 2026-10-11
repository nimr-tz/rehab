<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\StudentProofStatus;
use App\Models\RegistrationCategory;
use App\Services\DocumentService;
use App\Services\Mpesa\MpesaClient;
use App\Services\MpesaPaymentService;
use App\Services\RegistrationService;
use App\Support\Summit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class RegistrationController extends Controller
{
    public function __construct(private Summit $summit) {}

    public function show(Request $request): View
    {
        $edition = $this->summit->edition();
        $registration = $request->user()->registrationFor($edition)?->load('category', 'payments.reviewer');

        // A new M-Pesa session needs 30 seconds to become active: open it now,
        // after the page is sent, so a payment does not have to wait for it.
        if (MpesaPaymentService::availableFor($registration)) {
            dispatch(function () {
                try {
                    app(MpesaClient::class)->warmUp();
                } catch (Throwable $e) {
                    report($e);
                }
            })->afterResponse();
        }

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
            'student_proof' => $this->studentProofRules(),
            'confirm' => ['accepted'],
        ], [
            'passport_number.required_if' => 'Enter your passport number for the invitation letter.',
            'confirm.accepted' => 'Please confirm the registration details.',
        ]);

        $category = $edition->categories->firstWhere('id', (int) $data['category']);
        $this->requireStudentProof($request, $category);

        $request->user()->update(['institution' => $data['institution'], 'profession' => $data['profession']]);

        $service->register($request->user(), $edition, $category, $data, $request->file('student_proof'));

        return redirect()->route('registration.show')->with('status', $category->is_student
            ? 'You are registered. We will check your student proof, then you can pay the student rate.'
            : 'You are registered. Pay the fee below to confirm your place.');
    }

    /** A new proof of enrolment after the last one was not accepted. */
    public function studentProof(Request $request, RegistrationService $service): RedirectResponse
    {
        $registration = $request->user()->registrationFor($this->summit->edition());
        abort_unless($registration, 404);

        $request->validate(['student_proof' => ['required', ...$this->studentProofRules()]], [
            'student_proof.required' => 'Upload your student ID or a letter from your institution.',
        ]);

        try {
            $service->resubmitStudentProof($registration, $request->file('student_proof'));
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['student_proof' => $e->getMessage()]);
        }

        return redirect()->route('registration.show')->with('status', 'Thank you. We will check your new student proof.');
    }

    /** Move to another category before anything is paid. */
    public function changeCategory(Request $request, RegistrationService $service): RedirectResponse
    {
        $edition = $this->summit->edition();
        $registration = $request->user()->registrationFor($edition);
        abort_unless($registration, 404);

        $data = $request->validate([
            'category' => ['required', Rule::exists('registration_categories', 'id')->where('edition_id', $edition->id)->whereNotNull('amount')],
            'student_proof' => $this->studentProofRules(),
        ]);

        $category = $edition->categories->firstWhere('id', (int) $data['category']);
        if ($category->is_student && $registration->student_status !== StudentProofStatus::Approved) {
            $this->requireStudentProof($request, $category);
        }

        try {
            $service->changeCategory($registration, $category, $request->file('student_proof'));
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['category' => $e->getMessage()]);
        }

        return redirect()->route('registration.show')->with('status', 'Your category is now '.$category->name.', at '.$category->formattedFee().'.');
    }

    private function studentProofRules(): array
    {
        return ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.config('payments.proof_max_kb')];
    }

    private function requireStudentProof(Request $request, RegistrationCategory $category): void
    {
        if ($category->is_student && ! $request->hasFile('student_proof')) {
            throw ValidationException::withMessages([
                'student_proof' => 'The student rate needs proof of enrolment: upload your student ID or a letter from your institution.',
            ]);
        }
    }

    public function badgePage(Request $request): View
    {
        return view('registration.badge', [
            'registration' => $request->user()->registrationFor($this->summit->edition())?->load('category'),
        ]);
    }

    /** The participant corrects the name printed on their badge. */
    public function badgeName(Request $request): RedirectResponse
    {
        $registration = $request->user()->registrationFor($this->summit->edition());
        abort_unless($registration, 404);

        $data = $request->validate(['badge_name' => ['nullable', 'string', 'max:60']]);
        $registration->renameBadge($data['badge_name'] ?? null);

        return back()->with('status', 'The name on your badge is now '.$registration->displayName().'.');
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
