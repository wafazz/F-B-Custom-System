<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membership_tiers', function (Blueprint $table): void {
            $table->string('badge_image')->nullable()->after('color');
            $table->json('perks')->nullable()->after('badge_image');
        });
    }

    public function down(): void
    {
        Schema::table('membership_tiers', function (Blueprint $table): void {
            $table->dropColumn(['badge_image', 'perks']);
        });
    }
};
