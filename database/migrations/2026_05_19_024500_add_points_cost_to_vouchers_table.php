<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table): void {
            // null = free to claim; > 0 = costs that many loyalty points to
            // redeem (turns the voucher into a reward in the customer UI).
            $table->unsignedInteger('points_cost')->nullable()->after('new_users_only');
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table): void {
            $table->dropColumn('points_cost');
        });
    }
};
