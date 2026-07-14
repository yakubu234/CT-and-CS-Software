@extends('layouts.customer')

@section('title', request()->routeIs('customer.transactions') ? 'Transactions' : 'Account Statement')
@section('page_title', request()->routeIs('customer.transactions') ? 'Transactions' : 'Account Statement')
@section('page_subtitle', 'Filter, print, and review your account activity.')

@section('page_actions')
    <a href="{{ route('customer.transactions.export', request()->query()) }}" class="btn btn-outline-success">
        <i class="fas fa-file-csv mr-1"></i> Export
    </a>
    <button type="button" class="btn btn-outline-primary" onclick="window.print()">
        <i class="fas fa-print mr-1"></i> Print
    </button>
@endsection

@section('content')
    <div class="card customer-card mb-3">
        <form method="GET" action="{{ request()->routeIs('customer.transactions') ? route('customer.transactions') : route('customer.statement') }}">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="account_id">Account</label>
                            <select name="account_id" id="account_id" class="form-control">
                                <option value="">All accounts</option>
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->id }}" @selected((string) ($filters['account_id'] ?? '') === (string) $account->id)>
                                        {{ $account->account_number }} ({{ $account->product?->type ?: 'N/A' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="type">Type</label>
                            <select name="type" id="type" class="form-control">
                                <option value="">All</option>
                                <option value="cr" @selected(($filters['type'] ?? '') === 'cr')>Credit</option>
                                <option value="dr" @selected(($filters['type'] ?? '') === 'dr')>Debit</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="start_date">Start Date</label>
                            <input type="date" name="start_date" id="start_date" class="form-control" value="{{ $filters['start_date'] ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="end_date">End Date</label>
                            <input type="date" name="end_date" id="end_date" class="form-control" value="{{ $filters['end_date'] ?? '' }}">
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-right">
                <a href="{{ request()->routeIs('customer.transactions') ? route('customer.transactions') : route('customer.statement') }}" class="btn btn-outline-secondary">Reset</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter mr-1"></i> Apply Filters
                </button>
            </div>
        </form>
    </div>

    <div class="card customer-card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Account</th>
                    <th>Description</th>
                    <th>Method</th>
                    <th>Type</th>
                    <th class="text-right">Amount</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($transactions as $transaction)
                    <tr>
                        <td>{{ optional($transaction->trans_date)->format('d M Y') ?: 'N/A' }}</td>
                        <td>{{ $transaction->account?->account_number ?: 'N/A' }}<br><small class="text-muted">{{ $transaction->account?->product?->type ?: 'N/A' }}</small></td>
                        <td>{{ $transaction->description ?: $transaction->note ?: 'Transaction' }}</td>
                        <td>{{ $transaction->method ?: 'N/A' }}</td>
                        <td><span class="badge badge-{{ strtolower($transaction->dr_cr) === 'cr' ? 'success' : 'danger' }}">{{ strtoupper($transaction->dr_cr) }}</span></td>
                        <td class="text-right money-value">&#8358;{{ number_format((float) $transaction->amount, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No transactions match the selected filters.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $transactions->links() }}
        </div>
    </div>
@endsection
