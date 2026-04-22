@extends('layouts.admin')

@section('title', 'Loan Repayments')
@section('page_title', 'Loan Repayments')

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
                            <td class="text-info font-weight-bold">&#8358;{{ number_format((float) ($repayment->repayment_amount ?? 0), 2) }}</td>
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
                                <div class="d-flex flex-column">
                                    <a href="{{ route('loan-payments.show', $repayment) }}" class="btn btn-sm btn-outline-info mb-1">View</a>
                                    <a href="{{ route('loan-payments.edit', $repayment) }}" class="btn btn-sm btn-outline-primary mb-1">Edit</a>
                                    <form action="{{ route('loan-payments.destroy', $repayment) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this loan repayment? This will also reverse the society credit entries.')">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">No loan repayments found for this branch yet.</td>
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
