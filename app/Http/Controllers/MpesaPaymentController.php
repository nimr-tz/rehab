<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\MpesaPaymentService;
use App\Support\Summit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * The participant pays their registration fee with an M-Pesa prompt on their
 * phone. The payment page calls these with fetch and shows the progress; the
 * plain form posts still work without JavaScript.
 */
class MpesaPaymentController extends Controller
{
    private const CONFIRMED = 'Thank you. Your M-Pesa payment was received and your place is confirmed.';

    public function store(Request $request, Summit $summit, MpesaPaymentService $service): RedirectResponse|JsonResponse
    {
        $registration = $request->user()->registrationFor($summit->edition());
        abort_unless(MpesaPaymentService::availableFor($registration), 404);

        $data = $request->validate([
            'mpesa_phone' => ['required', 'string', 'max:20', function ($attribute, $value, $fail) {
                if (! MpesaPaymentService::acceptsNumber($value)) {
                    $fail('Enter an M-Pesa number, for example 0754 123 456.');
                }
            }],
        ], ['mpesa_phone.required' => 'Enter the M-Pesa number to pay from.']);

        // The call waits while the participant enters their PIN.
        set_time_limit(180);

        try {
            $payment = $service->start($registration, $data['mpesa_phone']);
        } catch (InvalidArgumentException $e) {
            return $request->wantsJson()
                ? response()->json(['state' => 'failed', 'message' => $e->getMessage()], 422)
                : back()->withInput()->withErrors(['mpesa_phone' => $e->getMessage()]);
        }

        return $request->wantsJson() ? $this->state($payment) : $this->redirectFor($payment);
    }

    /** Where the participant's latest M-Pesa payment stands, asking M-Pesa if it is still pending. */
    public function status(Request $request, Summit $summit, MpesaPaymentService $service): JsonResponse
    {
        $payment = $request->user()->registrationFor($summit->edition())?->payments()->where('gateway', 'mpesa')->first();
        abort_unless($payment, 404);

        return $this->state($service->refresh($payment));
    }

    /** The same check, for the "Check the payment" button without JavaScript. */
    public function check(Request $request, Summit $summit, MpesaPaymentService $service): RedirectResponse
    {
        $payment = $request->user()->registrationFor($summit->edition())?->pendingGatewayPayment();
        abort_unless($payment, 404);

        return $this->redirectFor($service->reconcile($payment));
    }

    private function state(Payment $payment): JsonResponse
    {
        $state = MpesaPaymentService::state($payment);

        return response()->json($payment->status === PaymentStatus::Verified ? ['message' => self::CONFIRMED] + $state : $state);
    }

    private function redirectFor(Payment $payment): RedirectResponse
    {
        return match ($payment->status) {
            PaymentStatus::Verified => redirect()->route('registration.show')->with('status', self::CONFIRMED),
            PaymentStatus::Pending => redirect()->route('registration.show'),
            default => redirect()->route('registration.show')->withInput(['channel' => 'mpesa'])->withErrors(['mpesa_phone' => MpesaPaymentService::message($payment)]),
        };
    }
}
