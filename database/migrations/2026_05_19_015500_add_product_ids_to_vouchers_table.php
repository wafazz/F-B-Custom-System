<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table): void {
            // null/empty = applies to every line item; otherwise the discount
            // is applied only to the subtotal of matching products.
            $table->json('product_ids')->nullable()->after('birthday_months');
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table): void {
            $table->dropColumn('product_ids');
        });
    }
};
