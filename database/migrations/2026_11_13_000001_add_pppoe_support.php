<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('connection_type', 20)->default('hotspot')->after('status')->index();
            $table->foreignId('package_id')->nullable()->after('connection_type')->constrained('packages')->nullOnDelete();
            $table->string('pppoe_username', 64)->nullable()->unique()->after('package_id');
            $table->text('pppoe_password')->nullable()->after('pppoe_username');
        });

        Schema::table('hotspot_sessions', function (Blueprint $table) {
            $table->string('connection_type', 20)->default('hotspot')->after('status')->index();
            // PPPoE sessions have no built-in expiry — expiry is enforced by
            // removing the /ppp/secret, so allow null here.
            $table->timestamp('expires_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('hotspot_sessions', function (Blueprint $table) {
            $table->dropIndex(['connection_type']);
            $table->dropColumn('connection_type');
            $table->timestamp('expires_at')->nullable(false)->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['pppoe_username']);
            $table->dropIndex(['connection_type']);
            $table->dropConstrainedForeignId('package_id');
            $table->dropColumn(['connection_type', 'pppoe_username', 'pppoe_password']);
        });
    }
};
