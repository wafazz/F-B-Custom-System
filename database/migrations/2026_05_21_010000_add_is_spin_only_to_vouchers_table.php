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
            // and can only land in a wallet via SpinService (spin-wheel prize).
            $table->boolean('is_spin_only')->default(false)->after('new_users_only');
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table): void {
            $table->dropColumn('is_spin_only');
        });
    }
};
