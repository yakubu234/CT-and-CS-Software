<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'id' => 1,
                'name' => 'Abiola',
                'email' => 'abiola@test.com',
                'user_type' => 'admin',
                'role_id' => null,
                'branch_id' => null,
                'status' => 1,
                'profile_picture' => 'default.png',
                'email_verified_at' => '2024-08-02 21:02:40',
                'password' => '$2y$10$uKlqcITh/34pY2BTFdywA.HXqN/Zt9FVwyRDtuqalIZVCHYWJa/hy',
                'two_factor_code' => null,
                'society_role' => null,
                'user_level' => null,
                'two_factor_expires_at' => null,
                'two_factor_code_count' => 0,
                'otp' => null,
                'otp_expires_at' => null,
                'otp_count' => 0,
                'provider' => null,
                'provider_id' => null,
                'remember_token' => 'gOrKxkCZcw9i8E0HEHgRc2uiKKBMIsnZpWyar8dqmjECqassV5c0tZGAEGX4',
                'created_at' => '2024-08-02 21:02:40',
                'updated_at' => '2024-08-02 21:02:40',
                'signature' => null,
                'designation' => null,
            ],
            [
                'id' => 5,
                'name' => 'Abiola2',
                'email' => 'abiola2@test.com',
                'user_type' => 'admin',
                'role_id' => null,
                'branch_id' => null,
                'status' => 1,
                'profile_picture' => 'default.png',
                'email_verified_at' => null,
                'password' => '$2y$10$uKlqcITh/34pY2BTFdywA.HXqN/Zt9FVwyRDtuqalIZVCHYWJa/hy',
                'two_factor_code' => null,
                'society_role' => null,
                'user_level' => null,
                'two_factor_expires_at' => '2024-10-02 14:20:43',
                'two_factor_code_count' => 0,
                'otp' => null,
                'otp_expires_at' => '2024-10-02 14:20:43',
                'otp_count' => 0,
                'provider' => null,
                'provider_id' => null,
                'remember_token' => null,
                'created_at' => null,
                'updated_at' => null,
                'signature' => null,
                'designation' => null,
            ],
        ];

        foreach ($users as $user) {
            DB::table('users')->updateOrInsert(
                ['email' => $user['email']],  // Check for existing records based on `email`
                $user  // Insert or update the user record
            );
        }
    }
}
