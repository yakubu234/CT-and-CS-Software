@extends('layouts.admin')

@section('title', 'Email Campaigns')
@section('page_title', 'Email Campaigns')

@section('content')
    <div class="card card-outline card-primary">
        @include('layouts.partials.table-toolbar', [
            'title' => 'Email Campaigns',
            'subtitle' => 'Official email communication batches sent to members.',
            'action' => route('email.campaigns.index'),
            'placeholder' => 'Search email campaigns',
        ])

        <div class="card-body">
            <div class="mb-3">
                <a href="{{ route('email.campaigns.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus mr-1"></i>
                    New Email Campaign
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Branch</th>
                        <th>Audience</th>
                        <th>Status</th>
                        <th>Scheduled</th>
                        <th>Sent</th>
                        <th>Messages</th>
                        <th style="width: 90px;">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($campaigns as $campaign)
                        <tr>
                            <td>
                                <div class="font-weight-bold">{{ $campaign->name }}</div>
                                <small class="text-muted">{{ $campaign->subject }}</small>
                            </td>
                            <td>{{ $campaign->branch?->name ?: 'All accessible branches' }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $campaign->audience_type)) }}</td>
                            <td><span class="badge badge-{{ $campaign->status === 'sent' ? 'success' : ($campaign->status === 'failed' ? 'danger' : 'secondary') }}">{{ ucfirst($campaign->status) }}</span></td>
                            <td>{{ optional($campaign->scheduled_at)->format('d M Y h:i A') ?: 'Immediate' }}</td>
                            <td>{{ optional($campaign->sent_at)->format('d M Y h:i A') ?: 'Pending' }}</td>
                            <td>{{ $campaign->messages_count ?? $campaign->messages()->count() }}</td>
                            <td><a href="{{ route('email.campaigns.show', $campaign) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No email campaigns found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">{{ $campaigns->links() }}</div>
        </div>
    </div>
@endsection
