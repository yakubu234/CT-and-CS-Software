@extends('layouts.admin')

@section('title', 'Loan Repayment Details')
@section('page_title', 'Loan Repayment Details')

@php
    $loan = $repayment->loan;
    $borrower = $loan?->borrower;
@endphp

@section('content')
    <div class="card card-outline card-primary mb-4">
        <div class="card-header">
            <h3 class="card-title">Repayment Overview</h3>
            <div class="card-tools">
                <a href="{{ route('loan-payments.index') }}" class="btn btn-sm btn-outline-secondary">Back to Repayments</a>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="small text-muted">Borrower</div>
                    <div class="font-weight-bold">{{ $borrower?->name ?: 'N/A' }}</div>
                    <div class="text-muted small">{{ $borrower?->detail?->member_no ?: $borrower?->member_no ?: 'N/A' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="small text-muted">Loan ID</div>
                    <div class="font-weight-bold">{{ $loan?->loan_id ?: 'N/A' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="small text-muted">Principal Paid</div>
                    <div class="font-weight-bold text-info">&#8358;{{ number_format((float) ($repayment->repayment_amount ?? 0), 2) }}</div>
                </div>
                <div class="col-md-3">
                    <div class="small text-muted">Interest Paid</div>
                    <div class="font-weight-bold text-success">&#8358;{{ number_format((float) ($repayment->interest_paid ?? 0), 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-outline card-primary mb-4">
        <div class="card-header">
            <h3 class="card-title">Repayment Details</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="small text-muted">Transaction Date</div>
                    <div class="font-weight-bold">{{ optional($repayment->paid_at)->format('d M Y') ?: 'N/A' }}</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="small text-muted">Balance Before Repayment</div>
                    <div class="font-weight-bold">&#8358;{{ number_format((float) ($repayment->total_outstanding ?? 0), 2) }}</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="small text-muted">Balance After Repayment</div>
                    <div class="font-weight-bold">&#8358;{{ number_format((float) ($repayment->balance ?? 0), 2) }}</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="small text-muted">Interest Rate Used</div>
                    <div class="font-weight-bold">{{ $repayment->interest_rate !== null ? rtrim(rtrim(number_format((float) $repayment->interest_rate, 2, '.', ''), '0'), '.') . '%' : 'N/A' }}</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="small text-muted">Interest Expected</div>
                    <div class="font-weight-bold">&#8358;{{ number_format((float) ($repayment->interest ?? 0), 2) }}</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="small text-muted">Interest Remaining</div>
                    <div class="font-weight-bold">&#8358;{{ number_format((float) ($repayment->outstanding_interest ?? 0), 2) }}</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="small text-muted">Carry Forward</div>
                    <div class="font-weight-bold">
                        {{
                            (int) ($repayment->carry_forward ?? 0) === 1
                                ? 'Yes'
                                : ((int) ($repayment->carry_forward ?? 0) === 2 ? 'Already settled later' : 'No')
                        }}
                    </div>
                </div>
                <div class="col-md-8 mb-3">
                    <div class="small text-muted">Remarks</div>
                    <div class="font-weight-bold">{{ $repayment->remarks ?: 'No remarks added' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Society Ledger Impact</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead>
                    <tr>
                        <th>Entry Type</th>
                        <th>Amount</th>
                        <th>Ledger Type</th>
                        <th>Recorded Date</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>Loan Repayment</td>
                        <td>&#8358;{{ number_format((float) ($repayment->principalTransaction?->amount ?? 0), 2) }}</td>
                        <td>{{ strtoupper($repayment->principalTransaction?->dr_cr ?? 'N/A') }}</td>
                        <td>{{ optional($repayment->principalTransaction?->trans_date)->format('d M Y') ?: 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td>Interest Repayment</td>
                        <td>&#8358;{{ number_format((float) ($repayment->interestTransaction?->amount ?? 0), 2) }}</td>
                        <td>{{ strtoupper($repayment->interestTransaction?->dr_cr ?? 'N/A') }}</td>
                        <td>{{ optional($repayment->interestTransaction?->trans_date)->format('d M Y') ?: 'N/A' }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
