<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resellers', function (Blueprint $table) {
            $table->id();
            $table->string('business_name');
            $table->string('contact_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('brand_name')->nullable()->comment('Portal white-label name');
            $table->string('logo_path')->nullable();
            // Own-router mode: reseller's MikroTik + own M-Pesa till
            $table->json('mpesa_credentials')->nullable()->comment('Encrypted per-reseller M-Pesa overrides');
            $table->enum('status', ['active', 'suspended'])->default('active');
            $table->timestamps();
        });

        Schema::table('routers', function (Blueprint $table) {
            $table->foreignId('reseller_id')->nullable()->after('id')->constrained('resellers')->nullOnDelete();
        });

        Schema::table('packages', function (Blueprint $table) {
            $table->foreignId('reseller_id')->nullable()->after('id')->constrained('resellers')->nullOnDelete();
            $table->decimal('wholesale_price', 10, 2)->nullable()->after('price')->comment('What reseller owes ISP per sale');
        });

        Schema::table('staff', function (Blueprint $table) {
            $table->foreignId('reseller_id')->nullable()->after('role')->constrained('resellers')->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('reseller_id')->nullable()->after('connection_type')->constrained('resellers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reseller_id');
        });
        Schema::table('staff', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reseller_id');
        });
        Schema::table('packages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reseller_id');
            $table->dropColumn('wholesale_price');
        });
        Schema::table('routers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reseller_id');
        });
        Schema::dropIfExists('resellers');
    }
};
