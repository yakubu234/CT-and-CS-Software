@extends('layouts.admin')

@section('title', 'Campaign Details')
@section('page_title', 'Campaign Details')

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-body">
            <div class="d-flex justify-content-between flex-wrap">
                <div class="mb-3">
                    <h4 class="mb-1">{{ $campaign->name }}</h4>
                    <div class="text-muted">{{ $campaign->branch?->name ?: 'All accessible branches' }} | {{ ucfirst($campaign->status) }}</div>
                </div>
                <div class="mb-3 text-md-right">
                    <div><strong>Audience:</strong> {{ $campaign->audience_type === 'selected_members' ? 'Selected members' : 'Branch members' }}</div>
                    <div><strong>Scheduled:</strong> {{ optional($campaign->scheduled_at)->format('d M Y h:i A') ?: 'Immediate' }}</div>
                </div>
            </div>

            <div class="border rounded p-3 bg-light mb-4">
                <strong>Message Preview</strong>
                <div class="mt-2">{{ $campaign->message }}</div>
            </div>

            <h5 class="mb-3">Recipient Logs</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                    <tr>
                        <th>Recipient</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Processed</th>
                        <th>Error</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($campaign->messages as $message)
                        <tr>
                            <td>{{ $message->recipient_name ?: $message->user?->name ?: 'N/A' }}</td>
                            <td>{{ $message->phone ?: 'N/A' }}</td>
                            <td><span class="badge badge-{{ $message->status === 'sent' ? 'success' : ($message->status === 'failed' ? 'danger' : 'secondary') }}">{{ ucfirst($message->status) }}</span></td>
                            <td>{{ optional($message->processed_at)->format('d M Y h:i A') ?: 'Pending' }}</td>
                            <td>{{ $message->error_message ?: 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No recipient log entries yet.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
