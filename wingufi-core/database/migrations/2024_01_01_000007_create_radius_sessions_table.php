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
        Schema::connection('wingufi_core')->create('radius_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('nas_id')->constrained('radius_nas')->onDelete('cascade');
            $table->foreignId('client_id')->nullable()->constrained('network_clients')->onDelete('set null');
            $table->string('username', 100);
            $table->string('acct_session_id', 100);
            $table->string('client_mac', 50)->nullable();
            $table->string('client_ip', 50)->nullable();
            $table->string('framed_ip', 50)->nullable();
            $table->timestamp('start_time');
            $table->timestamp('last_update_time');
            $table->timestamp('stop_time')->nullable();
            $table->integer('session_time')->nullable();
            $table->bigInteger('input_octets')->default(0);
            $table->bigInteger('output_octets')->default(0);
            $table->integer('input_packets')->default(0);
            $table->integer('output_packets')->default(0);
            $table->string('terminate_cause', 50)->nullable();
            $table->enum('status', ['active', 'stopped'])->default('active');
            $table->timestamps();

            $table->unique('acct_session_id');
            $table->index(['tenant_id', 'nas_id', 'acct_session_id']);
            $table->index('username');
            $table->index('client_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('wingufi_core')->dropIfExists('radius_sessions');
    }
};
