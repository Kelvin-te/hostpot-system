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
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('price');
            $table->foreignId('router_id')->constrained()->onDelete('cascade');
            $table->decimal('bandwidth_upload', 8, 2)->nullable()->comment('Upload bandwidth in Mbps');
            $table->decimal('bandwidth_download', 8, 2)->nullable()->comment('Download bandwidth in Mbps');
            $table->integer('session_timeout')->nullable()->comment('Session timeout in hours');
            $table->integer('idle_timeout')->nullable()->comment('Idle timeout in minutes');
            $table->integer('shared_users')->nullable()->default(1)->comment('Number of shared users allowed');
            $table->string('rate_limit', 50)->nullable()->comment('Custom rate limit string');
            $table->integer('validity_minutes')->nullable()->comment('Package validity in minutes');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['name', 'router_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
