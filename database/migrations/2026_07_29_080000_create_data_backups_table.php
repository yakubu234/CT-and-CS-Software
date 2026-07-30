<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_backups', function (Blueprint $table): void {
            $table->id();
            // Legacy installations use an integer users.id, so avoid an
            // unsigned-bigint foreign key that would fail on those databases.
            $table->unsignedInteger('created_by')->nullable()->index();
            $table->string('trigger', 20)->default('manual');
            $table->string('format', 10);
            $table->json('modules');
            $table->string('status', 20)->default('processing');
            $table->string('file_name')->nullable();
            $table->string('storage_path')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('google_drive_file_id')->nullable();
            $table->text('google_drive_url')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_backups');
    }
};
