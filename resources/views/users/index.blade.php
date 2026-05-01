@extends('layouts.admin')

@section('title', 'Users')
@section('page_title', 'Users')

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h3 class="card-title mb-0">Staff Users</h3>
                <small class="text-muted d-block mt-1">Manage admin-side accounts and assign permission roles.</small>
            </div>
            <a href="{{ route('users.create') }}" class="btn btn-primary mt-2 mt-md-0">
                <i class="fas fa-user-plus mr-1"></i>
                New User
            </a>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('users.index') }}" class="mb-3">
                <div class="input-group">
                    <input type="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search users...">
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
                        <th>Email</th>
                        <th>Role</th>
                        <th>Primary Branch</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>
                                <div class="font-weight-bold">{{ $user->name }}</div>
                                <div class="text-muted small">{{ $user->designation ?: ($user->society_role ?: 'Staff user') }}</div>
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->isSuperAdmin() ? 'Super Admin' : ($user->role?->name ?: 'Legacy Access') }}</td>
                            <td>{{ $user->branch?->name ?: 'N/A' }}</td>
                            <td>
                                <span class="badge badge-{{ (int) $user->status === 1 ? 'success' : 'secondary' }}">
                                    {{ (int) $user->status === 1 ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                @if (! $user->isSuperAdmin())
                                    <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Archive this user?')">Archive</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No staff users found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $users->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection
