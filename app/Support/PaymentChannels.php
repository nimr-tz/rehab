<?php

namespace App\Support;

use App\Enums\PaymentMethod;
use App\Models\Registration;
use App\Services\MpesaPaymentService;

/**
 * The ways a participant can pay their fee, as the payment page offers them.
 *
 * "instant" channels are confirmed by the operator while the participant waits
 * (M-Pesa today). "manual" channels are paid outside the portal and verified by
 * finance from the uploaded proof. A channel without an account in config is
 * not offered.
 */
class PaymentChannels
{
    /**
     * @return array<string, array{key: string, label: string, mode: string, method: string, provider: ?string, timing: string, details: array}>
     */
    public static function for(Registration $registration): array
    {
        $channels = [];
        $mobileMoney = $registration->currency === config('payments.mobile_money.currency');

        if ($mobileMoney) {
            foreach (config('payments.mobile_money.providers') as $key => $provider) {
                $instant = $key === 'mpesa' && MpesaPaymentService::availableFor($registration);

                $channels[$key] = [
                    'key' => $key,
                    'label' => $provider['label'],
                    'mode' => $instant ? 'instant' : 'manual',
                    'method' => PaymentMethod::MobileMoney->value,
                    'provider' => $key,
                    'timing' => $instant ? 'Instant: confirmed in about 15 seconds' : 'Pay, then upload the confirmation · verified in 1–2 working days',
                    'details' => $provider,
                ];
            }

            // M-Pesa is offered instantly even before a pay number is published.
            if (! isset($channels['mpesa']) && MpesaPaymentService::availableFor($registration)) {
                $channels = ['mpesa' => [
                    'key' => 'mpesa', 'label' => 'M-Pesa', 'mode' => 'instant',
                    'method' => PaymentMethod::MobileMoney->value, 'provider' => 'mpesa',
                    'timing' => 'Instant: confirmed in about 15 seconds', 'details' => [],
                ]] + $channels;
            }
        }

        $account = config('payments.bank_accounts')[$registration->currency] ?? null;
        if ($account) {
            $channels['bank'] = [
                'key' => 'bank',
                'label' => $account['bank_name'] ?: 'Bank transfer',
                'mode' => 'manual',
                'method' => PaymentMethod::BankTransfer->value,
                'provider' => null,
                'timing' => 'Bank transfer, then upload the slip · verified in 1–2 working days',
                'details' => $account,
            ];
        }

        // Instant channels first: they are the quickest for the participant.
        uasort($channels, fn ($a, $b) => ($a['mode'] === 'instant' ? 0 : 1) <=> ($b['mode'] === 'instant' ? 0 : 1));

        return $channels;
    }
}
