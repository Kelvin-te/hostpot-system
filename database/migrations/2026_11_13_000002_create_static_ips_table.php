<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('static_ips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('router_id')->constrained()->onDelete('cascade');
            $table->string('ip_address', 45)->index();
            $table->string('mac_address', 17)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('package_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['free', 'assigned', 'reserved'])->default('free')->index();
            $table->string('comment')->nullable();
            $table->boolean('synced_to_router')->default(false);
            $table->timestamps();

            $table->unique(['router_id', 'ip_address']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('static_ips');
    }
};
