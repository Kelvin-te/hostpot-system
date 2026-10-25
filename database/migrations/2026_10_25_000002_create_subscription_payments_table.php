<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('KES');
            $table->string('payment_method'); // mpesa, card, bank_transfer
            $table->string('transaction_reference')->nullable();
            $table->string('mpesa_receipt_number')->nullable();
            $table->string('mpesa_phone_number')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->timestamp('payment_date')->nullable();
            $table->timestamp('expires_at')->nullable(); // When this payment covers until
            $table->integer('months_paid')->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['subscription_id', 'status']);
            $table->index(['company_id', 'status']);
            $table->index('payment_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
