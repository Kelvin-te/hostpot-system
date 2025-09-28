<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'stripe' => [
        'model' => App\Models\User::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
    ],

    'bkash' => [
        'merchant_id' => env('BKASH_MERCHANT_ID'),
    ],

    'vintex' => [
        'api_url' => env('VINTEX_API_URL', 'https://sms.vintextechnologies.com/api/sendMessage'),
        'email' => env('VINTEX_EMAIL', 'admin@vintextechnologies.com'),
        'bearer_token' => env('VINTEX_BEARER_TOKEN', 'MGNlYzE4NzlkNTEzZGZmNGI2MWY4MDY2ODlkM2NlNmU='),
        'sender_id' => env('VINTEX_SENDER_ID', 'STERKE'),
    ],

];
