<?php

return [
    "sandbox"           => env("BKASH_SANDBOX", true),
    "bkash_app_key"     => env("BKASH_APP_KEY", ""),
    "bkash_app_secret"  => env("BKASH_APP_SECRET", ""),
    "bkash_username"    => env("BKASH_USERNAME", ""),
    "bkash_password"    => env("BKASH_PASSWORD", ""),
    "callbackURL"       => env("BKASH_CALLBACK_URL", ""),
    'timezone'          => env("APP_TIMEZONE", ""),
    'currency'          => env("CURRENCY", ""),
];
