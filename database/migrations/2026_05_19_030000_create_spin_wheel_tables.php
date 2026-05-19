<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spin_wheel_segments', function (Blueprint $table): void {
            $table->id();
            $table->string('label', 40);
            $table->string('color', 7)->default('#f59e0b');
            $table->unsignedInteger('weight')->default(1);
            $table->enum('prize_type', ['points', 'voucher', 'none']);
            $table->unsignedInteger('prize_points')->nullable();
            $table->foreignId('voucher_id')->nullable()->constrained('vouchers')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('spin_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('segment_id')->constrained('spin_wheel_segments')->cascadeOnDelete();
            $table->unsignedInteger('awarded_points')->default(0);
            $table->foreignId('voucher_claim_id')->nullable()->constrained('voucher_claims')->nullOnDelete();
            $table->timestamp('spun_at')->useCurrent();
            $table->timestamps();

            $table->index(['user_id', 'spun_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spin_attempts');
        Schema::dropIfExists('spin_wheel_segments');
    }
};
