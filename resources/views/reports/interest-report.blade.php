@extends('layouts.admin')

@section('title', 'Interest Report')
@section('page_title', 'Interest Report')

@push('styles')
    <style>
        .interest-report-hero {
            border: 1px solid #dbe7f3;
            border-radius: 1rem;
            padding: 1.25rem;
            margin-bottom: 1.25rem;
            background: linear-gradient(135deg, #f8fbff 0%, #eef6ff 40%, #f9fff9 100%);
        }

        .interest-report-title {
            margin: 0;
            font-size: 1.55rem;
            font-weight: 800;
            color: #10213a;
        }

        .interest-report-meta {
            margin-top: 0.35rem;
            color: #516072;
        }

        .interest-report-note {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            margin-top: 0.85rem;
            padding: 0.55rem 0.8rem;
            border-radius: 999px;
            background: rgba(2, 132, 199, 0.08);
            color: #075985;
            font-size: 0.82rem;
            font-weight: 600;
        }

        .interest-report-summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 0.9rem;
            margin-top: 1.25rem;
        }

        .interest-report-summary-card {
            border: 1px solid #dbe7f3;
            border-radius: 0.95rem;
            background: #fff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
        }

        .interest-report-summary-card .card-body {
            padding: 1rem;
        }

        .interest-report-summary-title {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            margin-bottom: 0.8rem;
            font-size: 0.98rem;
            font-weight: 800;
            color: #213047;
        }

        .interest-report-summary-value {
            font-size: 1.45rem;
            font-weight: 800;
        }

        .interest-report-table th,
        .interest-report-table td {
            padding: 0.8rem 0.65rem;
        }

        .interest-report-money {
            font-weight: 800;
            white-space: nowrap;
        }

        .interest-report-money.is-positive {
            color: #166534;
        }

        .interest-report-money.is-danger {
            color: #b91c1c;
        }

        .interest-report-money.is-neutral {
            color: #0f172a;
        }
    </style>
@endpush

