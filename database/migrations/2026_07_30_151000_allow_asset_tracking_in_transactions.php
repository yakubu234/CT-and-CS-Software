<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE transactions MODIFY tracking_id "
                . "ENUM('income','expenses','loan','loan_repayment','regular','assets') "
                . "NULL DEFAULT 'regular' COMMENT 'Used to track the transaction source'"
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::table('transactions')->where('tracking_id', 'assets')->update(['tracking_id' => 'expenses']);
            DB::statement(
                "ALTER TABLE transactions MODIFY tracking_id "
                . "ENUM('income','expenses','loan','loan_repayment','regular') "
                . "NULL DEFAULT 'regular' COMMENT 'Used to track the type of transaction for easy access and filtering'"
            );
        }
    }
};
