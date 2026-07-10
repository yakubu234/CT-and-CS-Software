@extends('layouts.admin')

@section('title', 'Assets')
@section('page_title', 'Assets')

@section('content')
    <div class="row">
        <div class="col-md-3 mb-3">
            <div class="card card-outline card-primary h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase font-weight-bold">Total Assets</div>
                    <div class="h4 mb-0">{{ number_format((int) $summary['total_assets']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card card-outline card-success h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase font-weight-bold">Total Cost</div>
                    <div class="h4 mb-0">&#8358;{{ number_format((float) $summary['total_cost'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card card-outline card-info h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase font-weight-bold">Active</div>
                    <div class="h4 mb-0">{{ number_format((int) $summary['active_assets']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card card-outline card-secondary h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase font-weight-bold">Disposed</div>
                    <div class="h4 mb-0">{{ number_format((int) $summary['disposed_assets']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-outline card-primary">
        @include('layouts.partials.table-toolbar', [
            'title' => 'Fixed Assets',
            'subtitle' => 'Capital assets owned by the cooperative, separate from day-to-day operating expenses.',
            'action' => route('assets.index'),
            'placeholder' => 'Search by asset name, category, supplier, or status',
        ])

        <div class="card-body">
            <form method="GET" action="{{ route('assets.index') }}" class="mb-3">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <select name="category" class="form-control select2">
                            <option value="">All categories</option>
                            @foreach ($categoryOptions as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['category'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <select name="status" class="form-control select2">
                            <option value="">All statuses</option>
                            @foreach ($statusOptions as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <select name="branch_id" class="form-control select2">
                            <option value="">All branches</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((string) ($filters['branch_id'] ?? '') === (string) $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <button type="submit" class="btn btn-primary mr-2">Apply</button>
                        <a href="{{ route('assets.index') }}" class="btn btn-outline-secondary">Clear</a>
                    </div>
                </div>
            </form>

            <div class="mb-3">
                <a href="{{ route('assets.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus mr-1"></i>
                    Record Asset
                </a>
                <a href="{{ route('asset-categories.index') }}" class="btn btn-outline-secondary ml-2">
                    <i class="fas fa-tags mr-1"></i>
                    Manage Categories
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                    <tr>
                        <th>Asset</th>
                        <th>Category</th>
                        <th>Branch</th>
                        <th>Purchase</th>
                        <th>Supplier</th>
                        <th>Status</th>
                        <th>Depreciation</th>
                        <th style="width: 130px;">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($assets as $asset)
                        <tr>
                            <td>
                                <div class="font-weight-bold">{{ $asset->name }}</div>
                                <small class="text-muted">{{ optional($asset->purchase_date)->format('d M Y') ?: 'No purchase date' }}</small>
                            </td>
                            <td>{{ $categoryOptions[$asset->category] ?? ucfirst(str_replace('_', ' ', $asset->category)) }}</td>
                            <td>{{ $asset->branch?->name ?: 'Society-wide' }}</td>
                            <td>&#8358;{{ number_format((float) $asset->purchase_cost, 2) }}</td>
                            <td>{{ $asset->supplier ?: 'N/A' }}</td>
                            <td>
                                <span class="badge badge-{{ $asset->status === 'active' ? 'success' : ($asset->status === 'under_repair' ? 'warning' : 'secondary') }}">
                                    {{ $statusOptions[$asset->status] ?? ucfirst($asset->status) }}
                                </span>
                            </td>
                            <td>{{ $asset->depreciation_rate !== null ? number_format((float) $asset->depreciation_rate, 2) . '% yearly' : 'N/A' }}</td>
                            <td>
                                <a href="{{ route('assets.show', $asset) }}" class="btn btn-sm btn-outline-primary mb-1">View</a>
                                <a href="{{ route('assets.edit', $asset) }}" class="btn btn-sm btn-outline-info mb-1">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No fixed assets have been recorded.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $assets->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection
