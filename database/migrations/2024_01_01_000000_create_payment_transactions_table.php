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
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            
            // M-Pesa specific fields
            $table->string('checkout_request_id')->unique();
            $table->string('merchant_request_id')->nullable();
            $table->string('phone_number');
            $table->decimal('amount', 10, 2);
            $table->string('account_reference');
            $table->string('transaction_desc');
            
            // Transaction status
            $table->enum('status', ['pending', 'completed', 'failed', 'expired'])->default('pending');
            
            // M-Pesa response fields
            $table->string('mpesa_receipt_number')->nullable();
            $table->timestamp('transaction_date')->nullable();
            $table->string('response_code')->nullable();
            $table->text('response_description')->nullable();
            $table->text('customer_message')->nullable();
            
            // Callback response fields
            $table->string('result_code')->nullable();
            $table->text('result_description')->nullable();
            $table->json('callback_data')->nullable();
            
            // Relations
            $table->foreignId('package_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('session_id')->nullable(); // Reference to hotspot session
            
            $table->timestamps();
            
            // Indexes
            $table->index(['status', 'created_at']);
            $table->index('phone_number');
            $table->index('mpesa_receipt_number');
            $table->index('session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
