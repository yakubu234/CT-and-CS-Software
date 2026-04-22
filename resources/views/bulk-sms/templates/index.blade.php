@extends('layouts.admin')

@section('title', 'SMS Templates')
@section('page_title', 'SMS Templates')

@section('content')
    <div class="card card-outline card-primary">
        @include('layouts.partials.table-toolbar', [
            'title' => 'SMS Templates',
            'subtitle' => 'Reusable SMS bodies for campaigns, birthdays, credits, debits, loan approval, and monthly statements.',
            'action' => route('bulk-sms.templates.index'),
            'placeholder' => 'Search SMS templates',
        ])

        <div class="card-body">
            <div class="mb-3">
                <a href="{{ route('bulk-sms.templates.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus mr-1"></i>
                    Create Template
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th style="width: 90px;">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($templates as $template)
                        <tr>
                            <td>
                                <div class="font-weight-semibold">{{ $template->name }}</div>
                                <small class="text-muted">{{ $template->description ?: \Illuminate\Support\Str::limit($template->body, 90) }}</small>
                            </td>
                            <td>{{ $categoryOptions[$template->category] ?? ucfirst(str_replace('_', ' ', $template->category)) }}</td>
                            <td>
                                <span class="badge badge-{{ $template->status ? 'success' : 'secondary' }}">
                                    {{ $template->status ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('bulk-sms.templates.edit', $template) }}" class="btn btn-sm btn-outline-primary mb-1" title="Edit">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <form action="{{ route('bulk-sms.templates.destroy', $template) }}" method="POST" onsubmit="return confirm('Delete this SMS template?')" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">No SMS templates found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $templates->links() }}
            </div>
        </div>
    </div>
@endsection
