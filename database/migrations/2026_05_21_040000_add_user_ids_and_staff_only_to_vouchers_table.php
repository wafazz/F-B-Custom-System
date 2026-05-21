<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table): void {
            // When populated, only users whose id is in this list can claim.
            // Null/empty = no per-user restriction.
            $table->json('user_ids')->nullable()->after('birthday_months');
            // When true, only users with a non-"customer" role (staff) can claim.
            $table->boolean('staff_only')->default(false)->after('user_ids');
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table): void {
            $table->dropColumn(['user_ids', 'staff_only']);
        });
    }
};
