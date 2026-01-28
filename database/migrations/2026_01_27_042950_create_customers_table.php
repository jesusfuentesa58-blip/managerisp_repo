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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('document_type', ['CC', 'NIT', 'CE', 'PP'])->default('CC');
            $table->string('document_number')->unique(); // La cédula será única
            $table->string('phone');
            $table->string('email')->nullable();
            // Nace como prospecto por defecto
            $table->enum('status', ['prospecto', 'activo', 'suspendido', 'retirado'])->default('prospecto');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
