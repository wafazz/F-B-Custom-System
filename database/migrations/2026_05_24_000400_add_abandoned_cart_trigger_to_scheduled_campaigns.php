<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scheduled_campaigns', function (Blueprint $table) {
            // 'schedule' = the cron-driven campaigns; 'abandoned_cart' = the
            // event-driven cart reminder, managed here but fired by its job.
            $table->string('trigger_type', 20)->default('schedule')->after('name');
            $table->unsignedSmallInteger('delay_minutes')->nullable()->after('run_time');
        });

        // Seed the single abandoned-cart reminder so the feature is editable
        // out of the box (admins tune the copy / delay / on-off).
        DB::table('scheduled_campaigns')->insert([
            'name' => 'Abandoned cart reminder',
            'trigger_type' => 'abandoned_cart',
            'title' => 'Your coffee is waiting ☕',
            'body' => 'You left items in your cart — complete your order & enjoy fresh bakes 🥐',
            'url' => '/',
            'delay_minutes' => 15,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('scheduled_campaigns')->where('trigger_type', 'abandoned_cart')->delete();
        Schema::table('scheduled_campaigns', function (Blueprint $table) {
            $table->dropColumn(['trigger_type', 'delay_minutes']);
        });
    }
};
