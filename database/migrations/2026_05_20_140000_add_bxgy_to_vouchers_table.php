<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            // Promote discount_type from enum to plain string so we can keep
            // adding modes without an ALTER ENUM dance on MySQL. Values are
            // validated in StoreOrderRequest + the Filament resource.
            $table->string('discount_type', 32)->change();

            // Buy X Get Y free — qualifying scope reuses product_ids / combo_ids,
            // free scope below is independent:
            //   null               → free items must come from product_ids/combo_ids (same scope)
            //   []                 → any item in the cart is eligible (free pool = full cart)
            //   [id, id, ...]      → cross-sell, free items must be one of these
            $table->unsignedTinyInteger('bxgy_buy_qty')->nullable()->after('combo_ids');
            $table->unsignedTinyInteger('bxgy_free_qty')->nullable()->after('bxgy_buy_qty');
            $table->json('bxgy_free_product_ids')->nullable()->after('bxgy_free_qty');
            $table->json('bxgy_free_combo_ids')->nullable()->after('bxgy_free_product_ids');
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn([
                'bxgy_buy_qty',
                'bxgy_free_qty',
                'bxgy_free_product_ids',
                'bxgy_free_combo_ids',
            ]);
        });
    }
};
