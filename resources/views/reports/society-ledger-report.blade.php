@extends('layouts.admin')

@section('title', 'Society Ledger Report')
@section('page_title', 'Society Ledger Report')

@section('content')
    <div class="card card-outline card-primary mb-3">
        <div class="card-header">
            <h3 class="card-title">{{ strtoupper($branch->name) }}</h3>
            <div class="card-tools">
                <a href="{{ route('reports.soc-ledger-report.export', request()->query()) }}" class="btn btn-sm btn-success">
                    <i class="fas fa-file-excel mr-1"></i> Export Excel
                </a>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('reports.soc-ledger-report') }}">
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
                            <input type="date" name="end_date" id="end_date" class="form-control" value="{{ $filters['end_date'] }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="member_id">Member</label>
                            <select name="member_id" id="member_id" class="form-control">
                                <option value="">All Members</option>
                                @foreach ($memberOptions as $member)
                                    <option value="{{ $member['id'] }}" @selected((string) $filters['member_id'] === (string) $member['id'])>
                                        {{ $member['name'] }} {{ $member['member_no'] ? '(' . $member['member_no'] . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="search">Search</label>
                            <input type="text" name="search" id="search" class="form-control" value="{{ $filters['search'] }}" placeholder="Member, account, type">
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter mr-1"></i> Filter
                        </button>
                        <a href="{{ route('reports.soc-ledger-report') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        @foreach ([
            ['label' => 'Transactions', 'value' => number_format((int) $summary['count']), 'class' => 'text-primary'],
            ['label' => 'Total Debit', 'value' => '&#8358;' . number_format((float) $summary['debit'], 2), 'class' => 'text-danger'],
            ['label' => 'Total Credit', 'value' => '&#8358;' . number_format((float) $summary['credit'], 2), 'class' => 'text-success'],
            ['label' => 'Net Movement', 'value' => '&#8358;' . number_format((float) $summary['net'], 2), 'class' => 'text-info'],
        ] as $card)
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase font-weight-bold">{{ $card['label'] }}</div>
                        <div class="h5 mb-0 {{ $card['class'] }}">{!! $card['value'] !!}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title">Grouped Member Ledger Preview</h3>
        </div>
        <div class="card-body">
            @forelse ($memberLedgers as $index => $ledger)
                <div class="border rounded mb-3">
                    <div class="p-3 bg-light d-flex flex-wrap justify-content-between align-items-center">
                        <div>
                            <div class="font-weight-bold">{{ $ledger['member']['name'] }}</div>
                            <div class="small text-muted">{{ $ledger['member']['member_no'] ?: 'No member number' }}</div>
                        </div>
                        <button class="btn btn-sm btn-outline-primary" type="button" data-toggle="collapse" data-target="#member-ledger-{{ $index }}">
                            View Preview
                        </button>
                    </div>
                    <div class="p-3">
                        <div class="row">
                            @foreach ([
                                ['label' => 'Shares', 'value' => $ledger['summary']['shares_balance']],
                                ['label' => 'Savings', 'value' => $ledger['summary']['savings_balance']],
                                ['label' => 'Deposit', 'value' => $ledger['summary']['deposit_balance']],
                                ['label' => 'Authentication', 'value' => $ledger['summary']['authentication_balance']],
                                ['label' => 'Loan Balance', 'value' => $ledger['summary']['loan_balance']],
                                ['label' => 'Loan Interest', 'value' => $ledger['summary']['loan_interest']],
                            ] as $item)
                                <div class="col-lg-2 col-md-4 col-sm-6 mb-2">
                                    <div class="border rounded p-2 h-100">
                                        <div class="small text-muted">{{ $item['label'] }}</div>
                                        <div class="font-weight-bold">&#8358;{{ number_format((float) $item['value'], 2) }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div id="member-ledger-{{ $index }}" class="collapse {{ $index === 0 ? 'show' : '' }}">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Particular</th>
                                    <th>Ref</th>
                                    <th class="text-right">Savings DR</th>
                                    <th class="text-right">Savings CR</th>
                                    <th class="text-right">Savings Bal</th>
                                    <th class="text-right">Loan DR</th>
                                    <th class="text-right">Loan CR</th>
                                    <th class="text-right">Loan Bal</th>
                                    <th class="text-right">Interest</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($ledger['rows'] as $row)
                                    <tr>
                                        <td>{{ $row['date'] }}</td>
                                        <td>{{ $row['particular'] }}</td>
                                        <td>{{ $row['reference'] }}</td>
                                        <td class="text-right">{{ (float) $row['SAVINGS_debit'] > 0 ? number_format((float) $row['SAVINGS_debit'], 2) : '' }}</td>
                                        <td class="text-right">{{ (float) $row['SAVINGS_credit'] > 0 ? number_format((float) $row['SAVINGS_credit'], 2) : '' }}</td>
                                        <td class="text-right">{{ number_format((float) $row['SAVINGS_balance'], 2) }}</td>
                                        <td class="text-right">{{ (float) $row['loan_debit'] > 0 ? number_format((float) $row['loan_debit'], 2) : '' }}</td>
                                        <td class="text-right">{{ (float) $row['loan_credit'] > 0 ? number_format((float) $row['loan_credit'], 2) : '' }}</td>
                                        <td class="text-right">{{ number_format((float) $row['loan_balance'], 2) }}</td>
                                        <td class="text-right">{{ (float) $row['loan_interest'] > 0 ? number_format((float) $row['loan_interest'], 2) : '' }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if ($ledger['total_rows'] > $ledger['rows']->count())
                            <div class="small text-muted p-2">
                                Showing {{ $ledger['rows']->count() }} of {{ $ledger['total_rows'] }} ledger rows. Export Excel for the complete member ledger.
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-4">No grouped member ledger preview is available for the selected filters.</div>
            @endforelse
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Member Transaction Ledger</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-bordered table-hover mb-0">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Member</th>
                    <th>Member No</th>
                    <th>Account</th>
                    <th>Description</th>
                    <th>Type</th>
                    <th class="text-right">Debit</th>
                    <th class="text-right">Credit</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($records as $transaction)
                    @php
                        $isCredit = strtolower((string) $transaction->dr_cr) === 'cr';
                    @endphp
                    <tr>
                        <td>{{ optional($transaction->trans_date)->format('d M Y') ?: 'N/A' }}</td>
                        <td>{{ $transaction->user?->name ?: 'N/A' }}</td>
                        <td>{{ $transaction->user?->display_member_no ?: 'N/A' }}</td>
                        <td>
                            <div class="font-weight-bold">{{ $transaction->account?->account_number ?: 'N/A' }}</div>
                            <div class="small text-muted">{{ $transaction->account?->product?->type ?: 'N/A' }}</div>
                        </td>
                        <td>{{ $transaction->description ?: $transaction->type ?: 'Transaction' }}</td>
                        <td>{{ $transaction->type ?: 'N/A' }}</td>
                        <td class="text-right text-danger">
                            {!! ! $isCredit ? '&#8358;' . number_format((float) $transaction->amount, 2) : '' !!}
                        </td>
                        <td class="text-right text-success">
                            {!! $isCredit ? '&#8358;' . number_format((float) $transaction->amount, 2) : '' !!}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No ledger transactions match the selected filters.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $records->links() }}
        </div>
    </div>
@endsection
