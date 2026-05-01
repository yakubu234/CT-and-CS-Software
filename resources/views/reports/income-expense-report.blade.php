@extends('layouts.admin')

@section('title', 'Income & Expenses Report')
@section('page_title', 'Income & Expenses Report')

@push('styles')
    <style>
        .income-report-hero {
            border: 1px solid #dbe7f3;
            border-radius: 1rem;
            padding: 1.25rem;
            margin-bottom: 1.25rem;
            background: linear-gradient(135deg, #f8fbff 0%, #eef6ff 40%, #f8fffb 100%);
        }

        .income-report-title {
            margin: 0;
            font-size: 1.55rem;
            font-weight: 800;
            color: #10213a;
        }

        .income-report-meta {
            margin-top: 0.35rem;
            color: #516072;
        }

        .income-report-period-pill {
            display: inline-flex;
            align-items: center;
            padding: 0.65rem 1rem;
            border-radius: 999px;
            background: #f1f5f9;
            color: #475569;
            font-weight: 700;
        }

        .income-report-summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1.05fr;
            gap: 1rem;
            margin-top: 1.3rem;
        }

        .income-report-summary-card {
            border: 1px solid #dbe7f3;
            border-radius: 1rem;
            background: #fff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
        }

        .income-report-summary-card .card-body {
            padding: 1rem 1.1rem;
        }

        .income-report-summary-title {
            margin-bottom: 0.9rem;
            padding-bottom: 0.7rem;
            border-bottom: 1px solid #e5edf5;
            font-size: 0.98rem;
            font-weight: 800;
            color: #213047;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            text-align: center;
        }

        .income-report-metric-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.9rem;
        }

        .income-report-metric-label {
            font-size: 0.86rem;
            color: #64748b;
            margin-bottom: 0.25rem;
        }

        .income-report-metric-value {
            font-size: 1.15rem;
            font-weight: 800;
        }

        .income-report-net-row {
            margin-top: 0.8rem;
            padding-top: 0.8rem;
            border-top: 1px solid #e5edf5;
            font-size: 0.94rem;
            text-align: center;
            color: #475569;
        }

        .income-report-closing-card {
            background: linear-gradient(180deg, #e0f2fe 0%, #eef9ff 100%);
            border-color: #bfdbfe;
        }

        .income-report-closing-value {
            font-size: clamp(2rem, 5vw, 4.2rem);
            line-height: 1.05;
            font-weight: 800;
            text-align: center;
            color: #059669;
            word-break: break-word;
        }

        .income-report-closing-note {
            text-align: center;
            color: #475569;
            margin-top: 0.7rem;
            font-size: 0.92rem;
        }

        .income-report-filters .card-body {
            padding-bottom: 0.5rem;
        }

        .income-report-table th,
        .income-report-table td {
            padding: 0.8rem 0.65rem;
        }

        .income-report-money {
            font-weight: 800;
            white-space: nowrap;
        }

        .income-report-money.credit {
            color: #166534;
        }

        .income-report-money.debit {
            color: #b91c1c;
        }

        .income-report-money.balance {
            color: #0f172a;
        }

        @media (max-width: 991.98px) {
            .income-report-summary-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
    <div class="income-report-hero">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3">
            <div>
                <h2 class="income-report-title">{{ strtoupper($branch->name) }}</h2>
                <div class="income-report-meta">
                    Period:
                    <strong>{{ $filters['start_date'] ? \Carbon\Carbon::parse($filters['start_date'])->format('d M Y') : 'Beginning' }}</strong>
                    —
                    <strong>{{ \Carbon\Carbon::parse($filters['end_date'])->format('d M Y') }}</strong>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
                <span class="income-report-period-pill">
                    {{ $filters['start_date'] ? \Carbon\Carbon::parse($filters['start_date'])->format('d M Y') : 'Beginning' }} — {{ \Carbon\Carbon::parse($filters['end_date'])->format('d M Y') }}
                </span>
                <a href="{{ route('reports.income-expense-report.export', request()->query()) }}" class="btn btn-success ml-2">
                    <i class="fas fa-file-excel mr-1"></i>
                    Export Excel
                </a>
            </div>
        </div>

        <hr>

        <div class="income-report-summary-grid">
            <div class="income-report-summary-card">
                <div class="card-body">
                    <div class="income-report-summary-title">Brought Forward Balance</div>
                    <div class="income-report-metric-grid">
                        <div>
                            <div class="income-report-metric-label">Credit</div>
                            <div class="income-report-metric-value text-primary">&#8358;{{ number_format((float) $summary['bf_credit'], 2) }}</div>
                        </div>
                        <div>
                            <div class="income-report-metric-label">Debit</div>
                            <div class="income-report-metric-value text-primary">&#8358;{{ number_format((float) $summary['bf_debit'], 2) }}</div>
                        </div>
                    </div>
                    <div class="income-report-net-row">
                        Net B/F:
                        <strong class="text-primary">&#8358;{{ number_format((float) $summary['bf_net'], 2) }}</strong>
                    </div>
                </div>
            </div>

            <div class="income-report-summary-card">
                <div class="card-body">
                    <div class="income-report-summary-title">Current Period Flow</div>
                    <div class="income-report-metric-grid">
                        <div>
                            <div class="income-report-metric-label">Inflow (Credit)</div>
                            <div class="income-report-metric-value text-success">&#8358;{{ number_format((float) $summary['current_credit'], 2) }}</div>
                        </div>
                        <div>
                            <div class="income-report-metric-label">Outflow (Debit)</div>
                            <div class="income-report-metric-value text-danger">&#8358;{{ number_format((float) $summary['current_debit'], 2) }}</div>
                        </div>
                    </div>
                    <div class="income-report-net-row">
                        Net Change:
                        <strong class="{{ (float) $summary['current_net'] >= 0 ? 'text-success' : 'text-danger' }}">
                            &#8358;{{ number_format((float) $summary['current_net'], 2) }}
                        </strong>
                    </div>
                </div>
            </div>

            <div class="income-report-summary-card income-report-closing-card">
                <div class="card-body">
                    <div class="income-report-summary-title">Closing Balance</div>
                    <div class="income-report-closing-value">
                        &#8358;{{ number_format((float) $summary['closing_balance'], 2) }}
                    </div>
                    <div class="income-report-closing-note">
                        (B/F Net + Current Net)
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-outline card-primary income-report-filters">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.income-expense-report') }}">
                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="start_date">Start Date</label>
                            <input type="date" id="start_date" name="start_date" class="form-control" value="{{ $filters['start_date'] }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="end_date">End Date</label>
                            <input type="date" id="end_date" name="end_date" class="form-control" value="{{ $filters['end_date'] }}" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="branch_name">Select Branch</label>
                            <input type="text" id="branch_name" class="form-control" value="{{ $branch->name }}" disabled>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="category">Category</label>
                            <select name="category" id="category" class="form-control select2" data-placeholder="All">
                                <option value="">All</option>
                                @foreach ($categoryOptions as $categoryOption)
                                    <option value="{{ $categoryOption }}" @selected($filters['category'] === $categoryOption)>{{ $categoryOption }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-filter mr-1"></i>
                            Apply Filters
                        </button>
                    </div>
                    <div class="col-12">
                        <div class="form-group mb-0">
                            <input
                                type="search"
                                name="search"
                                id="search"
                                class="form-control"
                                value="{{ $filters['search'] }}"
                                placeholder="Search description/type..."
                            >
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-outline card-primary">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
            <h3 class="card-title mb-0">Expenses & Income</h3>
            <span class="income-report-period-pill">
                {{ $filters['start_date'] ? \Carbon\Carbon::parse($filters['start_date'])->format('d M Y') : 'Beginning' }} — {{ \Carbon\Carbon::parse($filters['end_date'])->format('d M Y') }}
            </span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle income-report-table">
                    <thead class="thead-light">
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Credit</th>
                        <th>Debit</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th>Running Net</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr class="bg-light">
                        <td>{{ $filters['start_date'] ? \Carbon\Carbon::parse($filters['start_date'])->format('d M Y') : 'Beginning' }} (B/F)</td>
                        <td></td>
                        <td>Brought Forward</td>
                        <td></td>
                        <td></td>
                        <td><span class="income-report-money balance">&#8358;{{ number_format((float) $summary['bf_net'], 2) }}</span></td>
                        <td></td>
                        <td><span class="income-report-money balance">&#8358;{{ number_format((float) $summary['bf_net'], 2) }}</span></td>
                    </tr>

                    @forelse ($records as $record)
                        @php
                            $isCredit = strtolower((string) $record->dr_cr) === 'cr';
                            $credit = $isCredit ? round((float) $record->amount, 2) : 0;
                            $debit = $isCredit ? 0 : round((float) $record->amount, 2);
                            $balance = round((float) data_get($record->transaction_details, 'balance_after', 0), 2);
                        @endphp
                        <tr>
                            <td>{{ optional($record->trans_date)->format('d M Y h:i A') ?: 'N/A' }}</td>
                            <td>{{ $record->type ?: 'N/A' }}</td>
                            <td>{{ $record->description ?: $record->type ?: 'N/A' }}</td>
                            <td>
                                @if ($credit > 0)
                                    <span class="income-report-money credit">&#8358;{{ number_format($credit, 2) }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($debit > 0)
                                    <span class="income-report-money debit">&#8358;{{ number_format($debit, 2) }}</span>
                                @endif
                            </td>
                            <td><span class="income-report-money balance">&#8358;{{ number_format($balance, 2) }}</span></td>
                            <td>
                                <span class="badge badge-{{ (int) $record->status === 2 ? 'success' : ((int) $record->status === 1 ? 'warning' : 'secondary') }}">
                                    {{ (int) $record->status === 2 ? 'Completed' : ((int) $record->status === 1 ? 'Pending' : 'Draft') }}
                                </span>
                            </td>
                            <td><span class="income-report-money balance">&#8358;{{ number_format($balance, 2) }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No branch income or expense records match the selected filters.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $records->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection
