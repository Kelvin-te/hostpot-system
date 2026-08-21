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
        Schema::create('radius_accounting', function (Blueprint $table) {
            $table->id();
            $table->foreignId('router_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('authorization_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('hotspot_session_id')->nullable()->constrained()->onDelete('set null');
            $table->string('username')->nullable()->index();
            $table->string('nas_ip_address')->nullable();
            $table->string('nas_port')->nullable();
            $table->string('nas_identifier')->nullable();
            $table->string('session_id')->nullable()->index();
            $table->string('framed_ip_address')->nullable();
            $table->string('calling_station_id')->nullable();
            $table->string('called_station_id')->nullable();
            $table->timestamp('start_time')->nullable();
            $table->timestamp('last_update')->nullable();
            $table->timestamp('stop_time')->nullable();
            $table->integer('session_time')->default(0);
            $table->bigInteger('input_octets')->default(0);
            $table->bigInteger('output_octets')->default(0);
            $table->string('terminate_cause')->nullable();
            $table->enum('status', ['start', 'interim-update', 'stop'])->default('start')->index();
            $table->json('accounting_attributes')->nullable();
            $table->timestamps();

            $table->index(['session_id', 'status']);
            $table->index(['username', 'status']);
            $table->index(['start_time', 'stop_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('radius_accounting');
    }
};
