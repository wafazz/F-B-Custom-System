<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('opened_by_user_id')->constrained('users');
            $table->timestamp('opened_at');
            $table->decimal('opening_float', 10, 2)->default(0);
            $table->foreignId('closed_by_user_id')->nullable()->constrained('users');
            $table->timestamp('closed_at')->nullable();
            $table->decimal('expected_cash', 10, 2)->nullable();
            $table->decimal('counted_cash', 10, 2)->nullable();
            $table->decimal('variance', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'closed_at']);
        });

        Schema::create('pos_cash_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained('pos_shifts')->cascadeOnDelete();
            $table->enum('type', ['cash_in', 'cash_out']);
            $table->decimal('amount', 10, 2);
            $table->string('reason', 255);
            $table->foreignId('recorded_by_user_id')->constrained('users');
            $table->timestamps();

            $table->index('shift_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('shift_id')->nullable()->after('branch_id')
                ->constrained('pos_shifts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shift_id');
        });

        Schema::dropIfExists('pos_cash_movements');
        Schema::dropIfExists('pos_shifts');
    }
};
