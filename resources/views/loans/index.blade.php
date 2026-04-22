@extends('layouts.admin')

@section('title', 'Active Loans')
@section('page_title', 'Active Loans')

@section('content')
    <div class="card card-outline card-primary">
        @include('layouts.partials.table-toolbar', [
            'title' => 'Active Loans',
            'subtitle' => 'One active loan record per borrower in ' . $branch->name,
            'action' => route('loans.index'),
            'placeholder' => 'Search loans by borrower name or loan id',
        ])

        <div class="card-body">
            <div class="mb-3 d-flex flex-wrap align-items-center">
                <a href="{{ route('loans.create') }}" class="btn btn-primary mr-2 mb-2 mb-sm-0">
                    <i class="fas fa-plus mr-1"></i>
                    Create Loan Request
                </a>
                <a href="{{ route('loans.pending') }}" class="btn btn-outline-secondary mb-2 mb-sm-0">
                    <i class="fas fa-hourglass-half mr-1"></i>
                    Pending Requests
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                    <tr>
                        <th>Borrower</th>
                        <th>Loan ID</th>
                        <th>Outstanding</th>
                        <th>Recent Added</th>
                        <th>Total Paid</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th style="width: 140px;">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($loans as $loan)
                        <tr>
                            <td>
                                <div class="font-weight-bold">{{ $loan->borrower?->name ?: 'N/A' }}</div>
                                <div class="text-muted small">{{ $loan->borrower?->detail?->member_no ?: $loan->borrower?->member_no ?: 'N/A' }}</div>
                            </td>
                            <td>{{ $loan->loan_id }}</td>
                            <td class="text-info font-weight-bold">&#8358;{{ number_format((float) ($loan->balanace ?? 0), 2) }}</td>
                            <td>&#8358;{{ number_format((float) ($loan->recent_added_amount ?? 0), 2) }}</td>
                            <td>&#8358;{{ number_format((float) ($loan->total_paid ?? 0), 2) }}</td>
                            <td>{{ $loan->due_date ?: 'N/A' }}</td>
                            <td>
                                <span class="badge badge-{{ ((float) ($loan->balanace ?? 0)) > 0 ? 'success' : 'secondary' }}">
                                    {{ ((float) ($loan->balanace ?? 0)) > 0 ? 'Active' : 'Closed' }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('loans.show', $loan) }}" class="btn btn-sm btn-outline-info">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No active loans found for this branch.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $loans->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection
