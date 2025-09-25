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

    'environment' => env('MPESA_ENVIRONMENT', 'production'), // 'sandbox' or 'production'

    'consumer_key' => env('MPESA_CONSUMER_KEY', 'vbiS87DmgC60wt0hqF7JcXZjtH1rowOj25yEZhDqVeXcaCgF'),
    'consumer_secret' => env('MPESA_CONSUMER_SECRET', 'NOCOOn2ZhXAqILPwl7DhUYrjyKnPA0PQaMQ1qjhTrIziZuwN1QGwM9rcIUOKpDN1'),

    'shortcode' => env('MPESA_SHORTCODE', '4140993'), // Your paybill or till number
    'passkey' => env('MPESA_PASSKEY', '7593a57c40743af7f4d26c1310bbfa0255fbe525b0a3cf4a0b5674ec3bfeaaa2'), // STK Push passkey

    'callback_url' => env('MPESA_CALLBACK_URL', env('APP_URL') . '/api/mpesa/callback'),

    /*
    |--------------------------------------------------------------------------
    | API URLs
    |--------------------------------------------------------------------------
    */
    'urls' => [
        'sandbox' => [
            'base' => 'https://sandbox.safaricom.co.ke',
            'oauth' => 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials',
            'stk_push' => 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest',
            'stk_query' => 'https://sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query',
        ],
        'production' => [
            'base' => 'https://api.safaricom.co.ke',
            'oauth' => 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials',
            'stk_push' => 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest',
            'stk_query' => 'https://api.safaricom.co.ke/mpesa/stkpushquery/v1/query',
        ]
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
