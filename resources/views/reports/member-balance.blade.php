@extends('layouts.admin')

@section('title', 'Member Balance Report')
@section('page_title', 'Member Balance Report')

@push('styles')
    <style>
        .report-hero {
            border: 1px solid #dbe7f3;
            border-radius: 1rem;
            padding: 1.25rem;
            margin-bottom: 1.25rem;
            background: linear-gradient(135deg, #f8fbff 0%, #eef6ff 48%, #ffffff 100%);
        }

        .report-hero-title {
            margin: 0;
            font-size: 1.55rem;
            font-weight: 800;
            color: #10213a;
        }

        .report-hero-meta {
            margin-top: 0.35rem;
            color: #516072;
        }

        .report-summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 0.9rem;
            margin-top: 1.25rem;
        }

        .report-summary-card {
            height: 100%;
            border: 1px solid #dbe7f3;
            border-radius: 0.95rem;
            background: #fff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
        }

        .report-summary-card .card-body {
            padding: 1rem 1rem 0.9rem;
        }

        .report-summary-title {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            margin-bottom: 0.8rem;
            font-size: 1rem;
            font-weight: 800;
            color: #213047;
        }

        .report-summary-metric {
            display: flex;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 0.45rem;
            font-size: 0.92rem;
        }

        .report-summary-metric:last-child {
            margin-bottom: 0;
        }

        .report-summary-metric strong {
            text-align: right;
        }

        .report-filters-card .card-body {
            padding-bottom: 0.5rem;
        }

        .member-balance-table th,
        .member-balance-table td {
            padding: 0.8rem 0.6rem;
        }

        .member-balance-table .member-name {
            font-weight: 700;
            color: #14243b;
            line-height: 1.35;
        }

        .member-balance-table .member-meta {
            font-size: 0.82rem;
            color: #64748b;
        }

        .report-balance-stack {
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
        }

        .report-balance-stack small {
            color: #64748b;
        }

        .report-balance-value {
            font-weight: 800;
            line-height: 1.25;
        }

        .report-balance-value.is-negative {
            color: #b91c1c;
        }

        .report-balance-value.is-positive {
            color: #166534;
        }

        .report-balance-value.is-neutral {
            color: #0f172a;
        }
    </style>
@endpush

