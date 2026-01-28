<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jurisdictions', function (Blueprint $table) {
            $table->id();

            // Identidad
            $table->string('name');
            $table->string('code')->unique();

            // Geografía
            $table->string('department');
            $table->string('city');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Facturación
            $table->unsignedTinyInteger('billing_day');       // 1–28 recomendado
            $table->unsignedTinyInteger('due_day');           // 1–28 recomendado
            $table->unsignedTinyInteger('suspension_day');    // 1–28 recomendado
            $table->unsignedTinyInteger('suspend_after_invoices')->default(1);

            // Automatizaciones
            $table->boolean('auto_generate_invoices')->default(true);
            $table->boolean('auto_send_invoices')->default(true);
            $table->boolean('auto_suspend_services')->default(true);
            $table->boolean('auto_send_sms')->default(false);
            $table->boolean('auto_send_email')->default(true);

            // Estado
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jurisdictions');
    }
};
