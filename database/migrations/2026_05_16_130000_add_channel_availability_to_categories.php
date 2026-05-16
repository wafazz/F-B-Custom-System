<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('available_web')->default(true)->after('status');
            $table->boolean('available_pwa')->default(true)->after('available_web');
            $table->boolean('available_pos')->default(true)->after('available_pwa');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['available_web', 'available_pwa', 'available_pos']);
        });
    }
};
