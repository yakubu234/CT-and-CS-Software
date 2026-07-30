<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_backups', function (Blueprint $table): void {
            $table->timestamp('queued_at')->nullable()->after('status');
            $table->timestamp('processing_started_at')->nullable()->after('queued_at');
            $table->timestamp('downloaded_at')->nullable()->after('completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('data_backups', function (Blueprint $table): void {
            $table->dropColumn(['queued_at', 'processing_started_at', 'downloaded_at']);
        });
    }
};
