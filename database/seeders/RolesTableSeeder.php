<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Carbon;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $roles = [
            [
                'name' => 'Secretrary',
                'description' => 'This is the person who has access to the user',
                'created_at' => '2024-09-14 00:44:07',
                'updated_at' => '2024-09-14 00:44:07',
            ],
            [
                'name' => 'Treasurer',
                'description' => null,
                'created_at' => '2025-04-15 20:26:12',
                'updated_at' => '2025-04-15 20:26:12',
            ],
            [
                'name' => 'President',
                'description' => null,
                'created_at' => '2025-04-15 20:26:27',
                'updated_at' => '2025-04-15 20:26:27',
            ],
            [
                'name' => 'Vice President',
                'description' => null,
                'created_at' => '2025-04-15 20:26:42',
                'updated_at' => '2025-04-15 20:26:42',
            ],
            [
                'name' => 'Member',
                'description' => null,
                'created_at' => '2025-04-15 20:26:51',
                'updated_at' => '2025-04-15 20:26:51',
            ],
            [
                'name' => 'Assistant Secretary',
                'description' => null,
                'created_at' => '2025-04-15 20:27:21',
                'updated_at' => '2025-04-15 20:27:21',
            ],
            [
                'name' => 'Committee 1',
                'description' => null,
                'created_at' => '2025-04-15 20:29:09',
                'updated_at' => '2025-04-15 20:29:09',
            ],
            [
                'name' => 'Committee 2',
                'description' => null,
                'created_at' => '2025-04-15 20:29:22',
                'updated_at' => '2025-04-15 20:29:22',
            ],
            [
                'name' => 'Committee 3',
                'description' => null,
                'created_at' => '2025-04-15 20:29:37',
                'updated_at' => '2025-04-15 20:29:37',
            ],
            [
                'name' => 'Chief whip',
                'description' => null,
                'created_at' => '2025-04-15 20:30:06',
                'updated_at' => '2025-04-15 20:30:06',
            ],
            [
                'name' => 'Staff',
                'description' => null,
                'created_at' => '2025-04-15 20:30:20',
                'updated_at' => '2025-04-15 20:30:20',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']], // Match by `id`, you can use ['name' => $role['name']] instead
                [
                    'name' => $role['name'],
                    'description' => $role['description'],
                    'created_at' => Carbon::parse($role['created_at']),
                    'updated_at' => Carbon::parse($role['updated_at']),
                ]
            );
        }
    }
}
