@extends('layouts.admin')

@section('title', 'Loan Repayments')
@section('page_title', 'Loan Repayments')

@push('styles')
    <style>
        .loan-payment-action-icons {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
        }

        .loan-payment-action-icon {
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

        .loan-payment-action-icon:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
        }

        .loan-payment-action-icon.view {
            color: #2563eb;
        }

        .loan-payment-action-icon.edit {
            color: #0891b2;
        }

        .loan-payment-action-icon.delete {
            color: #dc2626;
            cursor: pointer;
        }

        .loan-payment-money {
            font-weight: 700;
            white-space: nowrap;
        }

        .loan-payment-money-subtext {
            font-size: 0.8rem;
            color: #64748b;
            margin-top: 0.15rem;
        }
    </style>
@endpush

@section('content')
    <div class="card card-outline card-primary">
        @include('layouts.partials.table-toolbar', [
            'title' => 'Loan Repayments',
            'subtitle' => 'Repayments and interest collections for ' . $branch->name,
            'action' => route('loan-payments.index'),
            'placeholder' => 'Search by loan id, borrower, or remarks',
        ])

        <div class="card-body">
            <div class="mb-3">
                <a href="{{ route('loan-payments.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus mr-1"></i>
                    Record Loan Repayment
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                    <tr>
                        <th>Paid Date</th>
                        <th>Loan</th>
                        <th>Borrower</th>
                        <th>Amt B4 Payment</th>
                        <th>Principal Paid</th>
                        <th>Interest Due</th>
                        <th>Interest Paid</th>
                        <th>Interest Remaining</th>
                        <th>Carry Forward</th>
                        <th>Loan Balance After</th>
                        <th style="width: 130px;">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($repayments as $repayment)
                        <tr>
                            <td>{{ optional($repayment->paid_at)->format('d M Y') ?: 'N/A' }}</td>
                            <td>{{ $repayment->loan?->loan_id ?: 'N/A' }}</td>
                            <td>
                                <div class="font-weight-bold">{{ $repayment->loan?->borrower?->name ?: 'N/A' }}</div>
                                <div class="text-muted small">{{ $repayment->loan?->borrower?->detail?->member_no ?: $repayment->loan?->borrower?->member_no ?: 'N/A' }}</div>
                            </td>
                            <td>
                                <div class="loan-payment-money">&#8358;{{ number_format((float) ($repayment->total_outstanding ?? 0), 2) }}</div>
                                <div class="loan-payment-money-subtext">Before principal payment</div>
                            </td>
                            <td class="text-info loan-payment-money">&#8358;{{ number_format((float) ($repayment->repayment_amount ?? 0), 2) }}</td>
                            <td>&#8358;{{ number_format((float) ($repayment->interest ?? 0), 2) }}</td>
                            <td>&#8358;{{ number_format((float) ($repayment->interest_paid ?? 0), 2) }}</td>
                            <td>&#8358;{{ number_format((float) ($repayment->outstanding_interest ?? 0), 2) }}</td>
                            <td>
                                <span class="badge badge-{{ (int) ($repayment->carry_forward ?? 0) === 1 ? 'warning' : ((int) ($repayment->carry_forward ?? 0) === 2 ? 'info' : 'secondary') }}">
                                    {{
                                        (int) ($repayment->carry_forward ?? 0) === 1
                                            ? 'Pending'
                                            : ((int) ($repayment->carry_forward ?? 0) === 2 ? 'Settled Later' : 'No')
                                    }}
                                </span>
                            </td>
                            <td>&#8358;{{ number_format((float) ($repayment->balance ?? 0), 2) }}</td>
                            <td>
                                <div class="loan-payment-action-icons">
                                    <a href="{{ route('loan-payments.show', $repayment) }}" class="loan-payment-action-icon view" title="View repayment" aria-label="View repayment">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('loan-payments.edit', $repayment) }}" class="loan-payment-action-icon edit" title="Edit repayment" aria-label="Edit repayment">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <form action="{{ route('loan-payments.destroy', $repayment) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="loan-payment-action-icon delete" title="Delete repayment" aria-label="Delete repayment" onclick="return confirm('Delete this loan repayment? This will also reverse the society credit entries.')">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted py-4">No loan repayments found for this branch yet.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $repayments->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection
