<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modifier_groups', function (Blueprint $table) {
            $table->boolean('allow_quantity')->default(false)->after('max_select');
        });
    }

    public function down(): void
    {
        Schema::table('modifier_groups', function (Blueprint $table) {
            $table->dropColumn('allow_quantity');
        });
    }
};
