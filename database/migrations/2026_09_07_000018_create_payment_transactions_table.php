<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('checkout_request_id')->unique();
            $table->string('merchant_request_id')->nullable();
            $table->string('phone_number');
            $table->decimal('amount', 10, 2);
            $table->string('account_reference');
            $table->string('transaction_desc');
            $table->enum('status', ['pending', 'completed', 'failed', 'expired'])->default('pending');
            $table->enum('gateway', ['mpesa', 'paystack', 'manual', 'wallet'])->default('mpesa');
            $table->enum('type', ['subscription', 'one_time', 'voucher', 'topup'])->default('one_time');
            $table->string('mpesa_receipt_number')->nullable();
            $table->timestamp('transaction_date')->nullable();
            $table->string('response_code')->nullable();
            $table->text('response_description')->nullable();
            $table->text('customer_message')->nullable();
            $table->string('result_code')->nullable();
            $table->text('result_description')->nullable();
            $table->json('callback_data')->nullable();
            $table->foreignId('package_id')->nullable()->index();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('session_id')->nullable();
            $table->foreignId('voucher_id')->nullable()->index();
            $table->foreignId('router_id')->nullable()->index();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('phone_number');
            $table->index('mpesa_receipt_number');
            $table->index('session_id');
            $table->index(['gateway', 'status', 'created_at']);
            $table->index(['router_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};