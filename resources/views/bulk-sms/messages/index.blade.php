@extends('layouts.admin')

@section('title', 'SMS Logs')
@section('page_title', 'SMS Logs')

@section('content')
    <div class="card card-outline card-primary">
        @include('layouts.partials.table-toolbar', [
            'title' => 'SMS Logs',
            'subtitle' => 'Every SMS prepared or sent: campaigns, automated alerts, manual alerts awaiting sending, and tests.',
            'action' => route('bulk-sms.logs.index'),
            'placeholder' => 'Search SMS logs',
        ])

        <div class="card-body">
            <form method="GET" action="{{ route('bulk-sms.logs.index') }}" class="form-row align-items-end mb-3">
                <div class="col-md-3">
                    <label for="status" class="small mb-1">Status</label>
                    <select name="status" id="status" class="form-control select2">
                        <option value="">All statuses</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="rule" class="small mb-1">Automation</label>
                    <select name="rule" id="rule" class="form-control select2">
                        <option value="">All sources</option>
                        @foreach ($rules as $rule)
                            <option value="{{ $rule->id }}" @selected($ruleId === $rule->id)>{{ $rule->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary btn-block">Filter</button>
                </div>
            </form>

            @error('sms')<div class="alert alert-danger">{{ $message }}</div>@enderror

            @if ($awaitingCount > 0)
                <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between">
                    <div>
                        <strong>{{ $awaitingCount }}</strong> message{{ $awaitingCount === 1 ? ' is' : 's are' }} awaiting manual sending{{ $ruleId ? ' for this automation' : '' }}.
                        <a href="{{ route('bulk-sms.logs.index', array_filter(['status' => 'awaiting', 'rule' => $ruleId])) }}">Review them</a>
                    </div>
                    <form method="POST" action="{{ route('bulk-sms.logs.send-awaiting') }}" onsubmit="return confirm('Send all {{ $awaitingCount }} awaiting SMS now?')">
                        @csrf
                        @if ($ruleId)<input type="hidden" name="rule" value="{{ $ruleId }}">@endif
                        <button type="submit" class="btn btn-sm btn-warning"><i class="fas fa-paper-plane mr-1"></i>Send all awaiting</button>
                    </form>
                </div>
            @endif

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
                        @php($badge = ['sent' => 'success', 'failed' => 'danger', 'awaiting' => 'warning', 'skipped' => 'secondary', 'pending' => 'info'][$message->status] ?? 'secondary')
                        <tr>
                            <td>
                                <div class="font-weight-semibold">{{ $message->recipient_name ?: $message->user?->name ?: 'N/A' }}</div>
                                <small class="text-muted">{{ $message->user?->display_member_no ?: 'N/A' }}</small>
                            </td>
                            <td>{{ $message->phone ?: 'N/A' }}</td>
                            <td>
                                @if (data_get($message->meta, 'test'))
                                    <span class="badge badge-dark">TEST</span>
                                @endif
                                {{ $message->campaign?->name ?: $message->automationRule?->name ?: (data_get($message->meta, 'test') ? 'Test SMS Alerts' : 'System') }}
                                @if ($message->automationRule)
                                    <br><small class="text-muted">{{ data_get($message->meta, 'send_mode') === 'manual' ? 'Manual' : 'Automated' }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-{{ $badge }}">{{ $statuses[$message->status] ?? ucfirst($message->status) }}</span>
                                @if ($message->error_message)
                                    <br><small class="text-danger">{{ \Illuminate\Support\Str::limit($message->error_message, 80) }}</small>
                                @endif
                                @if ($message->status === 'awaiting')
                                    <form method="POST" action="{{ route('bulk-sms.logs.send', $message) }}" class="mt-1">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-sm btn-warning py-0"><i class="fas fa-paper-plane mr-1"></i>Send</button>
                                    </form>
                                @endif
                            </td>
                            <td>{{ $message->provider ?: 'N/A' }}</td>
                            <td>{{ optional($message->sent_at)->format('d M Y h:i A') ?: '-' }}</td>
                            <td style="min-width: 260px;">{{ $message->message }}</td>
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
                {{ $messages->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
@endsection
