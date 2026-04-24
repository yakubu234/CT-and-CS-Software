@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@php
    $money = fn ($amount) => '&#8358;' . number_format((float) $amount, 2);
    $number = fn ($amount) => number_format((float) $amount);
@endphp

@push('styles')
    <style>
        .dashboard-hero {
            position: relative;
            overflow: hidden;
            border-radius: 1.25rem;
            padding: 1.5rem;
            margin-bottom: 1.25rem;
            background:
                radial-gradient(circle at top right, rgba(20, 184, 166, 0.22), transparent 34%),
                linear-gradient(135deg, #10213a 0%, #123f56 52%, #0f766e 100%);
            color: #fff;
            box-shadow: 0 24px 50px rgba(15, 23, 42, 0.18);
        }

        .dashboard-hero h2 {
            margin: 0;
            font-weight: 800;
            letter-spacing: -0.03em;
        }

        .dashboard-hero p {
            margin: 0.45rem 0 0;
            max-width: 720px;
            color: rgba(255, 255, 255, 0.78);
        }

        .dashboard-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.45rem 0.8rem;
            border: 1px solid rgba(255, 255, 255, 0.22);
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            color: #e6fffb;
            font-weight: 700;
            font-size: 0.88rem;
        }

        .metric-card {
            position: relative;
            height: 100%;
            overflow: hidden;
            border: 0;
            border-radius: 1rem;
            box-shadow: 0 14px 32px rgba(15, 23, 42, 0.08);
        }

        .metric-card::after {
            content: "";
            position: absolute;
            width: 120px;
            height: 120px;
            right: -34px;
            top: -38px;
            border-radius: 50%;
            opacity: 0.13;
            background: currentColor;
        }

        .metric-card .card-body {
            position: relative;
            z-index: 1;
            padding: 1.15rem;
        }

        .metric-icon {
            width: 44px;
            height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.9rem;
            margin-bottom: 0.8rem;
            color: #fff;
        }

        .metric-label {
            color: #64748b;
            font-weight: 700;
            font-size: 0.88rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .metric-value {
            margin-top: 0.25rem;
            color: #10213a;
            font-size: 1.45rem;
            font-weight: 900;
            line-height: 1.2;
            word-break: break-word;
        }

        .metric-hint {
            margin-top: 0.45rem;
            color: #64748b;
            font-size: 0.9rem;
        }

        .dashboard-panel {
            border: 1px solid #dbe7f3;
            border-radius: 1rem;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.05);
        }

        .dashboard-panel .card-header {
            border-bottom: 1px solid #e6eef7;
            background: #fff;
            border-radius: 1rem 1rem 0 0;
        }

        .dashboard-panel-title {
            margin: 0;
            font-size: 1rem;
            font-weight: 800;
            color: #10213a;
        }

        .cash-chip {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.95rem 1rem;
            border-radius: 0.9rem;
            background: #f8fbff;
            border: 1px solid #e3edf8;
        }

        .cash-chip span {
            color: #64748b;
            font-weight: 700;
        }

        .cash-chip strong {
            color: #10213a;
            font-size: 1.05rem;
        }

        .quick-action {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.85rem 0.95rem;
            border: 1px solid #e1ebf5;
            border-radius: 0.9rem;
            color: #10213a;
            background: #fff;
            font-weight: 800;
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }

        .quick-action:hover {
            color: #0f766e;
            text-decoration: none;
            transform: translateY(-2px);
            box-shadow: 0 12px 22px rgba(15, 23, 42, 0.08);
        }

        .quick-action i {
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.75rem;
            background: #e6fffb;
            color: #0f766e;
        }

        .activity-row {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 0.75rem;
            padding: 0.9rem 0;
            border-bottom: 1px solid #eef3f8;
        }

        .activity-row:last-child {
            border-bottom: 0;
        }

        .activity-title {
            margin: 0;
            font-weight: 800;
            color: #10213a;
        }

        .activity-meta {
            color: #64748b;
            font-size: 0.86rem;
        }

        .chart-box {
            min-height: 280px;
        }

        @media (max-width: 767.98px) {
            .dashboard-hero {
                padding: 1.15rem;
            }

            .metric-value {
                font-size: 1.18rem;
            }
        }
    </style>