@section('content')
    <div class="report-hero">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3">
            <div>
                <h2 class="report-hero-title">{{ strtoupper($branch->name) }}</h2>
                <div class="report-hero-meta">
                    Period:
                    <strong>{{ $filters['start_date'] ? \Carbon\Carbon::parse($filters['start_date'])->format('d M Y') : 'Beginning' }}</strong>
                    to
                    <strong>{{ \Carbon\Carbon::parse($filters['end_date'])->format('d M Y') }}</strong>
                </div>
                <div class="report-hero-meta">
                    Branch-scoped member balances with brought-forward and closing values.
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-3 mt-lg-0">
                <a href="{{ route('reports.member-balance') }}" class="btn btn-primary">
                    <i class="fas fa-sync-alt mr-1"></i>
                    Refresh Report
                </a>
            </div>
        </div>

        <div class="report-summary-grid">
            @foreach ($summaryCards as $card)
                <div class="report-summary-card">
                    <div class="card-body">
                        <div class="report-summary-title">
                            <i class="{{ $card['icon'] }}"></i>
                            <span>{{ $card['label'] }}</span>
                        </div>

                        <div class="report-summary-metric">
                            <span>B/F</span>
                            <strong class="{{ $card['opening'] < 0 ? 'text-danger' : 'text-primary' }}">
                                &#8358;{{ number_format((float) $card['opening'], 2) }}
                            </strong>
                        </div>

                        <div class="report-summary-metric">
                            <span>Current</span>
                            <strong class="{{ $card['tone'] }}">
                                &#8358;{{ number_format((float) $card['current'], 2) }}
                            </strong>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="card card-outline card-primary report-filters-card">
        <div class="card-header">
            <h3 class="card-title mb-0">Filters</h3>
        </div>
        <form method="GET" action="{{ route('reports.member-balance') }}">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="start_date">Start Date</label>
                            <input
                                type="date"
                                name="start_date"
                                id="start_date"
                                class="form-control"
                                value="{{ $filters['start_date'] }}"
                            >
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="end_date">End Date</label>
                            <input
                                type="date"
                                name="end_date"
                                id="end_date"
                                class="form-control"
                                value="{{ $filters['end_date'] }}"
                                required
                            >
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="member_id">Member</label>
                            <select
                                name="member_id"
                                id="member_id"
                                class="form-control select2"
                                data-placeholder="All members"
                            >
                                <option value="">All members</option>
                                @foreach ($memberOptions as $memberOption)
                                    <option
                                        value="{{ $memberOption->id }}"
                                        @selected((string) $filters['member_id'] === (string) $memberOption->id)
                                    >
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
                            <label for="search">Search Member/Number</label>
                            <input
                                type="search"
                                name="search"
                                id="search"
                                class="form-control"
                                value="{{ $filters['search'] }}"
                                placeholder="Search by member name, member number, or email"
                            >
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex flex-wrap justify-content-between align-items-center">
                <div class="text-muted small">
                    The branch is controlled by the active branch switcher, so this report always stays branch-specific.
                </div>
                <div class="d-flex flex-wrap align-items-center mt-2 mt-md-0">
                    <a href="{{ route('reports.member-balance') }}" class="btn btn-outline-secondary mr-2 mb-2 mb-sm-0">
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
                <h3 class="card-title mb-0">Member Balances</h3>
                <small class="text-muted d-block mt-1">
                    Showing {{ $members->total() }} filtered member record{{ $members->total() === 1 ? '' : 's' }}.
                </small>
            </div>
            <div class="mt-2 mt-md-0 ml-md-auto text-md-right">
                <a
                    href="{{ route('reports.member-balance.export', request()->query()) }}"
                    class="btn btn-outline-success"
                >
                    <i class="fas fa-file-excel mr-1"></i>
                    Export
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle member-balance-table">
                    <colgroup>
                        <col style="width: 18%;">
                        <col style="width: 11%;">
                        <col style="width: 10%;">
                        <col style="width: 10%;">
                        <col style="width: 10%;">
                        <col style="width: 10%;">
                        <col style="width: 10%;">
                        <col style="width: 11%;">
                        <col style="width: 10%;">
                    </colgroup>
                    <thead class="thead-light">
                    <tr>
                        <th>Member</th>
                        <th>Member No</th>
                        <th>Loan</th>
                        <th>Savings</th>
                        <th>Shares</th>
                        <th>Auth</th>
                        <th>Deposit</th>
                        <th>Total Net</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($members as $member)
                        @php
                            $loanOpening = (float) $member->loan_opening;
                            $loanCurrent = (float) $member->loan_current;
                            $savingsOpening = (float) $member->savings_opening;
                            $savingsCurrent = (float) $member->savings_current;
                            $sharesOpening = (float) $member->shares_opening;
                            $sharesCurrent = (float) $member->shares_current;
                            $authOpening = (float) $member->auth_opening;
                            $authCurrent = (float) $member->auth_current;
                            $depositOpening = (float) $member->deposit_opening;
                            $depositCurrent = (float) $member->deposit_current;
                            $netOpening = ($savingsOpening + $sharesOpening + $authOpening + $depositOpening) - $loanOpening;
                            $netCurrent = ($savingsCurrent + $sharesCurrent + $authCurrent + $depositCurrent) - $loanCurrent;
                        @endphp
                        <tr>
                            <td>
                                <div class="member-name">{{ $member->display_name ?: 'Unnamed Member' }}</div>
                                <div class="member-meta">{{ $member->email ?: 'No email supplied' }}</div>
                            </td>
                            <td>
                                <span class="font-weight-bold">{{ $member->member_no ?: 'N/A' }}</span>
                            </td>
                            @foreach ([
                                ['opening' => $loanOpening, 'current' => $loanCurrent, 'invert' => true],
                                ['opening' => $savingsOpening, 'current' => $savingsCurrent],
                                ['opening' => $sharesOpening, 'current' => $sharesCurrent],
                                ['opening' => $authOpening, 'current' => $authCurrent],
                                ['opening' => $depositOpening, 'current' => $depositCurrent],
                                ['opening' => $netOpening, 'current' => $netCurrent],
                            ] as $balance)
                                @php
                                    $currentClass = $balance['current'] > 0
                                        ? (($balance['invert'] ?? false) ? 'is-negative' : 'is-positive')
                                        : ($balance['current'] < 0 ? 'is-negative' : 'is-neutral');
                                @endphp
                                <td>
                                    <div class="report-balance-stack">
                                        <small>B/F</small>
                                        <span class="report-balance-value is-neutral">
                                            &#8358;{{ number_format((float) $balance['opening'], 2) }}
                                        </span>
                                        <small>Current</small>
                                        <span class="report-balance-value {{ $currentClass }}">
                                            &#8358;{{ number_format((float) $balance['current'], 2) }}
                                        </span>
                                    </div>
                                </td>
                            @endforeach
                            <td>
                                <a href="{{ route('members.show', $member->id) }}" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-user mr-1"></i>
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                No member balance records match the selected filters.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $members->links() }}
            </div>
        </div>
    </div>
@endsection
