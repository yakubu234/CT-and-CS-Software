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

Artisan::command('sms:process-pending', function (SmsCampaignService $campaignService, SmsAutomationService $automationService) {
    $queuedMessages = $campaignService->processScheduledMessages();
    $automaticMessages = $automationService->processDateBasedAutomations();

    $this->info('SMS processing complete.');
    $this->line("Queued campaign messages processed: {$queuedMessages}");
    $this->line("Automation messages processed: {$automaticMessages}");
})->purpose('Process queued SMS campaigns and date-based SMS automations.');
