@extends('layouts.admin')

@section('title', 'Archived Members')
@section('page_title', 'Archived Members')

@section('content')
    <div class="card card-outline card-secondary">
        @include('layouts.partials.table-toolbar', [
            'title' => 'Archived Members',
            'subtitle' => 'Inactive member records retained for ' . $branch->name . '. Restoring a member also restores accounts disabled when the member was archived.',
            'action' => route('members.archived'),
            'placeholder' => 'Search by name, email, member number, or designation',
        ])

        <div class="card-body">
            <div class="mb-3">
                <a href="{{ route('members.index') }}" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left mr-1"></i> Active Members
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                    <tr>
                        <th>Archived At</th>
                        <th>Member</th>
                        <th>Member Number</th>
                        <th>Mobile</th>
                        <th>Accounts</th>
                        <th>Status</th>
                        <th style="width: 190px;">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($members as $member)
                        <tr>
                            <td>{{ optional($member->deleted_at)->format('d M Y, h:i A') ?: 'N/A' }}</td>
                            <td>
                                <div class="font-weight-bold">{{ $member->name }}</div>
                                <div class="text-muted small">{{ $member->email }}</div>
                            </td>
                            <td>{{ $member->display_member_no ?: 'N/A' }}</td>
                            <td>{{ $member->detail?->mobile ?: 'N/A' }}</td>
                            <td>{{ $member->savings_accounts_count }}</td>
                            <td><span class="badge badge-secondary">Archived</span></td>
                            <td>
                                <a href="{{ route('members.archived.show', $member->id) }}" class="btn btn-sm btn-outline-info">View History</a>
                                <form action="{{ route('members.restore', $member->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-success" onclick="return confirm('Restore this member and the accounts archived with them?')">
                                        Restore
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No archived members found for this branch.</td>
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
