@extends('layouts.admin')

@section('title', 'All Transactions')
@section('page_title', 'All Transactions')

@push('styles')
    <style>
        .transaction-amount {
            font-weight: 700;
            font-size: 1rem;
        }

        .transaction-meta {
            font-size: 0.8rem;
            color: #64748b;
        }

        .transaction-amount-column {
            min-width: 140px;
            white-space: nowrap;
        }

        .transaction-drcr-column {
            width: 72px;
            min-width: 72px;
            max-width: 72px;
            text-align: center;
            white-space: nowrap;
        }

        .transaction-action-icons {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
        }

        .transaction-action-icon {
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            border: 1px solid #dbe5f0;
            background: #fff;
            text-decoration: none;
            transition: all 0.15s ease;
            font-size: 0.78rem;
        }

        .transaction-action-icon:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
        }

        .transaction-action-icon.view {
            color: #2563eb;
        }

        .transaction-action-icon.edit {
            color: #0891b2;
        }

        .transaction-action-icon.delete {
            color: #dc2626;
        }

        .transaction-action-icon.delete-button {
            cursor: pointer;
        }
    </style>
@endpush

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header border-0">
            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <div class="mb-2 mb-md-0">
                    <h3 class="card-title mb-0">Member Transactions</h3>
                    <small class="text-muted d-block mt-1">All member transaction entries recorded in {{ $branch->name }}</small>
                </div>

                <div class="d-flex flex-wrap align-items-center">
                    <a href="{{ route('transactions.create') }}" class="btn btn-primary btn-sm mr-2 mb-2 mb-md-0">
                        <i class="fas fa-plus mr-1"></i>
                        New Transaction
                    </a>

                    <form method="GET" action="{{ route('transactions.index') }}" class="form-inline">
                        <div class="input-group input-group-sm" style="min-width: 280px;">
                            <input
                                type="search"
                                name="search"
                                value="{{ request('search') }}"
                                class="form-control"
                                placeholder="Search by member, account, type, or member no"
                                aria-label="Search"
                            >
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-outline-secondary">
                                    <i class="fas fa-search"></i>
                                </button>
                                @if (request()->filled('search'))
                                    <a href="{{ route('transactions.index') }}" class="btn btn-outline-secondary">Clear</a>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="thead-light">
                    <tr>
                        <th>Date Entered</th>
                        <th>Date on Trans.</th>
                        <th>Member</th>
                        <th>Member No</th>
                        <th>Account Number</th>
                        <th class="transaction-amount-column">Amount</th>
                        <th class="transaction-amount-column">Current Balance</th>
                        <th class="transaction-drcr-column">Dr/Cr</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($transactions as $transaction)
                        <tr>
                            <td>
                                <div>{{ optional($transaction->created_at)->format('D, d M Y') ?: 'N/A' }}</div>
                                <div class="transaction-meta">{{ optional($transaction->created_at)->format('h:i A') ?: '' }}</div>
                            </td>
                            <td>
                                <div>{{ optional($transaction->trans_date)->format('D, d M Y') ?: 'N/A' }}</div>
                                <div class="transaction-meta">{{ optional($transaction->trans_date)->format('h:i A') ?: '12:00 AM' }}</div>
                            </td>
                            <td>{{ $transaction->user?->name ?: 'N/A' }}</td>
                            <td>{{ $transaction->user?->detail?->member_no ?: $transaction->user?->member_no ?: 'N/A' }}</td>
                            <td>{{ $transaction->account?->account_number ?: ($transaction->transaction_details['account_number'] ?? 'N/A') }}</td>
                            <td class="transaction-amount-column">
                                <div class="transaction-amount {{ strtolower($transaction->dr_cr) === 'cr' ? 'text-info' : 'text-danger' }}">
                                    {{ strtolower($transaction->dr_cr) === 'cr' ? '+' : '-' }}&#8358;{{ number_format((float) $transaction->amount, 2) }}
                                </div>
                            </td>
                            <td class="transaction-amount-column">
                                @php
                                    $currentBalance = $transaction->transaction_details['balance_after'] ?? $transaction->account?->balance;
                                @endphp
                                <div class="transaction-amount text-info">
                                    &#8358;{{ number_format((float) $currentBalance, 2) }}
                                </div>
                                <div class="transaction-meta">Balance after posting</div>
                            </td>
                            <td class="transaction-drcr-column text-uppercase">{{ $transaction->dr_cr }}</td>
                            <td>{{ $transaction->description ?: $transaction->type }}</td>
                            <td>
                                <span class="badge badge-success">Completed</span>
                            </td>
                            <td>
                                <div class="transaction-action-icons">
                                    <a
                                        href="{{ route('transactions.show', $transaction) }}"
                                        class="transaction-action-icon view"
                                        title="View transaction"
                                        aria-label="View transaction"
                                    >
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a
                                        href="{{ route('transactions.edit', $transaction) }}"
                                        class="transaction-action-icon edit"
                                        title="Edit transaction"
                                        aria-label="Edit transaction"
                                    >
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <form
                                        action="{{ route('transactions.destroy', $transaction) }}"
                                        method="POST"
                                        class="d-inline"
                                        onsubmit="return confirm('Are you sure you want to delete this transaction? This will reverse its balance effect.')"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="transaction-action-icon delete delete-button"
                                            title="Delete transaction"
                                            aria-label="Delete transaction"
                                        >
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted py-4">No transactions found for this branch yet.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $transactions->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection
