<?php

use App\Models\LoanDetail;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('loan_details')
            ->where(function ($query): void {
                $query->where('status', true)
                    ->orWhereExists(function ($transactionQuery): void {
                        $transactionQuery->selectRaw('1')
                            ->from('transactions')
                            ->whereColumn('transactions.loan_id', 'loan_details.loan_id')
                            ->where('transactions.tracking_id', 'loan')
                            ->where('transactions.is_branch', true)
                            ->whereNull('transactions.deleted_at')
                            ->where(function ($linkQuery): void {
                                $linkQuery->whereColumn('transactions.loan_details_id', 'loan_details.id')
                                    ->orWhereColumn('transactions.detail_id', 'loan_details.id');
                            });
                    });
            })
            ->where('decision_status', LoanDetail::STATUS_PENDING)
            ->orderBy('id')
            ->chunkById(500, function ($details): void {
                foreach ($details as $detail) {
                    $loan = DB::table('loans')->where('id', $detail->loan_id)->first();

                    DB::table('loan_details')
                        ->where('id', $detail->id)
                        ->update([
                            'decision_status' => LoanDetail::STATUS_APPROVED,
                            'approved_at' => $detail->approved_at
                                ?? $loan?->approved_date
                                ?? $detail->updated_at
                                ?? now(),
                            'approved_by' => $detail->approved_by
                                ?? $loan?->approved_user_id
                                ?? $detail->created_user_id,
                        ]);
                }
            });
    }

    public function down(): void
    {
        // Legacy approval state cannot be safely distinguished after reconciliation.
    }
};
