@extends('layouts.admin')

@section('title', 'Members')
@section('page_title', 'Members')

@section('content')
    <div class="card card-outline card-primary">
        @include('layouts.partials.table-toolbar', [
            'title' => 'Members',
            'subtitle' => 'All non-branch-account users for ' . $branch->name . ', including excos and former excos.',
            'action' => route('members.index'),
            'placeholder' => 'Search by name, email, member number, or designation',
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
                        <th>Designation</th>
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
                            <td>{{ $member->display_member_no ?: 'N/A' }}</td>
                            <td>
                                <div class="font-weight-bold">{{ $member->designation ?: 'Member' }}</div>
                                @if ($member->society_exco)
                                    <span class="badge badge-info">Current Exco</span>
                                @elseif ($member->former_exco)
                                    <span class="badge badge-secondary">Former Exco</span>
                                @endif
                            </td>
                            <td>{{ $member->detail?->mobile ?: 'N/A' }}</td>
                            <td>{{ trim(($member->detail?->city ?: '') . ' / ' . ($member->detail?->state ?: ''), ' /') ?: 'N/A' }}</td>
                            <td>
                                <span class="badge badge-{{ $member->status ? 'success' : 'secondary' }}">
                                    {{ $member->status ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('members.show', $member) }}" class="btn btn-sm btn-outline-info">View</a>
                                @if (! $member->society_exco && ! $member->former_exco)
                                    <a href="{{ route('members.edit', $member) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <a href="{{ route('members.show', $member) }}#documents" class="btn btn-sm btn-outline-secondary">Add Document</a>
                                    <form action="{{ route('members.destroy', $member) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Archive this member?')">
                                            Delete
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No members found for this branch.</td>
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
