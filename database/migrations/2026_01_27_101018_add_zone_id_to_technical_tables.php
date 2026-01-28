<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Agregar zone_id a INSTALACIONES
        Schema::table('installations', function (Blueprint $table) {
            $table->foreignId('zone_id')->nullable()->constrained()->after('router_id');
        });

        // 2. Agregar zone_id a SERVICIOS (El producto final)
        Schema::table('services', function (Blueprint $table) {
            $table->foreignId('zone_id')->nullable()->constrained()->after('router_id');
        });
    }

    public function down(): void
    {
        Schema::table('installations', function (Blueprint $table) {
            $table->dropForeign(['zone_id']);
            $table->dropColumn('zone_id');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropForeign(['zone_id']);
            $table->dropColumn('zone_id');
        });
    }
};