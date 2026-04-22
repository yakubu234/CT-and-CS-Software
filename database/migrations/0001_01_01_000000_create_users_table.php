<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('last_name')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('user_type', 20);# either admin, super_admin, or user
            $table->bigInteger('role_id')->nullable();
            $table->text('branch_id')->nullable();
            $table->integer('status')->comment('1 = active | 0 = active');# status of the user, e.g., 0 for inactive, 1 for active
            $table->string('profile_picture', 191)->nullable();
            $table->string('society_role', 191)->nullable();# any position in society, e.g., President, Secretary, etc.
            $table->boolean('society_exco')->default(false);# whether the user is part of the society executive committee
            $table->string('user_level', 191)->nullable();# user level e.g 0 for normal user, 1 for admin, 2 for super admin
            $table->boolean('branch_account')->default(false);# if user account is auto created for branch
            $table->boolean('is_verified')->default(false);# whether the user is verified
            $table->string('two_factor_code', 10)->nullable();
            $table->dateTime('two_factor_expires_at')->nullable();
            $table->integer('two_factor_code_count')->default(0);
            $table->string('otp', 10)->nullable();
            $table->dateTime('otp_expires_at')->nullable();
            $table->integer('otp_count')->default(0);
            $table->string('provider', 191)->nullable();
            $table->string('provider_id', 191)->nullable();
            $table->string('signature', 191)->nullable();
            $table->string('member_no', 191)->nullable();
            $table->string('designation', 191)->nullable();
            $table->rememberToken();
            $table->timestamps();

        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
