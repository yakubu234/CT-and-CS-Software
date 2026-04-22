@extends('layouts.admin')

@section('title', 'All Accounts')
@section('page_title', 'All Accounts')

@push('styles')
    <style>
        .account-summary-table th,
        .account-summary-table td {
            padding: 0.85rem 0.65rem;
        }

        .account-user-name {
            font-size: 1rem;
            line-height: 1.35;
        }

        .account-member-id {
            font-weight: 600;
            color: #334155;
        }

        .account-balance-card {
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
            min-height: 3.75rem;
        }

        .account-balance-card--empty {
            justify-content: center;
        }

        .account-number {
            font-size: 0.78rem;
            line-height: 1.2;
            color: #64748b;
            word-break: break-all;
        }

        .account-balance {
            font-size: 1.1rem;
            font-weight: 700;
            line-height: 1.2;
        }

        @media (max-width: 991.98px) {
            .account-user-name {
                font-size: 0.95rem;
            }

            .account-balance {
                font-size: 1rem;
            }
        }
    </style>
@endpush

@section('content')
    <div class="card card-outline card-primary">
        @include('layouts.partials.table-toolbar', [
            'title' => 'Branch Accounts',
            'subtitle' => 'All users and balances in ' . $branch->name,
            'action' => route('accounts.index'),
            'placeholder' => 'Search by member name, email, or member number',
        ])

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle account-summary-table">
                    <colgroup>
                        <col style="width: 27%;">
                        <col style="width: 13%;">
                        <col style="width: 15%;">
                        <col style="width: 15%;">
                        <col style="width: 15%;">
                        <col style="width: 15%;">
                    </colgroup>
                    <thead class="thead-light">
                    <tr>
                        <th>Name</th>
                        <th>Member ID</th>
                        <th>Savings</th>
                        <th>Shares</th>
                        <th>Auth</th>
                        <th>Deposit</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($users as $user)
                        @php
                            $accountsByType = $user->savingsAccounts
                                ->filter(fn ($account) => $account->product)
                                ->keyBy(fn ($account) => $account->product->type);
                        @endphp
                        <tr>
                            <td>
                                <div class="font-weight-bold text-uppercase account-user-name">{{ $user->name }}</div>
                                <div class="text-muted small">{{ $user->email }}</div>
                            </td>
                            <td>
                                <div class="account-member-id">{{ $user->detail?->member_no ?: $user->member_no ?: 'N/A' }}</div>
                            </td>

                            @foreach ($accountTypes as $accountType)
                                @php($account = $accountsByType->get($accountType))
                                <td>
                                    @if ($account)
                                        <div class="account-balance-card">
                                            <div class="account-number">{{ $account->account_number }}</div>
                                            <div class="account-balance {{ (float) $account->balance > 0 ? 'text-info' : 'text-danger' }}">
                                                &#8358;{{ number_format((float) $account->balance, 2) }}
                                            </div>
                                        </div>
                                    @else
                                        <div class="account-balance-card account-balance-card--empty">
                                            <span class="text-muted">N/A</span>
                                        </div>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                No accounts found for this branch.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $users->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection
