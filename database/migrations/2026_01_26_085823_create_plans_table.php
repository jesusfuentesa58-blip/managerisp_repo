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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('price'); // Valor en miles sin decimales
            
            // Configuración MikroTik (PPPoE)
            $table->string('pppoe_profile_name');
            $table->string('upload_speed');   // Ej: 10M
            $table->string('download_speed'); // Ej: 50M
            
            // Impuestos y Descuentos
            $table->enum('tax_type', ['included', 'added'])->default('included');
            $table->unsignedBigInteger('discount_value')->default(0);
            $table->integer('discount_duration_months')->default(0);
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
