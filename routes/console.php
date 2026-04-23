<?php

use App\Models\User;
use App\Services\BranchService;
use App\Services\Sms\SmsAutomationService;
use App\Services\Sms\SmsCampaignService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Services\BranchAccountReconciler;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('branches:reconcile-accounts {--dry-run}', function (BranchAccountReconciler $reconciler) {
    $summary = $reconciler->reconcile((bool) $this->option('dry-run'));

    $this->info('Branch account reconciliation complete.');
    $this->line("Branches scanned: {$summary['branches_scanned']}");
    $this->line("Branches relinked: {$summary['branches_updated']}");
    $this->line("Duplicate users deactivated: {$summary['users_deactivated']}");
    $this->line("Duplicate accounts deactivated: {$summary['accounts_deactivated']}");

    foreach ($summary['notes'] as $note) {
        $this->line("- {$note}");
    }
})->purpose('Reconcile duplicate branch-account users and branch savings accounts.');

Artisan::command('users:backfill-exco-accounts {--dry-run}', function (BranchService $branchService) {
    $dryRun = (bool) $this->option('dry-run');

    $excos = User::query()
        ->where('branch_account', false)
        ->where(function ($query): void {
            $query->where('society_exco', true)
                ->orWhere('former_exco', true);
        })
        ->orderBy('id')
        ->get();

    $usersScanned = 0;
    $usersUpdated = 0;
    $accountsCreated = 0;

    foreach ($excos as $user) {
        $usersScanned++;

        $beforeCount = $user->savingsAccounts()
            ->whereHas('product', function ($query): void {
                $query->whereIn('type', ['SAVINGS', 'SHARES', 'AUTHENTICATION', 'DEPOSIT']);
            })
            ->count();

        if (! $dryRun) {
            $branchService->ensureMemberAccounts($user);
            $user->refresh();
        }

        $afterCount = $dryRun
            ? 4
            : $user->savingsAccounts()
                ->whereHas('product', function ($query): void {
                    $query->whereIn('type', ['SAVINGS', 'SHARES', 'AUTHENTICATION', 'DEPOSIT']);
                })
                ->count();

        $delta = max(0, $afterCount - $beforeCount);

        if ($delta > 0) {
            $usersUpdated++;
            $accountsCreated += $delta;
            $this->line("- User {$user->id} ({$user->name}) " . ($dryRun ? "would receive" : "received") . " {$delta} missing account(s).");
        }
    }

    $this->info($dryRun ? 'Dry run complete.' : 'Exco account backfill complete.');
    $this->line("Exco users scanned: {$usersScanned}");
    $this->line("Users updated: {$usersUpdated}");
    $this->line("Accounts " . ($dryRun ? 'to create' : 'created') . ": {$accountsCreated}");
})->purpose('Backfill the 4 standard member accounts for existing current/former exco users.');

