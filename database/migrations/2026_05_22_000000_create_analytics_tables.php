<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per "user is currently online" signal, updated on every
        // authenticated storefront request. Anything older than ~5 min is
        // treated as offline.
        Schema::create('user_presence', function (Blueprint $table): void {
            $table->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
            $table->timestamp('last_seen_at')->useCurrent();
            $table->string('user_agent', 255)->nullable();
            $table->index('last_seen_at');
        });

        // One row per PWA install event. user_id is nullable because installs
        // can happen pre-login (anonymous). device_fingerprint is a UUID we
        // generate client-side and persist in localStorage, so we can
        // dedupe re-installs on the same device.
        Schema::create('pwa_installs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('device_fingerprint', 64);
            $table->string('user_agent', 255)->nullable();
            $table->string('platform', 40)->nullable();
            $table->timestamp('installed_at')->useCurrent();
            $table->timestamp('last_active_at')->useCurrent();
            $table->timestamps();

            $table->unique('device_fingerprint');
            $table->index('installed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pwa_installs');
        Schema::dropIfExists('user_presence');
    }
};
