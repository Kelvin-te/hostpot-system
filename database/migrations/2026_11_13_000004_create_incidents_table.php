<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('router_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 50)->default('router_down');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('affected_subscribers')->default(0);
            $table->enum('status', ['open', 'acknowledged', 'resolved'])->default('open')->index();
            $table->timestamp('opened_at');
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['router_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
