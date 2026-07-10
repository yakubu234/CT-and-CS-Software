@extends('layouts.admin')

@section('title', 'Asset Details')
@section('page_title', 'Asset Details')

@section('content')
    <div class="card card-outline card-primary mb-4">
        <div class="card-header">
            <h3 class="card-title">{{ $asset->name }}</h3>
            <div class="card-tools">
                <a href="{{ route('assets.edit', $asset) }}" class="btn btn-sm btn-primary mr-2">Edit</a>
                <a href="{{ route('assets.index') }}" class="btn btn-sm btn-outline-secondary">Back to Assets</a>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="small text-muted">Category</div>
                    <div class="font-weight-bold">{{ $categoryOptions[$asset->category] ?? ucfirst(str_replace('_', ' ', $asset->category)) }}</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="small text-muted">Branch</div>
                    <div class="font-weight-bold">{{ $asset->branch?->name ?: 'Society-wide' }}</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="small text-muted">Status</div>
                    <div class="font-weight-bold">{{ $statusOptions[$asset->status] ?? ucfirst($asset->status) }}</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="small text-muted">Purchase Date</div>
                    <div class="font-weight-bold">{{ optional($asset->purchase_date)->format('d M Y') ?: 'N/A' }}</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="small text-muted">Purchase Cost</div>
                    <div class="font-weight-bold">&#8358;{{ number_format((float) $asset->purchase_cost, 2) }}</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="small text-muted">Supplier</div>
                    <div class="font-weight-bold">{{ $asset->supplier ?: 'N/A' }}</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="small text-muted">Depreciation Rate</div>
                    <div class="font-weight-bold">{{ $asset->depreciation_rate !== null ? number_format((float) $asset->depreciation_rate, 2) . '% yearly' : 'N/A' }}</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="small text-muted">Disposed Date</div>
                    <div class="font-weight-bold">{{ optional($asset->disposed_at)->format('d M Y') ?: 'N/A' }}</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="small text-muted">Recorded By</div>
                    <div class="font-weight-bold">{{ $asset->creator?->name ?: 'N/A' }}</div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="small text-muted">Depreciation Note</div>
                    <div class="font-weight-bold">{{ $asset->depreciation_note ?: 'No depreciation note added.' }}</div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="small text-muted">Remarks</div>
                    <div class="font-weight-bold">{{ $asset->remarks ?: 'No remarks added.' }}</div>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('assets.destroy', $asset) }}" onsubmit="return confirm('Delete this asset record?')">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-outline-danger">
            <i class="fas fa-trash-alt mr-1"></i>
            Delete Asset
        </button>
    </form>
@endsection
