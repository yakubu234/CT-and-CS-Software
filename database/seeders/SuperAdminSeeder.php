<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->updateOrInsert(
            ['email' => 'superadmin@okiki.test'],
            [
                'name' => 'Super',
                'last_name' => 'Admin',
                'email' => 'superadmin@okiki.test',
                'email_verified_at' => now(),
                'password' => Hash::make('Admin@12345'),
                'user_type' => 'super_admin',
                'role_id' => null,
                'branch_id' => null,
                'status' => 1,
                'profile_picture' => 'default.png',
                'society_role' => null,
                'society_exco' => 0,
                'date_added_as_exco' => null,
                'former_exco' => 0,
                'date_removed_as_exco' => null,
                'user_level' => 'super_admin',
                'branch_account' => 0,
                'is_verified' => 1,
                'two_factor_code' => null,
                'two_factor_expires_at' => null,
                'two_factor_code_count' => 0,
                'otp' => null,
                'otp_expires_at' => null,
                'otp_count' => 0,
                'provider' => null,
                'provider_id' => null,
                'signature' => null,
                'member_no' => null,
                'designation' => 'Super Admin',
                'former_designation' => null,
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
