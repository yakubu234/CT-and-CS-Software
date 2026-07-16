@extends('layouts.customer')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')
@section('page_subtitle', 'Your account balances, loan position, and recent activity.')

@push('styles')
    <style>
        .dashboard-hero {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            align-items: center;
            padding: 1.25rem;
            margin-bottom: 1rem;
            border: 1px solid #dbe5f0;
            border-radius: 0.5rem;
            background: linear-gradient(135deg, #ffffff 0%, #eef7f4 48%, #eef4ff 100%);
        }

        .dashboard-hero-title {
            margin: 0;
            font-size: 1.4rem;
            font-weight: 800;
            color: #10213a;
        }

        .dashboard-hero-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.65rem;
        }

        .dashboard-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.4rem 0.7rem;
            border-radius: 999px;
            background: #fff;
            border: 1px solid #dbe5f0;
            color: #334155;
            font-size: 0.85rem;
            font-weight: 700;
        }

        .dashboard-hero-balance {
            min-width: 220px;
            padding: 0.9rem 1rem;
            border-radius: 0.5rem;
            background: #10213a;
            color: #fff;
            text-align: right;
        }

        .dashboard-hero-balance .label {
            display: block;
            font-size: 0.78rem;
            color: rgba(255, 255, 255, 0.75);
        }

        .dashboard-hero-balance .value {
            display: block;
            font-size: 1.35rem;
            font-weight: 800;
        }

        .summary-card {
            overflow: hidden;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .summary-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.08);
        }

        .summary-card .card-body {
            display: flex;
            align-items: center;
            gap: 0.85rem;
        }

        .summary-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            border-radius: 0.5rem;
            color: #fff;
            flex: 0 0 42px;
        }

        .summary-icon.savings {
            background: #2563eb;
        }

        .summary-icon.shares {
            background: #059669;
        }

        .summary-icon.authentication {
            background: #7c3aed;
        }

        .summary-icon.deposit {
            background: #d97706;
        }

        .loan-metric {
            padding: 0.85rem;
            border: 1px solid #e6eef6;
            border-radius: 0.5rem;
            background: #fbfdff;
            height: 100%;
        }

        .loan-metric-label {
            margin-bottom: 0.25rem;
            color: #64748b;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .action-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.75rem;
        }

        .action-tile {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.8rem;
            border: 1px solid #dbe5f0;
            border-radius: 0.5rem;
            color: #10213a;
            background: #fff;
            font-weight: 700;
        }

        .action-tile:hover {
            color: #0f5132;
            border-color: #b7decf;
            background: #f4fbf8;
        }

        .account-list {
            display: grid;
            gap: 0.75rem;
        }

        .account-row {
            display: flex;
            justify-content: space-between;
            gap: 0.75rem;
            align-items: center;
            padding: 0.85rem;
            border: 1px solid #e6eef6;
            border-radius: 0.5rem;
            color: #10213a;
            background: #fbfdff;
        }

        .account-row:hover {
            color: #10213a;
            border-color: #c7d7ea;
            background: #f4f8fc;
        }

        .activity-feed {
            display: grid;
            gap: 0.75rem;
        }

        .activity-item {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 0.8rem;
            align-items: center;
            padding: 0.85rem;
            border: 1px solid #e6eef6;
            border-radius: 0.5rem;
            background: #fff;
        }

        .activity-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 0.5rem;
            color: #fff;
        }

        .activity-icon.credit {
            background: #059669;
        }

        .activity-icon.debit {
            background: #dc2626;
        }

        @media (max-width: 767.98px) {
            .dashboard-hero {
                display: block;
            }

            .dashboard-hero-balance {
                min-width: 0;
                margin-top: 1rem;
                text-align: left;
            }

            .activity-item {
                grid-template-columns: auto 1fr;
            }

            .activity-amount {
                grid-column: 2;
                text-align: left !important;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $totalAccountBalance = collect($accountSummary)->sum(fn ($summary) => (float) $summary['balance']);
        $accountIcons = [
            'Savings' => 'fas fa-piggy-bank',
            'Shares' => 'fas fa-chart-pie',
            'Authentication' => 'fas fa-shield-alt',
            'Deposit' => 'fas fa-box',
        ];
    @endphp

    <div class="dashboard-hero">
        <div>
            <h2 class="dashboard-hero-title">Welcome back, {{ $customer->name }}</h2>
            <div class="dashboard-hero-meta">
                <span class="dashboard-pill">
                    <i class="fas fa-id-card"></i>
                    {{ $customer->display_member_no ?: 'Member' }}
                </span>
                <span class="dashboard-pill">
                    <i class="fas fa-code-branch"></i>
                    {{ $customer->branch?->name ?: 'No Branch' }}
                </span>
                <span class="dashboard-pill">
                    <i class="fas fa-hand-holding-usd"></i>
                    {{ $loanSnapshot['status'] }}
                </span>
            </div>
        </div>
        <div class="dashboard-hero-balance">
            <span class="label">Total Account Balance</span>
            <span class="value">&#8358;{{ number_format((float) $totalAccountBalance, 2) }}</span>
        </div>
    </div>

    <div class="row">
        @foreach ($accountSummary as $label => $summary)
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card customer-card summary-card h-100 position-relative">
                    <div class="card-body">
                        <span class="summary-icon {{ strtolower($label) }}">
                            <i class="{{ $accountIcons[$label] ?? 'fas fa-wallet' }}"></i>
                        </span>
                        <div>
                            <div class="text-muted small">{{ $label }}</div>
                            <div class="h5 money-value mb-1">&#8358;{{ number_format((float) $summary['balance'], 2) }}</div>
                            <div class="small text-primary">View history</div>
                        </div>
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
                            <div class="loan-metric">
                                <div class="loan-metric-label">Loan Amount Approved</div>
                                <div class="font-weight-bold money-value">&#8358;{{ number_format((float) $loanSnapshot['approved_amount'], 2) }}</div>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6 mb-3">
                            <div class="loan-metric">
                                <div class="loan-metric-label">Outstanding Loan Balance</div>
                                <div class="font-weight-bold money-value">&#8358;{{ number_format((float) $loanSnapshot['outstanding_balance'], 2) }}</div>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6 mb-3">
                            <div class="loan-metric">
                                <div class="loan-metric-label">Total Principal Repaid</div>
                                <div class="font-weight-bold money-value">&#8358;{{ number_format((float) $loanSnapshot['principal_repaid'], 2) }}</div>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6 mb-3">
                            <div class="loan-metric">
                                <div class="loan-metric-label">Total Interest Paid</div>
                                <div class="font-weight-bold money-value">&#8358;{{ number_format((float) $loanSnapshot['total_interest_paid'], 2) }}</div>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6 mb-3">
                            <div class="loan-metric">
                                <div class="loan-metric-label">Outstanding Interest</div>
                                <div class="font-weight-bold money-value">&#8358;{{ number_format((float) $loanSnapshot['outstanding_interest'], 2) }}</div>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6 mb-3">
                            <div class="loan-metric">
                                <div class="loan-metric-label">Next Repayment</div>
                                @if ($loanSnapshot['next_repayment_date'])
                                    <div class="font-weight-bold">{{ optional($loanSnapshot['next_repayment_date'])->format('d M Y') }}</div>
                                    <div class="small text-muted">&#8358;{{ number_format((float) $loanSnapshot['next_repayment_amount'], 2) }}</div>
                                @else
                                    <div class="font-weight-bold">No upcoming repayment found</div>
                                @endif
                            </div>
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
                    <div class="action-grid">
                    <a href="{{ route('customer.statement') }}" class="action-tile">
                        <i class="fas fa-file-invoice"></i>
                        <span>View Statement</span>
                    </a>
                    <a href="{{ route('customer.loans') }}" class="action-tile">
                        <i class="fas fa-hand-holding-usd"></i>
                        <span>Track Loan Requests</span>
                    </a>
                    <a href="{{ route('customer.repayments') }}" class="action-tile">
                        <i class="fas fa-receipt"></i>
                        <span>View Repayments</span>
                    </a>
                    <a href="{{ route('customer.profile') }}" class="action-tile">
                        <i class="fas fa-user-circle"></i>
                        <span>Update Profile</span>
                    </a>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="card customer-card">
        <div class="card-header">
            <h3 class="card-title">Recent Transactions</h3>
        </div>
        <div class="card-body">
            <div class="activity-feed">
            @forelse ($recentTransactions as $transaction)
                @php
                    $isCredit = strtolower((string) $transaction->dr_cr) === 'cr';
                @endphp
                <div class="activity-item">
                    <span class="activity-icon {{ $isCredit ? 'credit' : 'debit' }}">
                        <i class="fas fa-{{ $isCredit ? 'arrow-down' : 'arrow-up' }}"></i>
                    </span>
                    <div>
                        <div class="font-weight-bold">{{ $transaction->description ?: $transaction->note ?: 'Transaction' }}</div>
                        <div class="small text-muted">
                            {{ optional($transaction->trans_date)->format('d M Y') ?: 'N/A' }}
                            <span class="mx-1">&middot;</span>
                            {{ $transaction->account?->product?->type ?: 'N/A' }}
                            <span class="mx-1">&middot;</span>
                            {{ strtoupper($transaction->dr_cr) }}
                        </div>
                    </div>
                    <div class="activity-amount text-right font-weight-bold {{ $isCredit ? 'text-success' : 'text-danger' }}">
                        {{ $isCredit ? '+' : '-' }}&#8358;{{ number_format((float) $transaction->amount, 2) }}
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-4">No recent transactions found.</div>
            @endforelse
            </div>
        </div>
    </div>
@endsection
