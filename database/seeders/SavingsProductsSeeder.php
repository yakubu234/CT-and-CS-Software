<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SavingsProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'id' => 7,
                'name' => 'SAVINGS',
                'account_number_prefix' => 'SAV',
                'starting_account_number' => 10063,
                'currency_id' => 4,
                'interest_rate' => 3.50,
                'interest_method' => 'daily_outstanding_balance',
                'interest_period' => 1,
                'interest_posting_period' => null,
                'min_bal_interest_rate' => 1.00,
                'allow_withdraw' => 1,
                'minimum_account_balance' => 0.00,
                'minimum_deposit_amount' => 0.00,
                'maintenance_fee' => 0.00,
                'maintenance_fee_posting_period' => null,
                'status' => 1,
                'created_at' => '2024-11-10 12:26:56',
                'updated_at' => '2025-06-10 16:29:07',
                'default_account' => 1,
                'type' => 'SAVINGS',
            ],
            [
                'id' => 9,
                'name' => 'SHARES',
                'account_number_prefix' => 'SH',
                'starting_account_number' => 10039,
                'currency_id' => 4,
                'interest_rate' => 3.50,
                'interest_method' => 'daily_outstanding_balance',
                'interest_period' => 1,
                'interest_posting_period' => null,
                'min_bal_interest_rate' => 1.00,
                'allow_withdraw' => 1,
                'minimum_account_balance' => 0.00,
                'minimum_deposit_amount' => 0.00,
                'maintenance_fee' => 0.00,
                'maintenance_fee_posting_period' => null,
                'status' => 1,
                'created_at' => '2024-11-10 12:26:56',
                'updated_at' => '2025-05-31 01:36:05',
                'default_account' => 0,
                'type' => 'SHARES',
            ],
            [
                'id' => 10,
                'name' => 'AUTHENTICATION',
                'account_number_prefix' => 'AUTH',
                'starting_account_number' => 10039,
                'currency_id' => 4,
                'interest_rate' => 3.50,
                'interest_method' => 'daily_outstanding_balance',
                'interest_period' => 1,
                'interest_posting_period' => null,
                'min_bal_interest_rate' => 1.00,
                'allow_withdraw' => 1,
                'minimum_account_balance' => 0.00,
                'minimum_deposit_amount' => 0.00,
                'maintenance_fee' => 0.00,
                'maintenance_fee_posting_period' => null,
                'status' => 1,
                'created_at' => '2024-11-10 12:26:56',
                'updated_at' => '2025-05-31 01:36:05',
                'default_account' => 0,
                'type' => 'AUTHENTICATION',
            ],
            [
                'id' => 11,
                'name' => 'DEPOSIT',
                'account_number_prefix' => 'DEP',
                'starting_account_number' => 10039,
                'currency_id' => 4,
                'interest_rate' => 3.50,
                'interest_method' => 'daily_outstanding_balance',
                'interest_period' => 1,
                'interest_posting_period' => null,
                'min_bal_interest_rate' => 1.00,
                'allow_withdraw' => 1,
                'minimum_account_balance' => 0.00,
                'minimum_deposit_amount' => 0.00,
                'maintenance_fee' => 0.00,
                'maintenance_fee_posting_period' => null,
                'status' => 1,
                'created_at' => '2024-11-10 12:26:56',
                'updated_at' => '2025-05-31 01:36:05',
                'default_account' => 0,
                'type' => 'DEPOSIT',
            ],
        ];

        foreach ($products as $product) {
            DB::table('savings_products')->updateOrInsert(
                ['id' => $product['id']],  // Check for existing records based on `id`
                $product  // Insert or update the savings product record
            );
        }
    }
}
