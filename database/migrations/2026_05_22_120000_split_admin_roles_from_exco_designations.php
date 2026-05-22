<?php

use App\Support\PermissionRegistry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $adminRoleSlugs = [
            'admin',
            'administrator',
            'super-admin',
            'superadmin',
            'legacy-access',
            'legacy-user',
        ];

        $existingSortOrder = (int) (DB::table('designations')->max('sort_order') ?? 0);

        DB::table('roles')
            ->select('name', 'slug')
            ->orderBy('id')
            ->get()
            ->each(function (object $role) use (&$existingSortOrder, $adminRoleSlugs): void {
                $name = trim((string) ($role->name ?? ''));
                $slug = trim((string) ($role->slug ?? '')) ?: Str::slug($name);

                if ($name === '' || $slug === '' || in_array($slug, $adminRoleSlugs, true)) {
                    return;
                }

                if (! DB::table('designations')->where('slug', $slug)->exists()) {
                    $existingSortOrder++;

                    DB::table('designations')->insert([
                        'name' => $name,
                        'slug' => $slug,
                        'status' => 1,
                        'sort_order' => $existingSortOrder,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });

        $now = now();
        $permissions = json_encode(PermissionRegistry::all());

        DB::table('roles')->updateOrInsert(
            ['slug' => 'administrator'],
            [
                'name' => 'Administrator',
                'description' => 'Admin-side staff role with configurable permissions.',
                'permissions' => $permissions,
                'is_system' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    public function down(): void
    {
        DB::table('roles')->where('slug', 'administrator')->delete();
    }
};
