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
        Schema::connection('wingufi_core')->create('radius_accounting', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('nas_id')->constrained('radius_nas')->onDelete('cascade');
            $table->foreignId('client_id')->nullable()->constrained('network_clients')->onDelete('set null');
            $table->string('username', 100);
            $table->string('acct_session_id', 100);
            $table->enum('acct_status_type', ['Start', 'Interim-Update', 'Stop']);
            $table->integer('session_time')->nullable();
            $table->bigInteger('input_octets')->nullable();
            $table->bigInteger('output_octets')->nullable();
            $table->integer('input_packets')->nullable();
            $table->integer('output_packets')->nullable();
            $table->string('client_ip', 50)->nullable();
            $table->string('client_mac', 50)->nullable();
            $table->string('framed_ip', 50)->nullable();
            $table->timestamp('event_time');
            $table->string('terminate_cause', 50)->nullable();
            $table->json('raw_attributes')->nullable();
            $table->timestamps();

            $table->index('acct_session_id');
            $table->index('acct_status_type');
            $table->index('event_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('wingufi_core')->dropIfExists('radius_accounting');
    }
};
