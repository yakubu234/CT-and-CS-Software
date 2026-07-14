@extends('layouts.customer')

@section('title', 'Notifications')
@section('page_title', 'Notifications')
@section('page_subtitle', 'Email and SMS updates sent to your account.')

@section('content')
    @include('customer._date_filter', [
        'action' => route('customer.notifications'),
        'filters' => $filters,
        'prefix' => 'notifications',
    ])

    <div class="row">
        <div class="col-lg-6 mb-3">
            <div class="card customer-card h-100">
                <div class="card-header">
                    <h3 class="card-title">Email Notifications</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                        <tr>
                            <th>Sent</th>
                            <th>Subject</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($emails as $email)
                            <tr>
                                <td>{{ optional($email->sent_at ?? $email->created_at)->format('d M Y') }}</td>
                                <td>{{ $email->subject }}</td>
                                <td><span class="badge badge-light border">{{ ucfirst($email->status) }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">No email notifications found.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    {{ $emails->links() }}
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-3">
            <div class="card customer-card h-100">
                <div class="card-header">
                    <h3 class="card-title">SMS Notifications</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                        <tr>
                            <th>Sent</th>
                            <th>Message</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($smsMessages as $sms)
                            <tr>
                                <td>{{ optional($sms->sent_at ?? $sms->created_at)->format('d M Y') }}</td>
                                <td>{{ $sms->message }}</td>
                                <td><span class="badge badge-light border">{{ ucfirst($sms->status) }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">No SMS notifications found.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    {{ $smsMessages->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
