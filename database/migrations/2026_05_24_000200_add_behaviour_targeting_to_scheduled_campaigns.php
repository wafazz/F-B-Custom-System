<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scheduled_campaigns', function (Blueprint $table) {
            // 'all' = every opted-in customer; 'inactive' = customers who hit
            // an inactivity threshold (reactivation / silent / low-activity).
            $table->string('audience', 20)->default('all')->after('url');
            $table->string('inactivity_signal', 20)->nullable()->after('audience'); // last_order | last_seen
            $table->unsignedSmallInteger('inactivity_days')->nullable()->after('inactivity_signal');
        });
    }

    public function down(): void
    {
        Schema::table('scheduled_campaigns', function (Blueprint $table) {
            $table->dropColumn(['audience', 'inactivity_signal', 'inactivity_days']);
        });
    }
};
