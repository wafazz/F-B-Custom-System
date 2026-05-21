<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            // Order that proves the customer actually bought this product.
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('rating'); // 1-5
            $table->text('comment')->nullable();
            $table->boolean('is_hidden')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'product_id']);
            $table->index(['product_id', 'is_hidden']);
        });

        Schema::create('branch_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->boolean('is_hidden')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'branch_id']);
            $table->index(['branch_id', 'is_hidden']);
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->decimal('avg_rating', 3, 2)->default(0)->after('is_featured');
            $table->unsignedInteger('reviews_count')->default(0)->after('avg_rating');
        });

        Schema::table('branches', function (Blueprint $table): void {
            $table->decimal('avg_rating', 3, 2)->default(0);
            $table->unsignedInteger('reviews_count')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table): void {
            $table->dropColumn(['avg_rating', 'reviews_count']);
        });
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn(['avg_rating', 'reviews_count']);
        });
        Schema::dropIfExists('branch_reviews');
        Schema::dropIfExists('product_reviews');
    }
};
