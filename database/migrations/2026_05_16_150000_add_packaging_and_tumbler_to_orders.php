<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->json('packaging')->nullable()->after('notes');
            $table->boolean('use_own_tumbler')->default(false)->after('packaging');
            $table->decimal('tumbler_discount_amount', 10, 2)->default(0)->after('use_own_tumbler');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->decimal('tumbler_discount', 8, 2)->default(0)->after('base_price');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['packaging', 'use_own_tumbler', 'tumbler_discount_amount']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('tumbler_discount');
        });
    }
};
