<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE savings_accounts MODIFY account_number VARCHAR(50) NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE savings_accounts MODIFY account_number VARCHAR(30) NOT NULL');
    }
};
