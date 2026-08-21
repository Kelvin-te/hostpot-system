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
        Schema::connection('wingufi_core')->create('radius_nas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('nasname', 100);
            $table->string('shortname', 50)->nullable();
            $table->string('type', 50);
            $table->string('identifier', 100)->unique();
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('radius_secret_encrypted');
            $table->integer('auth_port')->default(1812);
            $table->integer('acct_port')->default(1813);
            $table->integer('coa_port')->nullable();
            $table->string('management_ip', 50)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('nasname');
            $table->index('identifier');
            $table->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('wingufi_core')->dropIfExists('radius_nas');
    }
};
