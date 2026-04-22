<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sms_automation_rules')) {
            return;
        }

        Schema::create('sms_automation_rules', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('template_id')->nullable()->index();
            $table->string('name');
            $table->string('event', 50);
            $table->boolean('status')->default(true);
            $table->string('schedule_time', 5)->nullable();
            $table->unsignedTinyInteger('day_of_month')->nullable();
            $table->json('filters')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_automation_rules');
    }
};
