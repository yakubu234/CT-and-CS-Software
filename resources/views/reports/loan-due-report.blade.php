@extends('layouts.admin')

@section('title', 'Loan Due Report')
@section('page_title', 'Loan Due Report')

@push('styles')
    <style>
        .loan-due-hero {
            border: 1px solid #dbe7f3;
            border-radius: 1rem;
            padding: 1.25rem;
            margin-bottom: 1.25rem;
            background: linear-gradient(135deg, #f8fbff 0%, #eef6ff 40%, #fbfff9 100%);
        }

        .loan-due-title {
            margin: 0;
            font-size: 1.55rem;
            font-weight: 800;
            color: #10213a;
        }

        .loan-due-meta {
            margin-top: 0.35rem;
            color: #516072;
        }

        .loan-due-summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 0.9rem;
            margin-top: 1.25rem;
        }

        .loan-due-summary-card {
            border: 1px solid #dbe7f3;
            border-radius: 0.95rem;
            background: #fff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
            height: 100%;
        }

        .loan-due-summary-card .card-body {
            padding: 1rem;
        }

        .loan-due-summary-title {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            margin-bottom: 0.8rem;
            font-size: 1rem;
            font-weight: 800;
            color: #213047;
        }

        .loan-due-summary-value {
            font-size: 1.55rem;
            font-weight: 800;
        }

        .loan-due-note {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            margin-top: 0.85rem;
            padding: 0.55rem 0.75rem;
            border-radius: 999px;
            background: rgba(14, 165, 233, 0.08);
            color: #0369a1;
            font-size: 0.82rem;
            font-weight: 600;
        }

        .loan-due-table th,
        .loan-due-table td {
            padding: 0.8rem 0.65rem;
        }

        .loan-due-member {
            font-weight: 700;
            color: #14243b;
            line-height: 1.35;
        }

        .loan-due-member-meta {
            font-size: 0.82rem;
            color: #64748b;
        }

        .loan-due-money {
            font-weight: 800;
            white-space: nowrap;
        }

        .loan-due-money.is-positive {
            color: #166534;
        }

        .loan-due-money.is-danger {
            color: #b91c1c;
        }

        .loan-due-filters .card-body {
            padding-bottom: 0.5rem;
        }
    </style>
@endpush

