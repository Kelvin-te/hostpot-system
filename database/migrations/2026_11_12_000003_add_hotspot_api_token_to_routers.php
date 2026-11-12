<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('routers', 'hotspot_api_token')) {
            Schema::table('routers', function (Blueprint $table) {
                $table->string('hotspot_api_token', 64)->nullable()->after('identifier')->index();
            });
        }

        foreach (DB::table('routers')->whereNull('hotspot_api_token')->orWhere('hotspot_api_token', '')->cursor() as $router) {
            DB::table('routers')->where('id', $router->id)->update([
                'hotspot_api_token' => Str::random(48),
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('routers', 'hotspot_api_token')) {
            Schema::table('routers', function (Blueprint $table) {
                $table->dropIndex(['hotspot_api_token']);
                $table->dropColumn('hotspot_api_token');
            });
        }
    }
};
