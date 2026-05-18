<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('point_rewards', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('banner_image')->nullable();
            $table->unsignedInteger('points');
            $table->unsignedInteger('max_claims_per_user')->default(1);
            $table->unsignedInteger('max_total_claims')->nullable();
            $table->unsignedInteger('claimed_count')->default(0);
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->enum('status', ['active', 'paused', 'expired'])->default('active');
            $table->timestamps();

            $table->index(['status', 'valid_from', 'valid_until']);
        });

        Schema::create('point_reward_claims', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('point_reward_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('points');
            $table->timestamp('claimed_at')->useCurrent();
            $table->timestamps();

            $table->index(['user_id', 'point_reward_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_reward_claims');
        Schema::dropIfExists('point_rewards');
    }
};
