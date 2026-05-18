<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table): void {
            // Path relative to storage/app/public/ — e.g. "vouchers/banners/xyz.jpg".
            // Rendered via /storage/{path} on the storefront.
            $table->string('banner_image')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table): void {
            $table->dropColumn('banner_image');
        });
    }
};
