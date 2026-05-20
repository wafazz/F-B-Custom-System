<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            // Daily time window (clock-time only, recurring every day).
            // Distinct from valid_from / valid_until which are absolute
            // datetimes. Both nullable; "open all day" when both null.
            // Wrap-around (e.g. 22:00 -> 02:00) handled in the service.
            $table->time('valid_from_time')->nullable()->after('valid_until');
            $table->time('valid_until_time')->nullable()->after('valid_from_time');
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn(['valid_from_time', 'valid_until_time']);
        });
    }
};
