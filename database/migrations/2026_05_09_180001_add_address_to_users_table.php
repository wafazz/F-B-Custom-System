<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('address_line', 255)->nullable()->after('photo');
            $table->string('city', 80)->nullable()->after('address_line');
            $table->string('postcode', 10)->nullable()->after('city');
            $table->string('state', 60)->nullable()->after('postcode');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['address_line', 'city', 'postcode', 'state']);
        });
    }
};
