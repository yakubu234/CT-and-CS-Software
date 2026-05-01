@extends('layouts.admin')

@section('title', 'Loan Report')
@section('page_title', 'Loan Report')

@push('styles')
    <style>
        .loan-report-hero {
            border: 1px solid #dbe7f3;
            border-radius: 1rem;
            padding: 1.25rem;
            margin-bottom: 1.25rem;
            background: linear-gradient(135deg, #f8fbff 0%, #eef6ff 42%, #f8fffb 100%);
        }

        .loan-report-title {
            margin: 0;
            font-size: 1.55rem;
            font-weight: 800;
            color: #10213a;
        }

        .loan-report-meta {
            margin-top: 0.35rem;
            color: #516072;
        }

        .loan-report-summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 0.9rem;
            margin-top: 1.25rem;
        }

        .loan-report-summary-card {
            border: 1px solid #dbe7f3;
            border-radius: 0.95rem;
            background: #fff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
            height: 100%;
        }

        .loan-report-summary-card .card-body {
            padding: 1rem;
        }

        .loan-report-summary-title {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            margin-bottom: 0.8rem;
            font-size: 0.98rem;
            font-weight: 800;
            color: #213047;
        }

        .loan-report-summary-value {
            font-size: 1.5rem;
            font-weight: 800;
        }

        .loan-report-filters .card-body {
            padding-bottom: 0.5rem;
        }

        .loan-report-kpi-note {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            margin-top: 0.85rem;
            padding: 0.55rem 0.75rem;
            border-radius: 999px;
            background: rgba(37, 99, 235, 0.08);
            color: #1d4ed8;
            font-size: 0.82rem;
            font-weight: 600;
        }

        .loan-report-table th,
        .loan-report-table td {
            padding: 0.8rem 0.65rem;
        }

        .loan-report-member {
            font-weight: 700;
            color: #14243b;
            line-height: 1.35;
        }

        .loan-report-member-meta {
            font-size: 0.82rem;
            color: #64748b;
        }

        .loan-report-money {
            font-weight: 800;
            white-space: nowrap;
        }

        .loan-report-money.is-negative {
            color: #b91c1c;
        }

        .loan-report-money.is-positive {
            color: #166534;
        }

        .loan-report-money.is-neutral {
            color: #0f172a;
        }
    </style>
@endpush

