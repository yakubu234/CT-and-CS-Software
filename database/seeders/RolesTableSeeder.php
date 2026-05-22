<?php

namespace Database\Seeders;

use App\Support\PermissionRegistry;
use Illuminate\Support\Carbon;
use Illuminate\Database\Seeder;
use App\Models\Role;
use Illuminate\Support\Str;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $roles = [
            [
                'name' => 'Administrator',
                'description' => 'Administrator account with configurable permissions for the admin panel.',
                'created_at' => '2026-05-22 00:00:00',
                'updated_at' => '2026-05-22 00:00:00',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']], // Match by `id`, you can use ['name' => $role['name']] instead
                [
                    'name' => $role['name'],
                    'slug' => Str::slug($role['name']),
                    'description' => $role['description'],
                    'permissions' => PermissionRegistry::all(),
                    'is_system' => true,
                    'created_at' => Carbon::parse($role['created_at']),
                    'updated_at' => Carbon::parse($role['updated_at']),
                ]
            );
        }
    }
}
