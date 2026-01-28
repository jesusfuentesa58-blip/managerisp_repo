<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            // Agregamos la columna 'coordinates' si no existe
            if (!Schema::hasColumn('service_requests', 'coordinates')) {
                $table->string('coordinates')->nullable()->after('address');
            }
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropColumn('coordinates');
        });
    }
};