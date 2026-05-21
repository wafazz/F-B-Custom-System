<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Configuration rows — one per streak day. Admin defines Day 1, 2, …
        // up to settings.max_days. reward_type = points | voucher.
        Schema::create('daily_check_in_rewards', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('day_number');
            $table->string('label', 60)->nullable();
            $table->enum('reward_type', ['points', 'voucher']);
            $table->unsignedInteger('points')->nullable();
            $table->foreignId('voucher_id')->nullable()->constrained('vouchers')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('day_number');
        });

        // Singleton settings row (id = 1) — max streak length + skip behavior.
        Schema::create('daily_check_in_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('max_days')->default(7);
            $table->boolean('reset_on_skip')->default(true);
            $table->timestamps();
        });

        // One row per user per calendar day they checked in.
        Schema::create('daily_check_ins', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('check_in_date');
            $table->unsignedSmallInteger('day_number_awarded');
            $table->enum('reward_type', ['points', 'voucher']);
            $table->unsignedInteger('awarded_points')->default(0);
            $table->foreignId('voucher_claim_id')->nullable()->constrained('voucher_claims')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'check_in_date']);
            $table->index(['user_id', 'check_in_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_check_ins');
        Schema::dropIfExists('daily_check_in_settings');
        Schema::dropIfExists('daily_check_in_rewards');
    }
};
