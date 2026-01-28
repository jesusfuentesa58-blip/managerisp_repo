<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('services', function (Blueprint $table) {
        $table->string('address')->nullable()->after('router_id');
        $table->string('neighborhood')->nullable()->after('address');
        $table->string('coordinates')->nullable()->after('neighborhood');
    });
}

public function down(): void
{
    Schema::table('services', function (Blueprint $table) {
        $table->dropColumn(['address', 'neighborhood', 'coordinates']);
    });
}
};
