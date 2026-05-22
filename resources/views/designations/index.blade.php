@extends('layouts.admin')

@section('title', 'Exco Roles')
@section('page_title', 'Exco Roles')

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h3 class="card-title mb-0">Exco Roles</h3>
                <small class="text-muted d-block mt-1">Manage the branch executive designations used in branch create and edit forms.</small>
            </div>
            <div class="d-flex flex-wrap mt-2 mt-md-0">
                <x-browser-back-button :fallback="route('dashboard')" class="btn btn-light mr-2 mb-2 mb-md-0" />
                <a href="{{ route('exco-roles.create') }}" class="btn btn-primary mb-2 mb-md-0">
                    <i class="fas fa-plus mr-1"></i>
                    New Exco Role
                </a>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('exco-roles.index') }}" class="mb-3">
                <div class="input-group">
                    <input type="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search exco roles...">
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-outline-secondary">Search</button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="thead-light">
                    <tr>
                        <th>Name</th>
                        <th>Sort Order</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($designations as $designation)
                        <tr>
                            <td class="font-weight-bold">{{ $designation->name }}</td>
                            <td>{{ $designation->sort_order }}</td>
                            <td>
                                <span class="badge badge-{{ (int) $designation->status === 1 ? 'success' : 'secondary' }}">
                                    {{ (int) $designation->status === 1 ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('exco-roles.edit', $designation) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form action="{{ route('exco-roles.destroy', $designation) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this exco role?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">No exco roles found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $designations->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection
