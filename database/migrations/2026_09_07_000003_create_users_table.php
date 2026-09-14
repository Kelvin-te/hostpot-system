<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable()->unique();
            $table->string('mac_address')->nullable()->index();
            $table->decimal('total_spent', 10, 2)->default(0);
            $table->unsignedInteger('total_sessions')->default(0);
            $table->unsignedBigInteger('total_data_used')->default(0);
            $table->timestamp('last_session_at')->nullable();
            $table->decimal('wallet_balance', 10, 2)->default(0);
            $table->enum('status', ['active', 'suspended', 'banned'])->default('active');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->foreignId('router_id')->nullable()->index();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};