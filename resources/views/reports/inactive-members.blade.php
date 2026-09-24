@extends('layouts.admin')

@section('title', 'Inactive Members Financial Report')
@section('page_title', 'Inactive Members Financial Report')

@section('content')
<div class="card card-outline card-primary">
    <div class="card-header"><h3 class="card-title">{{ $branch->name }}</h3>
        <div class="card-tools"><a class="btn btn-sm btn-success" href="{{ route('reports.inactive-members.export', request()->query()) }}"><i class="fas fa-file-excel mr-1"></i> Export Excel</a></div>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('reports.inactive-members') }}" class="form-row align-items-end">
            <div class="form-group col-md-4"><label for="member_id">Member</label><select id="member_id" name="member_id" class="form-control"><option value="">All inactive members</option>@foreach ($options as $option)<option value="{{ $option['id'] }}" @selected((string) request('member_id') === (string) $option['id'])>{{ $option['name'] }} ({{ $option['member_no'] ?: $option['id'] }})</option>@endforeach</select></div>
            <div class="form-group col-md-4"><label for="search">Search name or member number</label><input id="search" name="search" class="form-control" value="{{ request('search') }}"></div>
            <div class="form-group col-md-4"><button class="btn btn-primary" type="submit">Filter</button> <a class="btn btn-outline-secondary" href="{{ route('reports.inactive-members') }}">Reset</a></div>
        </form>
        <p class="text-muted mb-0">Balances include archived accounts and historical transactions. Building Fund appears here when recorded against a member account; society level Building Fund receipts remain in the Society Report.</p>
    </div>
</div>

<div class="row">
    <div class="col-md-4"><div class="small-box bg-info"><div class="inner"><h3>&#8358;{{ number_format($totals['account_total'], 2) }}</h3><p>Member Account Balances</p></div></div></div>
    <div class="col-md-4"><div class="small-box bg-warning"><div class="inner"><h3>&#8358;{{ number_format($totals['outstanding_loan'], 2) }}</h3><p>Outstanding Loans</p></div></div></div>
    <div class="col-md-4"><div class="small-box bg-success"><div class="inner"><h3>&#8358;{{ number_format($totals['net_position'], 2) }}</h3><p>Net Member Position</p></div></div></div>
</div>

<div class="card"><div class="card-body table-responsive p-0"><table class="table table-bordered table-striped table-sm mb-0">
    <thead><tr><th>Member</th><th class="text-right">Savings</th><th class="text-right">Shares</th><th class="text-right">Building Fund</th><th class="text-right">Authentication</th><th class="text-right">Deposits</th><th>Other Accounts</th><th class="text-right">Account Total</th><th class="text-right">Loan Amount</th><th class="text-right">Repaid</th><th class="text-right">Outstanding</th><th class="text-right">Net Position</th></tr></thead>
    <tbody>@forelse ($rows as $row)<tr>
        <td><a href="{{ route('reports.soc-ledger-report', ['member_id' => $row['id']]) }}"><strong>{{ $row['name'] }}</strong></a><br><small>{{ $row['member_no'] ?: 'ID ' . $row['id'] }}</small></td>
        @foreach (['savings', 'shares', 'building_fund', 'authentication', 'deposits'] as $field)<td class="text-right">{{ number_format($row[$field], 2) }}</td>@endforeach
        <td>@forelse ($row['other'] as $name => $amount)<div>{{ $name }}: {{ number_format($amount, 2) }}</div>@empty — @endforelse</td>
        @foreach (['account_total', 'loan_amount', 'loan_repayment', 'outstanding_loan', 'net_position'] as $field)<td class="text-right">{{ number_format($row[$field], 2) }}</td>@endforeach
    </tr>@empty<tr><td colspan="12" class="text-center text-muted py-4">No inactive or archived members match this filter.</td></tr>@endforelse</tbody>
</table></div></div>
@endsection
