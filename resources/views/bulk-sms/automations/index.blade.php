@extends('layouts.admin')

@section('title', 'SMS Automations')
@section('page_title', 'SMS Automations')

@section('content')
    <div class="card card-outline card-primary">
        @include('layouts.partials.table-toolbar', [
            'title' => 'SMS Automations',
            'subtitle' => 'Automatic SMS for credits, debits, loan approvals, birthdays, and monthly statements.',
            'action' => route('bulk-sms.automations.index'),
            'placeholder' => 'Search automation rules',
        ])

        <div class="card-body">
            <div class="mb-3">
                <a href="{{ route('bulk-sms.automations.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus mr-1"></i>
                    Create Automation
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Event</th>
                        <th>Branch</th>
                        <th>Schedule</th>
                        <th>Template</th>
                        <th>Status</th>
                        <th>Sending</th>
                        <th>Last 30 days</th>
                        <th style="width: 130px;">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($rules as $rule)
                        <tr>
                            <td>{{ $rule->name }}</td>
                            <td>{{ $eventOptions[$rule->event] ?? ucfirst(str_replace('_', ' ', $rule->event)) }}</td>
                            <td>{{ $rule->branch_id ? ($rule->branch?->name ?: 'Branch #' . $rule->branch_id) : 'All branches' }}</td>
                            <td>{{ $rule->scheduleLabel() }}</td>
                            <td>{{ $rule->template?->name ?: 'N/A' }}</td>
                            <td><span class="badge badge-{{ $rule->status ? 'success' : 'secondary' }}">{{ $rule->status ? 'Active' : 'Inactive' }}</span></td>
                            <td><span class="badge badge-{{ $rule->isManual() ? 'warning' : 'info' }}">{{ $rule->isManual() ? 'Manual' : 'Automated' }}</span></td>
                            <td class="small text-nowrap">
                                <a href="{{ route('bulk-sms.logs.index', ['rule' => $rule->id, 'status' => 'sent']) }}" class="text-success">{{ $rule->sent_count }} sent</a><br>
                                <a href="{{ route('bulk-sms.logs.index', ['rule' => $rule->id, 'status' => 'failed']) }}" class="text-danger">{{ $rule->failed_count }} failed</a>
                                @if ($rule->awaiting_count)
                                    <br><a href="{{ route('bulk-sms.logs.index', ['rule' => $rule->id, 'status' => 'awaiting']) }}" class="text-warning font-weight-bold">{{ $rule->awaiting_count }} awaiting</a>
                                @endif
                                @if ($rule->skipped_count)
                                    <br><a href="{{ route('bulk-sms.logs.index', ['rule' => $rule->id, 'status' => 'skipped']) }}" class="text-muted">{{ $rule->skipped_count }} skipped (no phone)</a>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('bulk-sms.test.create', ['rule' => $rule->id]) }}" class="btn btn-sm btn-outline-info mb-1" title="Test this alert">
                                    <i class="fas fa-vial"></i>
                                </a>
                                <a href="{{ route('bulk-sms.automations.edit', $rule) }}" class="btn btn-sm btn-outline-primary mb-1">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <form action="{{ route('bulk-sms.automations.destroy', $rule) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this automation rule?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No SMS automation rules found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $rules->links() }}
            </div>
        </div>
    </div>
@endsection
