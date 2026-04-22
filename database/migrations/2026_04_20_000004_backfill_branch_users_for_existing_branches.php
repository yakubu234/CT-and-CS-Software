<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $defaultProduct = DB::table('savings_products')
                ->where('default_account', 1)
                ->where('status', 1)
                ->first();

            $branches = DB::table('branches')
                ->whereNull('deleted_at')
                ->where(function ($query): void {
                    $query->whereNull('branch_user_id')
                        ->orWhereNotExists(function ($subQuery): void {
                            $subQuery->select(DB::raw(1))
                                ->from('users')
                                ->whereColumn('users.id', 'branches.branch_user_id');
                        });
                })
                ->orderBy('id')
                ->get();

            foreach ($branches as $branch) {
                $existingBranchUser = DB::table('users')
                    ->where('branch_id', (string) $branch->id)
                    ->where('branch_account', 1)
                    ->orderBy('id')
                    ->first();

                if ($existingBranchUser) {
                    $branchUserId = $existingBranchUser->id;
                } else {
                    $branchUserId = DB::table('users')->insertGetId([
                        'name' => $branch->name,
                        'last_name' => 'Branch',
                        'email' => $this->makeSystemEmail($branch->name, 'branch'),
                        'email_verified_at' => now(),
                        'password' => Hash::make(Str::password(32)),
                        'user_type' => 'branch',
                        'role_id' => null,
                        'branch_id' => (string) $branch->id,
                        'status' => 1,
                        'profile_picture' => null,
                        'society_role' => null,
                        'society_exco' => 0,
                        'user_level' => null,
                        'branch_account' => 1,
                        'is_verified' => 1,
                        'two_factor_code' => null,
                        'two_factor_expires_at' => null,
                        'two_factor_code_count' => 0,
                        'otp' => null,
                        'otp_expires_at' => null,
                        'otp_count' => 0,
                        'provider' => null,
                        'provider_id' => null,
                        'signature' => null,
                        'member_no' => null,
                        'designation' => 'Branch Account',
                        'remember_token' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('branches')
                    ->where('id', $branch->id)
                    ->update([
                        'branch_user_id' => $branchUserId,
                        'updated_at' => now(),
                    ]);

                if (! $defaultProduct) {
                    continue;
                }

                $hasBranchSavingsAccount = DB::table('savings_accounts')
                    ->where('user_id', $branchUserId)
                    ->where('is_branch_acount', 1)
                    ->exists();

                if ($hasBranchSavingsAccount) {
                    continue;
                }

                DB::table('savings_accounts')->insert([
                    'account_number' => $this->generateAccountNumber(
                        (string) $defaultProduct->account_number_prefix,
                        (int) $defaultProduct->starting_account_number
                    ),
                    'user_id' => $branchUserId,
                    'savings_product_id' => $defaultProduct->id,
                    'status' => 1,
                    'opening_balance' => 0,
                    'balance' => 0,
                    'description' => "account creation for {$branch->name} branch",
                    'created_user_id' => null,
                    'updated_user_id' => null,
                    'is_branch_acount' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        // Intentionally left empty. This backfill migration should not remove
        // users/accounts that may already be in active use.
    }

    private function makeSystemEmail(string $value, string $context): string
    {
        $base = Str::slug(Str::limit($value, 40, ''), '.');
        $base = $base !== '' ? $base : $context;

        do {
            $email = "{$context}.{$base}." . Str::lower(Str::random(6)) . '@system.local';
        } while (DB::table('users')->where('email', $email)->exists());

        return $email;
    }

    private function generateAccountNumber(string $prefix, int $startingNumber): string
    {
        $maxUsedValue = DB::table('savings_accounts')
            ->where('account_number', 'like', $prefix . '%')
            ->pluck('account_number')
            ->map(fn (string $accountNumber): int => (int) Str::after($accountNumber, $prefix))
            ->max() ?? 0;

        $nextNumber = max($startingNumber, $maxUsedValue + 1);

        return $prefix . $nextNumber;
    }
};
