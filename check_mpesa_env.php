<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$k = config('mpesa.consumer_key');
$s = config('mpesa.consumer_secret');
$sc = config('mpesa.shortcode');
$pk = config('mpesa.passkey');

function info($label, $v) {
    if ($v === null || $v === '') {
        echo "$label: EMPTY\n";
        return;
    }
    $len = strlen($v);
    $ws = preg_match('/\s/', $v) ? 'yes' : 'no';
    echo "$label: len=$len sample=" . substr($v, 0, 4) . '...' . substr($v, -2) . " has_whitespace=$ws\n";
}

info('consumer_key', $k);
info('consumer_secret', $s);
info('shortcode', $sc);
info('passkey', $pk);
echo "APP_ENV=" . config('app.env') . "\n";
