<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->boolean('auto_print_labels')->default(false);
            $table->unsignedTinyInteger('label_copies')->default(1);
            $table->string('label_size', 10)->default('58mm');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['auto_print_labels', 'label_copies', 'label_size']);
        });
    }
};
