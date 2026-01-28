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
        Schema::table('installations', function (Blueprint $table) {
            // Agregamos la columna zone_id si no existe
            if (!Schema::hasColumn('installations', 'zone_id')) {
                $table->foreignId('zone_id')->nullable()->constrained()->after('router_id');
            }
        });

        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'zone_id')) {
                $table->foreignId('zone_id')->nullable()->constrained()->after('router_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('installations_and_services', function (Blueprint $table) {
            //
        });
    }
};
