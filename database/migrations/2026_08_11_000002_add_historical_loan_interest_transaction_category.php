<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('transaction_categories')->updateOrInsert(
            ['name' => 'Historical Loan Interest'],
            [
                'related_to' => 'cr',
                'status' => 1,
                'note' => 'Historical loan interest received before detailed repayment records were captured.',
                'type_to_transaction' => 'expenses',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('transaction_categories')
            ->where('name', 'Historical Loan Interest')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('transactions')
                    ->whereColumn('transactions.detail_id', 'transaction_categories.id');
            })
            ->delete();
    }
};
