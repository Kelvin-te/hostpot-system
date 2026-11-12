<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->enum('status', ['pending', 'completed', 'failed', 'expired', 'refunded'])->default('pending')->change();
            $table->timestamp('refunded_at')->nullable()->after('result_description');
            $table->string('refund_reason')->nullable()->after('refunded_at');
            $table->foreignId('refunded_by')->nullable()->after('refund_reason')->constrained('staff')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('refunded_by');
            $table->dropColumn(['refunded_at', 'refund_reason']);
            $table->enum('status', ['pending', 'completed', 'failed', 'expired'])->default('pending')->change();
        });
    }
};
