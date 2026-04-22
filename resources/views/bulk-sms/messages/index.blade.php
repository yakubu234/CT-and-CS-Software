@extends('layouts.admin')

@section('title', 'SMS Logs')
@section('page_title', 'SMS Logs')

@section('content')
    <div class="card card-outline card-primary">
        @include('layouts.partials.table-toolbar', [
            'title' => 'SMS Logs',
            'subtitle' => 'Delivery history for manual campaigns and automatic event-driven messages.',
            'action' => route('bulk-sms.logs.index'),
            'placeholder' => 'Search SMS logs',
        ])

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                    <tr>
                        <th>Recipient</th>
                        <th>Phone</th>
                        <th>Source</th>
                        <th>Status</th>
                        <th>Provider</th>
                        <th>Sent</th>
                        <th>Message</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($messages as $message)
                        <tr>
                            <td>
                                <div class="font-weight-semibold">{{ $message->recipient_name ?: $message->user?->name ?: 'N/A' }}</div>
                                <small class="text-muted">{{ $message->user?->detail?->member_no ?: $message->user?->member_no ?: 'N/A' }}</small>
                            </td>
                            <td>{{ $message->phone ?: 'N/A' }}</td>
                            <td>{{ $message->campaign?->name ?: $message->automationRule?->name ?: 'System' }}</td>
                            <td><span class="badge badge-{{ $message->status === 'sent' ? 'success' : ($message->status === 'failed' ? 'danger' : 'secondary') }}">{{ ucfirst($message->status) }}</span></td>
                            <td>{{ $message->provider ?: 'N/A' }}</td>
                            <td>{{ optional($message->sent_at)->format('d M Y h:i A') ?: 'Pending' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($message->message, 100) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No SMS logs found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $messages->links() }}
            </div>
        </div>
    </div>
@endsection
