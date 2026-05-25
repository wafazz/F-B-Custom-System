<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scheduled_campaigns', function (Blueprint $table) {
            // false = nudge once on the day they hit N days inactive (drip);
            // true  = keep nudging while they stay N+ days inactive, throttled
            //         by inactivity_cooldown_days so it isn't daily spam.
            $table->boolean('inactivity_repeat')->default(false)->after('inactivity_days');
            $table->unsignedSmallInteger('inactivity_cooldown_days')->nullable()->after('inactivity_repeat');
        });
    }

    public function down(): void
    {
        Schema::table('scheduled_campaigns', function (Blueprint $table) {
            $table->dropColumn(['inactivity_repeat', 'inactivity_cooldown_days']);
        });
    }
};
