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
        Schema::connection('wingufi_core')->create('network_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('code', 100);
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->integer('download_speed')->nullable();
            $table->integer('upload_speed')->nullable();
            $table->integer('session_timeout')->nullable();
            $table->integer('validity_seconds')->nullable();
            $table->bigInteger('data_limit_bytes')->nullable();
            $table->integer('simultaneous_sessions')->default(1);
            $table->decimal('price', 10, 2)->nullable();
            $table->char('currency', 3)->nullable();
            $table->string('external_id', 100)->nullable();
            $table->string('external_type', 100)->nullable();
            $table->string('source_system', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['code', 'tenant_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('wingufi_core')->dropIfExists('network_packages');
    }
};
