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
        Schema::connection('wingufi_core')->create('network_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->uuid('uuid')->unique();
            $table->string('username', 100);
            $table->string('display_name', 255)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('phone', 50)->nullable();
            $table->enum('status', ['active', 'suspended', 'disabled'])->default('active');
            $table->string('password_hash', 255)->nullable();
            $table->string('password_type', 50)->nullable();
            $table->string('mac_address', 50)->nullable();
            $table->string('static_ip', 50)->nullable();
            $table->text('notes')->nullable();
            $table->string('external_id', 100)->nullable();
            $table->string('external_type', 100)->nullable();
            $table->string('source_system', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['username', 'tenant_id']);
            $table->index(['mac_address', 'tenant_id']);
            $table->index('status');
            $table->index(['external_id', 'source_system']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('wingufi_core')->dropIfExists('network_clients');
    }
};
