<?php

/*
|--------------------------------------------------------------------------
| Payments
|--------------------------------------------------------------------------
|
| Registration fees are paid by bank transfer or mobile money and confirmed
| inside the portal: the participant submits the transaction reference and a
| proof of payment, and a finance officer verifies it.
|
| Account details come from .env. A bank account or operator without a number
| is hidden from participants.
|
*/

return [

    // Registration references look like RH27-000123.
    'reference_prefix' => env('PAYMENT_REFERENCE_PREFIX', 'RH'),

    'bank_accounts' => array_filter([
        'TZS' => [
            'bank_name' => env('PAYMENT_BANK_TZS_BANK_NAME'),
            'account_name' => env('PAYMENT_BANK_TZS_ACCOUNT_NAME'),
            'account_number' => env('PAYMENT_BANK_TZS_ACCOUNT_NUMBER'),
            'branch' => env('PAYMENT_BANK_TZS_BRANCH'),
            'swift_code' => env('PAYMENT_BANK_TZS_SWIFT'),
        ],
        'USD' => [
            'bank_name' => env('PAYMENT_BANK_USD_BANK_NAME'),
            'account_name' => env('PAYMENT_BANK_USD_ACCOUNT_NAME'),
            'account_number' => env('PAYMENT_BANK_USD_ACCOUNT_NUMBER'),
            'branch' => env('PAYMENT_BANK_USD_BRANCH'),
            'swift_code' => env('PAYMENT_BANK_USD_SWIFT'),
        ],
    ], fn (array $account) => filled($account['account_number'])),

    'mobile_money' => [
        // Mobile money is in TZS only.
        'currency' => 'TZS',
        'providers' => array_filter([
            'mpesa' => ['label' => 'M-Pesa', 'pay_number' => env('PAYMENT_MPESA_PAY_NUMBER'), 'account_name' => env('PAYMENT_MPESA_ACCOUNT_NAME')],
            'airtel_money' => ['label' => 'Airtel Money', 'pay_number' => env('PAYMENT_AIRTEL_PAY_NUMBER'), 'account_name' => env('PAYMENT_AIRTEL_ACCOUNT_NAME')],
            'mixx_by_yas' => ['label' => 'Mixx by Yas', 'pay_number' => env('PAYMENT_MIXX_PAY_NUMBER'), 'account_name' => env('PAYMENT_MIXX_ACCOUNT_NAME')],
            'halopesa' => ['label' => 'HaloPesa', 'pay_number' => env('PAYMENT_HALOPESA_PAY_NUMBER'), 'account_name' => env('PAYMENT_HALOPESA_ACCOUNT_NAME')],
            // A Selcom Pay (Lipa) number takes payments from any network.
            'selcom' => ['label' => 'Selcom Pay', 'pay_number' => env('PAYMENT_SELCOM_PAY_NUMBER'), 'account_name' => env('PAYMENT_SELCOM_ACCOUNT_NAME')],
        ], fn (array $provider) => filled($provider['pay_number'])),
    ],

    // Proofs of payment are private: only finance staff can open them.
    'proof_disk' => env('PAYMENT_PROOF_DISK', 'local'),
    'proof_max_kb' => 5120,

];
