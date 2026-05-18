<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table): void {
            // Combos are first-class line items at checkout. When set
            // alongside (or instead of) product_ids, the voucher applies
            // to those combo lines too.
            $table->json('combo_ids')->nullable()->after('product_ids');
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table): void {
            $table->dropColumn('combo_ids');
        });
    }
};
