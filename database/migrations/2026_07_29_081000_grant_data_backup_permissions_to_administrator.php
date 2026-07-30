<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $permissions = ['data-backups.view', 'data-backups.manage'];

    public function up(): void
    {
        DB::table('roles')->where('slug', 'administrator')->get()->each(function ($role): void {
            $permissions = json_decode($role->permissions ?: '[]', true) ?: [];

            DB::table('roles')->where('id', $role->id)->update([
                'permissions' => json_encode(array_values(array_unique([...$permissions, ...$this->permissions]))),
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        DB::table('roles')->get()->each(function ($role): void {
            $permissions = json_decode($role->permissions ?: '[]', true) ?: [];

            DB::table('roles')->where('id', $role->id)->update([
                'permissions' => json_encode(array_values(array_diff($permissions, $this->permissions))),
                'updated_at' => now(),
            ]);
        });
    }
};
