<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // For buy-X-get-Y bundles: lines added through the promo picker
            // carry a role so the discount calc can sum exactly the items
            // the customer chose as free, and so refunds/audit can see what
            // was sold as paid vs. free.
            $table->string('voucher_code', 64)->nullable()->after('notes');
            $table->string('voucher_role', 8)->nullable()->after('voucher_code');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['voucher_code', 'voucher_role']);
        });
    }
};
