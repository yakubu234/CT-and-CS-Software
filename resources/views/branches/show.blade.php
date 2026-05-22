@extends('layouts.admin')

@section('title', 'Branch Details')
@section('page_title', 'Branch Details')

@php
    $storageUrl = fn (?string $path) => $path ? \Illuminate\Support\Facades\Storage::url($path) : null;
    $branchAccount = $branch->branchUser?->savingsAccounts?->firstWhere('is_branch_acount', true);
@endphp

@push('styles')
    <style>
        .branch-finance-hero {
            border: 1px solid #dbe7f3;
            border-radius: 1rem;
            padding: 1.25rem;
            margin-bottom: 1.25rem;
            background: linear-gradient(135deg, #f8fbff 0%, #eef6ff 42%, #fbfff9 100%);
        }

        .branch-finance-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .branch-finance-title {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 800;
            color: #10213a;
        }

        .branch-finance-meta {
            margin-top: 0.35rem;
            color: #516072;
        }

        .branch-finance-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.65rem 0.95rem;
            border-radius: 999px;
            background: #f1f5f9;
            color: #334155;
            font-weight: 700;
        }

        .branch-finance-grid {
            display: grid;
            grid-template-columns: 1.1fr 1.1fr 0.9fr;
            gap: 1rem;
            margin-top: 1.1rem;
        }

        .branch-finance-card {
            border: 1px solid #dbe7f3;
            border-radius: 1rem;
            background: #fff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
            height: 100%;
        }

        .branch-finance-card .card-body {
            padding: 1rem 1.1rem;
        }

        .branch-finance-card-title {
            margin-bottom: 0.9rem;
            padding-bottom: 0.7rem;
            border-bottom: 1px solid #e5edf5;
            font-size: 0.96rem;
            font-weight: 800;
            color: #213047;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            text-align: center;
        }

        .branch-finance-metric-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.85rem;
        }

        .branch-finance-label {
            font-size: 0.84rem;
            color: #64748b;
            margin-bottom: 0.25rem;
        }

        .branch-finance-value {
            font-size: 1.15rem;
            font-weight: 800;
            line-height: 1.15;
        }

        .branch-finance-net {
            margin-top: 0.85rem;
            padding-top: 0.8rem;
            border-top: 1px solid #e5edf5;
            text-align: center;
            color: #475569;
            font-size: 0.95rem;
        }

        .branch-finance-closing {
            background: linear-gradient(180deg, #e0f2fe 0%, #eef9ff 100%);
            border-color: #bfdbfe;
        }

        .branch-finance-closing-value {
            font-size: clamp(2rem, 4.2vw, 3.6rem);
            line-height: 1.02;
            font-weight: 800;
            text-align: center;
            color: #059669;
            word-break: break-word;
        }

        .branch-finance-closing-note {
            margin-top: 0.75rem;
            text-align: center;
            color: #475569;
            font-size: 0.92rem;
        }

        .branch-breakdown-trigger {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
        }

        .branch-breakdown-list {
            display: grid;
            gap: 0.8rem;
        }

        .branch-breakdown-item {
            border: 1px solid #e5edf5;
            border-radius: 0.9rem;
            padding: 0.9rem 1rem;
            background: #fff;
        }

        .branch-breakdown-item-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .branch-breakdown-item-label {
            font-weight: 800;
            color: #14243b;
        }

        .branch-breakdown-item-value {
            font-weight: 800;
            white-space: nowrap;
        }

        .branch-breakdown-subtext {
            margin-top: 0.35rem;
            color: #64748b;
            font-size: 0.9rem;
        }

        .branch-breakdown-account-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 0.8rem;
        }

        .branch-breakdown-account-card {
            border: 1px solid #e5edf5;
            border-radius: 0.9rem;
            padding: 0.9rem 1rem;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        }

        .branch-breakdown-account-label {
            font-size: 0.9rem;
            color: #64748b;
            margin-bottom: 0.35rem;
        }

        .branch-breakdown-account-value {
            font-size: 1.1rem;
            font-weight: 800;
            color: #10213a;
        }

        @media (max-width: 991.98px) {
            .branch-finance-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
    @if (session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif

    <div class="row mb-3">
        <div class="col-md-8">
            <div class="d-flex align-items-center">
                @if ($branch->photo)
                    <img src="{{ $storageUrl($branch->photo) }}" alt="{{ $branch->name }}" class="img-circle elevation-1 mr-3" style="width: 68px; height: 68px; object-fit: cover;">
                @else
                    <div class="bg-light border rounded-circle d-inline-flex align-items-center justify-content-center mr-3" style="width: 68px; height: 68px;">
                        <i class="fas fa-building text-secondary fa-lg"></i>
                    </div>
                @endif
                <div>
                    <h2 class="h4 mb-1">{{ $branch->name }}</h2>
                    <div class="text-muted">{{ $branch->address }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 text-md-right mt-3 mt-md-0">
            <a href="{{ route('branches.edit', $branch) }}" class="btn btn-primary">
                <i class="fas fa-edit mr-1"></i>
                Edit branch
            </a>
            <x-browser-back-button :fallback="route('branches.index')" class="btn btn-light" />
        </div>
    </div>

    <div class="branch-finance-hero">
        <div class="branch-finance-header">
            <div>
                <h3 class="branch-finance-title">Branch Cash Summary</h3>
                <div class="branch-finance-meta">
                    Brought forward is the total movement before
                    <strong>{{ $financeSummary['month_start']->format('d M Y') }}</strong>.
                </div>
                <div class="branch-finance-meta">
                    Inflows and outflows below cover <strong>{{ $financeSummary['month_label'] }}</strong>.
                </div>
            </div>

            <div class="branch-finance-pill">
                <i class="fas fa-calendar-alt"></i>
                {{ $financeSummary['month_label'] }}
            </div>
        </div>

        <div class="branch-finance-grid">
            <div class="branch-finance-card">
                <div class="card-body">
                    <div class="branch-finance-card-title">Brought Forward</div>
                    <div class="branch-finance-metric-grid">
                        <div>
                            <div class="branch-finance-label">Credit Before Month</div>
                            <div class="branch-finance-value text-primary">&#8358;{{ number_format((float) $financeSummary['brought_forward']['credit'], 2) }}</div>
                        </div>
                        <div>
                            <div class="branch-finance-label">Debit Before Month</div>
                            <div class="branch-finance-value text-primary">&#8358;{{ number_format((float) $financeSummary['brought_forward']['debit'], 2) }}</div>
                        </div>
                    </div>
                    <div class="branch-finance-net">
                        Net B/F:
                        <strong class="{{ (float) $financeSummary['brought_forward']['net'] >= 0 ? 'text-primary' : 'text-danger' }}">
                            &#8358;{{ number_format((float) $financeSummary['brought_forward']['net'], 2) }}
                        </strong>
                    </div>
                </div>
            </div>

            <div class="branch-finance-card">
                <div class="card-body">
                    <div class="branch-finance-card-title">Current Month Flow</div>
                    <div class="branch-finance-metric-grid">
                        <div>
                            <div class="branch-finance-label">Inflows</div>
                            <div class="branch-finance-value text-success">&#8358;{{ number_format((float) $financeSummary['current_month']['inflow'], 2) }}</div>
                        </div>
                        <div>
                            <div class="branch-finance-label">Outflows</div>
                            <div class="branch-finance-value text-danger">&#8358;{{ number_format((float) $financeSummary['current_month']['outflow'], 2) }}</div>
                        </div>
                    </div>
                    <div class="branch-finance-net">
                        Net Movement:
                        <strong class="{{ (float) $financeSummary['current_month']['net'] >= 0 ? 'text-success' : 'text-danger' }}">
                            &#8358;{{ number_format((float) $financeSummary['current_month']['net'], 2) }}
                        </strong>
                        <span class="text-muted d-block mt-1">
                            {{ number_format((int) $financeSummary['current_month']['transaction_count']) }} branch transaction{{ (int) $financeSummary['current_month']['transaction_count'] === 1 ? '' : 's' }} this month
                        </span>
                    </div>
                </div>
            </div>

            <div class="branch-finance-card branch-finance-closing">
                <div class="card-body">
                    <div class="branch-finance-card-title">Closing Position</div>
                    <div class="branch-finance-closing-value">
                        &#8358;{{ number_format((float) $financeSummary['closing_balance'], 2) }}
                    </div>
                    <div class="branch-finance-closing-note">
                        B/F net plus current month net
                    </div>
                    <div class="text-center mt-3">
                        <button type="button" class="btn btn-outline-primary branch-breakdown-trigger" data-toggle="modal" data-target="#branchFinanceBreakdownModal">
                            <i class="fas fa-stream"></i>
                            View Breakdown
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="branchFinanceBreakdownModal" tabindex="-1" role="dialog" aria-labelledby="branchFinanceBreakdownLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title font-weight-bold" id="branchFinanceBreakdownLabel">Branch Monthly Breakdown</h5>
                        <div class="text-muted small mt-1">
                            {{ $branch->name }} · {{ $financeSummary['month_label'] }}
                        </div>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light border">
                        Brought forward already covers every branch transaction before
                        <strong>{{ $financeSummary['month_start']->format('d M Y') }}</strong>.
                        The breakdown below shows the current month activity in the exact order requested.
                    </div>

                    <h6 class="text-uppercase text-muted font-weight-bold mb-3">Current Month Breakdown</h6>
                    <div class="branch-breakdown-list mb-4">
                        @foreach ($financeSummary['breakdown']['items'] as $item)
                            <div class="branch-breakdown-item">
                                <div class="branch-breakdown-item-head">
                                    <div class="branch-breakdown-item-label">{{ $item['label'] }}</div>
                                    <div class="branch-breakdown-item-value {{ (float) ($item['amount'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                        &#8358;{{ number_format((float) ($item['amount'] ?? 0), 2) }}
                                    </div>
                                </div>
                                <div class="branch-breakdown-subtext">
                                    @if ($item['label'] === 'Member transactions summary')
                                        Inflow: <strong>&#8358;{{ number_format((float) ($item['inflow'] ?? 0), 2) }}</strong>,
                                        Outflow: <strong>&#8358;{{ number_format((float) ($item['outflow'] ?? 0), 2) }}</strong>,
                                        Entries: <strong>{{ number_format((int) ($item['count'] ?? 0)) }}</strong>
                                    @else
                                        Records this month: <strong>{{ number_format((int) ($item['count'] ?? 0)) }}</strong>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <h6 class="text-uppercase text-muted font-weight-bold mb-3">Member Balances By Account Type</h6>
                    <div class="branch-breakdown-account-grid">
                        @foreach ($financeSummary['breakdown']['account_balances'] as $accountBalance)
                            <div class="branch-breakdown-account-card">
                                <div class="branch-breakdown-account-label">{{ $accountBalance['label'] }}</div>
                                <div class="branch-breakdown-account-value">&#8358;{{ number_format((float) $accountBalance['amount'], 2) }}</div>
                                <div class="branch-breakdown-subtext mt-2">
                                    {{ number_format((int) $accountBalance['count']) }} active account{{ (int) $accountBalance['count'] === 1 ? '' : 's' }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Branch profile</h3>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Branch prefix</dt>
                        <dd class="col-sm-7">{{ $branch->prefix ?: 'N/A' }}</dd>

                        <dt class="col-sm-5">Loan prefix</dt>
                        <dd class="col-sm-7">{{ $branch->id_prefix ?: 'N/A' }}</dd>

                        <dt class="col-sm-5">Meeting cycle</dt>
                        <dd class="col-sm-7 text-uppercase">{{ $branch->branch_meeting_days ?: 'N/A' }}</dd>

                        <dt class="col-sm-5">Contact email</dt>
                        <dd class="col-sm-7">{{ $branch->contact_email ?: 'N/A' }}</dd>

                        <dt class="col-sm-5">Contact phone</dt>
                        <dd class="col-sm-7">{{ $branch->contact_phone ?: 'N/A' }}</dd>

                        <dt class="col-sm-5">Registration number</dt>
                        <dd class="col-sm-7">{{ $branch->registration_number ?: 'N/A' }}</dd>

                        <dt class="col-sm-5">Year of registration</dt>
                        <dd class="col-sm-7">{{ $branch->year_of_registration ?: 'N/A' }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Branch account</h3>
                </div>
                <div class="card-body">
                    @if ($branch->branchUser)
                        <dl class="row mb-0">
                            <dt class="col-sm-4">User name</dt>
                            <dd class="col-sm-8">{{ $branch->branchUser->name }}</dd>

                            <dt class="col-sm-4">Email</dt>
                            <dd class="col-sm-8">{{ $branch->branchUser->email }}</dd>

                            <dt class="col-sm-4">Savings account</dt>
                            <dd class="col-sm-8">{{ $branchAccount?->account_number ?: 'Not found' }}</dd>

                            <dt class="col-sm-4">Status</dt>
                            <dd class="col-sm-8">{{ $branch->branchUser->status ? 'Active' : 'Inactive' }}</dd>
                        </dl>
                    @else
                        <p class="text-muted mb-0">No branch-account user is linked to this branch.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Current excos</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Designation</th>
                        <th>Assumed office</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($branch->excos as $exco)
                        <tr>
                            <td>{{ $exco->name }}</td>
                            <td>{{ $exco->designation ?: 'N/A' }}</td>
                            <td>{{ optional($exco->date_added_as_exco)->format('M d, Y h:i A') ?: 'N/A' }}</td>
                            <td><span class="badge badge-success">Serving</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">No active excos are currently attached to this branch.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Former excos</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Former designation</th>
                        <th>Assumed office</th>
                        <th>Ended office</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($branch->formerExcos as $exco)
                        <tr>
                            <td>{{ $exco->name }}</td>
                            <td>{{ $exco->former_designation ?: 'N/A' }}</td>
                            <td>{{ optional($exco->date_added_as_exco)->format('M d, Y h:i A') ?: 'N/A' }}</td>
                            <td>{{ optional($exco->date_removed_as_exco)->format('M d, Y h:i A') ?: 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">No former exco history has been recorded for this branch yet.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
