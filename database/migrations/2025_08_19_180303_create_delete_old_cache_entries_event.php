<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            CREATE EVENT IF NOT EXISTS delete_old_cache_entries
            ON SCHEDULE EVERY 1 HOUR
            DO
              DELETE FROM cachings WHERE created_at < NOW() - INTERVAL 1 HOUR
        ");
        DB::statement("SET GLOBAL event_scheduler = ON;");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP EVENT IF EXISTS delete_old_cache_entries");
    }
};
