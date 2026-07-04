<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ensure failed member transactions can be rolled back atomically.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE savings_accounts ENGINE = InnoDB');
        DB::statement('ALTER TABLE transactions ENGINE = InnoDB');
    }

    /**
     * Keep the ledger transactional; reverting to MyISAM would risk partial writes.
     */
    public function down(): void
    {
        //
    }
};
