<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            ['id' => 1, 'name' => 'mail_type', 'value' => 'smtp'],
            ['id' => 2, 'name' => 'backend_direction', 'value' => 'ltr'],
            ['id' => 3, 'name' => 'language', 'value' => 'English'],
            ['id' => 4, 'name' => 'email_verification', 'value' => 'disabled'],
            ['id' => 5, 'name' => 'allow_singup', 'value' => 'yes'],
            ['id' => 6, 'name' => 'starting_member_no', 'value' => '20241037'],
            ['id' => 7, 'name' => 'company_name', 'value' => 'ORE-OLUWAPO PHASE II ILARO MULTIPURPOSE UNION LIMITED'],
            ['id' => 8, 'name' => 'site_title', 'value' => 'ORE-OLUWAPO MULTIPURPOSE UNION LIMITED'],
            ['id' => 9, 'name' => 'phone', 'value' => ''],
            ['id' => 10, 'name' => 'email', 'value' => 'abiola@test.com'],
            ['id' => 11, 'name' => 'timezone', 'value' => 'Africa/Lagos'],
            ['id' => 12, 'name' => 'logo', 'value' => 'logo.png'],
            ['id' => 13, 'name' => 'currency_position', 'value' => 'left'],
            ['id' => 14, 'name' => 'date_format', 'value' => 'Y-m-d'],
            ['id' => 15, 'name' => 'time_format', 'value' => '12'],
            ['id' => 16, 'name' => 'member_signup', 'value' => '0'],
            ['id' => 17, 'name' => 'email_2fa_status', 'value' => '0'],
            ['id' => 18, 'name' => 'default_branch_name', 'value' => 'ORE-OLUWAPO PHASE II ILARO '],
            ['id' => 19, 'name' => 'address', 'value' => ''],
            ['id' => 20, 'name' => 'favicon', 'value' => 'file_1727951452.png'],
            ['id' => 21, 'name' => 'own_account_transfer_fee_type', 'value' => 'percentage'],
            ['id' => 22, 'name' => 'own_account_transfer_fee', 'value' => '0'],
            ['id' => 23, 'name' => 'other_account_transfer_fee_type', 'value' => 'percentage'],
            ['id' => 24, 'name' => 'other_account_transfer_fee', 'value' => '0'],
            ['id' => 25, 'name' => 'loan_prefix', 'value' => 'DEFLOAN'],
            ['id' => 26, 'name' => 'loan_starting_number', 'value' => '10001'],
            ['id' => 27, 'name' => 'mail_type', 'value' => 'smtp'],
            ['id' => 28, 'name' => 'backend_direction', 'value' => 'ltr'],
            ['id' => 29, 'name' => 'language', 'value' => 'English'],
            ['id' => 30, 'name' => 'email_verification', 'value' => 'disabled'],
            ['id' => 31, 'name' => 'allow_singup', 'value' => 'yes'],
            ['id' => 32, 'name' => 'starting_member_no', 'value' => '20241037'],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->updateOrInsert(
                ['name' => $setting['name']],  // Check for existing records based on `name`
                ['value' => $setting['value'], 'updated_at' => now()]  // Update the value if exists, otherwise insert
            );
        }
    }
}

