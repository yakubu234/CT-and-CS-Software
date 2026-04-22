@extends('layouts.admin')

@section('title', 'Declined Loans')
@section('page_title', 'Declined Loans')

@section('content')
    <div class="card card-outline card-danger">
        @include('layouts.partials.table-toolbar', [
            'title' => 'Declined Loans',
            'subtitle' => 'Declined loan requests in ' . $branch->name,
            'action' => route('loans.declined'),
            'placeholder' => 'Search declined loans by borrower or reason',
        ])

        <div class="card-body">
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
                        <th>Declined By</th>
                        <th>Reason</th>
                        <th style="width: 120px;">Action</th>
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
                            <td>{{ $loanRequest->decliner?->name ?: 'N/A' }}</td>
                            <td>{{ $loanRequest->decline_reason ?: 'No reason supplied' }}</td>
                            <td>
                                <a href="{{ route('loans.requests.show', $loanRequest) }}" class="btn btn-sm btn-outline-info">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No declined loans found for this branch.</td>
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
