<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('point_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['earn', 'redeem', 'adjustment', 'expire', 'refund']);
            $table->integer('points');
            $table->integer('balance_after');
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('membership_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->decimal('min_lifetime_spend', 12, 2)->default(0);
            $table->decimal('earn_multiplier', 4, 2)->default(1.00);
            $table->string('color', 16)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('sort_order');
        });

        Schema::create('customer_tier', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
            $table->foreignId('membership_tier_id')->constrained()->cascadeOnDelete();
            $table->decimal('lifetime_spend', 12, 2)->default(0);
            $table->timestamp('achieved_at')->nullable();
            $table->timestamps();

            $table->index('membership_tier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_tier');
        Schema::dropIfExists('membership_tiers');
        Schema::dropIfExists('point_transactions');
    }
};
