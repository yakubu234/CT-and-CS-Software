@extends('layouts.admin')

@section('title', 'Pending Loan Requests')
@section('page_title', 'Pending Loan Requests')

@section('content')
    <div class="card card-outline card-warning">
        @include('layouts.partials.table-toolbar', [
            'title' => 'Pending Loan Requests',
            'subtitle' => 'Requests awaiting approval or decline in ' . $branch->name,
            'action' => route('loans.pending'),
            'placeholder' => 'Search pending requests',
        ])

        <div class="card-body">
            <div class="alert alert-info">
                <strong>Society purse balance:</strong>
                &#8358;{{ number_format((float) $branchLedgerBalance, 2) }}
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                    <tr>
                        <th>Borrower</th>
                        <th>Loan ID</th>
                        <th>Release Date</th>
                        <th>Due Date</th>
                        <th>Amount</th>
                        <th>Week Interval</th>
                        <th>Requested By</th>
                        <th style="width: 160px;">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($loanRequests as $loanRequest)
                        <tr>
                            <td>
                                <div class="font-weight-bold">{{ $loanRequest->borrower?->name ?: 'N/A' }}</div>
                                <div class="text-muted small">{{ $loanRequest->borrower?->detail?->member_no ?: $loanRequest->borrower?->member_no ?: 'N/A' }}</div>
                            </td>
                            <td>{{ $loanRequest->loan?->loan_id ?: 'N/A' }}</td>
                            <td>{{ optional($loanRequest->release_date)->format('d M Y') ?: 'N/A' }}</td>
                            <td>{{ optional($loanRequest->due_date)->format('d M Y') ?: 'N/A' }}</td>
                            <td class="font-weight-bold">&#8358;{{ number_format((float) $loanRequest->applied_amount, 2) }}</td>
                            <td class="text-capitalize">{{ str_replace('-', ' ', $loanRequest->interest_week_interval ?: 'N/A') }}</td>
                            <td>{{ $loanRequest->creator?->name ?: 'N/A' }}</td>
                            <td>
                                <div class="d-flex flex-wrap">
                                    <a href="{{ route('loans.requests.show', $loanRequest) }}" class="btn btn-sm btn-outline-info mr-2 mb-1">Review</a>
                                    <a href="{{ route('loans.requests.edit', $loanRequest) }}" class="btn btn-sm btn-outline-primary mr-2 mb-1">Edit</a>
                                    <form action="{{ route('loans.requests.destroy', $loanRequest) }}" method="POST" class="mb-1">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this loan request?')">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No pending loan requests found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $loanRequests->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection
