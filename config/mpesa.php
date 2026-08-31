<?php

return [
    /*
    |--------------------------------------------------------------------------
    | M-Pesa Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for M-Pesa STK Push integration.
    | Make sure to set the appropriate environment variables in your .env file.
    |
    */

    'consumer_key' => env('MPESA_CONSUMER_KEY'),
    'consumer_secret' => env('MPESA_CONSUMER_SECRET'),

    'shortcode' => env('MPESA_SHORTCODE'), // Your paybill or till number
    'passkey' => env('MPESA_PASSKEY'), // STK Push passkey

    'callback_url' => env('MPESA_CALLBACK_URL'),

    // Tenant identifier embedded in STK Push AccountReference for shared paybill reconciliation.
    // Defaults to the client_id portion of the WinguFi Core API token (e.g. 'sterke-admin').
    'tenant_id' => env('TENANT_ID'),

    /*
    |--------------------------------------------------------------------------
    | Environment (sandbox|production)
    |--------------------------------------------------------------------------
    |
    | Safaricom's production (live) Daraja API requires the calling server's
    | public IP to be whitelisted on the Daraja portal for the go-live app.
    | Requests from a non-whitelisted IP are rejected at the edge/gateway
    | with an HTTP 400 and an EMPTY body (no JSON), before ever reaching the
    | OAuth service. If you are testing locally (e.g. on WAMP/XAMPP) and see
    | this exact symptom, set MPESA_ENV=sandbox in your .env and use the
    | sandbox consumer key/secret from your Daraja test app instead.
    |
    */
    'env' => env('MPESA_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | API URLs
    |--------------------------------------------------------------------------
    */
    'urls' => env('MPESA_ENV', 'production') === 'sandbox' ? [
        'base' => 'https://sandbox.safaricom.co.ke',
        'oauth' => 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials',
        'stk_push' => 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest',
        'stk_query' => 'https://sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query',
    ] : [
        'base' => 'https://api.safaricom.co.ke',
        'oauth' => 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials',
        'stk_push' => 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest',
        'stk_query' => 'https://api.safaricom.co.ke/mpesa/stkpushquery/v1/query',
    ],

    /*
    |--------------------------------------------------------------------------
    | Transaction Settings
    |--------------------------------------------------------------------------
    */
    'transaction_timeout' => env('MPESA_TRANSACTION_TIMEOUT', 300), // 5 minutes in seconds
    'query_interval' => env('MPESA_QUERY_INTERVAL', 10), // Query status every 10 seconds
    'max_query_attempts' => env('MPESA_MAX_QUERY_ATTEMPTS', 30), // Maximum query attempts
];
