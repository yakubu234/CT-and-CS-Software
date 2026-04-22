@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@push('styles')
    <style>
        .dashboard-placeholder {
            border: 1px solid #dbe7f3;
            border-radius: 1.1rem;
            background: linear-gradient(135deg, #f8fbff 0%, #eef7ff 50%, #ffffff 100%);
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
            overflow: hidden;
        }

        .dashboard-placeholder__bar {
            height: 8px;
            background: linear-gradient(90deg, #0ea5a4 0%, #38bdf8 100%);
        }

        .dashboard-placeholder__body {
            padding: 2rem;
        }

        .dashboard-placeholder__badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.45rem 0.8rem;
            border-radius: 999px;
            background: #e6fffb;
            color: #0f766e;
            font-weight: 700;
            font-size: 0.85rem;
        }

        .dashboard-placeholder__title {
            margin: 1rem 0 0.6rem;
            font-size: 1.9rem;
            font-weight: 800;
            color: #10213a;
        }

        .dashboard-placeholder__text {
            max-width: 720px;
            color: #516072;
            font-size: 1rem;
            line-height: 1.7;
            margin-bottom: 1.4rem;
        }

        .dashboard-placeholder__grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 0.9rem;
            margin-top: 1.5rem;
        }

        .dashboard-placeholder__card {
            border: 1px dashed #bfd5e8;
            border-radius: 0.95rem;
            background: rgba(255, 255, 255, 0.86);
            padding: 1rem;
        }

        .dashboard-placeholder__card h3 {
            margin: 0 0 0.4rem;
            font-size: 0.98rem;
            font-weight: 800;
            color: #17304f;
        }

        .dashboard-placeholder__card p {
            margin: 0;
            color: #64748b;
            font-size: 0.9rem;
            line-height: 1.55;
        }
    </style>
@endpush

@section('content')
    <div class="dashboard-placeholder">
        <div class="dashboard-placeholder__bar"></div>

        <div class="dashboard-placeholder__body">
            <span class="dashboard-placeholder__badge">
                <i class="fas fa-tools"></i>
                Currently in progress
            </span>

            <h2 class="dashboard-placeholder__title">Dashboard updates are in progress</h2>

            <p class="dashboard-placeholder__text">
                This dashboard is being refreshed to match the cooperative banking workflow properly.
                For now, the reporting and operational pages remain the reliable source for daily work.
                The dashboard will be updated soon with the correct summaries, balances, activity insights, and branch-aware metrics.
            </p>

            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('reports.member-balance') }}" class="btn btn-primary mr-2 mb-2">
                    <i class="fas fa-chart-bar mr-1"></i>
                    Go to Reports
                </a>
                <a href="{{ route('transactions.index') }}" class="btn btn-outline-primary mb-2">
                    <i class="fas fa-exchange-alt mr-1"></i>
                    View Transactions
                </a>
            </div>

            <div class="dashboard-placeholder__grid">
                <div class="dashboard-placeholder__card">
                    <h3>Reports first</h3>
                    <p>Member balance and other report pages remain available while the dashboard is being refined.</p>
                </div>
                <div class="dashboard-placeholder__card">
                    <h3>Branch-aware setup</h3>
                    <p>The refreshed dashboard will respect the active branch so users always know what branch they are viewing.</p>
                </div>
                <div class="dashboard-placeholder__card">
                    <h3>More accurate summaries</h3>
                    <p>We are preparing the right cards and metrics instead of showing outdated booking-related information.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
