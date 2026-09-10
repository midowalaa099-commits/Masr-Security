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
    | The local simulator must be explicitly enabled and is always disabled in
    | production. Missing credentials therefore disable online payments instead
    | of exposing a simulated checkout to real customers.
    */
    'sandbox_mode' => filter_var(env('PAYMOB_SANDBOX_MODE', false), FILTER_VALIDATE_BOOL),

];
