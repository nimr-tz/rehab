<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Services\RegistrationService;
use App\Support\Summit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function store(Request $request, Summit $summit, RegistrationService $service): RedirectResponse
    {
        $registration = $request->user()->registrationFor($summit->edition());
        abort_unless($registration?->canSubmitPayment(), 403, 'A payment cannot be submitted right now.');

        $data = $request->validate([
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'provider' => ['nullable', 'required_if:method,mobile_money', Rule::in(array_keys(config('payments.mobile_money.providers')))],
            'transaction_reference' => ['required', 'string', 'min:6', 'max:60'],
            'payer_name' => ['required', 'string', 'max:255'],
            'payer_phone' => ['nullable', 'string', 'max:32'],
            'paid_on' => ['required', 'date', 'before_or_equal:today'],
            'proof' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.config('payments.proof_max_kb')],
        ], [
            'provider.required_if' => 'Choose the mobile money operator you paid with.',
            'proof.required' => 'Upload the bank slip or a screenshot of the mobile money confirmation.',
        ]);

        // Mobile money is TZS only.
        if ($data['method'] === PaymentMethod::MobileMoney->value && $registration->currency !== config('payments.mobile_money.currency')) {
            return back()->withInput()->withErrors(['method' => 'Mobile money is available for TZS fees only. Please pay by bank transfer.']);
        }

        $service->submitPayment($registration, $data, $request->file('proof'));

        return redirect()->route('registration.show')
            ->with('status', 'Thank you. Your payment details were sent to our finance team for verification.');
    }
}
