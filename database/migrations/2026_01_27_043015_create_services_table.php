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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained();
            $table->foreignId('plan_id')->constrained();
            $table->foreignId('router_id')->constrained(); // MikroTik asignado
            $table->string('pppoe_user')->unique();
            $table->string('pppoe_password');
            $table->string('remote_address'); // IP Estática OBLIGATORIA
            $table->enum('status', ['activo', 'suspendido', 'retirado'])->default('activo');
            $table->date('installation_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
