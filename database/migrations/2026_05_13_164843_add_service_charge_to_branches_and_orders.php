<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->decimal('service_charge_rate', 5, 2)->default(0)->after('sst_enabled');
            $table->boolean('service_charge_enabled')->default(false)->after('service_charge_rate');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('service_charge_amount', 10, 2)->default(0)->after('sst_amount');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['service_charge_rate', 'service_charge_enabled']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('service_charge_amount');
        });
    }
};
