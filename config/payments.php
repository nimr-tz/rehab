<?php

/*
|--------------------------------------------------------------------------
| Payments
|--------------------------------------------------------------------------
|
| Registration, group and sponsor fees are paid by bank transfer or mobile
| money. Every payment is recorded as a PaymentTransaction and confirmed
| inside the system: the payer submits the transaction reference (plus a
| proof document where required) and a Finance Officer verifies it.
|
| Automated mobile money gateways (push-to-pay with callbacks) plug in
| through App\Payments\Contracts\MobileMoneyGateway. Register a driver under
| mobile_money.gateways and set PAYMENT_MOBILE_MONEY_GATEWAY to enable it.
| With no gateway configured, mobile money payments are confirmed manually
| like bank transfers.
|
*/

return [

    // Prefix for the payment reference shown to payers (e.g. RH-U-000123).
    'reference_prefix' => env('PAYMENT_REFERENCE_PREFIX', 'RH'),

    'currencies' => ['TZS', 'USD'],

    /*
    | Registration fees per category. Amounts must be confirmed by the
    | organisers before registration opens.
    */
    'registration_fees' => [
        'professional_local' => [
            'label' => 'Professional (Tanzania)',
            'amount' => (float) env('FEE_PROFESSIONAL_LOCAL', 0),
            'currency' => 'TZS',
        ],
        'professional_international' => [
            'label' => 'Professional (International)',
            'amount' => (float) env('FEE_PROFESSIONAL_INTERNATIONAL', 0),
            'currency' => 'USD',
        ],
        'student_local' => [
            'label' => 'Student (Tanzania)',
            'amount' => (float) env('FEE_STUDENT_LOCAL', 0),
            'currency' => 'TZS',
        ],
        'student_international' => [
            'label' => 'Student (International)',
            'amount' => (float) env('FEE_STUDENT_INTERNATIONAL', 0),
            'currency' => 'USD',
        ],
    ],

    'methods' => [

        'bank_transfer' => [
            'enabled' => (bool) env('PAYMENT_BANK_TRANSFER_ENABLED', true),
            'label' => 'Bank transfer',
            'requires_proof' => true,
            // One account per currency. Accounts without an account number are hidden.
            'accounts' => [
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
            ],
        ],

        'mobile_money' => [
            'enabled' => (bool) env('PAYMENT_MOBILE_MONEY_ENABLED', true),
            'label' => 'Mobile money',
            'requires_proof' => false,
            'currencies' => ['TZS'],
            // Operators payers can pay to. Operators without a pay number are hidden.
            'providers' => [
                'mpesa' => [
                    'label' => 'M-Pesa',
                    'pay_number' => env('PAYMENT_MPESA_PAY_NUMBER'),
                    'account_name' => env('PAYMENT_MPESA_ACCOUNT_NAME'),
                ],
                'airtel_money' => [
                    'label' => 'Airtel Money',
                    'pay_number' => env('PAYMENT_AIRTEL_PAY_NUMBER'),
                    'account_name' => env('PAYMENT_AIRTEL_ACCOUNT_NAME'),
                ],
                'mixx_by_yas' => [
                    'label' => 'Mixx by Yas',
                    'pay_number' => env('PAYMENT_MIXX_PAY_NUMBER'),
                    'account_name' => env('PAYMENT_MIXX_ACCOUNT_NAME'),
                ],
                'halopesa' => [
                    'label' => 'HaloPesa',
                    'pay_number' => env('PAYMENT_HALOPESA_PAY_NUMBER'),
                    'account_name' => env('PAYMENT_HALOPESA_ACCOUNT_NAME'),
                ],
            ],
            // Automated gateway driver key (see 'gateways'); null = manual confirmation.
            'gateway' => env('PAYMENT_MOBILE_MONEY_GATEWAY'),
            // driver key => class implementing App\Payments\Contracts\MobileMoneyGateway
            'gateways' => [],
        ],

    ],

    // Disk for proof-of-payment uploads. Must not be publicly served.
    'proof_disk' => env('PAYMENT_PROOF_DISK', 'local'),

];