@section('content')
    <div class="interest-report-hero">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3">
            <div>
                <h2 class="interest-report-title">{{ strtoupper($branch->name) }}</h2>
                <div class="interest-report-meta">
                    Period:
                    <strong>{{ $filters['start_date'] ? \Carbon\Carbon::parse($filters['start_date'])->format('d M Y') : 'Beginning' }}</strong>
                    to
                    <strong>{{ \Carbon\Carbon::parse($filters['end_date'])->format('d M Y') }}</strong>
                </div>
                <div class="interest-report-meta">
                    This report focuses on interest already collected plus any carried-forward interest still outstanding per member.
                </div>
                <div class="interest-report-note">
                    <i class="fas fa-file-excel"></i>
                    Export includes a summary sheet first, then one sheet per member using the member number.
                </div>
            </div>

            <div class="mt-3 mt-lg-0">
                <a href="{{ route('reports.interest-report.export', request()->query()) }}" class="btn btn-success">
                    <i class="fas fa-file-excel mr-1"></i>
                    Export Multi-Sheet Workbook
                </a>
            </div>
        </div>

        <div class="interest-report-summary-grid">
            <div class="interest-report-summary-card">
                <div class="card-body">
                    <div class="interest-report-summary-title">
                        <i class="fas fa-history"></i>
                        <span>Brought Forward</span>
                    </div>
                    <div class="interest-report-summary-value text-primary">
                        &#8358;{{ number_format((float) $summary['interest_brought_forward'], 2) }}
                    </div>
                </div>
            </div>
            <div class="interest-report-summary-card">
                <div class="card-body">
                    <div class="interest-report-summary-title">
                        <i class="fas fa-percent"></i>
                        <span>Current Period</span>
                    </div>
                    <div class="interest-report-summary-value text-success">
                        &#8358;{{ number_format((float) $summary['interest_current'], 2) }}
                    </div>
                </div>
            </div>
            <div class="interest-report-summary-card">
                <div class="card-body">
                    <div class="interest-report-summary-title">
                        <i class="fas fa-layer-group"></i>
                        <span>Total To Date</span>
                    </div>
                    <div class="interest-report-summary-value text-info">
                        &#8358;{{ number_format((float) $summary['interest_total'], 2) }}
                    </div>
                </div>
            </div>
            <div class="interest-report-summary-card">
                <div class="card-body">
                    <div class="interest-report-summary-title">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>Outstanding Interest</span>
                    </div>
                    <div class="interest-report-summary-value text-danger">
                        &#8358;{{ number_format((float) $summary['outstanding_interest'], 2) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title mb-0">Filters</h3>
        </div>
        <form method="GET" action="{{ route('reports.interest-report') }}">
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
                                        {{ \App\Support\MemberNumber::normalize($memberOption->member_code, $branch) ?: 'N/A' }} - {{ $memberOption->display_name }}
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
                            <label for="search">Search Member</label>
                            <input
                                type="search"
                                name="search"
                                id="search"
                                class="form-control"
                                value="{{ $filters['search'] }}"
                                placeholder="Search by member name, member number, or loan ID"
                            >
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex flex-wrap justify-content-between align-items-center">
                <div class="text-muted small">
                    The export workbook follows the same filters you apply here.
                </div>
                <div class="d-flex flex-wrap align-items-center mt-2 mt-md-0">
                    <a href="{{ route('reports.interest-report') }}" class="btn btn-outline-secondary mr-2 mb-2 mb-sm-0">
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

    <div class="row">
        <div class="col-lg-4">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title mb-0">Week By Week</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0 interest-report-table">
                            <thead class="thead-light">
                            <tr>
                                <th>Week</th>
                                <th>Interest</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($weeklyTrend as $week)
                                <tr>
                                    <td>{{ $week['week_label'] }}</td>
                                    <td><span class="interest-report-money is-positive">&#8358;{{ number_format((float) $week['total_interest'], 2) }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted py-4">No interest collections match the selected period.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card card-outline card-primary">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title mb-0">Member Interest Summary</h3>
                        <small class="text-muted d-block mt-1">
                            Showing {{ $members->total() }} member record{{ $members->total() === 1 ? '' : 's' }} in scope.
                        </small>
                    </div>
                    <div class="mt-2 mt-md-0">
                        <span class="badge badge-light p-2">
                            {{ number_format((int) $summary['member_count']) }} member{{ (int) $summary['member_count'] === 1 ? '' : 's' }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle interest-report-table">
                            <thead class="thead-light">
                            <tr>
                                <th>Member No</th>
                                <th>Member Name</th>
                                <th>B/F Interest</th>
                                <th>Current Interest</th>
                                <th>Total Interest</th>
                                <th>Outstanding</th>
                                <th>Loans</th>
                                <th>Last Interest Date</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($members as $member)
                                <tr>
                                    <td class="font-weight-bold">{{ \App\Support\MemberNumber::normalize($member->member_no, $branch) ?: 'N/A' }}</td>
                                    <td>{{ $member->member_name ?: 'Unnamed Member' }}</td>
                                    <td><span class="interest-report-money is-neutral">&#8358;{{ number_format((float) ($member->interest_brought_forward ?? 0), 2) }}</span></td>
                                    <td><span class="interest-report-money is-positive">&#8358;{{ number_format((float) ($member->interest_current ?? 0), 2) }}</span></td>
                                    <td><span class="interest-report-money is-positive">&#8358;{{ number_format((float) ($member->interest_total ?? 0), 2) }}</span></td>
                                    <td><span class="interest-report-money {{ (float) ($member->outstanding_interest ?? 0) > 0 ? 'is-danger' : 'is-neutral' }}">&#8358;{{ number_format((float) ($member->outstanding_interest ?? 0), 2) }}</span></td>
                                    <td>{{ number_format((int) ($member->loan_count ?? 0)) }}</td>
                                    <td>{{ $member->last_interest_date ? \Carbon\Carbon::parse($member->last_interest_date)->format('d M Y') : 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No member interest records match the selected filters.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $members->withQueryString()->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