@section('content')
    <div class="loan-report-hero">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3">
            <div>
                <h2 class="loan-report-title">{{ strtoupper($branch->name) }}</h2>
                <div class="loan-report-meta">
                    Period:
                    <strong>{{ $filters['start_date'] ? \Carbon\Carbon::parse($filters['start_date'])->format('d M Y') : 'Beginning' }}</strong>
                    to
                    <strong>{{ \Carbon\Carbon::parse($filters['end_date'])->format('d M Y') }}</strong>
                </div>
                <div class="loan-report-meta">
                    Loan portfolio view for this branch. Repayment and interest figures follow the selected period, while outstanding is shown as at the period end date.
                </div>
                <div class="loan-report-kpi-note">
                    <i class="fas fa-layer-group"></i>
                    {{ number_format((int) ($summaryMeta['loan_count'] ?? 0)) }} loans in scope, {{ number_format((int) ($summaryMeta['active_count'] ?? 0)) }} still active
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-3 mt-lg-0">
                <a href="{{ route('reports.loan-report') }}" class="btn btn-primary">
                    <i class="fas fa-sync-alt mr-1"></i>
                    Refresh Report
                </a>
            </div>
        </div>

        <div class="loan-report-summary-grid">
            @foreach ($summaryCards as $card)
                <div class="loan-report-summary-card">
                    <div class="card-body">
                        <div class="loan-report-summary-title">
                            <i class="{{ $card['icon'] }}"></i>
                            <span>{{ $card['label'] }}</span>
                        </div>
                        <div class="loan-report-summary-value {{ $card['tone'] }}">
                            &#8358;{{ number_format((float) $card['value'], 2) }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="card card-outline card-primary loan-report-filters">
        <div class="card-header">
            <h3 class="card-title mb-0">Filters</h3>
        </div>
        <form method="GET" action="{{ route('reports.loan-report') }}">
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
                    The active branch switcher controls the branch context for this report.
                </div>
                <div class="d-flex flex-wrap align-items-center mt-2 mt-md-0">
                    <a href="{{ route('reports.loan-report') }}" class="btn btn-outline-secondary mr-2 mb-2 mb-sm-0">
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
                <h3 class="card-title mb-0">Loan Portfolio</h3>
                <small class="text-muted d-block mt-1">
                    Showing {{ $loans->total() }} filtered loan record{{ $loans->total() === 1 ? '' : 's' }}.
                </small>
            </div>
            <div class="mt-2 mt-md-0 ml-md-auto text-md-right">
                <a href="{{ route('reports.loan-report.export', request()->query()) }}" class="btn btn-outline-success">
                    <i class="fas fa-file-excel mr-1"></i>
                    Export
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle loan-report-table">
                    <thead class="thead-light">
                    <tr>
                        <th>Loan ID</th>
                        <th>Member</th>
                        <th>Release Date</th>
                        <th>Due Date</th>
                        <th>Original Loan</th>
                        <th>Additional Loan</th>
                        <th>Paid In Period</th>
                        <th>Interest In Period</th>
                        <th>Outstanding</th>
                        <th>Last Payment</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($loans as $loan)
                        @php
                            $originalAmount = round((float) ($loan->original_amount ?? 0), 2);
                            $totalDisbursed = round((float) ($loan->total_disbursed_amount ?? 0), 2);
                            $additionalAmount = round(max($totalDisbursed - $originalAmount, 0), 2);
                            $principalPaidPeriod = round((float) ($loan->principal_paid_period ?? 0), 2);
                            $interestPaidPeriod = round((float) ($loan->interest_paid_period ?? 0), 2);
                            $principalPaidTotal = round((float) ($loan->principal_paid_total ?? 0), 2);
                            $outstandingAmount = round(max($totalDisbursed - $principalPaidTotal, 0), 2);
                            $status = $outstandingAmount > 0 ? 'Active' : 'Closed';
                        @endphp
                        <tr>
                            <td class="font-weight-bold">{{ $loan->loan_id }}</td>
                            <td>
                                <div class="loan-report-member">{{ $loan->borrower_name ?: 'Unnamed Member' }}</div>
                                <div class="loan-report-member-meta">{{ $loan->member_no ?: 'N/A' }}</div>
                            </td>
                            <td>{{ $loan->first_release_date ? \Carbon\Carbon::parse($loan->first_release_date)->format('d M Y') : 'N/A' }}</td>
                            <td>{{ $loan->due_date ? \Carbon\Carbon::parse($loan->due_date)->format('d M Y') : 'N/A' }}</td>
                            <td><span class="loan-report-money is-neutral">&#8358;{{ number_format($originalAmount, 2) }}</span></td>
                            <td><span class="loan-report-money {{ $additionalAmount > 0 ? 'is-positive' : 'is-neutral' }}">&#8358;{{ number_format($additionalAmount, 2) }}</span></td>
                            <td><span class="loan-report-money {{ $principalPaidPeriod > 0 ? 'is-positive' : 'is-neutral' }}">&#8358;{{ number_format($principalPaidPeriod, 2) }}</span></td>
                            <td><span class="loan-report-money {{ $interestPaidPeriod > 0 ? 'is-positive' : 'is-neutral' }}">&#8358;{{ number_format($interestPaidPeriod, 2) }}</span></td>
                            <td><span class="loan-report-money {{ $outstandingAmount > 0 ? 'is-negative' : 'is-neutral' }}">&#8358;{{ number_format($outstandingAmount, 2) }}</span></td>
                            <td>{{ $loan->last_payment_date ? \Carbon\Carbon::parse($loan->last_payment_date)->format('d M Y') : 'No payment yet' }}</td>
                            <td>
                                <span class="badge badge-{{ $outstandingAmount > 0 ? 'warning' : 'success' }}">
                                    {{ $status }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('loans.show', $loan->id) }}" class="btn btn-sm btn-outline-primary">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center text-muted py-4">No loan records match the selected filters.</td>
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
