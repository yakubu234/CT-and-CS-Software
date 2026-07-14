@extends('layouts.customer')

@section('title', 'Loan Repayments')
@section('page_title', 'Loan Repayments')
@section('page_subtitle', 'Principal, interest, penalties, balances, and upcoming due dates.')

@section('content')
    @include('customer._date_filter', [
        'action' => route('customer.repayments'),
        'filters' => $filters,
        'prefix' => 'repayments',
    ])

    <div class="card customer-card mb-3">
        <div class="card-body">
            <div class="text-muted small">Upcoming Repayment</div>
            @if ($nextRepaymentDue)
                <div class="h5 mb-0">
                    {{ optional($nextRepaymentDue->due_date)->format('d M Y') }}
                    <span class="text-muted">for loan {{ $nextRepaymentDue->loan?->loan_id ?: 'N/A' }}</span>
                </div>
            @else
                <div class="h5 mb-0">No upcoming repayment found</div>
            @endif
        </div>
    </div>

    <div class="card customer-card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>Paid At</th>
                    <th>Loan ID</th>
                    <th class="text-right">Principal</th>
                    <th class="text-right">Interest</th>
                    <th class="text-right">Penalty</th>
                    <th class="text-right">Total</th>
                    <th class="text-right">Balance</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($payments as $payment)
                    <tr>
                        <td>{{ optional($payment->paid_at)->format('d M Y') ?: 'N/A' }}</td>
                        <td>{{ $payment->loan?->loan_id ?: 'N/A' }}</td>
                        <td class="text-right">&#8358;{{ number_format((float) $payment->repayment_amount, 2) }}</td>
                        <td class="text-right">&#8358;{{ number_format((float) $payment->interest, 2) }}</td>
                        <td class="text-right">&#8358;{{ number_format((float) $payment->late_penalties, 2) }}</td>
                        <td class="text-right money-value">&#8358;{{ number_format((float) $payment->total_amount, 2) }}</td>
                        <td class="text-right">&#8358;{{ number_format((float) $payment->balance, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No repayments found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $payments->links() }}</div>
    </div>
@endsection
