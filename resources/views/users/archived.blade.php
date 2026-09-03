@extends('layouts.admin')

@section('title', 'Archived Users')
@section('page_title', 'Archived Users')

@section('content')
    <div class="card card-outline card-secondary">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h3 class="card-title mb-0">Archived Staff Users</h3>
                <small class="text-muted d-block mt-1">Restore archived staff accounts without losing their roles, branch assignments, permissions, or records.</small>
            </div>
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary mt-2 mt-md-0">
                <i class="fas fa-arrow-left mr-1"></i> Active Users
            </a>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('users.archived') }}" class="mb-3">
                <div class="input-group">
                    <input type="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search archived users...">
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
                        <th>Archived</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>
                                <div class="font-weight-bold">{{ $user->name }}</div>
                                <div class="text-muted small">{{ $user->designation ?: 'Staff user' }}</div>
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->role?->name ?: 'Legacy Access' }}</td>
                            <td>{{ $user->branch?->name ?: 'N/A' }}</td>
                            <td>{{ optional($user->deleted_at)->format('d M Y, h:i A') ?: 'N/A' }}</td>
                            <td>
                                <form action="{{ route('users.restore', $user->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-success" onclick="return confirm('Restore and reactivate this user? Their existing role, assignments, permissions, and records will be retained.')">
                                        <i class="fas fa-undo mr-1"></i> Restore
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No archived staff users found.</td>
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
