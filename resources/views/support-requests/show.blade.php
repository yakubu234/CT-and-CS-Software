@extends('layouts.admin')

@section('title', 'Support Request')
@section('page_title', 'Support Request')

@section('content')
    <div class="row">
        <div class="col-lg-5 mb-3">
            <div class="card card-outline card-primary h-100">
                <div class="card-header">
                    <h3 class="card-title">Request Details</h3>
                    <div class="card-tools">
                        <x-browser-back-button :fallback="route('support-requests.index')" />
                    </div>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Member</dt>
                        <dd class="col-sm-8">
                            <div class="font-weight-bold">{{ $supportRequest->user?->name ?: 'N/A' }}</div>
                            <div class="text-muted small">{{ $supportRequest->user?->display_member_no ?: 'N/A' }}</div>
                            @if ($supportRequest->user)
                                <a href="{{ route('members.show', $supportRequest->user) }}" class="btn btn-sm btn-outline-primary mt-2">
                                    View Member
                                </a>
                            @endif
                        </dd>

                        <dt class="col-sm-4">Branch</dt>
                        <dd class="col-sm-8">{{ $supportRequest->branch?->name ?: 'N/A' }}</dd>

                        <dt class="col-sm-4">Category</dt>
                        <dd class="col-sm-8">{{ ucfirst($supportRequest->category) }}</dd>

                        <dt class="col-sm-4">Submitted</dt>
                        <dd class="col-sm-8">{{ optional($supportRequest->created_at)->format('d M Y h:i A') }}</dd>

                        <dt class="col-sm-4">Status</dt>
                        <dd class="col-sm-8">
                            <span class="badge badge-{{
                                $supportRequest->status === 'resolved' || $supportRequest->status === 'closed'
                                    ? 'success'
                                    : ($supportRequest->status === 'in_progress' ? 'info' : 'warning')
                            }}">
                                {{ str_replace('_', ' ', ucfirst($supportRequest->status)) }}
                            </span>
                        </dd>

                        @if ($supportRequest->resolved_at)
                            <dt class="col-sm-4">Resolved</dt>
                            <dd class="col-sm-8">{{ $supportRequest->resolved_at->format('d M Y h:i A') }}</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-7 mb-3">
            <div class="card card-outline card-primary h-100">
                <div class="card-header">
                    <h3 class="card-title">{{ $supportRequest->subject }}</h3>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <div class="text-muted small mb-1">Member Message</div>
                        <div class="border rounded p-3 bg-light">{{ $supportRequest->message }}</div>
                    </div>

                    <form method="POST" action="{{ route('support-requests.update', $supportRequest) }}">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label for="status">Status</label>
                            <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
                                @foreach ($statusOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $supportRequest->status) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="admin_response">Response To Member</label>
                            <textarea
                                name="admin_response"
                                id="admin_response"
                                rows="6"
                                class="form-control @error('admin_response') is-invalid @enderror"
                                placeholder="Write the response the member will see in their portal."
                            >{{ old('admin_response', $supportRequest->admin_response) }}</textarea>
                            @error('admin_response')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary">
                            Save Response
                        </button>
                        <a href="{{ route('support-requests.index') }}" class="btn btn-outline-secondary">Back to Requests</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
