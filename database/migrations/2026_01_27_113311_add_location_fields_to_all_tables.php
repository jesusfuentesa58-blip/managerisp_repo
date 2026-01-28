<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Agregar ubicación al CLIENTE (Dirección Principal/Fiscal)
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'address')) {
                $table->string('address')->nullable()->after('email');
            }
            if (!Schema::hasColumn('customers', 'neighborhood')) {
                $table->string('neighborhood')->nullable()->after('address');
            }
            if (!Schema::hasColumn('customers', 'coordinates')) {
                $table->string('coordinates')->nullable()->after('neighborhood');
            }
        });

        // 2. Agregar ubicación al SERVICIO (Dirección de Instalación Final)
        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'address')) {
                $table->string('address')->nullable()->after('zone_id');
            }
            if (!Schema::hasColumn('services', 'neighborhood')) {
                $table->string('neighborhood')->nullable()->after('address');
            }
            if (!Schema::hasColumn('services', 'coordinates')) {
                $table->string('coordinates')->nullable()->after('neighborhood');
            }
        });

        // 3. Asegurar ubicación en SOLICITUDES (Por si falta alguna)
        Schema::table('service_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('service_requests', 'address')) {
                $table->string('address')->nullable()->after('customer_id');
            }
            if (!Schema::hasColumn('service_requests', 'neighborhood')) {
                $table->string('neighborhood')->nullable()->after('address');
            }
            if (!Schema::hasColumn('service_requests', 'coordinates')) {
                $table->string('coordinates')->nullable()->after('neighborhood');
            }
        });
    }

    public function down(): void
    {
        // Solo eliminamos si existen
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['address', 'neighborhood', 'coordinates']);
        });
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['address', 'neighborhood', 'coordinates']);
        });
        // Nota: No borramos las de service_requests en down() por seguridad, 
        // ya que esas suelen ser críticas.
    }
};