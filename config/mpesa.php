<?php

/*
|--------------------------------------------------------------------------
| M-Pesa (Vodacom Tanzania OpenAPI)
|--------------------------------------------------------------------------
|
| Credentials come from the application on openapiportal.m-pesa.com. The API
| key is encrypted with the platform's public key to open a session, and the
| session ID is encrypted the same way for every later call.
|
*/

return [

    // Offer "Pay with M-Pesa" to participants. Off until the organisation has a shortcode.
    'enabled' => (bool) env('MPESA_ENABLED', false),

    // "sandbox" or "openapi" (live).
    'environment' => env('MPESA_ENVIRONMENT', 'sandbox'),

    'host' => 'https://openapi.m-pesa.com',
    'market' => 'vodacomTZN',
    'country' => 'TZN',
    'currency' => 'TZS',

    'api_key' => env('MPESA_API_KEY'),

    // Base64 public key from the developer portal, without PEM headers.
    'public_key' => env('MPESA_PUBLIC_KEY'),

    // Must match the trusted sources on the portal application.
    'origin' => env('MPESA_ORIGIN', '*'),

    // The organisation's shortcode. The sandbox accepts 000000.
    'service_provider_code' => env('MPESA_SERVICE_PROVIDER_CODE', '000000'),

    // Keep below the session lifetime set on the portal application.
    'session_minutes' => (int) env('MPESA_SESSION_MINUTES', 55),

    // A new session can take up to 30 seconds to become usable.
    'session_warmup_seconds' => (int) env('MPESA_SESSION_WARMUP_SECONDS', 30),

    // A C2B call waits while the customer enters their PIN.
    'timeout_seconds' => (int) env('MPESA_TIMEOUT_SECONDS', 120),

];
