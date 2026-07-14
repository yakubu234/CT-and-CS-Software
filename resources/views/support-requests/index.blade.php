@extends('layouts.admin')

@section('title', 'Support Requests')
@section('page_title', 'Support Requests')

@section('content')
    <div class="card card-outline card-primary mb-3">
        <div class="card-header">
            <h3 class="card-title">Filters</h3>
        </div>
        <form method="GET" action="{{ route('support-requests.index') }}">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select name="status" id="status" class="form-control">
                                <option value="">All statuses</option>
                                @foreach ($statusOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="category">Category</label>
                            <select name="category" id="category" class="form-control">
                                <option value="">All categories</option>
                                @foreach ($categoryOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(($filters['category'] ?? '') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="search">Search</label>
                            <input type="search" name="search" id="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Member, subject, message, or member number">
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-right">
                <a href="{{ route('support-requests.index') }}" class="btn btn-outline-secondary">Reset</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter mr-1"></i> Apply Filters
                </button>
            </div>
        </form>
    </div>

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Member Support Requests</h3>
            <div class="card-tools">
                <span class="text-muted small">Branch: {{ $branch->name }}</span>
            </div>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>Submitted</th>
                    <th>Member</th>
                    <th>Subject</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($requests as $supportRequest)
                    <tr>
                        <td>{{ optional($supportRequest->created_at)->format('d M Y h:i A') }}</td>
                        <td>
                            <div class="font-weight-bold">{{ $supportRequest->user?->name ?: 'N/A' }}</div>
                            <div class="text-muted small">{{ $supportRequest->user?->display_member_no ?: 'N/A' }}</div>
                        </td>
                        <td>
                            <div class="font-weight-bold">{{ $supportRequest->subject }}</div>
                            <div class="text-muted small">{{ \Illuminate\Support\Str::limit($supportRequest->message, 90) }}</div>
                        </td>
                        <td>{{ ucfirst($supportRequest->category) }}</td>
                        <td>
                            <span class="badge badge-{{
                                $supportRequest->status === 'resolved' || $supportRequest->status === 'closed'
                                    ? 'success'
                                    : ($supportRequest->status === 'in_progress' ? 'info' : 'warning')
                            }}">
                                {{ str_replace('_', ' ', ucfirst($supportRequest->status)) }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('support-requests.show', $supportRequest) }}" class="btn btn-sm btn-outline-primary">
                                View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No support requests match the selected filters.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $requests->links() }}
        </div>
    </div>
@endsection
