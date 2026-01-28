<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('router_provisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('router_id')->constrained()->cascadeOnDelete();
            $table->string('method'); // e.g. 'address-list'
            $table->string('status')->index(); // 'success'|'failed'
            $table->text('message')->nullable(); // optional details or exception message
            $table->timestamps(); // ran_at = created_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('router_provisions');
    }
};
