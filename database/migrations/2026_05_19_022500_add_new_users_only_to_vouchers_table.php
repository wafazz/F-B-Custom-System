<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table): void {
            // When true, only customers who haven't placed any orders yet
            // can claim the voucher (welcome / first-time offers).
            $table->boolean('new_users_only')->default(false)->after('combo_ids');
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table): void {
            $table->dropColumn('new_users_only');
        });
    }
};
