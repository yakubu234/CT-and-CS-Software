<?php

namespace App\Http\Controllers;

use App\Models\CustomerSupportRequest;
use App\Models\EmailMessage;
use App\Models\Loan;
use App\Models\LoanDetail;
use App\Models\LoanPayment;
use App\Models\SmsMessage;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CustomerPortalController extends Controller
{
    public function dashboard(Request $request): View
    {
        $customer = $this->customer($request);
        $accounts = $this->memberAccounts($customer);
        $loans = $this->loansQuery($customer)
            ->with('details')
            ->latest('id')
            ->limit(5)
            ->get();

        return view('customer.dashboard', [
            'customer' => $customer,
            'accounts' => $accounts,
            'accountSummary' => $this->accountSummary($accounts),
            'loanSnapshot' => $this->loanSnapshot($customer),
            'recentTransactions' => $this->transactionsQuery($customer)->limit(8)->get(),
            'loans' => $loans,
        ]);
    }

    public function editPassword(Request $request): View
    {
        return view('customer.password', [
            'customer' => $this->customer($request),
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $customer = $this->customer($request);
        $wasForcedChange = (bool) $customer->must_change_password;

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8), 'different:current_password'],
        ]);

        if (! Hash::check($validated['current_password'], $customer->password)) {
            return back()
                ->withErrors(['current_password' => 'The current password is incorrect.'])
                ->onlyInput('current_password');
        }

        $customer->password = $validated['password'];
        $customer->must_change_password = false;
        $customer->save();

        return redirect()
            ->route($wasForcedChange ? 'customer.dashboard' : 'customer.profile')
            ->with('status', 'Your password has been updated successfully.');
    }

    public function accounts(Request $request): View
    {
        $customer = $this->customer($request);

        return view('customer.accounts', [
            'customer' => $customer,
            'accounts' => $this->memberAccounts($customer),
        ]);
    }

    public function statement(Request $request): View
    {
        $customer = $this->customer($request);
        $accounts = $this->memberAccounts($customer);

        $transactions = $this->filteredTransactionsQuery($customer, $request, $accounts)
            ->paginate(20)
            ->withQueryString();

        return view('customer.statement', [
            'customer' => $customer,
            'accounts' => $accounts,
            'transactions' => $transactions,
            'filters' => $request->only(['account_id', 'type', 'start_date', 'end_date']),
        ]);
    }

    public function loans(Request $request): View
    {
        $customer = $this->customer($request);

        return view('customer.loans', [
            'customer' => $customer,
            'loans' => $this->applyDateRange($this->loansQuery($customer), $request, 'created_at')
                ->with(['details.payments'])
                ->latest('id')
                ->paginate(15)
                ->withQueryString(),
            'loanRequests' => $this->applyDateRange(
                LoanDetail::query()
                    ->with('loan')
                    ->where('borrower_id', $customer->id),
                $request,
                'created_at'
            )
                    ->latest('id')
                    ->paginate(15, ['*'], 'requests_page')
                    ->withQueryString(),
            'filters' => $request->only(['start_date', 'end_date']),
        ]);
    }

    public function repayments(Request $request): View
    {
        $customer = $this->customer($request);

        return view('customer.repayments', [
            'customer' => $customer,
            'payments' => $this->applyDateRange(
                LoanPayment::query()
                    ->with(['loan', 'detail'])
                    ->whereHas('loan', function (Builder $query) use ($customer): void {
                        $query->where('borrower_id', $customer->id);
                    }),
                $request,
                'paid_at'
            )
                    ->latest('paid_at')
                    ->latest('id')
                    ->paginate(20)
                    ->withQueryString(),
            'nextRepaymentDue' => $this->nextRepaymentDue($customer),
            'filters' => $request->only(['start_date', 'end_date']),
        ]);
    }

    public function transactions(Request $request): View
    {
        return $this->statement($request);
    }

    public function exportTransactions(Request $request)
    {
        $customer = $this->customer($request);
        $accounts = $this->memberAccounts($customer);
        $transactions = $this->filteredTransactionsQuery($customer, $request, $accounts)->get();

        return response()->streamDownload(function () use ($transactions): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Account Number', 'Account Type', 'Description', 'Method', 'Type', 'Amount']);

            foreach ($transactions as $transaction) {
                fputcsv($handle, [
                    optional($transaction->trans_date)->format('Y-m-d'),
                    $transaction->account?->account_number,
                    $transaction->account?->product?->type,
                    $transaction->description ?: $transaction->note ?: 'Transaction',
                    $transaction->method,
                    strtoupper((string) $transaction->dr_cr),
                    number_format((float) $transaction->amount, 2, '.', ''),
                ]);
            }

            fclose($handle);
        }, 'member-transactions.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function notifications(Request $request): View
    {
        $customer = $this->customer($request);

        return view('customer.notifications', [
            'customer' => $customer,
            'emails' => $this->applyDateRange(
                EmailMessage::query()->where('user_id', $customer->id),
                $request,
                'created_at'
            )
                    ->latest('created_at')
                    ->paginate(15, ['*'], 'emails_page')
                    ->withQueryString(),
            'smsMessages' => $this->applyDateRange(
                SmsMessage::query()->where('user_id', $customer->id),
                $request,
                'created_at'
            )
                    ->latest('created_at')
                    ->paginate(15, ['*'], 'sms_page')
                    ->withQueryString(),
            'filters' => $request->only(['start_date', 'end_date']),
        ]);
    }

    public function profile(Request $request): View
    {
        $customer = $this->customer($request)->load(['detail', 'branch', 'documents']);

        return view('customer.profile', [
            'customer' => $customer,
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $customer = $this->customer($request);

        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($customer->id),
            ],
            'mobile' => ['required', 'string', 'max:50'],
        ]);

        $customer->email = $validated['email'];
        $customer->save();

        $customer->detail()->updateOrCreate(
            ['user_id' => $customer->id],
            [
                'branch_id' => $customer->branch_id,
                'mobile' => $validated['mobile'],
            ]
        );

        return redirect()
            ->route('customer.profile')
            ->with('status', 'Your profile has been updated successfully.');
    }

    public function support(Request $request): View
    {
        $customer = $this->customer($request);

        return view('customer.support', [
            'customer' => $customer,
            'requests' => $this->applyDateRange(
                CustomerSupportRequest::query()->where('user_id', $customer->id),
                $request,
                'created_at'
            )
                    ->latest('id')
                    ->paginate(10)
                    ->withQueryString(),
            'filters' => $request->only(['start_date', 'end_date']),
        ]);
    }

    public function storeSupport(Request $request): RedirectResponse
    {
        $customer = $this->customer($request);

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        CustomerSupportRequest::create([
            ...$validated,
            'user_id' => $customer->id,
            'branch_id' => $customer->branch_id,
            'status' => 'open',
        ]);

        return redirect()
            ->route('customer.support')
            ->with('status', 'Your support request has been submitted.');
    }

    protected function customer(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }

    protected function memberAccounts(User $customer)
    {
        return $customer->savingsAccounts()
            ->with('product')
            ->where('is_branch_acount', false)
            ->orderBy('id')
            ->get()
            ->sortBy(fn ($account): int => match (strtoupper((string) ($account->product?->type ?? ''))) {
                'SAVINGS' => 1,
                'SHARES' => 2,
                'AUTHENTICATION' => 3,
                'DEPOSIT' => 4,
                default => 5,
            })
            ->values();
    }

    protected function accountSummary($accounts): array
    {
        $rows = $accounts->mapWithKeys(function ($account): array {
            return [
                strtoupper((string) ($account->product?->type ?? 'ACCOUNT')) => [
                    'balance' => (float) $account->balance,
                    'account_id' => $account->id,
                ],
            ];
        });

        return collect([
            'Savings' => $rows['SAVINGS'] ?? ['balance' => 0, 'account_id' => null],
            'Shares' => $rows['SHARES'] ?? ['balance' => 0, 'account_id' => null],
            'Authentication' => $rows['AUTHENTICATION'] ?? ['balance' => 0, 'account_id' => null],
            'Deposit' => $rows['DEPOSIT'] ?? ['balance' => 0, 'account_id' => null],
        ])->map(function (array $summary): array {
            $accountId = $summary['account_id'] ?? null;

            return [
                'balance' => (float) ($summary['balance'] ?? 0),
                'url' => $accountId
                    ? route('customer.statement', ['account_id' => $accountId])
                    : route('customer.accounts'),
            ];
        })->all();
    }

    protected function loanSnapshot(User $customer): array
    {
        $loans = Loan::query()
            ->where('borrower_id', $customer->id)
            ->whereHas('details', function (Builder $query): void {
                $query->where('decision_status', LoanDetail::STATUS_APPROVED);
            })
            ->with(['payments' => function ($query): void {
                $query->whereNull('deleted_at');
            }])
            ->get();

        $loanIds = $loans->pluck('id')->all();
        $approvedAmount = round((float) $loans->sum(fn (Loan $loan): float => (float) ($loan->applied_amount ?? 0)), 2);
        $outstandingBalance = round((float) $loans->sum(fn (Loan $loan): float => (float) ($loan->balanace ?? 0)), 2);
        $principalRepaid = round((float) $loans->sum(fn (Loan $loan): float => (float) ($loan->total_paid ?? 0)), 2);
        $totalInterestPaid = round((float) $loans->sum(function (Loan $loan): float {
            return (float) $loan->payments->sum(fn (LoanPayment $payment): float => (float) ($payment->interest_paid ?? $payment->is_interest_paid ?? 0));
        }), 2);
        $outstandingInterest = round((float) $loans->sum(function (Loan $loan): float {
            return (float) $loan->payments
                ->where('carry_forward', 1)
                ->sum(fn (LoanPayment $payment): float => (float) ($payment->outstanding_interest ?? 0));
        }), 2);
        $progress = $approvedAmount > 0 ? round(min(($principalRepaid / $approvedAmount) * 100, 100), 1) : 0;

        $nextRepayment = $loanIds === []
            ? null
            : LoanDetail::query()
                ->with('loan')
                ->whereIn('loan_id', $loanIds)
                ->where('borrower_id', $customer->id)
                ->where('decision_status', LoanDetail::STATUS_APPROVED)
                ->where('repayment_status', false)
                ->orderBy('due_date')
                ->first();

        $hasOverdue = $loanIds !== [] && LoanDetail::query()
            ->whereIn('loan_id', $loanIds)
            ->where('borrower_id', $customer->id)
            ->where('decision_status', LoanDetail::STATUS_APPROVED)
            ->where('repayment_status', false)
            ->whereDate('due_date', '<', now()->toDateString())
            ->exists();

        return [
            'approved_amount' => $approvedAmount,
            'outstanding_balance' => $outstandingBalance,
            'total_interest_paid' => $totalInterestPaid,
            'outstanding_interest' => $outstandingInterest,
            'principal_repaid' => $principalRepaid,
            'next_repayment_date' => $nextRepayment?->due_date,
            'next_repayment_amount' => (float) ($nextRepayment?->applied_amount ?? 0),
            'status' => $hasOverdue ? 'Overdue' : ($outstandingBalance > 0 ? 'Active' : ($approvedAmount > 0 ? 'Completed' : 'No Active Loan')),
            'progress' => $progress,
            'has_loans' => $approvedAmount > 0,
        ];
    }

    protected function loansQuery(User $customer): Builder
    {
        return Loan::query()
            ->where('borrower_id', $customer->id)
            ->where(function (Builder $query): void {
                $query->whereRaw('CAST(COALESCE(balanace, 0) AS DECIMAL(15,2)) > 0')
                    ->orWhereRaw('CAST(COALESCE(amount_due, 0) AS DECIMAL(15,2)) > 0')
                    ->orWhere('status', 1);
            });
    }

    protected function transactionsQuery(User $customer): Builder
    {
        return Transaction::query()
            ->with('account.product')
            ->where('user_id', $customer->id)
            ->where('is_branch', false)
            ->latest('trans_date')
            ->latest('id');
    }

    protected function filteredTransactionsQuery(User $customer, Request $request, $accounts): Builder
    {
        return $this->transactionsQuery($customer)
            ->when($request->filled('account_id'), function (Builder $query) use ($request, $accounts): void {
                $accountIds = $accounts->pluck('id')->all();
                $accountId = (int) $request->input('account_id');

                if (in_array($accountId, $accountIds, true)) {
                    $query->where('savings_account_id', $accountId);
                }
            })
            ->when($request->filled('type'), function (Builder $query) use ($request): void {
                $query->where('dr_cr', $request->input('type'));
            })
            ->when($request->filled('start_date'), function (Builder $query) use ($request): void {
                $query->whereDate('trans_date', '>=', $request->input('start_date'));
            })
            ->when($request->filled('end_date'), function (Builder $query) use ($request): void {
                $query->whereDate('trans_date', '<=', $request->input('end_date'));
            });
    }

    protected function applyDateRange(Builder $query, Request $request, string $column): Builder
    {
        return $query
            ->when($request->filled('start_date'), function (Builder $query) use ($request, $column): void {
                $query->whereDate($column, '>=', $request->input('start_date'));
            })
            ->when($request->filled('end_date'), function (Builder $query) use ($request, $column): void {
                $query->whereDate($column, '<=', $request->input('end_date'));
            });
    }

    protected function nextRepaymentDue(User $customer): ?LoanDetail
    {
        return LoanDetail::query()
            ->with('loan')
            ->where('borrower_id', $customer->id)
            ->where('decision_status', LoanDetail::STATUS_APPROVED)
            ->where('repayment_status', false)
            ->whereDate('due_date', '>=', now()->toDateString())
            ->orderBy('due_date')
            ->first();
    }
}
