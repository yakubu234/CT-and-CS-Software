@extends('layouts.admin')

@section('title', 'Archived Branches')
@section('page_title', 'Archived Branches')

@section('content')
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card">
        @include('layouts.partials.table-toolbar', [
            'title' => 'Archived branches',
            'subtitle' => 'Archived branches and their associated records are preserved and can be restored.',
            'placeholder' => 'Search archived branches',
        ])
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                    <tr>
                        <th>Branch</th>
                        <th>Prefix</th>
                        <th>Contact</th>
                        <th>Archived</th>
                        <th class="text-right">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($branches as $branch)
                        <tr>
                            <td>
                                <strong>{{ $branch->name }}</strong>
                                <small class="d-block text-muted">{{ $branch->address }}</small>
                            </td>
                            <td>{{ $branch->prefix ?: 'N/A' }}</td>
                            <td>
                                {{ $branch->contact_email }}
                                <small class="d-block text-muted">{{ $branch->contact_phone ?: 'No phone' }}</small>
                            </td>
                            <td>{{ optional($branch->deleted_at)->format('d M Y, h:i A') }}</td>
                            <td class="text-right">
                                <form action="{{ route('branches.restore', $branch->id) }}" method="POST" onsubmit="return confirm('Restore this branch to the active branch list?');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-success">
                                        <i class="fas fa-undo mr-1"></i> Restore
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No archived branches found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($branches->hasPages())
            <div class="card-footer clearfix">{{ $branches->links() }}</div>
        @endif
    </div>

    <a href="{{ route('branches.index') }}" class="btn btn-outline-primary">
        <i class="fas fa-arrow-left mr-1"></i> Back to active branches
    </a>
@endsection
