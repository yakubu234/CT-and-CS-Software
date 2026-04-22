<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currencies = [
            [
                'id' => 4,
                'name' => 'NGN',
                'exchange_rate' => 1.000000,
                'base_currency' => 1,
                'status' => 1,
                'created_at' => '2024-10-24 15:50:24',
                'updated_at' => '2024-10-24 15:50:24',
            ],
            [
                'id' => 5,
                'name' => 'USD',
                'exchange_rate' => 1.000000,
                'base_currency' => 1,
                'status' => 1,
                'created_at' => null,
                'updated_at' => null,
            ],
            [
                'id' => 6,
                'name' => 'EUR',
                'exchange_rate' => 0.850000,
                'base_currency' => 0,
                'status' => 1,
                'created_at' => null,
                'updated_at' => null,
            ],
            [
                'id' => 7,
                'name' => 'INR',
                'exchange_rate' => 74.500000,
                'base_currency' => 0,
                'status' => 1,
                'created_at' => null,
                'updated_at' => null,
            ]
        ];

        foreach ($currencies as $currency) {
            DB::table('currencies')->updateOrInsert(
                ['name' => $currency['name']],  // Check for existing records based on `name`
                $currency  // Insert or update the currency record
            );
        }
    }
}