Artisan::command('branches:backfill-account-users {--dry-run} {--repair-invalid-accounts}', function (BranchService $branchService) {
    $dryRun = (bool) $this->option('dry-run');
    $repairInvalidAccounts = (bool) $this->option('repair-invalid-accounts');

    $branches = \App\Models\Branch::query()
        ->whereNull('deleted_at')
        ->orderBy('id')
        ->get();

    $defaultProduct = \App\Models\SavingsProduct::query()
        ->where('default_account', 1)
        ->where('status', 1)
        ->first();

    if (! $defaultProduct) {
        $this->error('No active default savings product was found. Please mark one savings product as default before running this command.');

        return \Symfony\Component\Console\Command\Command::FAILURE;
    }

    $accountNumberColumnLength = (int) (
        \Illuminate\Support\Facades\DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', \Illuminate\Support\Facades\DB::getDatabaseName())
            ->where('TABLE_NAME', 'savings_accounts')
            ->where('COLUMN_NAME', 'account_number')
            ->value('CHARACTER_MAXIMUM_LENGTH') ?: 0
    );

    $accountsToCreate = 0;

    foreach ($branches as $branch) {
        $existingBranchUser = \App\Models\User::query()
            ->where('branch_id', (string) $branch->id)
            ->where('branch_account', true)
            ->orderBy('id')
            ->first();

        if (! $existingBranchUser) {
            $accountsToCreate++;
            continue;
        }

        $hasBranchSavingsAccount = \App\Models\SavingsAccount::query()
            ->where('user_id', $existingBranchUser->id)
            ->where('is_branch_acount', 1)
            ->exists();

        if (! $hasBranchSavingsAccount) {
            $accountsToCreate++;
        }
    }

    $accountPrefix = $defaultProduct->account_number_prefix ?? '';
    $maxUsedValue = \App\Models\SavingsAccount::query()
        ->where('account_number', 'like', $accountPrefix . '%')
        ->get()
        ->map(function (\App\Models\SavingsAccount $account) use ($accountPrefix): int {
            return (int) \Illuminate\Support\Str::after($account->account_number, $accountPrefix);
        })
        ->max() ?? 0;

    $projectedLastAccountNumber = $accountPrefix . max(
        (int) $defaultProduct->starting_account_number,
        $maxUsedValue + $accountsToCreate
    );

    if ($accountNumberColumnLength > 0 && strlen($projectedLastAccountNumber) > $accountNumberColumnLength) {
        $this->error('The savings_accounts.account_number column is too short for the next branch account number.');
        $this->line("Current column length: {$accountNumberColumnLength}");
        $this->line("Projected account number: {$projectedLastAccountNumber} (" . strlen($projectedLastAccountNumber) . ' characters)');
        $this->line('Run migrations first, or run this SQL manually before retrying:');
        $this->line('ALTER TABLE savings_accounts MODIFY account_number VARCHAR(50) NOT NULL;');

        return \Symfony\Component\Console\Command\Command::FAILURE;
    }

    $branchesScanned = 0;
    $usersCreated = 0;
    $branchesLinked = 0;
    $accountsCreated = 0;
    $accountsRepaired = 0;
    $alreadyHealthy = 0;

    foreach ($branches as $branch) {
        $branchesScanned++;

        $existingBranchUser = \App\Models\User::query()
            ->where('branch_id', (string) $branch->id)
            ->where('branch_account', true)
            ->orderBy('id')
            ->first();

        $hasBranchSavingsAccount = false;

        if ($existingBranchUser) {
            $hasBranchSavingsAccount = \App\Models\SavingsAccount::query()
                ->where('user_id', $existingBranchUser->id)
                ->where('is_branch_acount', 1)
                ->exists();
        }

        $needsUser = ! $existingBranchUser;
        $needsLink = ! $existingBranchUser || (int) $branch->branch_user_id !== (int) $existingBranchUser->id;
        $needsAccount = $existingBranchUser && ! $hasBranchSavingsAccount;

        $invalidBranchAccounts = collect();

        if ($repairInvalidAccounts && $existingBranchUser) {
            $duplicateAccountNumbers = \App\Models\SavingsAccount::query()
                ->where('is_branch_acount', 1)
                ->select('account_number')
                ->groupBy('account_number')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('account_number')
                ->all();

            $invalidBranchAccounts = \App\Models\SavingsAccount::query()
                ->where('user_id', $existingBranchUser->id)
                ->where('is_branch_acount', 1)
                ->where(function ($query) use ($defaultProduct, $duplicateAccountNumbers): void {
                    $query->where('savings_product_id', $defaultProduct->id)
                        ->where(function ($inner) use ($defaultProduct, $duplicateAccountNumbers): void {
                            $inner->whereIn('account_number', $duplicateAccountNumbers)
                                ->orWhereRaw(
                                    'CAST(SUBSTRING(account_number, ?) AS UNSIGNED) < ?',
                                    [strlen((string) $defaultProduct->account_number_prefix) + 1, (int) $defaultProduct->starting_account_number]
                                );
                        });
                })
                ->whereNotExists(function ($query): void {
                    $query->select(\Illuminate\Support\Facades\DB::raw(1))
                        ->from('transactions')
                        ->whereColumn('transactions.savings_account_id', 'savings_accounts.id');
                })
                ->get();
        }

        if (! $needsUser && ! $needsLink && ! $needsAccount && $invalidBranchAccounts->isEmpty()) {
            $alreadyHealthy++;
            continue;
        }

        if ($dryRun) {
            $parts = [];

            if ($needsUser) {
                $parts[] = 'create branch user';
                $parts[] = 'link branch_user_id';
                $parts[] = 'create branch savings account';
                $usersCreated++;
                $branchesLinked++;
                $accountsCreated++;
            } else {
                if ($needsLink) {
                    $parts[] = 'link branch_user_id';
                    $branchesLinked++;
                }

                if ($needsAccount) {
                    $parts[] = 'create branch savings account';
                    $accountsCreated++;
                }

                if ($invalidBranchAccounts->isNotEmpty()) {
                    $parts[] = 'repair ' . $invalidBranchAccounts->count() . ' invalid branch account number(s)';
                    $accountsRepaired += $invalidBranchAccounts->count();
                }
            }

            $this->line("- Branch {$branch->id} ({$branch->name}) would " . implode(' and ', $parts) . '.');
            continue;
        }

        $result = $branchService->ensureBranchAccount($branch);

        if ($result['user_created']) {
            $usersCreated++;
        }

        if ($result['branch_linked']) {
            $branchesLinked++;
        }

        if ($result['account_created']) {
            $accountsCreated++;
        }

        if ($repairInvalidAccounts && $invalidBranchAccounts->isNotEmpty()) {
            foreach ($invalidBranchAccounts as $account) {
                $oldAccountNumber = $account->account_number;

                $account->forceFill([
                    'account_number' => $branchService->nextAccountNumberForProduct($defaultProduct),
                    'description' => trim((string) $account->description . ' | Repaired truncated branch account number'),
                ])->save();

                $accountsRepaired++;
                $this->line("- Branch {$branch->id} ({$branch->name}): repaired branch account {$account->id} from {$oldAccountNumber} to {$account->account_number}.");
            }
        }

        $actions = [];
        if ($result['user_created']) {
            $actions[] = 'created branch user';
        }
        if ($result['branch_linked']) {
            $actions[] = 'linked branch';
        }
        if ($result['account_created']) {
            $actions[] = 'created branch savings account';
        }

        if ($actions !== []) {
            $this->line("- Branch {$branch->id} ({$branch->name}): " . implode(', ', $actions) . '.');
        }
    }

    $this->info($dryRun ? 'Dry run complete.' : 'Branch account user backfill complete.');
    $this->line("Branches scanned: {$branchesScanned}");
    $this->line("Branch users " . ($dryRun ? 'to create' : 'created') . ": {$usersCreated}");
    $this->line("Branches " . ($dryRun ? 'to relink' : 'relinked') . ": {$branchesLinked}");
    $this->line("Branch savings accounts " . ($dryRun ? 'to create' : 'created') . ": {$accountsCreated}");
    $this->line("Branch savings accounts " . ($dryRun ? 'to repair' : 'repaired') . ": {$accountsRepaired}");
    $this->line("Already healthy: {$alreadyHealthy}");
})->purpose('Create missing branch-account users and relink branch account records for existing branches.');

Artisan::command('sms:process-pending', function (SmsCampaignService $campaignService, SmsAutomationService $automationService) {
    $queuedMessages = $campaignService->processScheduledMessages();
    $automaticMessages = $automationService->processDateBasedAutomations();

    $this->info('SMS processing complete.');
    $this->line("Queued campaign messages processed: {$queuedMessages}");
    $this->line("Automation messages processed: {$automaticMessages}");
})->purpose('Process queued SMS campaigns and date-based SMS automations.');
