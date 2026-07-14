@extends('layouts.customer')

@section('title', 'My Accounts')
@section('page_title', 'My Accounts')
@section('page_subtitle', 'Your cooperative account numbers, product types, status, and balances.')

@section('content')
    <div class="card customer-card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>Account Number</th>
                    <th>Product Type</th>
                    <th>Status</th>
                    <th class="text-right">Current Balance</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($accounts as $account)
                    <tr>
                        <td class="font-weight-bold">{{ $account->account_number }}</td>
                        <td>{{ $account->product?->type ?: 'N/A' }}</td>
                        <td>
                            <span class="badge badge-{{ (int) $account->status === 1 ? 'success' : 'secondary' }}">
                                {{ (int) $account->status === 1 ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="text-right money-value">&#8358;{{ number_format((float) $account->balance, 2) }}</td>
                        <td>
                            <a href="{{ route('customer.statement', ['account_id' => $account->id]) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-file-invoice mr-1"></i> Statement
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">No account records found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
