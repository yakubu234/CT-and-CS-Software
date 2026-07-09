@extends('layouts.admin')

@section('title', 'Email Templates')
@section('page_title', 'Email Templates')

@section('content')
    <div class="card card-outline card-primary">
        @include('layouts.partials.table-toolbar', [
            'title' => 'Email Templates',
            'subtitle' => 'Reusable email content for official cooperative communications.',
            'action' => route('email.templates.index'),
            'placeholder' => 'Search email templates',
        ])

        <div class="card-body">
            <div class="mb-3">
                <a href="{{ route('email.templates.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus mr-1"></i>
                    New Template
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th style="width: 140px;">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($templates as $template)
                        <tr>
                            <td>
                                <div class="font-weight-bold">{{ $template->name }}</div>
                                <small class="text-muted">{{ $template->description }}</small>
                            </td>
                            <td>{{ ucfirst(str_replace('_', ' ', $template->category)) }}</td>
                            <td>{{ $template->subject }}</td>
                            <td><span class="badge badge-{{ $template->status ? 'success' : 'secondary' }}">{{ $template->status ? 'Active' : 'Inactive' }}</span></td>
                            <td>
                                <a href="{{ route('email.templates.edit', $template) }}" class="btn btn-sm btn-outline-primary mb-1">Edit</a>
                                <form action="{{ route('email.templates.destroy', $template) }}" method="POST" onsubmit="return confirm('Delete this email template?')" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger mb-1">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No email templates found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">{{ $templates->links() }}</div>
        </div>
    </div>
@endsection
