<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Encrypted values are longer than plaintext — widen secret columns to TEXT.
     */
    public function up(): void
    {
        $columns = [
            'routers' => ['password'],
            'settings' => [
                'mail_password',
                'sms_api_key',
                'mpesa_api_key',
                'mpesa_consumer_key',
                'mpesa_consumer_secret',
                'mpesa_passkey',
                'card_payment_api_key',
                'smtp_password',
            ],
        ];

        foreach ($columns as $table => $fields) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table, $fields) {
                foreach ($fields as $field) {
                    if (Schema::hasColumn($table, $field)) {
                        $blueprint->text($field)->nullable()->change();
                    }
                }
            });
        }
    }

    public function down(): void
    {
        // Keep TEXT — safe superset.
    }
};
