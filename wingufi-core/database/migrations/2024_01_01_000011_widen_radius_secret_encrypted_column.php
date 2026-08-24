<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The column was originally created as a default-length VARCHAR (191, per
     * Schema::defaultStringLength() in AppServiceProvider), which is too short
     * to hold a real Crypt::encryptString() payload (~230+ chars). Widen it to
     * TEXT so any secret length can be stored safely. Raw SQL is used because
     * doctrine/dbal (required by Blueprint::change()) is not installed.
     */
    public function up(): void
    {
        DB::connection('wingufi_core')->statement(
            'ALTER TABLE `radius_nas` MODIFY `radius_secret_encrypted` TEXT NOT NULL'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::connection('wingufi_core')->statement(
            'ALTER TABLE `radius_nas` MODIFY `radius_secret_encrypted` VARCHAR(191) NOT NULL'
        );
    }
};
