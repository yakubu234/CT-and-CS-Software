@extends('layouts.admin')

@section('title', 'User Roles')
@section('page_title', 'User Roles')

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h3 class="card-title mb-0">Roles</h3>
                <small class="text-muted d-block mt-1">Create permission bundles for staff access control.</small>
            </div>
            <a href="{{ route('user-roles.create') }}" class="btn btn-primary mt-2 mt-md-0">
                <i class="fas fa-plus mr-1"></i>
                New Role
            </a>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('user-roles.index') }}" class="mb-3">
                <div class="input-group">
                    <input type="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search roles...">
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
                        <th>Description</th>
                        <th>Permissions</th>
                        <th>System</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($roles as $role)
                        <tr>
                            <td class="font-weight-bold">{{ $role->name }}</td>
                            <td>{{ $role->description ?: 'N/A' }}</td>
                            <td>
                                <div class="d-flex flex-wrap">
                                    @foreach (($role->permissions ?? []) as $permission)
                                        <span class="badge badge-light border mr-1 mb-1">{{ $permissionLabels[$permission] ?? $permission }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-{{ $role->is_system ? 'info' : 'secondary' }}">
                                    {{ $role->is_system ? 'System' : 'Custom' }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('user-roles.edit', $role) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                @if (! $role->is_system)
                                    <form action="{{ route('user-roles.destroy', $role) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this role?')">Delete</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No roles found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $roles->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection
