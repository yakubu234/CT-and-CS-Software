<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_branch_memberships', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('branch_id')->index();
            $table->string('member_number', 191);
            $table->boolean('is_primary')->default(false);
            $table->boolean('status')->default(true)->index();
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'branch_id'], 'member_branch_user_branch_unique');
        });

        Schema::table('savings_accounts', function (Blueprint $table): void {
            $table->unsignedBigInteger('member_branch_membership_id')->nullable()->after('user_id')->index();
            $table->unsignedBigInteger('branch_id')->nullable()->after('member_branch_membership_id')->index();
        });

        if (! Schema::hasColumn('loans', 'member_branch_membership_id')) {
            Schema::table('loans', function (Blueprint $table): void {
                $table->unsignedBigInteger('member_branch_membership_id')->nullable()->after('borrower_id')->index();
            });
        }

        Schema::table('transactions', function (Blueprint $table): void {
            $table->unsignedBigInteger('member_branch_membership_id')->nullable()->after('user_id')->index();
        });

        DB::table('users')
            ->where('user_type', 'customer')
            ->where('branch_account', false)
            ->whereNotNull('branch_id')
            ->orderBy('id')
            ->chunkById(200, function ($users): void {
                foreach ($users as $user) {
                    $branchId = (int) $user->branch_id;
                    if ($branchId <= 0) {
                        continue;
                    }

                    $memberNumber = DB::table('user_details')->where('user_id', $user->id)->value('member_no')
                        ?: $user->member_no
                        ?: 'MEMBER-' . $user->id;

                    DB::table('member_branch_memberships')->updateOrInsert(
                        ['user_id' => $user->id, 'branch_id' => $branchId],
                        [
                            'member_number' => $memberNumber,
                            'is_primary' => true,
                            'status' => ! $user->deleted_at && (int) $user->status === 1,
                            'joined_at' => $user->created_at,
                            'created_at' => $user->created_at,
                            'updated_at' => now(),
                        ]
                    );
                }
            });

        DB::table('member_branch_memberships')->orderBy('id')->each(function ($membership): void {
            DB::table('savings_accounts')
                ->where('user_id', $membership->user_id)
                ->where('is_branch_acount', false)
                ->whereNull('member_branch_membership_id')
                ->update([
                    'member_branch_membership_id' => $membership->id,
                    'branch_id' => $membership->branch_id,
                ]);

            DB::table('loans')
                ->where('borrower_id', $membership->user_id)
                ->where('branch_id', $membership->branch_id)
                ->whereNull('member_branch_membership_id')
                ->update(['member_branch_membership_id' => $membership->id]);

            DB::table('transactions')
                ->where('user_id', $membership->user_id)
                ->where('branch_id', $membership->branch_id)
                ->whereNull('member_branch_membership_id')
                ->update(['member_branch_membership_id' => $membership->id]);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', fn (Blueprint $table) => $table->dropColumn('member_branch_membership_id'));
        Schema::table('loans', fn (Blueprint $table) => $table->dropColumn('member_branch_membership_id'));
        Schema::table('savings_accounts', fn (Blueprint $table) => $table->dropColumn(['member_branch_membership_id', 'branch_id']));
        Schema::dropIfExists('member_branch_memberships');
    }
};
