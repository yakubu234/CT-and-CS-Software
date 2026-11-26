<?php

namespace App\Services\Sms;

use App\Models\Branch;
use App\Models\LoanPayment;
use App\Models\MemberBranchMembership;
use App\Models\SavingsAccount;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Builds the placeholder values for each kind of SMS from real system records. Live
 * alerts and the test page both use this, so a preview matches what members receive.
 */
class SmsContextBuilder
{
    public function money(float|int|string|null $value): string
    {
        return number_format((float) $value, 2);
    }

    public function accountLabel(?string $type): string
    {
        return Str::title(Str::lower((string) ($type ?: 'Account')));
    }

    /** Placeholders every message gets: member and society identity. */
    public function common(User $user, ?Branch $branch, ?MemberBranchMembership $membership = null): array
    {
        $societyName = $branch?->name ?: $membership?->branch?->name ?: $user->branch?->name;
        $memberNumber = $membership?->member_number ?: $user->display_member_no;

        return [
            'society_name' => $societyName,
            'branch_name' => $societyName,
            'parent_society_name' => SmsPlaceholderCatalog::PARENT_SOCIETY_NAME,
            'member_name' => $user->name,
            'first_name' => $user->getRawOriginal('name'),
            'last_name' => $user->last_name,
            'member_id' => $memberNumber,
            'member_no' => $memberNumber,
            'mobile' => $user->detail?->mobile,
        ];
    }

    /** The member's society membership for a branch (active first), if any. */
    public function membershipFor(User $user, ?int $branchId): ?MemberBranchMembership
    {
        if (! $branchId) {
            return null;
        }

        return MemberBranchMembership::query()
            ->with('branch')
            ->where('user_id', $user->id)
            ->where('branch_id', $branchId)
            ->orderByDesc('status')
            ->first();
    }

    /**
     * Current balance of each of the four member accounts, plus total and summary.
     * Scoped to one society membership when given, so other societies are not mixed in.
     *
     * @param  array<string, float>  $adjustments  per account key (savings, shares, ...), added to the balance
     */
    public function balances(User $user, ?MemberBranchMembership $membership, array $adjustments = []): array
    {
        $accounts = SavingsAccount::query()
            ->with('product')
            ->where('user_id', $user->id)
            ->where('is_branch_acount', false)
            ->where('status', 1)
            ->when($membership, fn ($query) => $query->where('member_branch_membership_id', $membership->id))
            ->get();

        $byKey = array_fill_keys(array_keys(SmsPlaceholderCatalog::ACCOUNT_TYPES), 0.0);
        $others = [];
        foreach ($accounts as $account) {
            $key = $this->accountKey($account->product?->type);
            if ($key) {
                $byKey[$key] += (float) $account->balance;
            } else {
                $label = $this->accountLabel($account->product?->type);
                $others[$label] = ($others[$label] ?? 0) + (float) $account->balance;
            }
        }

        foreach ($adjustments as $key => $amount) {
            if (array_key_exists($key, $byKey)) {
                $byKey[$key] += (float) $amount;
            }
        }

        $total = array_sum($byKey) + array_sum($others);
        $summary = [];
        $compact = [];
        foreach ($byKey as $key => $balance) {
            $label = $this->accountLabel(SmsPlaceholderCatalog::ACCOUNT_TYPES[$key]);
            $summary[] = $label . ' ' . $this->money($balance);
            $compact[] = ['savings' => 'Sav', 'shares' => 'Shr', 'authentication' => 'Auth', 'deposit' => 'Dep'][$key] . ' ' . $this->money($balance);
        }
        foreach ($others as $label => $balance) {
            $summary[] = $label . ' ' . $this->money($balance);
            $compact[] = $label . ' ' . $this->money($balance);
        }

        $context = [];
        foreach ($byKey as $key => $balance) {
            $context[$key . '_balance'] = $this->money($balance);
        }

        return [
            ...$context,
            'total_member_balance' => $this->money($total),
            'current_balance' => $this->money($total),
            'balance_summary' => implode(', ', $summary),
            'statement_summary' => implode(', ', $summary),
            'statement_compact' => implode(' | ', $compact),
            'statement_total_balance' => $this->money($total),
        ];
    }

