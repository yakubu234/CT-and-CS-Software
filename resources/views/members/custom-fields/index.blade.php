@extends('layouts.admin')

@section('title', 'Member Custom Fields')
@section('page_title', 'Member Custom Fields')

@section('content')
    <div class="card card-outline card-primary">
        @include('layouts.partials.table-toolbar', [
            'title' => 'Member Custom Fields',
            'subtitle' => 'These fields will appear automatically on the member form.',
            'action' => route('members.custom-fields.index'),
            'placeholder' => 'Search custom fields',
        ])

        <div class="card-body">
            <div class="mb-3">
                <a href="{{ route('members.custom-fields.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus mr-1"></i>
                    Add Custom Field
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                    <tr>
                        <th>Field Name</th>
                        <th>Field Type</th>
                        <th>Default</th>
                        <th>File Size</th>
                        <th>Required</th>
                        <th>Status</th>
                        <th style="width: 180px;">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($fields as $field)
                        <tr>
                            <td>{{ $field->field_name }}</td>
                            <td class="text-capitalize">{{ $field->field_type }}</td>
                            <td>{{ $field->default_value ?: 'N/A' }}</td>
                            <td>{{ $field->field_type === 'file' ? (($field->max_size ?: 'N/A') . ' KB') : 'N/A' }}</td>
                            <td>{{ $field->is_required === 'required' ? 'Required' : 'Optional' }}</td>
                            <td>
                                <span class="badge badge-{{ $field->status ? 'success' : 'secondary' }}">
                                    {{ $field->status ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('members.custom-fields.edit', $field) }}" class="btn btn-sm btn-outline-primary">
                                    Edit
                                </a>
                                <form action="{{ route('members.custom-fields.destroy', $field) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this custom field?')">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No custom fields found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $fields->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection
