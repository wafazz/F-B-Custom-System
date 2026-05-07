<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->unique()->after('email');
            $table->timestamp('phone_verified_at')->nullable()->after('email_verified_at');
            $table->date('date_of_birth')->nullable()->after('phone_verified_at');
            $table->enum('gender', ['male', 'female', 'other'])->nullable()->after('date_of_birth');
            $table->string('photo')->nullable()->after('gender');
            $table->string('referral_code', 16)->nullable()->unique()->after('photo');
            $table->foreignId('referred_by')->nullable()->after('referral_code')->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('preferred_branch_id')->nullable()->after('referred_by');
            $table->boolean('marketing_consent')->default(false)->after('preferred_branch_id');
            $table->boolean('whatsapp_consent')->default(true)->after('marketing_consent');
            $table->boolean('push_consent')->default(true)->after('whatsapp_consent');
            $table->string('locale', 5)->default('en')->after('push_consent');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referred_by']);
            $table->dropColumn([
                'phone',
                'phone_verified_at',
                'date_of_birth',
                'gender',
                'photo',
                'referral_code',
                'referred_by',
                'preferred_branch_id',
                'marketing_consent',
                'whatsapp_consent',
                'push_consent',
                'locale',
                'deleted_at',
            ]);
        });
    }
};
