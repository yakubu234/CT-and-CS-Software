<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\SavingsAccount;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BranchAccountReconciler
{
    public function reconcile(bool $dryRun = false): array
    {
        $summary = [
            'branches_scanned' => 0,
            'branches_updated' => 0,
            'users_deactivated' => 0,
            'accounts_deactivated' => 0,
            'notes' => [],
        ];

        $branches = Branch::query()
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get();

        foreach ($branches as $branch) {
            $summary['branches_scanned']++;

            $branchUsers = User::query()
                ->where('branch_account', true)
                ->where('branch_id', (string) $branch->id)
                ->orderBy('id')
                ->get();

            if ($branchUsers->isEmpty()) {
                continue;
            }

            $canonicalUser = $this->pickCanonicalUser($branch, $branchUsers);
            $branchChanged = false;

            if ((int) $branch->branch_user_id !== (int) $canonicalUser->id) {
                $summary['notes'][] = "Branch {$branch->id} relinked from {$branch->branch_user_id} to {$canonicalUser->id}.";
                $branchChanged = true;

                if (! $dryRun) {
                    $branch->forceFill([
                        'branch_user_id' => $canonicalUser->id,
                    ])->save();
                }
            }

            $canonicalAccount = $this->pickCanonicalSavingsAccount($canonicalUser);

            foreach ($branchUsers as $branchUser) {
                if ((int) $branchUser->id === (int) $canonicalUser->id) {
                    $this->deactivateDuplicateAccountsForUser($branchUser, $canonicalAccount, $summary, $dryRun);
                    continue;
                }

                $duplicateAccounts = SavingsAccount::query()
                    ->where('user_id', $branchUser->id)
                    ->where('is_branch_acount', 1)
                    ->get();

                foreach ($duplicateAccounts as $account) {
                    if (! $this->hasAccountTransactions($account)) {
                        $summary['accounts_deactivated']++;
                        $summary['notes'][] = "Branch {$branch->id} account {$account->id} marked inactive as duplicate.";

                        if (! $dryRun) {
                            $account->forceFill([
                                'status' => 0,
                                'is_branch_acount' => 0,
                                'description' => $this->appendLegacyNote($account->description, 'Legacy duplicate branch account'),
                            ])->save();
                        }
                    }
                }

                if (! $this->hasUserTransactions($branchUser)) {
                    $summary['users_deactivated']++;
                    $summary['notes'][] = "Branch {$branch->id} user {$branchUser->id} marked inactive as duplicate.";

                    if (! $dryRun) {
                        $branchUser->forceFill([
                            'status' => 0,
                            'branch_id' => null,
                            'designation' => $this->appendLegacyNote($branchUser->designation, 'Legacy duplicate branch user'),
                        ])->save();
                    }
                }
            }

            if ($branchChanged) {
                $summary['branches_updated']++;
            }
        }

        return $summary;
    }

    protected function pickCanonicalUser(Branch $branch, Collection $branchUsers): User
    {
        return $branchUsers
            ->map(function (User $user) use ($branch): array {
                $accounts = SavingsAccount::query()
                    ->where('user_id', $user->id)
                    ->where('is_branch_acount', 1)
                    ->get();

                $accountTransactionCount = $accounts->sum(fn (SavingsAccount $account): int => $this->accountTransactionCount($account));

                return [
                    'user' => $user,
                    'score' => (
                        ($accountTransactionCount * 1000) +
                        ($accounts->count() * 100) +
                        ($this->userTransactionCount($user) * 10) +
                        (((int) $branch->branch_user_id === (int) $user->id) ? 5 : 0) +
                        (1000000 - (int) $user->id)
                    ),
                ];
            })
            ->sortByDesc('score')
            ->pluck('user')
            ->first();
    }

    protected function pickCanonicalSavingsAccount(User $user): ?SavingsAccount
    {
        return SavingsAccount::query()
            ->where('user_id', $user->id)
            ->where('is_branch_acount', 1)
            ->get()
            ->sortByDesc(function (SavingsAccount $account): int {
                return ($this->accountTransactionCount($account) * 1000) + (1000000 - (int) $account->id);
            })
            ->first();
    }

    protected function deactivateDuplicateAccountsForUser(
        User $user,
        ?SavingsAccount $canonicalAccount,
        array &$summary,
        bool $dryRun
    ): void {
        $accounts = SavingsAccount::query()
            ->where('user_id', $user->id)
            ->where('is_branch_acount', 1)
            ->orderBy('id')
            ->get();

        foreach ($accounts as $account) {
            if ($canonicalAccount && (int) $account->id === (int) $canonicalAccount->id) {
                continue;
            }

            if ($this->hasAccountTransactions($account)) {
                continue;
            }

            $summary['accounts_deactivated']++;
            $summary['notes'][] = "User {$user->id} account {$account->id} marked inactive as duplicate.";

            if (! $dryRun) {
                $account->forceFill([
                    'status' => 0,
                    'is_branch_acount' => 0,
                    'description' => $this->appendLegacyNote($account->description, 'Legacy duplicate branch account'),
                ])->save();
            }
        }
    }

    protected function hasUserTransactions(User $user): bool
    {
        return $this->userTransactionCount($user) > 0;
    }

    protected function hasAccountTransactions(SavingsAccount $account): bool
    {
        return $this->accountTransactionCount($account) > 0;
    }

    protected function userTransactionCount(User $user): int
    {
        return DB::table('transactions')
            ->where('user_id', $user->id)
            ->count();
    }

    protected function accountTransactionCount(SavingsAccount $account): int
    {
        return DB::table('transactions')
            ->where('savings_account_id', $account->id)
            ->count();
    }

    protected function appendLegacyNote(?string $value, string $note): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return $note;
        }

        if (str_contains($value, $note)) {
            return $value;
        }

        return "{$value} | {$note}";
    }
}
