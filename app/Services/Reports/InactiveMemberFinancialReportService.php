<?php

namespace App\Services\Reports;

use App\Models\Branch;
use App\Models\User;
use App\Support\MemberNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InactiveMemberFinancialReportService
{
    public function build(Branch $branch, Request $request): array
    {
        $members = User::withTrashed()
            ->with('detail')
            ->where('branch_id', $branch->id)
            ->where('branch_account', false)
            ->where('user_type', 'customer')
            ->where(function ($query): void {
                $query->where('status', 0)->orWhereNotNull('deleted_at');
            })
            ->orderBy('name')
            ->get();

        $options = $members->map(fn (User $member): array => [
            'id' => $member->id,
            'name' => $member->name,
            'member_no' => MemberNumber::normalize($member->detail?->member_no ?: $member->member_no, $branch),
        ]);

        $memberId = (int) $request->integer('member_id');
        $search = trim((string) $request->input('search'));
        $members = $members->filter(function (User $member) use ($memberId, $search): bool {
            if ($memberId > 0 && (int) $member->id !== $memberId) {
                return false;
            }

            return $search === '' || str_contains(mb_strtolower($member->name . ' ' . $member->member_no . ' ' . ($member->detail?->member_no ?? '')), mb_strtolower($search));
        })->values();

        $ids = $members->pluck('id')->all();
        $accounts = DB::table('transactions')
            ->join('savings_accounts', 'savings_accounts.id', '=', 'transactions.savings_account_id')
            ->join('savings_products', 'savings_products.id', '=', 'savings_accounts.savings_product_id')
            ->whereIn('transactions.user_id', $ids)
            ->where('transactions.branch_id', $branch->id)
            ->where('transactions.is_branch', false)
            ->whereNull('transactions.deleted_at')
            ->where('transactions.tracking_id', 'regular')
            ->groupBy('transactions.user_id', 'savings_products.id', 'savings_products.name', 'savings_products.type')
            ->selectRaw("transactions.user_id, savings_products.name, savings_products.type, SUM(CASE WHEN LOWER(transactions.dr_cr) = 'cr' THEN transactions.amount ELSE -transactions.amount END) as balance")
            ->get()->groupBy('user_id');

        $openingBalances = DB::table('savings_accounts')
            ->join('savings_products', 'savings_products.id', '=', 'savings_accounts.savings_product_id')
            ->whereIn('savings_accounts.user_id', $ids)
            ->where('savings_accounts.is_branch_acount', false)
            ->groupBy('savings_accounts.user_id', 'savings_products.id', 'savings_products.name', 'savings_products.type')
            ->selectRaw('savings_accounts.user_id, savings_products.name, savings_products.type, SUM(savings_accounts.opening_balance) as balance')
            ->get()->groupBy('user_id');

        $loans = DB::table('loan_details')
            ->whereIn('borrower_id', $ids)
            ->where('branch_id', $branch->id)
            ->where('decision_status', 'approved')
            ->groupBy('borrower_id')
            ->selectRaw('borrower_id, SUM(applied_amount) as amount')
            ->pluck('amount', 'borrower_id');

        $repayments = DB::table('loan_payments')
            ->join('loans', 'loans.id', '=', 'loan_payments.loan_id')
            ->whereIn('loans.borrower_id', $ids)
            ->where('loans.branch_id', $branch->id)
            ->whereNull('loan_payments.deleted_at')
            ->groupBy('loans.borrower_id')
            ->selectRaw('loans.borrower_id, SUM(loan_payments.repayment_amount) as amount')
            ->pluck('amount', 'borrower_id');

        $rows = $members->map(function (User $member) use ($branch, $accounts, $openingBalances, $loans, $repayments): array {
            $balances = ['SAVINGS' => 0.0, 'SHARES' => 0.0, 'AUTHENTICATION' => 0.0, 'DEPOSIT' => 0.0, 'BUILDING_FUND' => 0.0];
            $other = [];

            foreach ($accounts->get($member->id, collect())->concat($openingBalances->get($member->id, collect())) as $account) {
                $type = strtoupper((string) $account->type);
                $amount = round((float) $account->balance, 2);
                if (str_contains(mb_strtolower((string) $account->name), 'building fund')) {
                    $balances['BUILDING_FUND'] += $amount;
                } elseif (array_key_exists($type, $balances)) {
                    $balances[$type] += $amount;
                } else {
                    $name = $account->name ?: $type;
                    $other[$name] = round(($other[$name] ?? 0) + $amount, 2);
                }
            }

            $loanAmount = round((float) ($loans[$member->id] ?? 0), 2);
            $loanRepayment = round((float) ($repayments[$member->id] ?? 0), 2);
            $outstanding = round(max($loanAmount - $loanRepayment, 0), 2);
            $accountTotal = round(array_sum($balances) + array_sum($other), 2);

            return [
                'id' => $member->id,
                'name' => $member->name,
                'member_no' => MemberNumber::normalize($member->detail?->member_no ?: $member->member_no, $branch),
                'savings' => round($balances['SAVINGS'], 2),
                'shares' => round($balances['SHARES'], 2),
                'building_fund' => round($balances['BUILDING_FUND'], 2),
                'authentication' => round($balances['AUTHENTICATION'], 2),
                'deposits' => round($balances['DEPOSIT'], 2),
                'other' => $other,
                'account_total' => $accountTotal,
                'loan_amount' => $loanAmount,
                'loan_repayment' => $loanRepayment,
                'outstanding_loan' => $outstanding,
                'net_position' => round($accountTotal - $outstanding, 2),
            ];
        });

        return ['rows' => $rows, 'options' => $options, 'totals' => [
            'account_total' => round((float) $rows->sum('account_total'), 2),
            'outstanding_loan' => round((float) $rows->sum('outstanding_loan'), 2),
            'net_position' => round((float) $rows->sum('net_position'), 2),
        ]];
    }
}
