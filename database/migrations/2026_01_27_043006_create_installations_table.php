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
        Schema::create('installations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')->constrained();
            $table->foreignId('technician_id')->constrained('users'); // Técnico asignado
            $table->enum('status', ['programada', 'en_progreso', 'finalizada', 'fallida'])->default('programada');
            $table->dateTime('scheduled_at'); // Fecha programada
            $table->json('event_log')->nullable(); // Aquí guardaremos los eventos del diario técnico
            $table->float('signal_dbm')->nullable(); // Señal de fibra
            $table->string('onu_serial')->nullable(); // Serial del equipo
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installations');
    }
};
