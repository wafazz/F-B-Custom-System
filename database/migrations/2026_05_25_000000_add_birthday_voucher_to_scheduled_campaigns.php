<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scheduled_campaigns', function (Blueprint $table) {
            // For audience='birthday': the optional voucher we nudge customers
            // to claim during their birthday month. Once they claim it they drop
            // out of the audience, so the reminders stop for them.
            $table->unsignedBigInteger('voucher_id')->nullable()->after('radius_meters');
        });
    }

    public function down(): void
    {
        Schema::table('scheduled_campaigns', function (Blueprint $table) {
            $table->dropColumn('voucher_id');
        });
    }
};
