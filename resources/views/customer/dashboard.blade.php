@extends('layouts.customer')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')
@section('page_subtitle', 'Your account balances, loan position, and recent activity.')

@section('content')
    <div class="row">
        @foreach ($accountSummary as $label => $summary)
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card customer-card h-100 position-relative">
                    <div class="card-body">
                        <div class="text-muted small">{{ $label }}</div>
                        <div class="h4 money-value mb-1">&#8358;{{ number_format((float) $summary['balance'], 2) }}</div>
                        <div class="small text-primary">View history</div>
                        <a href="{{ $summary['url'] }}" class="stretched-link" aria-label="View {{ $label }} history"></a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-lg-8 mb-3">
            <div class="card customer-card h-100 position-relative">
                <div class="card-header">
                    <h3 class="card-title">Loan Snapshot</h3>
                    <div class="card-tools">
                        <span class="badge badge-{{
                            $loanSnapshot['status'] === 'Overdue'
                                ? 'danger'
                                : ($loanSnapshot['status'] === 'Completed' ? 'success' : ($loanSnapshot['status'] === 'Active' ? 'warning' : 'secondary'))
                        }}">
                            {{ $loanSnapshot['status'] }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 col-sm-6 mb-3">
                            <div class="text-muted small">Loan Amount Approved</div>
                            <div class="font-weight-bold money-value">&#8358;{{ number_format((float) $loanSnapshot['approved_amount'], 2) }}</div>
                        </div>
                        <div class="col-md-4 col-sm-6 mb-3">
                            <div class="text-muted small">Outstanding Loan Balance</div>
                            <div class="font-weight-bold money-value">&#8358;{{ number_format((float) $loanSnapshot['outstanding_balance'], 2) }}</div>
                        </div>
                        <div class="col-md-4 col-sm-6 mb-3">
                            <div class="text-muted small">Total Principal Repaid</div>
                            <div class="font-weight-bold money-value">&#8358;{{ number_format((float) $loanSnapshot['principal_repaid'], 2) }}</div>
                        </div>
                        <div class="col-md-4 col-sm-6 mb-3">
                            <div class="text-muted small">Total Interest Paid</div>
                            <div class="font-weight-bold money-value">&#8358;{{ number_format((float) $loanSnapshot['total_interest_paid'], 2) }}</div>
                        </div>
                        <div class="col-md-4 col-sm-6 mb-3">
                            <div class="text-muted small">Outstanding Interest</div>
                            <div class="font-weight-bold money-value">&#8358;{{ number_format((float) $loanSnapshot['outstanding_interest'], 2) }}</div>
                        </div>
                        <div class="col-md-4 col-sm-6 mb-3">
                            <div class="text-muted small">Next Repayment</div>
                            @if ($loanSnapshot['next_repayment_date'])
                                <div class="font-weight-bold">{{ optional($loanSnapshot['next_repayment_date'])->format('d M Y') }}</div>
                                <div class="small text-muted">&#8358;{{ number_format((float) $loanSnapshot['next_repayment_amount'], 2) }}</div>
                            @else
                                <div class="font-weight-bold">No upcoming repayment found</div>
                            @endif
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div class="text-muted small">Loan Progress</div>
                        <div class="font-weight-bold">{{ number_format((float) $loanSnapshot['progress'], 1) }}% Repaid</div>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <div
                            class="progress-bar bg-success"
                            role="progressbar"
                            style="width: {{ (float) $loanSnapshot['progress'] }}%;"
                            aria-valuenow="{{ (float) $loanSnapshot['progress'] }}"
                            aria-valuemin="0"
                            aria-valuemax="100"
                        ></div>
                    </div>
                    <a href="{{ route('customer.loans') }}" class="stretched-link" aria-label="View loan details and repayment schedule"></a>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-3">
            <div class="card customer-card h-100">
                <div class="card-header">
                    <h3 class="card-title">Quick Actions</h3>
                </div>
                <div class="card-body">
                    <a href="{{ route('customer.statement') }}" class="btn btn-outline-primary btn-block text-left">
                        <i class="fas fa-file-invoice mr-2"></i> View Statement
                    </a>
                    <a href="{{ route('customer.loans') }}" class="btn btn-outline-primary btn-block text-left">
                        <i class="fas fa-hand-holding-usd mr-2"></i> Track Loan Requests
                    </a>
                    <a href="{{ route('customer.repayments') }}" class="btn btn-outline-primary btn-block text-left">
                        <i class="fas fa-receipt mr-2"></i> View Repayments
                    </a>
                    <a href="{{ route('customer.profile') }}" class="btn btn-outline-primary btn-block text-left">
                        <i class="fas fa-user-circle mr-2"></i> Update Profile
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-3">
            <div class="card customer-card h-100">
                <div class="card-header">
                    <h3 class="card-title">My Accounts</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <tbody>
                        @forelse ($accounts as $account)
                            <tr>
                                <td>{{ $account->product?->type ?: 'Account' }}</td>
                                <td class="text-right font-weight-bold">
                                    <a href="{{ route('customer.statement', ['account_id' => $account->id]) }}" class="text-reset">
                                        &#8358;{{ number_format((float) $account->balance, 2) }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="text-muted">No account records found.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card customer-card">
        <div class="card-header">
            <h3 class="card-title">Recent Transactions</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Account</th>
                    <th>Description</th>
                    <th>Type</th>
                    <th class="text-right">Amount</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($recentTransactions as $transaction)
                    <tr>
                        <td>{{ optional($transaction->trans_date)->format('d M Y') ?: 'N/A' }}</td>
                        <td>{{ $transaction->account?->product?->type ?: 'N/A' }}</td>
                        <td>{{ $transaction->description ?: $transaction->note ?: 'Transaction' }}</td>
                        <td><span class="badge badge-{{ strtolower($transaction->dr_cr) === 'cr' ? 'success' : 'danger' }}">{{ strtoupper($transaction->dr_cr) }}</span></td>
                        <td class="text-right">&#8358;{{ number_format((float) $transaction->amount, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">No recent transactions found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
