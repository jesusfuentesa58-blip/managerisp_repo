<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            $table->boolean('provisioned_address_list')->default(false)->after('suspension_method');
            $table->timestamp('provisioned_at')->nullable()->after('provisioned_address_list');
        });
    }

    public function down(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            $table->dropColumn(['provisioned_address_list', 'provisioned_at']);
        });
    }
};
