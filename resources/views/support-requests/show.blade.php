@extends('layouts.admin')

@section('title', 'Complaint Conversation')
@section('page_title', 'Complaint Conversation')

@section('content')
    @php
        $isStaffConversation = $supportRequest->conversation_type === 'admin_admin';
        $statusClass = in_array($supportRequest->status, ['resolved', 'closed'], true) ? 'success' : ($supportRequest->status === 'in_progress' ? 'info' : 'warning');
    @endphp
    <div class="row">
        <div class="col-lg-4 mb-3">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Complaint Details</h3>
                    <div class="card-tools"><x-browser-back-button :fallback="route('support-requests.index')" /></div>
                </div>
                <div class="card-body">
                    <h5>{{ $supportRequest->subject }}</h5>
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Type</dt>
                        <dd class="col-sm-7">{{ $isStaffConversation ? 'Admin to Admin' : 'Customer to Admin' }}</dd>
                        <dt class="col-sm-5">From</dt>
                        <dd class="col-sm-7">{{ $supportRequest->creator?->name ?: $supportRequest->user?->name ?: 'N/A' }}</dd>
                        <dt class="col-sm-5">To</dt>
                        <dd class="col-sm-7">{{ $isStaffConversation ? ($supportRequest->recipient?->name ?: 'N/A') : 'Branch Administrators' }}</dd>
                        <dt class="col-sm-5">Branch</dt>
                        <dd class="col-sm-7">{{ $supportRequest->branch?->name ?: 'N/A' }}</dd>
                        <dt class="col-sm-5">Category</dt>
                        <dd class="col-sm-7">{{ ucfirst($supportRequest->category) }}</dd>
                        <dt class="col-sm-5">Priority</dt>
                        <dd class="col-sm-7"><span class="badge badge-{{ $supportRequest->priority === 'urgent' ? 'danger' : ($supportRequest->priority === 'high' ? 'warning' : 'secondary') }}">{{ ucfirst($supportRequest->priority) }}</span></dd>
                        <dt class="col-sm-5">Status</dt>
                        <dd class="col-sm-7"><span class="badge badge-{{ $statusClass }}">{{ str_replace('_', ' ', ucfirst($supportRequest->status)) }}</span></dd>
                    </dl>
                </div>
                <div class="card-footer">
                    <form method="POST" action="{{ route('support-requests.update', $supportRequest) }}">
                        @csrf
                        @method('PUT')
                        <div class="form-group">
                            <label for="status">Update Status</label>
                            <select name="status" id="status" class="form-control" required>
                                @foreach ($statusOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $supportRequest->status) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="btn btn-primary btn-block" type="submit">Save Status</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8 mb-3">
            <div class="card card-outline card-primary">
                <div class="card-header"><h3 class="card-title">Conversation</h3></div>
                <div class="card-body" style="max-height: 560px; overflow-y: auto;">
                    @forelse ($supportRequest->messages as $message)
                        @php($mine = (int) $message->sender_id === (int) auth()->id())
                        <div class="d-flex mb-3 {{ $mine ? 'justify-content-end' : 'justify-content-start' }}">
                            <div class="rounded p-3 {{ $mine ? 'bg-primary text-white' : 'bg-light border' }}" style="max-width: 82%;">
                                <div class="small font-weight-bold mb-1">{{ $message->sender?->name ?: 'Cooperative Support' }}</div>
                                <div style="white-space: pre-wrap;">{{ $message->message }}</div>
                                <div class="small mt-2 {{ $mine ? 'text-white-50' : 'text-muted' }}">{{ optional($message->created_at)->format('d M Y, h:i A') }}</div>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted text-center">No messages in this conversation.</p>
                    @endforelse
                </div>
                <div class="card-footer">
                    <form method="POST" action="{{ route('support-requests.reply', $supportRequest) }}">
                        @csrf
                        <div class="form-group">
                            <label for="message">Reply</label>
                            <textarea name="message" id="message" rows="4" class="form-control @error('message') is-invalid @enderror" required>{{ old('message') }}</textarea>
                            @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane mr-1"></i> Send Reply</button>
                        <a href="{{ route('support-requests.index') }}" class="btn btn-outline-secondary">Back</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
