<?php

namespace Database\Seeders;

use App\Models\TransactionCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TransactionCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $data = [
            ['name' => 'Savings', 'related_to' => 'cr', 'status' => 1, 'note' => null, 'created_at' => '2025-01-20 22:07:05', 'updated_at' => '2025-01-20 22:07:05', 'type_to_transaction' => 'transaction'],
            ['name' => 'Deposit', 'related_to' => 'cr', 'status' => 1, 'note' => null, 'created_at' => '2025-01-20 22:07:26', 'updated_at' => '2025-01-20 22:07:26', 'type_to_transaction' => 'transaction'],
            ['name' => 'Authentication', 'related_to' => 'cr', 'status' => 1, 'note' => null, 'created_at' => '2025-01-20 22:07:46', 'updated_at' => '2025-01-20 22:07:46', 'type_to_transaction' => 'transaction'],
            ['name' => 'Shares', 'related_to' => 'cr', 'status' => 1, 'note' => null, 'created_at' => '2025-01-20 22:08:16', 'updated_at' => '2025-01-20 22:08:16', 'type_to_transaction' => 'transaction'],
            ['name' => 'Withdraw of Savings', 'related_to' => 'dr', 'status' => 1, 'note' => null, 'created_at' => '2025-01-20 22:08:46', 'updated_at' => '2025-01-20 22:08:46', 'type_to_transaction' => 'transaction'],
            ['name' => 'Withdraw of Deposit', 'related_to' => 'dr', 'status' => 1, 'note' => null, 'created_at' => '2025-01-20 22:09:02', 'updated_at' => '2025-01-20 22:09:02', 'type_to_transaction' => 'transaction'],
            ['name' => 'Withdraw of Authentication', 'related_to' => 'dr', 'status' => 1, 'note' => null, 'created_at' => '2025-01-20 22:09:16', 'updated_at' => '2025-01-20 22:09:16', 'type_to_transaction' => 'transaction'],
            ['name' => 'Withdraw of shares', 'related_to' => 'dr', 'status' => 1, 'note' => null, 'created_at' => '2025-01-20 22:09:57', 'updated_at' => '2025-01-20 22:09:57', 'type_to_transaction' => 'transaction'],
            ['name' => 'Withdraw of Asset', 'related_to' => 'dr', 'status' => 1, 'note' => null, 'created_at' => '2025-01-21 17:54:56', 'updated_at' => '2025-01-21 17:54:56', 'type_to_transaction' => 'expenses'],
            ['name' => 'Purchase of Stationary', 'related_to' => 'dr', 'status' => 1, 'note' => null, 'created_at' => '2025-01-21 17:55:34', 'updated_at' => '2025-01-21 17:55:34', 'type_to_transaction' => 'expenses'],
            ['name' => 'Loan to Member', 'related_to' => 'dr', 'status' => 1, 'note' => null, 'created_at' => '2025-01-21 17:56:13', 'updated_at' => '2025-01-21 17:56:13', 'type_to_transaction' => 'expenses'],
            ['name' => 'Fares and Transport', 'related_to' => 'dr', 'status' => 1, 'note' => null, 'created_at' => '2025-01-21 17:56:50', 'updated_at' => '2025-01-21 17:56:50', 'type_to_transaction' => 'expenses'],
            ['name' => 'Wages and Salary', 'related_to' => 'dr', 'status' => 1, 'note' => null, 'created_at' => '2025-01-21 17:58:50', 'updated_at' => '2025-01-21 17:58:50', 'type_to_transaction' => 'expenses'],
            ['name' => 'Meeting Expenses', 'related_to' => 'dr', 'status' => 1, 'note' => null, 'created_at' => '2025-01-21 17:59:19', 'updated_at' => '2025-01-21 17:59:19', 'type_to_transaction' => 'expenses'],
            ['name' => 'Entertainment Expenses', 'related_to' => 'dr', 'status' => 1, 'note' => null, 'created_at' => '2025-01-21 18:00:30', 'updated_at' => '2025-01-21 18:00:30', 'type_to_transaction' => 'expenses'],
            ['name' => 'Building Fund', 'related_to' => 'cr', 'status' => 1, 'note' => null, 'created_at' => '2025-01-21 18:01:21', 'updated_at' => '2025-01-21 18:01:21', 'type_to_transaction' => 'expenses'],
            ['name' => 'sales of Stationary', 'related_to' => 'cr', 'status' => 1, 'note' => null, 'created_at' => '2025-01-21 18:02:02', 'updated_at' => '2025-01-21 18:02:02', 'type_to_transaction' => 'expenses'],
            ['name' => 'Administrative Charges', 'related_to' => 'cr', 'status' => 1, 'note' => null, 'created_at' => '2025-01-21 18:02:30', 'updated_at' => '2025-01-21 18:02:30', 'type_to_transaction' => 'expenses'],
        ];

        foreach ($data as $category) {
            TransactionCategory::updateOrCreate(
                ['name' => $category['name']],
                [
                    'related_to' => $category['related_to'],
                    'status' => 1,
                    'note' => null,
                    'type_to_transaction' => $category['type_to_transaction'],
                ]
            );
        }
    }
}
