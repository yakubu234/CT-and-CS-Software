<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('savings_accounts', function (Blueprint $table): void {
            $table->timestamp('archived_with_member_at')->nullable()->after('disabled_at');
        });

        $archivedMembers = DB::table('users')
            ->whereNotNull('deleted_at')
            ->where('user_type', 'customer')
            ->where('branch_account', false)
            ->pluck('deleted_at', 'id');

        foreach ($archivedMembers as $memberId => $archivedAt) {
            DB::table('savings_accounts')
                ->where('user_id', $memberId)
                ->where('is_branch_acount', false)
                ->where('status', 1)
                ->update([
                    'status' => 0,
                    'disabled_at' => $archivedAt,
                    'archived_with_member_at' => $archivedAt,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('savings_accounts', function (Blueprint $table): void {
            $table->dropColumn('archived_with_member_at');
        });
    }
};
