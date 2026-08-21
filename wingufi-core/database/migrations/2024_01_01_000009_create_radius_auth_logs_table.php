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
        Schema::connection('wingufi_core')->create('radius_auth_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('nas_id')->nullable()->constrained('radius_nas')->onDelete('set null');
            $table->foreignId('client_id')->nullable()->constrained('network_clients')->onDelete('set null');
            $table->string('username', 100);
            $table->string('client_ip', 50)->nullable();
            $table->string('client_mac', 50)->nullable();
            $table->string('request_type', 50);
            $table->enum('result', ['accepted', 'rejected', 'error']);
            $table->string('failure_reason', 255)->nullable();
            $table->timestamp('event_time');
            $table->string('request_id', 100)->nullable();
            $table->timestamps();

            $table->index(['username', 'tenant_id']);
            $table->index('result');
            $table->index('event_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('wingufi_core')->dropIfExists('radius_auth_logs');
    }
};
