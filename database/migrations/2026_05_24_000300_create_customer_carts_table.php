<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->json('items')->nullable();          // lightweight snapshot for the reminder
            $table->unsignedInteger('item_count')->default(0);
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->dateTime('notified_at')->nullable(); // set once we've reminded; cleared by re-build
            $table->timestamps();

            $table->index('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_carts');
    }
};
