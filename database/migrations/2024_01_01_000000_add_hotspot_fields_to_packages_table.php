<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->decimal('bandwidth_upload', 8, 2)->nullable()->after('price')->comment('Upload bandwidth in Mbps');
            $table->decimal('bandwidth_download', 8, 2)->nullable()->after('bandwidth_upload')->comment('Download bandwidth in Mbps');
            $table->integer('session_timeout')->nullable()->after('bandwidth_download')->comment('Session timeout in hours');
            $table->integer('idle_timeout')->nullable()->after('session_timeout')->comment('Idle timeout in minutes');
            $table->integer('shared_users')->nullable()->default(1)->after('idle_timeout')->comment('Number of shared users allowed');
            $table->string('rate_limit', 50)->nullable()->after('shared_users')->comment('Custom rate limit string');
            $table->integer('validity_days')->nullable()->after('rate_limit')->comment('Package validity in days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn([
                'bandwidth_upload',
                'bandwidth_download', 
                'session_timeout',
                'idle_timeout',
                'shared_users',
                'rate_limit',
                'validity_days'
            ]);
        });
    }
};
