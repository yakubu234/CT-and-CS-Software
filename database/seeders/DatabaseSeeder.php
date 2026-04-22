<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            SettingsSeeder::class, 
            RolesTableSeeder::class,
            UsersSeeder::class,
            SuperAdminSeeder::class,
            CurrencySeeder::class,
            DesignationSeeder::class,
            SavingsProductsSeeder::class,
            CustomFieldsTableSeeder::class,
            TransactionCategoriesSeeder::class,
            SmsTemplateSeeder::class,
        ]);

    }
}
