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
        Schema::create('radius_nas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('router_id')->constrained()->onDelete('cascade');
            $table->string('nas_identifier')->unique();
            $table->string('nas_ip_address')->nullable();
            $table->string('nas_secret')->nullable();
            $table->string('nas_port')->nullable();
            $table->enum('nas_type', ['other', 'cisco', 'mikrotik'])->default('mikrotik');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('radius_nas');
    }
};
