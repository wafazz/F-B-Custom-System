<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('sku', 50)->unique();
            $table->decimal('base_price', 10, 2);
            $table->boolean('sst_applicable')->default(true);
            $table->string('image')->nullable();
            $table->json('gallery')->nullable();
            $table->unsignedSmallInteger('calories')->nullable();
            $table->unsignedSmallInteger('prep_time_minutes')->default(5);
            $table->enum('status', ['active', 'hidden', 'discontinued'])->default('active');
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'is_featured']);
            $table->index(['category_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
