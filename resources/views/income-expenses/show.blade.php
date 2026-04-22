@extends('layouts.admin')

@section('title', 'Income or Expense Details')
@section('page_title', 'Income or Expense Details')

@php
    $isIncome = strtolower($incomeExpense->dr_cr) === 'cr';
    $details = $incomeExpense->transaction_details ?? [];
    $balanceAfter = $details['balance_after'] ?? null;
@endphp

@push('styles')
    <style>
        .income-expense-detail-shell {
            display: grid;
            gap: 1.25rem;
        }

        .income-expense-hero-card {
            border-radius: 1.15rem;
            overflow: hidden;
            border: 1px solid #dbe5f0;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.06);
            background: #fff;
        }

        .income-expense-hero-top {
            padding: 1.4rem 1.5rem;
            background: {{ $isIncome ? 'linear-gradient(135deg, #0ea5e9 0%, #14b8a6 100%)' : 'linear-gradient(135deg, #ef4444 0%, #f97316 100%)' }};
            color: #fff;
        }

        .income-expense-hero-top h2 {
            margin: 0;
            font-size: 2rem;
            font-weight: 800;
        }

        .income-expense-hero-meta {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.2rem 1.5rem;
            background: #fff;
        }

        .income-expense-panel {
            border: 1px solid #dbe5f0;
            border-radius: 1rem;
            background: #fff;
            padding: 1.25rem;
        }

        .income-expense-panel h4 {
            margin-bottom: 1rem;
            font-size: 1.05rem;
            font-weight: 700;
            color: #0f172a;
        }

        .income-expense-detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
        }

        .income-expense-detail-item {
            border: 1px solid #e5edf5;
            border-radius: 0.9rem;
            padding: 0.9rem 1rem;
            background: #fbfdff;
        }

        .income-expense-detail-label {
            font-size: 0.76rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748b;
            margin-bottom: 0.35rem;
        }

        .income-expense-detail-value {
            color: #0f172a;
            font-weight: 700;
        }
    </style>
@endpush

@section('content')
    <div class="income-expense-detail-shell">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <a href="{{ route('income-expenses.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i>
                Back to Entries
            </a>
            <div class="d-flex mt-2 mt-md-0">
                <a href="{{ route('income-expenses.edit', $incomeExpense) }}" class="btn btn-primary mr-2">
                    <i class="fas fa-pen mr-1"></i>
                    Edit Entry
                </a>
                <form action="{{ route('income-expenses.destroy', $incomeExpense) }}" method="POST" onsubmit="return confirm('Delete this income or expense entry?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger">
                        <i class="fas fa-trash-alt mr-1"></i>
                        Delete
                    </button>
                </form>
            </div>
        </div>

        <div class="income-expense-hero-card">
            <div class="income-expense-hero-top">
                <div class="d-flex justify-content-between align-items-start flex-wrap">
                    <div>
                        <div class="small text-uppercase font-weight-bold mb-2" style="letter-spacing: 0.08em;">{{ $isIncome ? 'Income Entry' : 'Expense Entry' }}</div>
                        <h2>&#8358;{{ number_format((float) $incomeExpense->amount, 2) }}</h2>
                        <div class="mt-2">{{ $incomeExpense->type ?: ($incomeExpense->transaction_details['category_name'] ?? 'N/A') }}</div>
                    </div>
                    <span class="badge badge-light px-3 py-2">{{ $relatedToLabels[strtolower($incomeExpense->dr_cr)] ?? strtoupper($incomeExpense->dr_cr) }}</span>
                </div>
            </div>
            <div class="income-expense-hero-meta">
                <div>
                    <div class="text-muted small">Entry Date</div>
                    <div class="font-weight-bold">{{ optional($incomeExpense->trans_date)->format('D, d M Y') ?: 'N/A' }}</div>
                </div>
                <div>
                    <div class="text-muted small">Active Branch</div>
                    <div class="font-weight-bold">{{ $branch->name }}</div>
                </div>
                <div>
                    <div class="text-muted small">Status</div>
                    <div class="font-weight-bold">Completed</div>
                </div>
                <div>
                    <div class="text-muted small">Current Balance</div>
                    <div class="font-weight-bold">&#8358;{{ number_format((float) $balanceAfter, 2) }}</div>
                </div>
            </div>
        </div>

        <div class="income-expense-panel">
            <h4>Entry Information</h4>
            <div class="income-expense-detail-grid">
                <div class="income-expense-detail-item">
                    <div class="income-expense-detail-label">Category</div>
                    <div class="income-expense-detail-value">{{ $category?->name ?: ($incomeExpense->type ?: 'N/A') }}</div>
                </div>
                <div class="income-expense-detail-item">
                    <div class="income-expense-detail-label">Direction</div>
                    <div class="income-expense-detail-value">{{ $relatedToLabels[strtolower($incomeExpense->dr_cr)] ?? strtoupper($incomeExpense->dr_cr) }}</div>
                </div>
                <div class="income-expense-detail-item">
                    <div class="income-expense-detail-label">Description</div>
                    <div class="income-expense-detail-value">{{ $incomeExpense->description ?: 'N/A' }}</div>
                </div>
                <div class="income-expense-detail-item">
                    <div class="income-expense-detail-label">Recorded At</div>
                    <div class="income-expense-detail-value">{{ optional($incomeExpense->created_at)->format('D, d M Y h:i A') ?: 'N/A' }}</div>
                </div>
                <div class="income-expense-detail-item">
                    <div class="income-expense-detail-label">Society Balance After</div>
                    <div class="income-expense-detail-value text-info">&#8358;{{ number_format((float) $balanceAfter, 2) }}</div>
                </div>
            </div>
        </div>

        <div class="income-expense-panel">
            <h4>Audit Trail</h4>
            <div class="income-expense-detail-grid">
                <div class="income-expense-detail-item">
                    <div class="income-expense-detail-label">Created By</div>
                    <div class="income-expense-detail-value">
                        {{ $incomeExpense->creator?->name ?: 'N/A' }}
                        @if ($incomeExpense->created_at)
                            <div class="text-muted small mt-1">{{ $incomeExpense->created_at->format('D, d M Y h:i A') }}</div>
                        @endif
                    </div>
                </div>
                <div class="income-expense-detail-item">
                    <div class="income-expense-detail-label">Updated By</div>
                    <div class="income-expense-detail-value">
                        {{ $incomeExpense->updater?->name ?: 'N/A' }}
                        @if ($incomeExpense->updated_at)
                            <div class="text-muted small mt-1">{{ $incomeExpense->updated_at->format('D, d M Y h:i A') }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
