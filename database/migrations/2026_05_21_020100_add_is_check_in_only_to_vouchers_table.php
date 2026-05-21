<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table): void {
            // When true, the voucher is hidden from the public /vouchers list
            // and can only land in a wallet via DailyCheckInService.
            $table->boolean('is_check_in_only')->default(false)->after('is_spin_only');
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table): void {
            $table->dropColumn('is_check_in_only');
        });
    }
};
