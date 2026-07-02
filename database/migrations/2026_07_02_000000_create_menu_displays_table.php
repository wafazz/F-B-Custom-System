<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_displays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 60);
            $table->string('heading', 80)->nullable();
            $table->string('token', 80)->unique();
            $table->boolean('is_active')->default(true);
            $table->string('layout', 20)->default('grid');
            $table->unsignedSmallInteger('seconds_per_slide')->default(8);
            $table->boolean('show_price')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index('is_active');
        });

        Schema::create('menu_display_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_display_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);

            $table->unique(['menu_display_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_display_category');
        Schema::dropIfExists('menu_displays');
    }
};
