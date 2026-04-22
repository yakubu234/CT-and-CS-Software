@extends('layouts.admin')

@section('title', 'Income & Expenses')
@section('page_title', 'Income & Expenses')

@push('styles')
    <style>
        .income-expense-amount {
            font-weight: 700;
            font-size: 1rem;
            white-space: nowrap;
        }

        .income-expense-meta {
            font-size: 0.8rem;
            color: #64748b;
        }

        .income-expense-action-icons {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
        }

        .income-expense-action-icon {
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            border: 1px solid #dbe5f0;
            background: #fff;
            text-decoration: none;
            font-size: 0.78rem;
            transition: all 0.15s ease;
        }

        .income-expense-action-icon:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
        }

        .income-expense-action-icon.view {
            color: #2563eb;
        }

        .income-expense-action-icon.edit {
            color: #0891b2;
        }

        .income-expense-action-icon.delete {
            color: #dc2626;
            cursor: pointer;
        }

        .income-expense-related-column {
            width: 92px;
            min-width: 92px;
            text-align: center;
            white-space: nowrap;
        }
    </style>
@endpush

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header border-0">
            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <div class="mb-2 mb-md-0">
                    <h3 class="card-title mb-0">Income & Expense Entries</h3>
                    <small class="text-muted d-block mt-1">All society-level income and expense records for {{ $branch->name }}</small>
                </div>

                <div class="d-flex flex-wrap align-items-center">
                    <a href="{{ route('income-expenses.create') }}" class="btn btn-primary btn-sm mr-2 mb-2 mb-md-0">
                        <i class="fas fa-plus mr-1"></i>
                        New Entry
                    </a>

                    <form method="GET" action="{{ route('income-expenses.index') }}" class="form-inline">
                        <div class="input-group input-group-sm" style="min-width: 280px;">
                            <input
                                type="search"
                                name="search"
                                value="{{ request('search') }}"
                                class="form-control"
                                placeholder="Search by category, description, or type"
                                aria-label="Search"
                            >
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-outline-secondary">
                                    <i class="fas fa-search"></i>
                                </button>
                                @if (request()->filled('search'))
                                    <a href="{{ route('income-expenses.index') }}" class="btn btn-outline-secondary">Clear</a>
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
                        <th>Date on Entry</th>
                        <th>Category</th>
                        <th class="income-expense-related-column">Type</th>
                        <th>Amount</th>
                        <th>Current Balance</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($records as $record)
                        <tr>
                            <td>
                                <div>{{ optional($record->created_at)->format('D, d M Y') ?: 'N/A' }}</div>
                                <div class="income-expense-meta">{{ optional($record->created_at)->format('h:i A') ?: '' }}</div>
                            </td>
                            <td>
                                <div>{{ optional($record->trans_date)->format('D, d M Y') ?: 'N/A' }}</div>
                                <div class="income-expense-meta">{{ optional($record->trans_date)->format('h:i A') ?: '12:00 AM' }}</div>
                            </td>
                            <td>{{ $record->type ?: ($record->transaction_details['category_name'] ?? 'N/A') }}</td>
                            <td class="income-expense-related-column text-uppercase">
                                {{ $relatedToLabels[strtolower($record->dr_cr)] ?? strtoupper($record->dr_cr) }}
                            </td>
                            <td>
                                <div class="income-expense-amount {{ strtolower($record->dr_cr) === 'cr' ? 'text-info' : 'text-danger' }}">
                                    {{ strtolower($record->dr_cr) === 'cr' ? '+' : '-' }}&#8358;{{ number_format((float) $record->amount, 2) }}
                                </div>
                            </td>
                            <td>
                                @php
                                    $currentBalance = $record->transaction_details['balance_after'] ?? null;
                                @endphp
                                <div class="income-expense-amount text-info">
                                    &#8358;{{ number_format((float) $currentBalance, 2) }}
                                </div>
                                <div class="income-expense-meta">Society balance after posting</div>
                            </td>
                            <td>{{ $record->description ?: $record->type }}</td>
                            <td><span class="badge badge-success">Completed</span></td>
                            <td>
                                <div class="income-expense-action-icons">
                                    <a href="{{ route('income-expenses.show', $record) }}" class="income-expense-action-icon view" title="View entry" aria-label="View entry">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('income-expenses.edit', $record) }}" class="income-expense-action-icon edit" title="Edit entry" aria-label="Edit entry">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <form action="{{ route('income-expenses.destroy', $record) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this income or expense entry?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="income-expense-action-icon delete" title="Delete entry" aria-label="Delete entry">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No income or expense entries found for this branch yet.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $records->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection
