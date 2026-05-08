<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(0);
            $table->unsignedInteger('low_threshold')->default(5);
            $table->boolean('is_available')->default(true);
            $table->boolean('track_quantity')->default(false);
            $table->timestamp('last_restocked_at')->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'product_id']);
            $table->index(['branch_id', 'is_available']);
            $table->index(['quantity', 'low_threshold']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_stock_id')->constrained('branch_stock')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['restock', 'sale', 'wastage', 'adjustment', 'transfer']);
            $table->integer('quantity_change');
            $table->integer('quantity_after');
            $table->string('reason')->nullable();
            $table->morphs('reference');
            $table->timestamps();

            $table->index(['branch_stock_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('branch_stock');
    }
};
