<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Branch;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class AssetAccountingService
{
    public function __construct(protected BalanceSyncService $balanceSyncService)
    {
    }

    public function create(Branch $branch, User $actor, array $payload): Asset
    {
        return DB::transaction(function () use ($branch, $actor, $payload): Asset {
            $this->ensureSocietyAccount($branch);

            $asset = Asset::create([
                ...$payload,
                'branch_id' => $branch->id,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $transaction = $this->upsertPurchaseTransaction($asset, $branch, $actor);

            if ($transaction) {
                $asset->update(['purchase_transaction_id' => $transaction->id]);
                $this->balanceSyncService->validateBranchLedgerMutation($branch, [$transaction->id]);
            }

            $this->balanceSyncService->syncBranchLedger($branch, false);

            return $asset->fresh(['branch', 'purchaseTransaction']);
        });
    }

    public function update(Asset $asset, Branch $branch, User $actor, array $payload): Asset
    {
        return DB::transaction(function () use ($asset, $branch, $actor, $payload): Asset {
            $this->ensureSocietyAccount($branch);
            $asset->update([
                ...$payload,
                'branch_id' => $branch->id,
                'updated_by' => $actor->id,
            ]);

            $transaction = $this->upsertPurchaseTransaction($asset, $branch, $actor);

            if ($transaction) {
                $asset->update(['purchase_transaction_id' => $transaction->id]);
                $this->balanceSyncService->validateBranchLedgerMutation($branch, [$transaction->id]);
            } elseif ($asset->purchaseTransaction) {
                $asset->purchaseTransaction->delete();
                $asset->update(['purchase_transaction_id' => null]);
            }

            $this->balanceSyncService->syncBranchLedger($branch, false);

            return $asset->fresh(['branch', 'purchaseTransaction']);
        });
    }

    public function delete(Asset $asset, Branch $branch, User $actor): void
    {
        DB::transaction(function () use ($asset, $branch, $actor): void {
            $transaction = $asset->purchaseTransaction;

            if ($transaction) {
                $transaction->forceFill(['updated_user_id' => $actor->id])->save();
                $transaction->delete();
            }

            $asset->forceFill(['updated_by' => $actor->id])->save();
            $asset->delete();
            $this->balanceSyncService->syncBranchLedger($branch, false);
        });
    }

    public function linkExistingAsset(Asset $asset, User $actor, bool $validateBalance = true): Asset
    {
        $branch = $asset->branch;

        if (! $branch) {
            throw new RuntimeException("Asset #{$asset->id} has no branch and cannot be posted to a society purse.");
        }

        return DB::transaction(function () use ($asset, $branch, $actor, $validateBalance): Asset {
            $this->ensureSocietyAccount($branch);
            $transaction = $this->upsertPurchaseTransaction($asset, $branch, $actor);

            if ($transaction) {
                $asset->update(['purchase_transaction_id' => $transaction->id]);

                if ($validateBalance) {
                    $this->balanceSyncService->validateBranchLedgerMutation($branch, [$transaction->id]);
                }
            }

            $this->balanceSyncService->syncBranchLedger($branch, false);

            return $asset->fresh(['purchaseTransaction']);
        });
    }

    protected function upsertPurchaseTransaction(Asset $asset, Branch $branch, User $actor): ?Transaction
    {
        $amount = round((float) $asset->purchase_cost, 2);
        $transaction = $asset->purchaseTransaction;

        if ($amount <= 0) {
            return null;
        }

        $values = [
            'user_id' => $branch->branch_user_id,
            'trans_date' => $asset->purchase_date?->format('Y-m-d') ?: now()->format('Y-m-d'),
            'savings_account_id' => null,
            'charge' => 0,
            'amount' => $amount,
            'gateway_amount' => 0,
            'dr_cr' => 'dr',
            'type' => 'Asset Purchase',
            'attachment' => null,
            'method' => 'System',
            'status' => 2,
            'note' => null,
            'description' => "Asset purchase: {$asset->name}",
            'loan_id' => null,
            'ref_id' => null,
            'parent_id' => null,
            'gateway_id' => null,
            'updated_user_id' => $transaction ? $actor->id : null,
            'branch_id' => $branch->id,
            'transaction_details' => [
                'scope' => 'asset-purchase',
                'asset_id' => $asset->id,
                'asset_name' => $asset->name,
                'category' => $asset->category,
                'supplier' => $asset->supplier,
                'branch_name' => $branch->name,
                'balance_before' => null,
                'balance_after' => null,
            ],
            'tracking_id' => 'assets',
            'detail_id' => (string) $asset->id,
            'is_branch' => 1,
            'loan_details_id' => null,
            'loan_repayment_id' => null,
        ];

        if ($transaction) {
            $transaction->update($values);

            return $transaction->refresh();
        }

        return Transaction::create([
            ...$values,
            'created_user_id' => $actor->id,
            'batch_id' => (string) Str::uuid(),
        ]);
    }

    protected function ensureSocietyAccount(Branch $branch): void
    {
        if (! $branch->branch_user_id) {
            throw new RuntimeException('The active branch is not linked to a society account user yet.');
        }
    }
}
