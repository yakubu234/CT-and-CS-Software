<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('email_smtp_accounts')) {
            Schema::create('email_smtp_accounts', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('host');
                $table->unsignedInteger('port')->default(587);
                $table->string('encryption', 20)->nullable();
                $table->string('username')->nullable();
                $table->text('password')->nullable();
                $table->string('from_address');
                $table->string('from_name')->nullable();
                $table->unsignedInteger('hourly_limit')->default(100);
                $table->unsignedInteger('sent_in_window')->default(0);
                $table->timestamp('window_started_at')->nullable();
                $table->timestamp('paused_until')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('email_preferences')) {
            Schema::create('email_preferences', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->boolean('email_enabled')->default(true);
                $table->timestamp('paused_until')->nullable();
                $table->string('reason')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->unique(['branch_id', 'user_id']);
            });
        }

        if (Schema::hasTable('email_messages') && ! Schema::hasColumn('email_messages', 'smtp_account_id')) {
            Schema::table('email_messages', function (Blueprint $table): void {
                $table->unsignedBigInteger('smtp_account_id')->nullable()->after('mailer')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('email_messages') && Schema::hasColumn('email_messages', 'smtp_account_id')) {
            Schema::table('email_messages', function (Blueprint $table): void {
                $table->dropColumn('smtp_account_id');
            });
        }

        Schema::dropIfExists('email_preferences');
        Schema::dropIfExists('email_smtp_accounts');
    }
};
