<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_backups', function (Blueprint $table): void {
            $table->timestamp('scheduled_for')->nullable()->after('queued_at');
            $table->timestamp('upload_started_at')->nullable()->after('processing_started_at');
            $table->unsignedSmallInteger('drive_destination_count')->default(0)->after('upload_started_at');
            $table->unsignedSmallInteger('drive_completed_count')->default(0)->after('drive_destination_count');
        });
    }

    public function down(): void
    {
        Schema::table('data_backups', function (Blueprint $table): void {
            $table->dropColumn([
                'scheduled_for',
                'upload_started_at',
                'drive_destination_count',
                'drive_completed_count',
            ]);
        });
    }
};
