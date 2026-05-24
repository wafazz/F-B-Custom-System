<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');                 // admin-facing label
            $table->string('title');                // push title
            $table->text('body');                   // push body ({name} placeholder)
            $table->string('url')->default('/');    // deep-link target
            $table->string('frequency', 20)->default('once'); // once | daily
            $table->dateTime('scheduled_at')->nullable();      // for "once"
            $table->time('run_time')->nullable();              // for "daily"
            $table->boolean('is_active')->default(true);
            $table->dateTime('last_sent_at')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'frequency']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_campaigns');
    }
};
