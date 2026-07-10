@extends('layouts.admin')

@section('title', 'Email Logs')
@section('page_title', 'Email Logs')

@section('content')
    <div class="card card-outline card-primary">
        @include('layouts.partials.table-toolbar', [
            'title' => 'Email Logs',
            'subtitle' => 'Delivery history for email campaigns, reminders, and official communications.',
            'action' => route('email.logs.index'),
            'placeholder' => 'Search email logs',
        ])

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                    <tr>
                        <th>Recipient</th>
                        <th>Email</th>
                        <th>Source</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>SMTP Account</th>
                        <th>Sent</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($messages as $message)
                        <tr>
                            <td>
                                <div class="font-weight-semibold">{{ $message->recipient_name ?: $message->user?->name ?: 'N/A' }}</div>
                                <small class="text-muted">{{ $message->user?->display_member_no ?: 'N/A' }}</small>
                            </td>
                            <td>{{ $message->email ?: 'N/A' }}</td>
                            <td>{{ $message->campaign?->name ?: 'System' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($message->subject, 80) }}</td>
                            <td><span class="badge badge-{{ $message->status === 'sent' ? 'success' : ($message->status === 'failed' ? 'danger' : 'secondary') }}">{{ ucfirst($message->status) }}</span></td>
                            <td>
                                <div>{{ $message->smtpAccount?->name ?: 'N/A' }}</div>
                                <small class="text-muted">{{ $message->mailer ?: 'N/A' }}</small>
                            </td>
                            <td>{{ optional($message->sent_at)->format('d M Y h:i A') ?: 'Pending' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No email logs found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">{{ $messages->links() }}</div>
        </div>
    </div>
@endsection
