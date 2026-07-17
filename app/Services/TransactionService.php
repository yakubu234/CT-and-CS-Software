<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\SavingsAccount;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Sms\SmsAutomationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class TransactionService
{
    public function __construct(
        protected BalanceSyncService $balanceSyncService,
    ) {
    }

    public function createBatch(Branch $branch, User $actor, User $member, string $transactionDate, array $entries): Collection
    {
        return DB::transaction(function () use ($branch, $actor, $member, $transactionDate, $entries): Collection {
            $batchId = (string) Str::uuid();
            $transactions = new Collection();
            $accountsToSync = [];
            $hasBranchDebit = false;

            foreach ($entries as $entry) {
                $account = $this->resolveAccountForMember($member, (int) $entry['savings_account_id']);
                $amount = $this->normalizeAmount($entry['amount']);
                $drCr = strtolower($entry['dr_cr']);
                $hasBranchDebit = $hasBranchDebit || $drCr === 'dr';

                $transaction = Transaction::create([
                    'user_id' => $member->id,
                    'trans_date' => $transactionDate,
                    'savings_account_id' => $account->id,
                    'charge' => 0,
                    'amount' => $amount,
                    'gateway_amount' => 0,
                    'dr_cr' => $drCr,
                    'type' => $this->displayType($account),
                    'attachment' => null,
                    'method' => 'Manual',
                    'status' => 2,
                    'note' => null,
                    'description' => $entry['description'] ?: $this->displayType($account),
                    'loan_id' => null,
                    'ref_id' => null,
                    'parent_id' => null,
                    'gateway_id' => null,
                    'created_user_id' => $actor->id,
                    'updated_user_id' => null,
                    'branch_id' => $branch->id,
                    'transaction_details' => [
                        'member_no' => $member->display_member_no,
                        'account_number' => $account->account_number,
                        'account_type' => $account->product?->type,
                        'balance_before' => null,
                        'balance_after' => null,
                    ],
                    'tracking_id' => 'regular',
                    'detail_id' => null,
                    'is_branch' => 0,
                    'loan_details_id' => null,
                    'loan_repayment_id' => null,
                    'batch_id' => $batchId,
                ]);

                $this->createBranchMirrorTransaction($branch, $actor, $transaction, $account, $batchId);
                $accountsToSync[$account->id] = $account;

                $transactions->push($transaction->fresh(['account.product', 'user.detail', 'creator']));
                DB::afterCommit(function () use ($transaction): void {
                    app(SmsAutomationService::class)->handleTransaction($transaction->fresh(['user.detail', 'account.product']));
                });
            }

            foreach ($accountsToSync as $account) {
                $account->updated_user_id = $actor->id;
                $this->balanceSyncService->syncSavingsAccount($account);
            }

            $this->balanceSyncService->syncBranchLedger($branch, $hasBranchDebit);

            return $transactions;
        });
    }

    public function updateTransaction(Branch $branch, User $actor, Transaction $transaction, array $payload): Transaction
    {
        return DB::transaction(function () use ($branch, $actor, $transaction, $payload): Transaction {
            $transaction->loadMissing(['account.product', 'user.detail', 'mirrors']);

            if ($transaction->is_branch) {
                throw new RuntimeException('Branch mirror transactions cannot be edited directly.');
            }

            $member = $transaction->user;
            $originalAccount = $transaction->account;

            if (! $member || ! $originalAccount) {
                throw new RuntimeException('This transaction is missing its linked member account.');
            }

            $newAccount = $this->resolveAccountForMember($member, (int) $payload['savings_account_id']);
            $newAmount = $this->normalizeAmount($payload['amount']);
            $newDrCr = strtolower($payload['dr_cr']);
            $newDate = $payload['trans_date'];
            $newDescription = $payload['description'] ?: $this->displayType($newAccount);

            $transaction->update([
                'trans_date' => $newDate,
                'savings_account_id' => $newAccount->id,
                'amount' => $newAmount,
                'dr_cr' => $newDrCr,
                'type' => $this->displayType($newAccount),
                'description' => $newDescription,
                'updated_user_id' => $actor->id,
                'transaction_details' => [
                    'member_no' => $member->display_member_no,
                    'account_number' => $newAccount->account_number,
                    'account_type' => $newAccount->product?->type,
                    'balance_before' => null,
                    'balance_after' => null,
                ],
            ]);

            foreach ($transaction->mirrors as $mirror) {
                $mirror->update([
                    'user_id' => $branch->branch_user_id ?: $mirror->user_id,
                    'trans_date' => $newDate,
                    'amount' => $newAmount,
                    'dr_cr' => $newDrCr,
                    'type' => $this->displayType($newAccount),
                    'description' => $newDescription,
                    'updated_user_id' => $actor->id,
                    'branch_id' => $branch->id,
                    'transaction_details' => [
                        'source_transaction_id' => $transaction->id,
                        'member_id' => $member->id,
                        'member_name' => $member->name,
                        'member_no' => $member->display_member_no,
                        'account_number' => $newAccount->account_number,
                        'account_type' => $newAccount->product?->type,
                    ],
                ]);
            }

            $originalAccount->updated_user_id = $actor->id;
            $this->balanceSyncService->syncSavingsAccount($originalAccount);

            if (! $newAccount->is($originalAccount)) {
                $newAccount->updated_user_id = $actor->id;
                $this->balanceSyncService->syncSavingsAccount($newAccount);
            }

            $this->balanceSyncService->syncBranchLedger($branch, $newDrCr === 'dr');

            return $transaction->fresh(['account.product', 'user.detail', 'creator', 'updater', 'mirrors']);
        });
    }

    public function deleteTransaction(User $actor, Transaction $transaction): void
    {
        DB::transaction(function () use ($actor, $transaction): void {
            $transaction->loadMissing(['account', 'mirrors']);

            if ($transaction->is_branch) {
                throw new RuntimeException('Branch mirror transactions cannot be deleted directly.');
            }

            if (! $transaction->account) {
                throw new RuntimeException('This transaction is missing its linked account.');
            }

            $account = $transaction->account;
            $branch = Branch::query()->find($transaction->branch_id);

            if (! $branch) {
                throw new RuntimeException('The linked branch for this transaction could not be found.');
            }

            foreach ($transaction->mirrors as $mirror) {
                if (strtolower((string) $mirror->dr_cr) === 'cr') {
                    $this->balanceSyncService->validateBranchCreditRemoval($branch, $mirror);
                }

                $mirror->forceFill([
                    'updated_user_id' => $actor->id,
                ])->save();

                $mirror->delete();
            }

            $transaction->forceFill([
                'updated_user_id' => $actor->id,
            ])->save();

            $transaction->delete();

            $account->updated_user_id = $actor->id;
            $this->balanceSyncService->syncSavingsAccount($account);
            $this->balanceSyncService->syncBranchLedger($branch, false);
        });
    }

    protected function createBranchMirrorTransaction(
        Branch $branch,
        User $actor,
        Transaction $transaction,
        SavingsAccount $account,
        string $batchId
    ): ?Transaction {
        if (! $branch->branch_user_id) {
            return null;
        }

        return Transaction::create([
            'user_id' => $branch->branch_user_id,
            'trans_date' => $transaction->trans_date,
            'savings_account_id' => null,
            'charge' => 0,
            'amount' => $transaction->amount,
            'gateway_amount' => 0,
            'dr_cr' => $transaction->dr_cr,
            'type' => $transaction->type,
            'attachment' => null,
            'method' => 'Manual',
            'status' => 2,
            'note' => null,
            'description' => $transaction->description,
            'loan_id' => null,
            'ref_id' => null,
            'parent_id' => $transaction->id,
            'gateway_id' => null,
            'created_user_id' => $actor->id,
            'updated_user_id' => null,
            'branch_id' => $branch->id,
            'transaction_details' => [
                'source_transaction_id' => $transaction->id,
                'member_id' => $transaction->user_id,
                'member_name' => $transaction->user?->name,
                'member_no' => $transaction->user?->display_member_no,
                'account_number' => $account->account_number,
                'account_type' => $account->product?->type,
            ],
            'tracking_id' => 'regular',
            'detail_id' => (string) $branch->id,
            'is_branch' => 1,
            'loan_details_id' => null,
            'loan_repayment_id' => null,
            'batch_id' => $batchId,
        ]);
    }

    protected function resolveAccountForMember(User $member, int $accountId): SavingsAccount
    {
        $account = SavingsAccount::query()
            ->with('product')
            ->where('user_id', $member->id)
            ->where('is_branch_acount', false)
            ->where('status', 1)
            ->find($accountId);

        if (! $account) {
            throw new RuntimeException('The selected account is invalid for the chosen member.');
        }

        return $account;
    }

    protected function normalizeAmount(mixed $amount): float
    {
        return round((float) $amount, 2);
    }

    protected function displayType(SavingsAccount $account): string
    {
        return Str::headline(strtolower($account->product?->type ?? 'Transaction'));
    }
}
