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

    /*
    |--------------------------------------------------------------------------
    | API URLs
    |--------------------------------------------------------------------------
    */
    'urls' => [
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
