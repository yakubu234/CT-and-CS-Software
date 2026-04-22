@extends('layouts.admin')

@section('title', 'Inactive Accounts')
@section('page_title', 'Inactive Accounts')

@push('styles')
    <style>
        .inactive-accounts-table th,
        .inactive-accounts-table td {
            padding: 0.8rem 0.6rem;
        }

        .inactive-accounts-table .badge {
            font-size: 0.75rem;
            letter-spacing: 0.02em;
        }

        .inactive-account-holder {
            font-weight: 600;
            color: #1e293b;
        }

        .inactive-account-meta {
            font-size: 0.78rem;
            color: #64748b;
        }
    </style>
@endpush

@section('content')
    <div class="card card-outline card-primary">
        @include('layouts.partials.table-toolbar', [
            'title' => 'Inactive Accounts',
            'subtitle' => 'Recover disabled accounts in ' . $branch->name,
            'action' => route('accounts.inactive'),
            'placeholder' => 'Search by account number or account holder',
        ])

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle inactive-accounts-table">
                    <colgroup>
                        <col style="width: 15%;">
                        <col style="width: 13%;">
                        <col style="width: 15%;">
                        <col style="width: 13%;">
                        <col style="width: 19%;">
                        <col style="width: 13%;">
                        <col style="width: 12%;">
                    </colgroup>
                    <thead class="thead-light">
                    <tr>
                        <th>Disabled At</th>
                        <th>Account Type</th>
                        <th>Account Number</th>
                        <th>Current Balance</th>
                        <th>Account Holder</th>
                        <th>Member ID</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($accounts as $account)
                        <tr>
                            <td>{{ optional($account->disabled_at ?? $account->updated_at)->format('d M Y, h:i A') ?: 'N/A' }}</td>
                            <td>
                                <span class="badge badge-light border">{{ $account->product?->type ?: 'N/A' }}</span>
                            </td>
                            <td>{{ $account->account_number }}</td>
                            <td class="font-weight-bold {{ (float) $account->balance > 0 ? 'text-info' : 'text-danger' }}">
                                &#8358;{{ number_format((float) $account->balance, 2) }}
                            </td>
                            <td>
                                <div class="inactive-account-holder">{{ $account->user?->name ?: 'N/A' }}</div>
                                <div class="inactive-account-meta">{{ $account->user?->email ?: 'No email' }}</div>
                            </td>
                            <td>{{ $account->user?->detail?->member_no ?: $account->user?->member_no ?: 'N/A' }}</td>
                            <td>
                                <form action="{{ route('accounts.reactivate', $account) }}" method="POST" class="mb-0">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-success btn-block">
                                        Re-enable
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                No inactive accounts found for this branch.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $accounts->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection
