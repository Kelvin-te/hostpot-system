<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'mpesa_consumer_key')) {
                $table->string('mpesa_consumer_key')->nullable()->after('mpesa_api_key');
            }
            if (!Schema::hasColumn('settings', 'mpesa_consumer_secret')) {
                $table->string('mpesa_consumer_secret')->nullable()->after('mpesa_consumer_key');
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (Schema::hasColumn('settings', 'mpesa_consumer_key')) {
                $table->dropColumn('mpesa_consumer_key');
            }
            if (Schema::hasColumn('settings', 'mpesa_consumer_secret')) {
                $table->dropColumn('mpesa_consumer_secret');
            }
        });
    }
};
