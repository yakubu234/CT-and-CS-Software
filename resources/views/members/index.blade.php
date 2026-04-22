@extends('layouts.admin')

@section('title', 'Members')
@section('page_title', 'Members')

@section('content')
    <div class="card card-outline card-primary">
        @include('layouts.partials.table-toolbar', [
            'title' => 'Members',
            'subtitle' => 'Manage members for ' . $branch->name,
            'action' => route('members.index'),
            'placeholder' => 'Search members by name, email, or member number',
        ])

        <div class="card-body">
            <div class="mb-3 d-flex gap-2">
                <a href="{{ route('members.create') }}" class="btn btn-primary">
                    <i class="fas fa-user-plus mr-1"></i>
                    Create Member
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                    <tr>
                        <th>Member</th>
                        <th>Member Number</th>
                        <th>Mobile</th>
                        <th>City / State</th>
                        <th>Status</th>
                        <th style="width: 220px;">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($members as $member)
                        <tr>
                            <td>
                                <div class="font-weight-bold">{{ $member->name }}</div>
                                <div class="text-muted small">{{ $member->email }}</div>
                            </td>
                            <td>{{ $member->detail?->member_no ?: $member->member_no ?: 'N/A' }}</td>
                            <td>{{ $member->detail?->mobile ?: 'N/A' }}</td>
                            <td>{{ trim(($member->detail?->city ?: '') . ' / ' . ($member->detail?->state ?: ''), ' /') ?: 'N/A' }}</td>
                            <td>
                                <span class="badge badge-{{ $member->status ? 'success' : 'secondary' }}">
                                    {{ $member->status ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('members.show', $member) }}" class="btn btn-sm btn-outline-info">View</a>
                                <a href="{{ route('members.edit', $member) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <a href="{{ route('members.show', $member) }}#documents" class="btn btn-sm btn-outline-secondary">Add Document</a>
                                <form action="{{ route('members.destroy', $member) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Archive this member?')">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No members found for this branch.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $members->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection
