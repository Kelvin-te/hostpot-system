<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * FreeRADIUS's SQL "dynamic clients" loader needs the raw shared secret
     * to validate RADIUS requests; it cannot decrypt Laravel's
     * Crypt::encryptString() envelope stored in radius_secret_encrypted.
     *
     * This column exists solely so a dedicated, least-privilege MySQL user
     * (used only by FreeRADIUS's `sql` module) can read the plaintext
     * secret. It must never be selected by application code, returned in
     * an API response, or written to logs. See wingufi-core/FREERADIUS_SQL_CLIENTS.md.
     */
    public function up(): void
    {
        Schema::connection('wingufi_core')->table('radius_nas', function (Blueprint $table) {
            $table->text('radius_secret_plain')->nullable()->after('radius_secret_encrypted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('wingufi_core')->table('radius_nas', function (Blueprint $table) {
            $table->dropColumn('radius_secret_plain');
        });
    }
};
