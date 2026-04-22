@extends('layouts.admin')

@section('title', 'SMS Campaigns')
@section('page_title', 'SMS Campaigns')

@section('content')
    <div class="card card-outline card-primary">
        @include('layouts.partials.table-toolbar', [
            'title' => 'SMS Campaigns',
            'subtitle' => 'Send now or schedule holiday, celebration, branch-wide, or selected-member messages.',
            'action' => route('bulk-sms.campaigns.index'),
            'placeholder' => 'Search campaigns',
        ])

        <div class="card-body">
            <div class="mb-3">
                <a href="{{ route('bulk-sms.campaigns.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus mr-1"></i>
                    Create Campaign
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
                        <th style="width: 70px;">View</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($campaigns as $campaign)
                        <tr>
                            <td>
                                <div class="font-weight-semibold">{{ $campaign->name }}</div>
                                <small class="text-muted">{{ \Illuminate\Support\Str::limit($campaign->message, 80) }}</small>
                            </td>
                            <td>{{ $campaign->branch?->name ?: 'All accessible branches' }}</td>
                            <td>{{ $campaign->audience_type === 'selected_members' ? 'Selected members' : 'Branch members' }}</td>
                            <td><span class="badge badge-info">{{ ucfirst($campaign->status) }}</span></td>
                            <td>{{ optional($campaign->scheduled_at)->format('d M Y h:i A') ?: 'Immediate' }}</td>
                            <td class="text-center">
                                <a href="{{ route('bulk-sms.campaigns.show', $campaign) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No SMS campaigns found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $campaigns->links() }}
            </div>
        </div>
    </div>
@endsection
