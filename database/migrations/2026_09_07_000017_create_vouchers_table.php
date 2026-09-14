<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('package_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['active', 'used', 'expired', 'disabled'])->default('active');
            $table->timestamp('used_at')->nullable();
            $table->string('used_by_mac')->nullable();
            $table->string('used_by_ip')->nullable();
            $table->foreignId('session_id')->nullable()->index();
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['code', 'status']);
            $table->index(['status', 'expires_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};