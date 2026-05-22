@extends('layouts.admin')

@section('title', 'Loan Details')
@section('page_title', 'Loan Details')

@push('styles')
    <style>
        .loan-history-action-icons {
            display: inline-flex;
            flex-direction: row;
            align-items: center;
            gap: 0.25rem;
            flex-wrap: wrap;
        }

        .loan-history-action-icon {
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

        .loan-history-action-icon:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
        }

        .loan-history-action-icon.view {
            color: #2563eb;
        }

        .loan-history-action-icon.edit {
            color: #0891b2;
        }

        .loan-history-action-icon.delete {
            color: #dc2626;
            cursor: pointer;
        }
    </style>
@endpush

@section('content')
    <div class="card card-outline card-primary mb-4">
        <div class="card-header">
            <h3 class="card-title">Loan Overview</h3>
            <div class="card-tools">
                <x-browser-back-button :fallback="route('loans.index')" label="Return to All Loans" />
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="small text-muted">Borrower</div>
                    <div class="font-weight-bold">{{ $loan->borrower?->name ?: 'N/A' }}</div>
                    <div class="text-muted small">{{ $loan->borrower?->detail?->member_no ?: $loan->borrower?->member_no ?: 'N/A' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="small text-muted">Loan ID</div>
                    <div class="font-weight-bold">{{ $loan->loan_id }}</div>
                </div>
                <div class="col-md-3">
                    <div class="small text-muted">Outstanding Balance</div>
                    <div class="font-weight-bold text-info">&#8358;{{ number_format((float) ($loan->balanace ?? 0), 2) }}</div>
                </div>
                <div class="col-md-3">
                    <div class="small text-muted">Total Paid</div>
                    <div class="font-weight-bold">&#8358;{{ number_format((float) ($loan->total_paid ?? 0), 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Loan Request History</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                    <tr>
                        <th>Created</th>
                        <th>Release Date</th>
                        <th>Due Date</th>
                        <th>Amount</th>
                        <th>Week Interval</th>
                        <th>Status</th>
                        <th>Repayment Status</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($loan->details as $detail)
                        <tr>
                            <td>{{ optional($detail->created_at)->format('d M Y h:i A') ?: 'N/A' }}</td>
                            <td>{{ optional($detail->release_date)->format('d M Y') ?: 'N/A' }}</td>
                            <td>{{ optional($detail->due_date)->format('d M Y') ?: 'N/A' }}</td>
                            <td>&#8358;{{ number_format((float) $detail->applied_amount, 2) }}</td>
                            <td class="text-capitalize">{{ str_replace('-', ' ', $detail->interest_week_interval ?: 'N/A') }}</td>
                            <td>
                                <span class="badge badge-{{
                                    $detail->decision_status === 'approved' ? 'success' : ($detail->decision_status === 'declined' ? 'danger' : 'warning')
                                }}">
                                    {{ ucfirst($detail->decision_status) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-{{ $detail->repayment_status ? 'success' : 'secondary' }}">
                                    {{ $detail->repayment_status ? 'Settled' : 'Outstanding' }}
                                </span>
                            </td>
                            <td>
                                <div class="loan-history-action-icons">
                                    <a href="{{ route('loans.requests.show', $detail) }}" class="loan-history-action-icon view" title="View loan history" aria-label="View loan history">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if ($detail->canBeEdited())
                                        <a href="{{ route('loans.requests.edit', $detail) }}" class="loan-history-action-icon edit" title="Edit loan history" aria-label="Edit loan history">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                    @endif
                                    @if ($detail->canBeDeleted())
                                        <form action="{{ route('loans.requests.destroy', $detail) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="loan-history-action-icon delete" title="Delete loan history" aria-label="Delete loan history" onclick="return confirm('Delete this loan request?')">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @if ($detail->payments->isNotEmpty())
                            <tr>
                                <td colspan="8" class="bg-light">
                                    <div class="font-weight-bold mb-2">Repayment History</div>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered mb-0">
                                            <thead>
                                            <tr>
                                                <th>Paid At</th>
                                                <th>Repayment</th>
                                                <th>Interest</th>
                                                <th>Late Penalty</th>
                                                <th>Total</th>
                                                <th>Balance</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach ($detail->payments as $payment)
                                                <tr>
                                                    <td>{{ optional($payment->paid_at)->format('d M Y') ?: 'N/A' }}</td>
                                                    <td>&#8358;{{ number_format((float) ($payment->repayment_amount ?? 0), 2) }}</td>
                                                    <td>&#8358;{{ number_format((float) ($payment->interest ?? 0), 2) }}</td>
                                                    <td>&#8358;{{ number_format((float) ($payment->late_penalties ?? 0), 2) }}</td>
                                                    <td>&#8358;{{ number_format((float) ($payment->total_amount ?? 0), 2) }}</td>
                                                    <td>&#8358;{{ number_format((float) ($payment->balance ?? 0), 2) }}</td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No loan history found for this borrower yet.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
