<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Paymob
    |--------------------------------------------------------------------------
    |
    | Credentials come from the Paymob dashboard. All values are environment
    | driven and are never exposed to the frontend.
    |
    */

    'base_url' => env('PAYMOB_API_URL', 'https://accept.paymob.com'),
    'secret_key' => env('PAYMOB_SECRET_KEY'),
    'public_key' => env('PAYMOB_PUBLIC_KEY'),
    'api_key' => env('PAYMOB_API_KEY'),
    'card_integration_id' => (int) env('PAYMOB_CARD_INTEGRATION_ID', 0),
    'wallet_integration_id' => (int) env('PAYMOB_WALLET_INTEGRATION_ID', 0),
    'hmac_secret' => env('PAYMOB_HMAC_SECRET'),
    'expiration' => (int) env('PAYMOB_EXPIRATION', 3600),
    'currency' => 'EGP',

    /*
    | When the secret key is not configured the gateway falls back to a local
    | sandbox that simulates the Paymob callbacks. This keeps the application
    | fully runnable before real credentials are provided. Explicitly set
    | PAYMOB_SANDBOX_MODE=true to force the sandbox even with credentials.
    */
    'sandbox_mode' => filter_var(env('PAYMOB_SANDBOX_MODE', false), FILTER_VALIDATE_BOOL),

];
