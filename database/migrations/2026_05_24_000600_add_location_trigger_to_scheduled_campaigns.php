<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scheduled_campaigns', function (Blueprint $table) {
            // For trigger_type='location': the target outlet + how close a
            // customer must be (metres). delay_minutes is reused as the
            // per-customer cooldown so they aren't pinged repeatedly nearby.
            $table->unsignedBigInteger('branch_id')->nullable()->after('url');
            $table->unsignedInteger('radius_meters')->nullable()->after('branch_id');
        });
    }

    public function down(): void
    {
        Schema::table('scheduled_campaigns', function (Blueprint $table) {
            $table->dropColumn(['branch_id', 'radius_meters']);
        });
    }
};
