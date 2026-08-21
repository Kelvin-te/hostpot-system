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
        Schema::connection('wingufi_core')->create('network_authorizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained('network_clients')->onDelete('cascade');
            $table->foreignId('package_id')->constrained('network_packages')->onDelete('cascade');
            $table->string('source_type', 50);
            $table->string('source_id', 100);
            $table->string('username', 100);
            $table->enum('status', ['active', 'expired', 'revoked', 'pending'])->default('active');
            $table->timestamp('starts_at');
            $table->timestamp('expires_at');
            $table->integer('session_timeout')->nullable();
            $table->integer('download_speed')->nullable();
            $table->integer('upload_speed')->nullable();
            $table->bigInteger('data_limit_bytes')->nullable();
            $table->bigInteger('data_used_bytes')->default(0);
            $table->integer('simultaneous_sessions')->default(1);
            $table->string('external_id', 100)->nullable();
            $table->string('external_type', 100)->nullable();
            $table->string('source_system', 100)->nullable();
            $table->timestamps();
            $table->timestamp('revoked_at')->nullable();

            $table->index(['username', 'tenant_id']);
            $table->index('client_id');
            $table->index('status');
            $table->index('expires_at');
            $table->unique(['external_id', 'source_system']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('wingufi_core')->dropIfExists('network_authorizations');
    }
};
