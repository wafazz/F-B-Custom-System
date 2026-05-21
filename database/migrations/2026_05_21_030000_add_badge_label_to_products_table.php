<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            // Admin-controlled visibility badge — e.g. "Feature",
            // "Best Seller", or any free-text label like "New" / "Limited".
            // Shown as a pill on the product card.
            $table->string('badge_label', 30)->nullable()->after('is_featured');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('badge_label');
        });
    }
};
