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
                        <th>Template</th>
                        <th>Status</th>
                        <th style="width: 90px;">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($rules as $rule)
                        <tr>
                            <td>{{ $rule->name }}</td>
                            <td>{{ $eventOptions[$rule->event] ?? ucfirst(str_replace('_', ' ', $rule->event)) }}</td>
                            <td>{{ $rule->branch?->name ?: 'All accessible branches' }}</td>
                            <td>{{ $rule->template?->name ?: 'N/A' }}</td>
                            <td><span class="badge badge-{{ $rule->status ? 'success' : 'secondary' }}">{{ $rule->status ? 'Active' : 'Inactive' }}</span></td>
                            <td class="text-center">
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
                            <td colspan="6" class="text-center text-muted py-4">No SMS automation rules found.</td>
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
