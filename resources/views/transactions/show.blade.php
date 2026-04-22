@extends('layouts.admin')

@section('title', 'Transaction Details')
@section('page_title', 'Transaction Details')

@push('styles')
    <style>
        .transaction-shell {
            background:
                radial-gradient(circle at top left, rgba(45, 201, 189, 0.12), transparent 30%),
                linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
        }

        .transaction-topbar {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.1rem 1.25rem 0;
        }

        .transaction-topbar-copy h2 {
            margin: 0;
            font-size: 1.55rem;
            font-weight: 700;
            color: #0f172a;
        }

        .transaction-topbar-copy p {
            margin: 0.35rem 0 0;
            color: #64748b;
        }

        .transaction-hero {
            position: relative;
            overflow: hidden;
            margin: 1rem 1.25rem 0;
            border-radius: 1.2rem;
            background: linear-gradient(135deg, #0f766e 0%, #14b8a6 55%, #5eead4 100%);
            color: #fff;
            padding: 1.6rem;
            box-shadow: 0 20px 40px rgba(15, 118, 110, 0.18);
        }

        .transaction-hero::after {
            content: "";
            position: absolute;
            inset: auto -80px -100px auto;
            width: 220px;
            height: 220px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.14);
        }

        .transaction-hero-label {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.35rem 0.7rem;
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .transaction-hero-amount {
            margin: 1rem 0 0.35rem;
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 800;
            line-height: 1;
        }

        .transaction-hero-subtitle {
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.88);
        }

        .transaction-pill-group {
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem;
            justify-content: flex-end;
            position: relative;
            z-index: 1;
        }

        .transaction-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.5rem 0.9rem;
            border-radius: 999px;
            font-weight: 700;
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .transaction-pill--light {
            background: rgba(255, 255, 255, 0.18);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.18);
        }

        .transaction-pill--white {
            background: #fff;
            color: #0f766e;
        }

        .transaction-meta-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.9rem;
            margin: 1.1rem 1.25rem 0;
        }

        .transaction-stat-card {
            padding: 0.95rem 1rem;
            border: 1px solid #dbe5f0;
            border-radius: 1rem;
            background: #fff;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.04);
        }

        .transaction-stat-label {
            margin-bottom: 0.35rem;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
        }

        .transaction-stat-value {
            font-size: 1.05rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.35;
        }

        .transaction-content {
            padding: 1.25rem;
        }

        .transaction-panel {
            height: 100%;
            border: 1px solid #dbe5f0;
            border-radius: 1.1rem;
            background: #fff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
        }

        .transaction-panel-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.1rem;
            border-bottom: 1px solid #e5edf5;
        }

        .transaction-panel-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.4rem;
            height: 2.4rem;
            border-radius: 0.85rem;
            background: #ecfeff;
            color: #0f766e;
        }

        .transaction-panel-title {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 700;
            color: #0f172a;
        }

        .transaction-panel-subtitle {
            margin: 0.15rem 0 0;
            font-size: 0.84rem;
            color: #64748b;
        }

        .transaction-panel-body {
            padding: 1rem 1.1rem 1.15rem;
        }

        .transaction-detail-grid {
            display: grid;
            gap: 0.95rem;
        }

        .transaction-detail-item {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            padding-bottom: 0.85rem;
            border-bottom: 1px dashed #e2e8f0;
        }

        .transaction-detail-item:last-child {
            padding-bottom: 0;
            border-bottom: 0;
        }

        .transaction-detail-label {
            font-size: 0.82rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748b;
        }

        .transaction-detail-value {
            max-width: 60%;
            text-align: right;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.4;
        }

        .transaction-detail-value--credit {
            color: #0891b2;
        }

        .transaction-detail-value--debit {
            color: #dc2626;
        }

        .transaction-audit-wrap {
            margin-top: 1.25rem;
            border: 1px solid #dbe5f0;
            border-radius: 1.1rem;
            background: #fff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
            overflow: hidden;
        }

        .transaction-audit-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem 1.1rem;
            border-bottom: 1px solid #e5edf5;
        }

        .transaction-audit-title {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 700;
            color: #0f172a;
        }

        .transaction-audit-box {
            border-radius: 0.9rem;
            padding: 1rem 1.1rem;
            border: 1px solid transparent;
        }

        .transaction-audit-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
            padding: 1rem 1.1rem 1.1rem;
        }

        .transaction-audit-label {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.4rem;
        }

        .transaction-audit-value {
            color: #0f172a;
            font-weight: 600;
            line-height: 1.5;
        }

        @media (max-width: 991.98px) {
            .transaction-meta-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .transaction-pill-group {
                justify-content: flex-start;
                margin-top: 1rem;
            }

            .transaction-detail-value {
                max-width: 56%;
            }
        }

        @media (max-width: 767.98px) {
            .transaction-topbar {
                flex-direction: column;
                align-items: stretch;
            }

            .transaction-meta-grid,
            .transaction-audit-grid {
                grid-template-columns: 1fr;
            }

            .transaction-hero {
                padding: 1.2rem;
            }

            .transaction-detail-item {
                flex-direction: column;
                gap: 0.35rem;
            }

            .transaction-detail-value {
                max-width: 100%;
                text-align: left;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $details = $transaction->transaction_details ?? [];
        $balanceAfter = $details['balance_after'] ?? $transaction->account?->balance;
    @endphp

    <div class="card card-outline card-primary overflow-hidden transaction-shell">
        <div class="transaction-topbar">
            <div class="transaction-topbar-copy">
                <h2>Transaction Details</h2>
                <p>Review the full transaction record, account impact, and audit history.</p>
            </div>
            <div class="d-flex flex-wrap">
                <a href="{{ route('transactions.index') }}" class="btn btn-light btn-sm mr-2 mb-2">Back</a>
                <a href="{{ route('transactions.edit', $transaction) }}" class="btn btn-primary btn-sm mr-2 mb-2">Edit</a>
                <button
                    type="submit"
                    class="btn btn-outline-danger btn-sm mb-2"
                    form="transaction-delete-form"
                    onclick="return confirm('Delete this transaction? The account balance will be adjusted automatically.')"
                >
                    Delete
                </button>
            </div>
        </div>

        <div class="transaction-hero">
            <div class="d-flex flex-wrap justify-content-between align-items-start">
                <div>
                    <span class="transaction-hero-label">
                        <i class="fas fa-receipt"></i>
                        Transaction Record
                    </span>
                    <div class="transaction-hero-amount">&#8358;{{ number_format((float) $transaction->amount, 2) }}</div>
                    <div class="transaction-hero-subtitle">
                        Account: {{ $transaction->account?->account_number ?: ($details['account_number'] ?? 'N/A') }}
                    </div>
                </div>
                <div class="transaction-pill-group">
                    <span class="transaction-pill transaction-pill--white">Completed</span>
                    <span class="transaction-pill transaction-pill--light text-uppercase">{{ $transaction->dr_cr }}</span>
                    <span class="transaction-pill transaction-pill--light">{{ $transaction->type }}</span>
                </div>
            </div>
        </div>

        <div class="transaction-meta-grid">
            <div class="transaction-stat-card">
                <div class="transaction-stat-label">Member</div>
                <div class="transaction-stat-value">{{ $transaction->user?->name ?: 'N/A' }}</div>
            </div>
            <div class="transaction-stat-card">
                <div class="transaction-stat-label">Member No</div>
                <div class="transaction-stat-value">{{ $transaction->user?->detail?->member_no ?: $transaction->user?->member_no ?: 'N/A' }}</div>
            </div>
            <div class="transaction-stat-card">
                <div class="transaction-stat-label">Balance After</div>
                <div class="transaction-stat-value text-info">&#8358;{{ number_format((float) $balanceAfter, 2) }}</div>
            </div>
            <div class="transaction-stat-card">
                <div class="transaction-stat-label">Entered By</div>
                <div class="transaction-stat-value">{{ $transaction->creator?->name ?: 'N/A' }}</div>
            </div>
        </div>

        <div class="transaction-content">
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="transaction-panel">
                        <div class="transaction-panel-header">
                            <div class="transaction-panel-icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div>
                                <h4 class="transaction-panel-title">Transaction Information</h4>
                                <p class="transaction-panel-subtitle">Core timing and posting details.</p>
                            </div>
                        </div>
                        <div class="transaction-panel-body">
                            <div class="transaction-detail-grid">
                                <div class="transaction-detail-item">
                                    <div class="transaction-detail-label">Date & Time</div>
                                    <div class="transaction-detail-value">{{ optional($transaction->trans_date)->format('D, d M Y h:i A') ?: 'N/A' }}</div>
                                </div>
                                <div class="transaction-detail-item">
                                    <div class="transaction-detail-label">Transaction Type</div>
                                    <div class="transaction-detail-value">{{ $transaction->type }}</div>
                                </div>
                                <div class="transaction-detail-item">
                                    <div class="transaction-detail-label">Method</div>
                                    <div class="transaction-detail-value">{{ $transaction->method }}</div>
                                </div>
                                <div class="transaction-detail-item">
                                    <div class="transaction-detail-label">Amount</div>
                                    <div class="transaction-detail-value {{ strtolower($transaction->dr_cr) === 'cr' ? 'transaction-detail-value--credit' : 'transaction-detail-value--debit' }}">
                                        {{ strtolower($transaction->dr_cr) === 'cr' ? '+' : '-' }}&#8358;{{ number_format((float) $transaction->amount, 2) }}
                                    </div>
                                </div>
                                <div class="transaction-detail-item">
                                    <div class="transaction-detail-label">Status</div>
                                    <div class="transaction-detail-value">Completed</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 mb-4">
                    <div class="transaction-panel">
                        <div class="transaction-panel-header">
                            <div class="transaction-panel-icon">
                                <i class="fas fa-wallet"></i>
                            </div>
                            <div>
                                <h4 class="transaction-panel-title">Account Information</h4>
                                <p class="transaction-panel-subtitle">Member and account-level context for this entry.</p>
                            </div>
                        </div>
                        <div class="transaction-panel-body">
                            <div class="transaction-detail-grid">
                                <div class="transaction-detail-item">
                                    <div class="transaction-detail-label">Member</div>
                                    <div class="transaction-detail-value">{{ $transaction->user?->name ?: 'N/A' }}</div>
                                </div>
                                <div class="transaction-detail-item">
                                    <div class="transaction-detail-label">Member No</div>
                                    <div class="transaction-detail-value">{{ $transaction->user?->detail?->member_no ?: $transaction->user?->member_no ?: 'N/A' }}</div>
                                </div>
                                <div class="transaction-detail-item">
                                    <div class="transaction-detail-label">Account Number</div>
                                    <div class="transaction-detail-value">{{ $transaction->account?->account_number ?: ($details['account_number'] ?? 'N/A') }}</div>
                                </div>
                                <div class="transaction-detail-item">
                                    <div class="transaction-detail-label">Description</div>
                                    <div class="transaction-detail-value">{{ $transaction->description ?: 'N/A' }}</div>
                                </div>
                                <div class="transaction-detail-item">
                                    <div class="transaction-detail-label">Balance After</div>
                                    <div class="transaction-detail-value transaction-detail-value--credit">&#8358;{{ number_format((float) $balanceAfter, 2) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="transaction-audit-wrap">
                <div class="transaction-audit-header">
                    <div>
                        <h4 class="transaction-audit-title">Audit Trail</h4>
                        <div class="text-muted small">Track who created and last updated this record.</div>
                    </div>
                </div>

                <div class="transaction-audit-grid">
                    <div class="transaction-audit-box" style="background:#edfdf8; border-color:#bbf7d0;">
                        <div class="transaction-audit-label text-success">Created By</div>
                        <div class="transaction-audit-value">
                            {{ $transaction->creator?->name ?: 'N/A' }}<br>
                            <span class="text-muted font-weight-normal">{{ optional($transaction->created_at)->format('D, d M Y h:i A') ?: 'N/A' }}</span>
                        </div>
                    </div>

                    <div class="transaction-audit-box" style="background:#eff6ff; border-color:#bfdbfe;">
                        <div class="transaction-audit-label text-primary">Updated By</div>
                        <div class="transaction-audit-value">
                            @if ($transaction->updater)
                                {{ $transaction->updater->name }}<br>
                                <span class="text-muted font-weight-normal">{{ optional($transaction->updated_at)->format('D, d M Y h:i A') ?: 'N/A' }}</span>
                            @else
                                N/A
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('transactions.destroy', $transaction) }}" method="POST" id="transaction-delete-form" class="d-none">
        @csrf
        @method('DELETE')
    </form>
@endsection