    /**
     * Credit or debit alert for one posting (all of a member's entries saved together).
     *
     * @param  array<int, array{type: ?string, amount: float|string, account_number: ?string}>  $entries
     * @param  array<string, float>  $balanceAdjustments  only for previews of unsaved sample postings
     */
    public function transactionBatch(
        User $user,
        ?Branch $branch,
        ?MemberBranchMembership $membership,
        string $direction,
        array $entries,
        Carbon|string|null $date,
        array $balanceAdjustments = []
    ): array {
        $verb = strtolower($direction) === 'dr' ? 'debited' : 'credited';
        $amounts = array_fill_keys(array_keys(SmsPlaceholderCatalog::ACCOUNT_TYPES), 0.0);
        $details = [];
        $types = [];
        $numbers = [];
        $total = 0.0;

        foreach ($entries as $entry) {
            $amount = (float) $entry['amount'];
            $total += $amount;
            $key = $this->accountKey($entry['type'] ?? null);
            $label = $this->accountLabel($entry['type'] ?? null);

            if ($key) {
                $amounts[$key] += $amount;
            }
            $details[$label] = ($details[$label] ?? 0) + $amount;
            $types[$label] = $label;
            if (! empty($entry['account_number'])) {
                $numbers[$entry['account_number']] = $entry['account_number'];
            }
        }

        // List accounts in the standard order: Savings, Shares, Authentication, Deposit, others.
        $order = array_values(array_map(fn ($type) => $this->accountLabel($type), SmsPlaceholderCatalog::ACCOUNT_TYPES));
        $position = fn (string $label): int => ($index = array_search($label, $order, true)) === false ? 99 : $index;
        uksort($details, fn ($a, $b) => $position($a) <=> $position($b));

        $context = [
            'total_' . $verb => $this->money($total),
            $verb . '_account_details' => implode(', ', array_map(fn ($label, $amount) => $label . ' ' . $this->money($amount), array_keys($details), $details)),
            'amount' => $this->money($total),
            'account_type' => implode(', ', array_keys($details)),
            'account_number' => implode(', ', $numbers),
            'transaction_type' => $verb === 'debited' ? 'DR' : 'CR',
            'transaction_date' => $this->date($date),
        ];
        foreach ($amounts as $key => $amount) {
            $context[$key . '_' . $verb] = $this->money($amount);
        }

        return [
            ...$this->common($user, $branch, $membership),
            ...$context,
            ...$this->balances($user, $membership, $balanceAdjustments),
        ];
    }

    public function loanRepayment(LoanPayment $payment): array
    {
        $payment->loadMissing(['loan.borrower.detail', 'loan.branch', 'loan.membership.branch']);
        $loan = $payment->loan;
        $borrower = $loan?->borrower;

        if (! $borrower) {
            return [];
        }

        return $this->loanRepaymentValues(
            $borrower,
            $loan->branch,
            $loan->membership,
            [
                'principal_paid' => $payment->repayment_amount,
                'interest_paid' => $payment->interest_paid,
                'total_repayment' => $payment->total_amount,
                'outstanding_loan_balance' => $payment->balance,
                'outstanding_interest' => $payment->outstanding_interest,
                'loan_id' => $loan->loan_id,
                'loan_amount' => $loan->applied_amount,
                'transaction_date' => $payment->paid_at,
            ]
        );
    }

    /** Loan repayment placeholders from raw figures (used by live alerts and the test page). */
    public function loanRepaymentValues(User $borrower, ?Branch $branch, ?MemberBranchMembership $membership, array $figures): array
    {
        return [
            ...$this->common($borrower, $branch, $membership),
            'principal_paid' => $this->money($figures['principal_paid'] ?? 0),
            'interest_paid' => $this->money($figures['interest_paid'] ?? 0),
            'total_repayment' => $this->money($figures['total_repayment'] ?? ((float) ($figures['principal_paid'] ?? 0) + (float) ($figures['interest_paid'] ?? 0))),
            'outstanding_loan_balance' => $this->money($figures['outstanding_loan_balance'] ?? 0),
            'outstanding_interest' => $this->money($figures['outstanding_interest'] ?? 0),
            'loan_id' => (string) ($figures['loan_id'] ?? ''),
            'loan_amount' => $this->money($figures['loan_amount'] ?? 0),
            'transaction_date' => $this->date($figures['transaction_date'] ?? null),
        ];
    }

    public function date(Carbon|string|null $date): string
    {
        if (! $date) {
            return '';
        }

        return ($date instanceof Carbon ? $date : Carbon::parse($date))->format('d M Y');
    }

    public function accountKey(?string $type): ?string
    {
        $key = array_search(strtoupper((string) $type), SmsPlaceholderCatalog::ACCOUNT_TYPES, true);

        return $key === false ? null : $key;
    }
}
