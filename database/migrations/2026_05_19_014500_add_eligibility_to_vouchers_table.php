<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table): void {
            // null = any tier; otherwise an array of membership_tiers.id
            $table->json('tier_ids')->nullable()->after('branch_ids');
            // null = any month; otherwise an array of ints 1..12
            $table->json('birthday_months')->nullable()->after('tier_ids');
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table): void {
            $table->dropColumn(['tier_ids', 'birthday_months']);
        });
    }
};
