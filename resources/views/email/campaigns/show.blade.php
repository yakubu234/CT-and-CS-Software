@extends('layouts.admin')

@section('title', 'Email Campaign Details')
@section('page_title', 'Email Campaign Details')

@section('content')
    <div class="card card-outline card-primary mb-4">
        <div class="card-header">
            <h3 class="card-title">{{ $campaign->name }}</h3>
            <div class="card-tools">
                <a href="{{ route('email.campaigns.index') }}" class="btn btn-sm btn-outline-secondary">Back to Campaigns</a>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <div class="small text-muted">Status</div>
                    <span class="badge badge-{{ $campaign->status === 'sent' ? 'success' : ($campaign->status === 'failed' ? 'danger' : 'secondary') }}">{{ ucfirst($campaign->status) }}</span>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="small text-muted">Branch</div>
                    <div class="font-weight-bold">{{ $campaign->branch?->name ?: 'All accessible branches' }}</div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="small text-muted">Scheduled</div>
                    <div class="font-weight-bold">{{ optional($campaign->scheduled_at)->format('d M Y h:i A') ?: 'Immediate' }}</div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="small text-muted">Sent</div>
                    <div class="font-weight-bold">{{ optional($campaign->sent_at)->format('d M Y h:i A') ?: 'Pending' }}</div>
                </div>
                <div class="col-12">
                    <div class="small text-muted">Subject</div>
                    <div class="font-weight-bold">{{ $campaign->subject }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Recipients</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                    <tr>
                        <th>Recipient</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Sent</th>
                        <th>Error</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($campaign->messages as $message)
                        <tr>
                            <td>{{ $message->recipient_name ?: $message->user?->name ?: 'N/A' }}</td>
                            <td>{{ $message->email ?: 'N/A' }}</td>
                            <td><span class="badge badge-{{ $message->status === 'sent' ? 'success' : ($message->status === 'failed' ? 'danger' : 'secondary') }}">{{ ucfirst($message->status) }}</span></td>
                            <td>{{ optional($message->sent_at)->format('d M Y h:i A') ?: 'Pending' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($message->error_message, 80) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No recipient emails were queued.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
