@extends('layouts.customer')

@section('title', 'My Loans')
@section('page_title', 'My Loans')
@section('page_subtitle', 'Active loans and loan request approval history.')

@section('content')
    <div class="card customer-card mb-4">
        <div class="card-header">
            <h3 class="card-title">Active Loans</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>Loan ID</th>
                    <th>Amount Borrowed</th>
                    <th>Due Date</th>
                    <th>Repayment Status</th>
                    <th class="text-right">Outstanding</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($loans as $loan)
                    <tr>
                        <td class="font-weight-bold">{{ $loan->loan_id }}</td>
                        <td>&#8358;{{ number_format((float) $loan->applied_amount, 2) }}</td>
                        <td>{{ optional($loan->due_date)->format('d M Y') ?: 'N/A' }}</td>
                        <td>
                            <span class="badge badge-{{ $loan->repayment_status ? 'success' : 'warning' }}">
                                {{ $loan->repayment_status ? 'Settled' : 'Outstanding' }}
                            </span>
                        </td>
                        <td class="text-right money-value">&#8358;{{ number_format((float) ($loan->balanace ?? 0), 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">No active loans found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $loans->links() }}</div>
    </div>

    <div class="card customer-card">
        <div class="card-header">
            <h3 class="card-title">Loan Request History</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>Requested</th>
                    <th>Loan ID</th>
                    <th>Release Date</th>
                    <th>Due Date</th>
                    <th class="text-right">Amount</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($loanRequests as $request)
                    <tr>
                        <td>{{ optional($request->created_at)->format('d M Y') ?: 'N/A' }}</td>
                        <td>{{ $request->loan?->loan_id ?: 'N/A' }}</td>
                        <td>{{ optional($request->release_date)->format('d M Y') ?: 'N/A' }}</td>
                        <td>{{ optional($request->due_date)->format('d M Y') ?: 'N/A' }}</td>
                        <td class="text-right">&#8358;{{ number_format((float) $request->applied_amount, 2) }}</td>
                        <td>
                            <span class="badge badge-{{ $request->decision_status === 'approved' ? 'success' : ($request->decision_status === 'declined' ? 'danger' : 'warning') }}">
                                {{ ucfirst($request->decision_status) }}
                            </span>
                            @if ($request->decline_reason)
                                <div class="small text-muted mt-1">{{ $request->decline_reason }}</div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No loan requests found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $loanRequests->links() }}</div>
    </div>
@endsection
