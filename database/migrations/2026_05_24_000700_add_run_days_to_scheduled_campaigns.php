<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scheduled_campaigns', function (Blueprint $table) {
            // Weekday filter for daily broadcasts (0=Sun … 6=Sat). Null/empty
            // = every day; pick days for peak-day targeting (e.g. Fri–Sun).
            $table->json('run_days')->nullable()->after('run_time');
        });
    }

    public function down(): void
    {
        Schema::table('scheduled_campaigns', function (Blueprint $table) {
            $table->dropColumn('run_days');
        });
    }
};
