<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\CustomField;
use App\Models\Loan;
use App\Models\LoanDetail;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Sms\SmsAutomationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LoanService
{
    public function currentOutstandingForBorrower(Branch $branch, User $borrower): float
    {
        $loan = Loan::query()
            ->where('branch_id', $branch->id)
            ->where('borrower_id', $borrower->id)
            ->first();

        if (! $loan) {
            return 0.0;
        }

        return round((float) ($loan->balanace ?? $loan->amount_due ?? 0), 2);
    }

    public function createRequest(Branch $branch, User $actor, User $borrower, array $payload): LoanDetail
    {
        return DB::transaction(function () use ($branch, $actor, $borrower, $payload): LoanDetail {
            $loan = Loan::query()
                ->where('branch_id', $branch->id)
                ->where('borrower_id', $borrower->id)
                ->first();

            if (! $loan) {
                $loan = Loan::create([
                    'loan_id' => $this->reserveNextLoanNumber($branch),
                    'loan_product_id' => 0,
                    'borrower_id' => $borrower->id,
                    'first_payment_date' => $payload['due_date'],
                    'release_date' => $payload['release_date'],
                    'applied_amount' => '0',
                    'total_payable' => '0',
                    'due_date' => $payload['due_date'],
                    'interest_rate' => '0',
                    'interest' => '0',
                    'total_paid' => '0',
                    'late_payment_penalties' => $payload['late_payment_penalties'] ?? 0,
                    'attachment' => null,
                    'description' => null,
                    'remarks' => null,
                    'status' => 0,
                    'approved_date' => null,
                    'approved_user_id' => null,
                    'created_user_id' => $actor->id,
                    'updated_user_id' => null,
                    'branch_id' => $branch->id,
                    'custom_fields' => [],
                    'repayment_status' => 'active',
                    'recent_added_amount' => '0',
                    'amount_due' => '0',
                    'balanace' => '0',
                    'interest_calculation' => null,
                ]);
            }

            $customFields = $this->prepareCustomFieldValues($payload['custom_fields'] ?? []);

            return LoanDetail::create([
                'applied_amount' => round((float) $payload['amount'], 2),
                'amount_repayed' => 0,
                'created_user_id' => $actor->id,
                'loan_id' => $loan->id,
                'borrower_id' => $borrower->id,
                'branch_id' => $branch->id,
                'release_date' => $payload['release_date'],
                'due_date' => $payload['due_date'],
                'late_payment_penalties' => $payload['late_payment_penalties'] ?? null,
                'remarks' => null,
                'interest_rate' => 0,
                'custom_fields' => $customFields,
                'interest' => 0,
                'status' => false,
                'repayment_status' => false,
                'interest_week_interval' => $payload['interest_week_interval'],
                'decision_status' => LoanDetail::STATUS_PENDING,
                'attachment' => $this->storeOptionalFile($payload['attachment'] ?? null, 'loans/attachments'),
            ]);
        });
    }

    public function approve(Branch $branch, User $actor, LoanDetail $detail): LoanDetail
    {
        return DB::transaction(function () use ($branch, $actor, $detail): LoanDetail {
            $detail->loadMissing(['loan.borrower.detail', 'borrower']);

            if ($detail->decision_status !== LoanDetail::STATUS_PENDING) {
                throw new RuntimeException('Only pending loan requests can be approved.');
            }

            $availableBalance = $this->branchLedgerBalance($branch);
            $amount = round((float) $detail->applied_amount, 2);

            if ($availableBalance < $amount) {
                throw new RuntimeException('The society purse does not have enough balance to approve this loan.');
            }

            if (! $branch->branch_user_id) {
                throw new RuntimeException('The active branch is not linked to a society account user yet.');
            }

            $loan = $detail->loan;

            Transaction::create([
                'user_id' => $branch->branch_user_id,
                'trans_date' => $detail->release_date,
                'savings_account_id' => null,
                'charge' => 0,
                'amount' => $amount,
                'gateway_amount' => 0,
                'dr_cr' => 'dr',
                'type' => 'Loan Disbursement',
                'attachment' => null,
                'method' => 'Manual',
                'status' => 2,
                'note' => null,
                'description' => 'Loan approved for ' . ($detail->borrower?->name ?: 'borrower'),
                'loan_id' => $loan->id,
                'ref_id' => null,
                'parent_id' => null,
                'gateway_id' => null,
                'created_user_id' => $actor->id,
                'updated_user_id' => null,
                'branch_id' => $branch->id,
                'transaction_details' => [
                    'loan_id' => $loan->loan_id,
                    'loan_detail_id' => $detail->id,
                    'borrower_id' => $detail->borrower_id,
                    'borrower_name' => $detail->borrower?->name,
                    'member_no' => $detail->borrower?->detail?->member_no ?: $detail->borrower?->member_no,
                    'balance_before' => $availableBalance,
                    'balance_after' => round($availableBalance - $amount, 2),
                ],
                'tracking_id' => 'loan',
                'detail_id' => (string) $detail->id,
                'is_branch' => 1,
                'loan_details_id' => $detail->id,
                'loan_repayment_id' => null,
                'batch_id' => null,
            ]);

            $newOutstanding = round((float) ($loan->amount_due ?? 0) + $amount, 2);
            $newBalance = round((float) ($loan->balanace ?? 0) + $amount, 2);

            $loan->update([
                'first_payment_date' => $detail->due_date,
                'release_date' => $detail->release_date,
                'applied_amount' => (string) $newOutstanding,
                'total_payable' => (string) $newOutstanding,
                'due_date' => $detail->due_date?->format('Y-m-d'),
                'late_payment_penalties' => (string) ($detail->late_payment_penalties ?? 0),
                'status' => 1,
                'approved_date' => now()->toDateString(),
                'approved_user_id' => $actor->id,
                'updated_user_id' => $actor->id,
                'recent_added_amount' => (string) $amount,
                'amount_due' => (string) $newOutstanding,
                'balanace' => (string) $newBalance,
                'repayment_status' => $newBalance > 0 ? 'active' : 'refunded',
            ]);

            $detail->update([
                'status' => true,
                'decision_status' => LoanDetail::STATUS_APPROVED,
                'approved_at' => now(),
                'approved_by' => $actor->id,
                'declined_at' => null,
                'declined_by' => null,
                'decline_reason' => null,
            ]);

            DB::afterCommit(function () use ($detail): void {
                app(SmsAutomationService::class)->handleLoanApproved($detail->fresh(['borrower.detail', 'borrower.branch', 'loan']));
            });

            return $detail->fresh(['loan.borrower.detail', 'borrower', 'approver']);
        });
    }

    public function decline(User $actor, LoanDetail $detail, ?string $reason = null): LoanDetail
    {
        if ($detail->decision_status !== LoanDetail::STATUS_PENDING) {
            throw new RuntimeException('Only pending loan requests can be declined.');
        }

        $detail->update([
            'decision_status' => LoanDetail::STATUS_DECLINED,
            'declined_at' => now(),
            'declined_by' => $actor->id,
            'decline_reason' => $reason,
        ]);

        return $detail->fresh(['loan.borrower.detail', 'borrower', 'decliner']);
    }

    public function updateRequest(Branch $branch, User $actor, LoanDetail $detail, array $payload): LoanDetail
    {
        return DB::transaction(function () use ($branch, $actor, $detail, $payload): LoanDetail {
            $detail->loadMissing(['loan.details.payments', 'borrower.detail']);

            if (! $detail->canBeEdited()) {
                throw new RuntimeException('This loan request can no longer be edited.');
            }

            $newAmount = round((float) $payload['amount'], 2);
            $repaidAmount = $detail->totalRepaymentMade();

            if ($detail->decision_status === LoanDetail::STATUS_APPROVED && $newAmount < $repaidAmount) {
                throw new RuntimeException(
                    'This approved loan already has repayments. You cannot reduce it below the total repaid amount of ₦' .
                    number_format($repaidAmount, 2) . '.'
                );
            }

            $existingCustomFields = is_array($detail->custom_fields) ? $detail->custom_fields : [];
            $customFields = $this->prepareCustomFieldValues($payload['custom_fields'] ?? [], $existingCustomFields);

            $updates = [
                'applied_amount' => $newAmount,
                'release_date' => $payload['release_date'],
                'due_date' => $payload['due_date'],
                'late_payment_penalties' => $payload['late_payment_penalties'] ?? null,
                'interest_week_interval' => $payload['interest_week_interval'],
                'custom_fields' => $customFields,
            ];

            if (($payload['attachment'] ?? null) instanceof UploadedFile) {
                $updates['attachment'] = $this->storeOptionalFile($payload['attachment'], 'loans/attachments');
            }

            $detail->update($updates);

            if ($detail->decision_status === LoanDetail::STATUS_APPROVED) {
                $transaction = Transaction::query()
                    ->where('loan_details_id', $detail->id)
                    ->where('tracking_id', 'loan')
                    ->where('is_branch', true)
                    ->whereNull('deleted_at')
                    ->first();

                $availableBalance = $this->branchLedgerBalance($branch) + (float) ($transaction?->amount ?? 0);

                if ($availableBalance < $newAmount) {
                    throw new RuntimeException('The society purse does not have enough balance for this updated loan amount.');
                }

                if ($transaction) {
                    $transaction->update([
                        'trans_date' => $detail->release_date,
                        'amount' => $newAmount,
                        'description' => 'Loan approved for ' . ($detail->borrower?->name ?: 'borrower'),
                        'transaction_details' => array_merge($transaction->transaction_details ?? [], [
                            'loan_id' => $detail->loan?->loan_id,
                            'loan_detail_id' => $detail->id,
                            'borrower_id' => $detail->borrower_id,
                            'borrower_name' => $detail->borrower?->name,
                            'member_no' => $detail->borrower?->detail?->member_no ?: $detail->borrower?->member_no,
                            'balance_after' => round($availableBalance - $newAmount, 2),
                        ]),
                        'updated_user_id' => $actor->id,
                    ]);
                }
            }

            $this->syncLoanAggregate($detail->loan->fresh());

            return $detail->fresh(['loan.borrower.detail', 'borrower', 'approver', 'decliner']);
        });
    }

    public function deleteRequest(User $actor, LoanDetail $detail): void
    {
        DB::transaction(function () use ($actor, $detail): void {
            $detail->loadMissing(['loan.details', 'borrower.detail']);

            if (! $detail->canBeDeleted()) {
                throw new RuntimeException(
                    'This loan cannot be deleted anymore because repayment has already been recorded for it. ' .
                    'You can edit it instead and adjust it down to the current repayment amount to mark it as completed.'
                );
            }

            if ($detail->decision_status === LoanDetail::STATUS_APPROVED) {
                Transaction::query()
                    ->where('loan_details_id', $detail->id)
                    ->where('tracking_id', 'loan')
                    ->where('is_branch', true)
                    ->whereNull('deleted_at')
                    ->get()
                    ->each(function (Transaction $transaction) use ($actor): void {
                        $transaction->update([
                            'updated_user_id' => $actor->id,
                            'loan_details_id' => null,
                            'detail_id' => (string) $transaction->detail_id,
                        ]);
                        $transaction->delete();
                    });
            }

            $loan = $detail->loan;

            $detail->delete();

            if ($loan->details()->count() === 0) {
                $loan->delete();

                return;
            }

            $this->syncLoanAggregate($loan->fresh());
        });
    }

    public function prepareCustomFieldValues(array $values, array $existingValues = []): array
    {
        $fields = CustomField::query()
            ->where('table', 'loans')
            ->where('status', true)
            ->orderBy('order')
            ->orderBy('field_name')
            ->get()
            ->keyBy(fn (CustomField $field): string => (string) $field->id);

        $payload = [];

        foreach ($fields as $fieldId => $field) {
            if (! array_key_exists($fieldId, $values) && $field->field_type === CustomField::TYPE_FILE) {
                if (! empty($existingValues[$fieldId]['value'])) {
                    $payload[$fieldId] = $existingValues[$fieldId];
                }

                continue;
            }

            if (! array_key_exists($fieldId, $values)) {
                continue;
            }

            $value = $values[$fieldId];

            if ($field->field_type === CustomField::TYPE_FILE) {
                if ($value instanceof UploadedFile) {
                    $value = $value->store('loans/custom-fields', 'public');
                } else {
                    $value = $existingValues[$fieldId]['value'] ?? null;
                }
            }

            if ($value === null || $value === '') {
                continue;
            }

            $payload[$fieldId] = [
                'field_id' => (int) $field->id,
                'label' => $field->field_name,
                'type' => $field->field_type,
                'value' => $value,
            ];
        }

        return $payload;
    }

    public function reserveNextLoanNumber(Branch $branch): string
    {
        DB::update(
            "
                UPDATE branches
                SET loan_count = LPAD(CAST(COALESCE(NULLIF(loan_count, ''), '0') AS UNSIGNED) + ?, 4, '0')
                WHERE id = ?
            ",
            [1, $branch->id]
        );

        $branch->refresh();

        $prefix = $branch->id_prefix ?: ($branch->prefix ?: 'LOAN');

        return $prefix . '_' . str_pad((string) ((int) $branch->loan_count), 4, '0', STR_PAD_LEFT);
    }

    public function branchLedgerBalance(Branch $branch): float
    {
        return round(
            (float) Transaction::query()
                ->where('branch_id', $branch->id)
                ->where('is_branch', true)
                ->whereNull('deleted_at')
                ->sum(DB::raw("case when lower(dr_cr) = 'cr' then amount else -amount end")),
            2
        );
    }

    public function projectedOutstandingForDetail(LoanDetail $detail, ?float $replacementAmount = null): float
    {
        $loan = $detail->loan;
        $currentOutstanding = (float) ($loan?->balanace ?? 0);
        $currentAmount = (float) ($detail->applied_amount ?? 0);
        $newAmount = $replacementAmount ?? $currentAmount;
        $repaidAmount = $detail->totalRepaymentMade();

        return round(max($currentOutstanding - ($currentAmount - $repaidAmount) + ($newAmount - $repaidAmount), 0), 2);
    }

    protected function storeOptionalFile(?UploadedFile $file, string $path): ?string
    {
        if (! $file) {
            return null;
        }

        return $file->store($path, 'public');
    }

    public function syncLoanAggregate(Loan $loan): void
    {
        $loan->loadMissing('details.payments');

        $approvedDetails = $loan->details
            ->where('decision_status', LoanDetail::STATUS_APPROVED)
            ->sortBy('id')
            ->values();

        if ($approvedDetails->isEmpty()) {
            $loan->update([
                'first_payment_date' => optional($loan->first_payment_date)->format('Y-m-d'),
                'release_date' => optional($loan->release_date)->format('Y-m-d'),
                'applied_amount' => '0',
                'total_payable' => '0',
                'due_date' => optional($loan->due_date)->format('Y-m-d'),
                'late_payment_penalties' => '0',
                'status' => 0,
                'approved_date' => null,
                'approved_user_id' => null,
                'recent_added_amount' => '0',
                'amount_due' => '0',
                'balanace' => '0',
                'total_paid' => '0',
                'repayment_status' => 'active',
            ]);

            return;
        }

        $latestApproved = $approvedDetails->last();
        $totalApproved = round((float) $approvedDetails->sum(fn (LoanDetail $detail): float => (float) $detail->applied_amount), 2);
        $totalPaid = round((float) $approvedDetails->sum(fn (LoanDetail $detail): float => $detail->totalRepaymentMade()), 2);
        $balance = round(max($totalApproved - $totalPaid, 0), 2);

        foreach ($approvedDetails as $detail) {
            $detailPaid = $detail->totalRepaymentMade();
            $detail->forceFill([
                'amount_repayed' => $detailPaid,
                'repayment_status' => $detailPaid >= (float) $detail->applied_amount,
            ])->save();
        }

        $loan->update([
            'first_payment_date' => $latestApproved->due_date?->format('Y-m-d'),
            'release_date' => $latestApproved->release_date?->format('Y-m-d'),
            'applied_amount' => (string) $totalApproved,
            'total_payable' => (string) $totalApproved,
            'due_date' => $latestApproved->due_date?->format('Y-m-d'),
            'late_payment_penalties' => (string) ((float) ($latestApproved->late_payment_penalties ?? 0)),
            'status' => $balance > 0 ? 1 : 2,
            'approved_date' => optional($latestApproved->approved_at)->toDateString() ?: null,
            'approved_user_id' => $latestApproved->approved_by,
            'recent_added_amount' => (string) ((float) $latestApproved->applied_amount),
            'amount_due' => (string) $totalApproved,
            'balanace' => (string) $balance,
            'total_paid' => (string) $totalPaid,
            'repayment_status' => $balance > 0 ? 'active' : 'refunded',
        ]);
    }
}
