<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('point_rewards', function (Blueprint $table): void {
            $table->renameColumn('points', 'points_cost');
            $table->renameColumn('max_total_claims', 'stock');
        });

        Schema::table('point_rewards', function (Blueprint $table): void {
            $table->foreignId('product_id')->nullable()->after('points_cost')->constrained()->nullOnDelete();
            $table->enum('kind', ['product', 'merchandise'])->default('merchandise')->after('product_id');
        });

        Schema::table('point_reward_claims', function (Blueprint $table): void {
            $table->renameColumn('points', 'points_spent');
        });

        Schema::table('point_reward_claims', function (Blueprint $table): void {
            $table->string('pickup_code', 12)->nullable()->unique()->after('points_spent');
            $table->timestamp('fulfilled_at')->nullable()->after('claimed_at');
        });
    }

    public function down(): void
    {
        Schema::table('point_reward_claims', function (Blueprint $table): void {
            $table->dropColumn(['pickup_code', 'fulfilled_at']);
            $table->renameColumn('points_spent', 'points');
        });

        Schema::table('point_rewards', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('product_id');
            $table->dropColumn('kind');
            $table->renameColumn('stock', 'max_total_claims');
            $table->renameColumn('points_cost', 'points');
        });
    }
};