@section('content')
    <div class="loan-due-hero">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3">
            <div>
                <h2 class="loan-due-title">{{ strtoupper($branch->name) }}</h2>
                <div class="loan-due-meta">
                    Period:
                    <strong>{{ $filters['start_date'] ? \Carbon\Carbon::parse($filters['start_date'])->format('d M Y') : 'Beginning' }}</strong>
                    to
                    <strong>{{ \Carbon\Carbon::parse($filters['end_date'])->format('d M Y') }}</strong>
                </div>
                <div class="loan-due-meta">
                    Outstanding loan balances for this branch as at the selected end date, with approval and due-date context.
                </div>
                <div class="loan-due-note">
                    <i class="fas fa-clock"></i>
                    {{ number_format((int) ($summaryMeta['loan_count'] ?? 0)) }} outstanding loans currently in scope
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-3 mt-lg-0">
                <a href="{{ route('reports.loan-due-report') }}" class="btn btn-primary">
                    <i class="fas fa-sync-alt mr-1"></i>
                    Refresh Report
                </a>
            </div>
        </div>

        <div class="loan-due-summary-grid">
            @foreach ($summaryCards as $card)
                <div class="loan-due-summary-card">
                    <div class="card-body">
                        <div class="loan-due-summary-title">
                            <i class="{{ $card['icon'] }}"></i>
                            <span>{{ $card['label'] }}</span>
                        </div>
                        <div class="loan-due-summary-value {{ $card['tone'] }}">
                            &#8358;{{ number_format((float) $card['value'], 2) }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="card card-outline card-primary loan-due-filters">
        <div class="card-header">
            <h3 class="card-title mb-0">Filters</h3>
        </div>
        <form method="GET" action="{{ route('reports.loan-due-report') }}">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="start_date">Start Date</label>
                            <input type="date" name="start_date" id="start_date" class="form-control" value="{{ $filters['start_date'] }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="end_date">End Date</label>
                            <input type="date" name="end_date" id="end_date" class="form-control" value="{{ $filters['end_date'] }}" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="member_id">Member</label>
                            <select name="member_id" id="member_id" class="form-control select2" data-placeholder="All members">
                                <option value="">All members</option>
                                @foreach ($memberOptions as $memberOption)
                                    <option value="{{ $memberOption->id }}" @selected((string) $filters['member_id'] === (string) $memberOption->id)>
                                        {{ $memberOption->member_code ?: 'N/A' }} - {{ $memberOption->display_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="per_page">Rows Per Page</label>
                            <select name="per_page" id="per_page" class="form-control select2" data-native-select="true">
                                @foreach ([15, 25, 50, 100] as $size)
                                    <option value="{{ $size }}" @selected((int) request('per_page', 15) === $size)>{{ $size }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group mb-0">
                            <label for="search">Search Loan / Member</label>
                            <input
                                type="search"
                                name="search"
                                id="search"
                                class="form-control"
                                value="{{ $filters['search'] }}"
                                placeholder="Search by loan ID, member name, or member number"
                            >
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex flex-wrap justify-content-between align-items-center">
                <div class="text-muted small">
                    This report stays inside the currently selected branch context.
                </div>
                <div class="d-flex flex-wrap align-items-center mt-2 mt-md-0">
                    <a href="{{ route('reports.loan-due-report') }}" class="btn btn-outline-secondary mr-2 mb-2 mb-sm-0">
                        Reset Filters
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter mr-1"></i>
                        Apply Filters
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="card card-outline card-primary">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h3 class="card-title mb-0">Loans Due</h3>
                <small class="text-muted d-block mt-1">
                    Showing {{ $loans->total() }} outstanding loan record{{ $loans->total() === 1 ? '' : 's' }}.
                </small>
            </div>
            <div class="mt-2 mt-md-0 ml-md-auto text-md-right">
                <a href="{{ route('reports.loan-due-report.export', request()->query()) }}" class="btn btn-outline-success">
                    <i class="fas fa-file-excel mr-1"></i>
                    Export
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle loan-due-table">
                    <thead class="thead-light">
                    <tr>
                        <th>Loan ID</th>
                        <th>Branch</th>
                        <th>Member</th>
                        <th>Phone</th>
                        <th>Approval Date</th>
                        <th>Approved By</th>
                        <th>Interest</th>
                        <th>Total Amount</th>
                        <th>Total Paid</th>
                        <th>Due Date</th>
                        <th>Balance</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($loans as $loan)
                        @php
                            $totalAmount = round((float) ($loan->total_amount ?? 0), 2);
                            $totalPaid = round((float) ($loan->total_paid ?? 0), 2);
                            $balance = round((float) ($loan->outstanding_amount ?? 0), 2);
                        @endphp
                        <tr>
                            <td class="font-weight-bold">{{ $loan->loan_id }}</td>
                            <td>{{ $branch->name }}</td>
                            <td>
                                <div class="loan-due-member">{{ $loan->borrower_name ?: 'Unnamed Member' }}</div>
                                <div class="loan-due-member-meta">{{ $loan->member_no ?: 'N/A' }}</div>
                            </td>
                            <td>{{ $loan->member_phone ?: 'N/A' }}</td>
                            <td>{{ $loan->approval_date ? \Carbon\Carbon::parse($loan->approval_date)->format('d M Y') : 'N/A' }}</td>
                            <td>{{ $loan->approved_by_name ?: 'N/A' }}</td>
                            <td>{{ number_format((float) ($loan->interest_rate ?? 0), 4) }}</td>
                            <td><span class="loan-due-money is-positive">&#8358;{{ number_format($totalAmount, 2) }}</span></td>
                            <td><span class="loan-due-money {{ $totalPaid > 0 ? 'is-positive' : '' }}">&#8358;{{ number_format($totalPaid, 2) }}</span></td>
                            <td>{{ $loan->latest_due_date ? \Carbon\Carbon::parse($loan->latest_due_date)->format('d M Y') : 'N/A' }}</td>
                            <td><span class="loan-due-money is-danger">&#8358;{{ number_format($balance, 2) }}</span></td>
                            <td>
                                <a href="{{ route('loans.show', $loan->id) }}" class="btn btn-sm btn-outline-primary">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center text-muted py-4">No outstanding loan records match the selected filters.</td>
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
