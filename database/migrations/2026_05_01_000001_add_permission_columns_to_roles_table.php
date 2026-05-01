<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('slug', 80)->nullable()->after('name');
            $table->json('permissions')->nullable()->after('description');
            $table->boolean('is_system')->default(false)->after('permissions');
        });

        DB::table('roles')
            ->orderBy('id')
            ->get(['id', 'name'])
            ->each(function ($role): void {
                DB::table('roles')
                    ->where('id', $role->id)
                    ->update([
                        'slug' => Str::slug((string) $role->name) ?: ('role-' . $role->id),
                        'permissions' => json_encode([]),
                    ]);
            });

        Schema::table('roles', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn(['slug', 'permissions', 'is_system']);
        });
    }
};
