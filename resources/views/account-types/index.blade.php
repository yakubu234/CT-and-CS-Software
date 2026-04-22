@extends('layouts.admin')

@section('title', 'Account Types')
@section('page_title', 'Account Types')

@push('styles')
    <style>
        .account-type-name {
            font-weight: 700;
            color: #1e293b;
        }

        .account-type-meta {
            font-size: 0.8rem;
            color: #64748b;
        }
    </style>
@endpush

@section('content')
    <div class="card card-outline card-primary">
        @include('layouts.partials.table-toolbar', [
            'title' => 'Account Types',
            'subtitle' => 'Manage account configuration and interest settings',
            'action' => route('account-types.index'),
            'placeholder' => 'Search by name, type, or prefix',
        ])

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="thead-light">
                    <tr>
                        <th>Name</th>
                        <th>Prefix</th>
                        <th>Next Account Number</th>
                        <th>Interest Rate</th>
                        <th>Interest Method</th>
                        <th>Interest Period</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td>
                                <div class="account-type-name">{{ $product->name }}</div>
                                <div class="account-type-meta">{{ $product->currency_name }}</div>
                            </td>
                            <td>{{ $product->account_number_prefix ?: 'N/A' }}</td>
                            <td>{{ $product->next_account_number }}</td>
                            <td>{{ number_format((float) $product->interest_rate, 2) }}%</td>
                            <td>{{ str($product->interest_method)->replace('_', ' ')->title() ?: 'N/A' }}</td>
                            <td>{{ $product->interest_period ? 'Every ' . $product->interest_period . ' month' . ($product->interest_period > 1 ? 's' : '') : 'N/A' }}</td>
                            <td>
                                <span class="badge {{ (int) $product->status === 1 ? 'badge-success' : 'badge-secondary' }}">
                                    {{ (int) $product->status === 1 ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{ route('account-types.show', $product) }}" class="btn btn-sm btn-outline-primary">View</a>
                                    <a href="{{ route('account-types.edit', $product) }}" class="btn btn-sm btn-primary">Edit</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No account types found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $products->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection
