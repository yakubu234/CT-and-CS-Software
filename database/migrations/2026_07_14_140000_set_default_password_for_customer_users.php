<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('user_type', 'customer')
            ->update([
                'password' => Hash::make('12345678'),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        //
    }
};
