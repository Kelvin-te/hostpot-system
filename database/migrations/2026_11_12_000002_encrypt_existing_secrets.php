<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Encrypt existing plaintext secrets now that the models cast them as
     * 'encrypted'. Values that already decrypt cleanly are left alone.
     */
    public function up(): void
    {
        $columns = [
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
            'routers' => ['password'],
        ];

        foreach ($columns as $table => $fields) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $fields = array_values(array_filter($fields, fn ($f) => Schema::hasColumn($table, $f)));
            if (empty($fields)) {
                continue;
            }

            foreach (DB::table($table)->select(array_merge(['id'], $fields))->cursor() as $row) {
                $updates = [];

                foreach ($fields as $field) {
                    $value = $row->{$field};

                    if (!is_string($value) || $value === '') {
                        continue;
                    }

                    try {
                        Crypt::decryptString($value);
                        continue; // already encrypted
                    } catch (\Throwable) {
                        // plaintext — encrypt below
                    }

                    $updates[$field] = Crypt::encryptString($value);
                }

                if ($updates) {
                    DB::table($table)->where('id', $row->id)->update($updates);
                }
            }
        }
    }

    public function down(): void
    {
        // Irreversible by design — secrets stay encrypted.
    }
};