@endpush

@section('content')
    <div class="dashboard-hero">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3">
            <div>
                <span class="dashboard-pill">
                    <i class="fas fa-code-branch"></i>
                    {{ $branch->name }}
                </span>
                <h2 class="mt-3">Cooperative performance at a glance</h2>
                <p>
                    A branch-aware overview of society purse movement, member wallets, loan exposure,
                    pending approvals, and the latest operational activity.
                </p>
            </div>
            <div class="text-lg-right mt-3 mt-lg-0">
                <div class="dashboard-pill">
                    <i class="far fa-calendar-alt"></i>
                    {{ now()->format('D, d M Y') }}
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @foreach ($dashboard['cards'] as $card)
            <div class="col-xl-3 col-md-6 mb-3">
                <a href="{{ $card['route'] }}" class="text-decoration-none">
                    <div class="card metric-card text-{{ $card['tone'] }}">
                        <div class="card-body">
                            <div class="metric-icon bg-{{ $card['tone'] }}">
                                <i class="{{ $card['icon'] }}"></i>
                            </div>
                            <div class="metric-label">{{ $card['label'] }}</div>
                            <div class="metric-value">
                                {!! $card['format'] === 'currency' ? $money($card['value']) : $number($card['value']) !!}
                            </div>
                            <div class="metric-hint">{{ $card['hint'] }}</div>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-lg-8 mb-3">
            <div class="card dashboard-panel h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="dashboard-panel-title">Society Cash Movement</h3>
                    <span class="text-muted small">Last 6 months</span>
                </div>
                <div class="card-body">
                    <div class="chart-box">
                        <canvas id="cashFlowChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-3">
            <div class="card dashboard-panel h-100">
                <div class="card-header">
                    <h3 class="dashboard-panel-title">Today vs This Month</h3>
                </div>
                <div class="card-body">
                    <div class="cash-chip mb-3">
                        <span>Today Inflow</span>
                        <strong class="text-success">{!! $money($dashboard['cash_today']['credits']) !!}</strong>
                    </div>
                    <div class="cash-chip mb-3">
                        <span>Today Outflow</span>
                        <strong class="text-danger">{!! $money($dashboard['cash_today']['debits']) !!}</strong>
                    </div>
                    <div class="cash-chip mb-3">
                        <span>Month Inflow</span>
                        <strong class="text-success">{!! $money($dashboard['cash_this_month']['credits']) !!}</strong>
                    </div>
                    <div class="cash-chip">
                        <span>Month Net</span>
                        <strong class="{{ $dashboard['cash_this_month']['net'] < 0 ? 'text-danger' : 'text-primary' }}">
                            {!! $money($dashboard['cash_this_month']['net']) !!}
                        </strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4 mb-3">
            <div class="card dashboard-panel h-100">
                <div class="card-header">
                    <h3 class="dashboard-panel-title">Member Wallet Mix</h3>
                </div>
                <div class="card-body">
                    <div class="chart-box">
                        <canvas id="accountMixChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-3">
            <div class="card dashboard-panel h-100">
                <div class="card-header">
                    <h3 class="dashboard-panel-title">Activity Source Mix</h3>
                </div>
                <div class="card-body">
                    <div class="chart-box">
                        <canvas id="sourceMixChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-3">
            <div class="card dashboard-panel h-100">
                <div class="card-header">
                    <h3 class="dashboard-panel-title">Loan Health</h3>
                </div>
                <div class="card-body">
                    <div class="cash-chip mb-3">
                        <span>Overdue Loans</span>
                        <strong class="text-danger">{{ number_format($dashboard['loan_stats']['overdue_count']) }}</strong>
                    </div>
                    <div class="cash-chip mb-3">
                        <span>Overdue Amount</span>
                        <strong class="text-danger">{!! $money($dashboard['loan_stats']['overdue_amount']) !!}</strong>
                    </div>
                    <div class="cash-chip mb-3">
                        <span>Repayment This Month</span>
                        <strong class="text-success">{!! $money($dashboard['loan_stats']['repayments_this_month']) !!}</strong>
                    </div>
                    <a href="{{ route('loans.pending') }}" class="btn btn-outline-primary btn-block">
                        Review Pending Loans
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4 mb-3">
            <div class="card dashboard-panel h-100">
                <div class="card-header">
                    <h3 class="dashboard-panel-title">Quick Actions</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach ($dashboard['quick_actions'] as $action)
                            <div class="col-12 mb-2">
                                <a href="{{ $action['route'] }}" class="quick-action">
                                    <i class="{{ $action['icon'] }}"></i>
                                    <span>{{ $action['label'] }}</span>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8 mb-3">
            <div class="card dashboard-panel h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="dashboard-panel-title">Recent Society Activity</h3>
                    <a href="{{ route('transactions.index') }}" class="btn btn-outline-primary btn-sm">View all</a>
                </div>
                <div class="card-body">
                    @forelse ($dashboard['recent_activity'] as $activity)
                        <div class="activity-row">
                            <div>
                                <p class="activity-title">
                                    {{ $activity->description ?: $activity->type ?: 'Society activity' }}
                                </p>
                                <div class="activity-meta">
                                    {{ $activity->trans_date?->format('d M Y') ?? 'No date' }}
                                    &middot;
                                    {{ \Illuminate\Support\Str::headline($activity->tracking_id ?: 'transaction') }}
                                    @if ($activity->account?->account_number)
                                        &middot; {{ $activity->account->account_number }}
                                    @endif
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="badge badge-{{ strtolower($activity->dr_cr) === 'cr' ? 'success' : 'danger' }}">
                                    {{ strtoupper($activity->dr_cr) }}
                                </span>
                                <div class="font-weight-bold mt-1">
                                    {!! $money($activity->amount) !!}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-4">
                            No recent branch activity has been recorded yet.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('vendor/adminlte/plugins/chart.js/Chart.bundle.min.js') }}"></script>
    <script>
        (function () {
            const cashFlow = @json($dashboard['cash_flow_chart']);
            const accountMix = @json($dashboard['account_chart']);
            const sourceMix = @json($dashboard['source_chart']);

            function money(value) {
                return '\u20A6' + Number(value || 0).toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });
            }

            const gridColor = 'rgba(148, 163, 184, 0.22)';

            new Chart(document.getElementById('cashFlowChart').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: cashFlow.labels,
                    datasets: [
                        {
                            label: 'Inflow',
                            data: cashFlow.credits,
                            backgroundColor: 'rgba(15, 118, 110, 0.82)',
                            borderColor: '#0f766e',
                            borderWidth: 1,
                        },
                        {
                            label: 'Outflow',
                            data: cashFlow.debits,
                            backgroundColor: 'rgba(220, 38, 38, 0.72)',
                            borderColor: '#dc2626',
                            borderWidth: 1,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: { position: 'bottom' },
                    tooltips: {
                        callbacks: {
                            label: function (tooltipItem, data) {
                                return data.datasets[tooltipItem.datasetIndex].label + ': ' + money(tooltipItem.yLabel);
                            },
                        },
                    },
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: true,
                                callback: money,
                            },
                            gridLines: { color: gridColor },
                        }],
                        xAxes: [{
                            gridLines: { display: false },
                        }],
                    },
                },
            });

            new Chart(document.getElementById('accountMixChart').getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: accountMix.labels,
                    datasets: [{
                        data: accountMix.values,
                        backgroundColor: ['#0f766e', '#2563eb', '#f59e0b', '#64748b'],
                        borderWidth: 0,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: { position: 'bottom' },
                    cutoutPercentage: 62,
                    tooltips: {
                        callbacks: {
                            label: function (tooltipItem, data) {
                                const label = data.labels[tooltipItem.index] || '';
                                const value = data.datasets[0].data[tooltipItem.index] || 0;
                                return label + ': ' + money(value);
                            },
                        },
                    },
                },
            });

            new Chart(document.getElementById('sourceMixChart').getContext('2d'), {
                type: 'pie',
                data: {
                    labels: sourceMix.labels,
                    datasets: [{
                        data: sourceMix.values,
                        backgroundColor: ['#14b8a6', '#f97316', '#6366f1', '#ef4444', '#64748b'],
                        borderWidth: 0,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: { position: 'bottom' },
                },
            });
        })();
    </script>
@endpush
