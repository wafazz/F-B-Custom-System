<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 20)->unique();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->text('address');
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->json('operating_hours')->nullable();
            $table->unsignedInteger('pickup_radius_meters')->default(0);
            $table->decimal('sst_rate', 5, 2)->default(6.00);
            $table->boolean('sst_enabled')->default(true);
            $table->text('receipt_header')->nullable();
            $table->text('receipt_footer')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('logo')->nullable();
            $table->enum('status', ['active', 'closed', 'maintenance'])->default('active');
            $table->boolean('accepts_orders')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'accepts_orders']);
            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
