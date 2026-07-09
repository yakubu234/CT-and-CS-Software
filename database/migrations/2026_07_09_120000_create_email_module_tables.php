<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('email_templates')) {
            Schema::create('email_templates', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('slug')->nullable()->unique();
                $table->string('category', 50);
                $table->string('description')->nullable();
                $table->string('subject');
                $table->longText('body');
                $table->boolean('status')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('email_campaigns')) {
            Schema::create('email_campaigns', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->unsignedBigInteger('template_id')->nullable()->index();
                $table->string('name');
                $table->string('audience_type', 50)->default('branch_members');
                $table->string('subject');
                $table->longText('body');
                $table->string('status', 30)->default('draft');
                $table->timestamp('scheduled_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->json('filters')->nullable();
                $table->json('meta')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('email_messages')) {
            Schema::create('email_messages', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('campaign_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->string('email')->nullable()->index();
                $table->string('recipient_name')->nullable();
                $table->string('subject');
                $table->longText('body');
                $table->string('mailer', 50)->nullable();
                $table->string('status', 20)->default('pending');
                $table->text('error_message')->nullable();
                $table->string('related_type')->nullable();
                $table->unsignedBigInteger('related_id')->nullable();
                $table->string('reference_key')->nullable()->index();
                $table->timestamp('scheduled_for')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('email_messages');
        Schema::dropIfExists('email_campaigns');
        Schema::dropIfExists('email_templates');
    }
};
