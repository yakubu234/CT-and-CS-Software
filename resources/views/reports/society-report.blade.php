@extends('layouts.admin')

@section('title', 'Society Report')
@section('page_title', 'Society Report')

@section('content')
    <div class="card card-outline card-primary mb-3">
        <div class="card-header">
            <h3 class="card-title">{{ strtoupper($branch->name) }}</h3>
            <div class="card-tools">
                <a href="{{ route('reports.society-report.export', request()->query()) }}" class="btn btn-sm btn-success">
                    <i class="fas fa-file-excel mr-1"></i> Export Excel
                </a>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('reports.society-report') }}">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="start_date">Start Date</label>
                            <input type="date" name="start_date" id="start_date" class="form-control" value="{{ $filters['start_date'] }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="end_date">End Date</label>
                            <input type="date" name="end_date" id="end_date" class="form-control" value="{{ $filters['end_date'] }}">
                        </div>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-group w-100">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter mr-1"></i> Filter
                            </button>
                            <a href="{{ route('reports.society-report') }}" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        @foreach ([
            ['label' => 'Brought Forward', 'value' => $summary['brought_forward'], 'class' => 'text-primary'],
            ['label' => 'Account Subtotal', 'value' => $summary['product_subtotal'], 'class' => 'text-info'],
            ['label' => 'Loan + Expense Debit', 'value' => $summary['loan_expense_debit'], 'class' => 'text-danger'],
            ['label' => 'Final Total', 'value' => $summary['final_total'], 'class' => 'text-success'],
        ] as $card)
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase font-weight-bold">{{ $card['label'] }}</div>
                        <div class="h5 mb-0 {{ $card['class'] }}">&#8358;{{ number_format((float) $card['value'], 2) }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if ($warnings !== [])
        <div class="alert alert-warning">
            <div class="font-weight-bold mb-1">
                <i class="fas fa-exclamation-triangle mr-1"></i> Review Notes
            </div>
            <ul class="mb-0 pl-3">
                @foreach ($warnings as $warning)
                    <li>{{ $warning }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title">Reconciliation Breakdown</h3>
        </div>
        <div class="card-body">
            <div class="row">
                @foreach ([
                    ['label' => 'Opening Balance', 'value' => $reconciliation['opening_balance'], 'tone' => 'text-primary'],
                    ['label' => 'Member Account Credits', 'value' => $reconciliation['member_account_credit'], 'tone' => 'text-success'],
                    ['label' => 'Member Account Debits', 'value' => $reconciliation['member_account_debit'], 'tone' => 'text-danger'],
                    ['label' => 'Income', 'value' => $reconciliation['income'], 'tone' => 'text-success'],
                    ['label' => 'Expenses', 'value' => $reconciliation['expenses'], 'tone' => 'text-danger'],
                    ['label' => 'Loan Disbursements', 'value' => $reconciliation['loan_disbursements'], 'tone' => 'text-danger'],
                    ['label' => 'Principal Repayments', 'value' => $reconciliation['principal_repayments'], 'tone' => 'text-success'],
                    ['label' => 'Interest Repayments', 'value' => $reconciliation['interest_repayments'], 'tone' => 'text-success'],
                ] as $item)
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small text-uppercase font-weight-bold">{{ $item['label'] }}</div>
                            <div class="h6 mb-0 {{ $item['tone'] }}">&#8358;{{ number_format((float) $item['value'], 2) }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="border rounded p-3 bg-light">
                <div class="text-muted small text-uppercase font-weight-bold">Closing Society Balance</div>
                <div class="h4 mb-0 {{ (float) $reconciliation['closing_balance'] < 0 ? 'text-danger' : 'text-success' }}">
                    &#8358;{{ number_format((float) $reconciliation['closing_balance'], 2) }}
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Society Balance Summary</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-bordered table-hover mb-0">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Details</th>
                    <th class="text-right">Debit</th>
                    <th class="text-right">Credit</th>
                    <th class="text-right">Balance</th>
                </tr>
                </thead>
                <tbody>
                <tr class="font-weight-bold">
                    <td></td>
                    <td>Brought Forward</td>
                    <td></td>
                    <td></td>
                    <td class="text-right">&#8358;{{ number_format((float) $summary['brought_forward'], 2) }}</td>
                </tr>
                @forelse ($rows as $row)
                    <tr>
                        <td></td>
                        <td>{{ $row['name'] }}</td>
                        <td class="text-right text-danger">&#8358;{{ number_format((float) $row['total_debit'], 2) }}</td>
                        <td class="text-right text-success">&#8358;{{ number_format((float) $row['total_credit'], 2) }}</td>
                        <td class="text-right font-weight-bold">&#8358;{{ number_format((float) $row['balance'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">No member account movement found for this period.</td>
                    </tr>
                @endforelse
                <tr class="font-weight-bold bg-light">
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="text-right">Sub Total</td>
                    <td class="text-right">&#8358;{{ number_format((float) $summary['product_subtotal'], 2) }}</td>
                </tr>
                <tr class="font-weight-bold">
                    <td></td>
                    <td>Brought Forward + Sub Total</td>
                    <td class="text-right">&#8358;{{ number_format((float) $summary['product_subtotal'], 2) }} + &#8358;{{ number_format((float) $summary['brought_forward'], 2) }}</td>
                    <td class="text-center">=</td>
                    <td class="text-right">&#8358;{{ number_format((float) $summary['current_total'], 2) }}</td>
                </tr>
                <tr class="font-weight-bold">
                    <td></td>
                    <td>Expenses / Income</td>
                    <td class="text-right text-danger">&#8358;{{ number_format((float) $expenses['total_debit'], 2) }}</td>
                    <td class="text-right text-success">&#8358;{{ number_format((float) $expenses['total_credit'], 2) }}</td>
                    <td></td>
                </tr>
                <tr class="font-weight-bold">
                    <td></td>
                    <td>Loans</td>
                    <td class="text-right text-danger">&#8358;{{ number_format((float) $loans['total_debit'], 2) }}</td>
                    <td class="text-right text-success">&#8358;{{ number_format((float) $loans['total_credit'], 2) }}</td>
                    <td></td>
                </tr>
                <tr class="font-weight-bold bg-light">
                    <td></td>
                    <td>Sum (Loan + Expenses)</td>
                    <td class="text-right">&#8358;{{ number_format((float) $summary['loan_expense_debit'], 2) }}</td>
                    <td class="text-right">&#8358;{{ number_format((float) $summary['loan_expense_credit'], 2) }}</td>
                    <td></td>
                </tr>
                <tr class="font-weight-bold">
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="text-right">Final Total</td>
                    <td class="text-right text-success">&#8358;{{ number_format((float) $summary['final_total'], 2) }}</td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection
