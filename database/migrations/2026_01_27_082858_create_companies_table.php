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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nombre Legal (IntSoftv)
            $table->string('nit')->nullable(); // Identificación Tributaria
            $table->string('email')->nullable(); // Correo de contacto principal
            $table->string('phone')->nullable(); // Teléfono Soporte
            $table->string('address')->nullable(); // Dirección Principal
            $table->string('city')->nullable(); // Ciudad
            
            // CONFIGURACIÓN TÉCNICA
            $table->string('domain')->nullable(); // Ej: intsoftv.com (para los correos automáticos)
            $table->string('website')->nullable(); 
            
            // IMAGEN
            $table->string('logo_path')->nullable(); // Ruta del logo
            $table->string('slogan')->nullable(); 
            
            // CONFIGURACIÓN DE FACTURACIÓN (Futuro)
            $table->string('currency_symbol')->default('$');
            $table->string('time_zone')->default('America/Bogota');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
