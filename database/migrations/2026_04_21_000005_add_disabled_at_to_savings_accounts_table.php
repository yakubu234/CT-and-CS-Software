<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('savings_accounts', function (Blueprint $table) {
            $table->timestamp('disabled_at')->nullable()->after('updated_at');
        });

        DB::table('savings_accounts')
            ->where('status', 0)
            ->whereNull('disabled_at')
            ->update([
                'disabled_at' => DB::raw('updated_at'),
            ]);
    }

    public function down(): void
    {
        Schema::table('savings_accounts', function (Blueprint $table) {
            $table->dropColumn('disabled_at');
        });
    }
};
