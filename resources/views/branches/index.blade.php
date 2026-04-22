@extends('layouts.admin')

@section('title', 'Branches')
@section('page_title', 'Branches')

@php
    $storageUrl = fn (?string $path) => $path ? \Illuminate\Support\Facades\Storage::url($path) : null;
@endphp

@section('content')
    @if (session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif

    <div class="row mb-3">
        <div class="col-md-8">
            <div class="card card-outline card-primary mb-0">
                <div class="card-body">
                    <h3 class="card-title font-weight-bold mb-2">Branch Administration</h3>
                    <p class="text-muted mb-0">
                        Each branch created here is onboarded as its own branch-account user and immediately gets its default savings account.
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <a href="{{ route('branches.create') }}" class="btn btn-primary btn-lg btn-block">
                <i class="fas fa-plus mr-1"></i>
                Create branch
            </a>
        </div>
    </div>

    <div class="card">
        @include('layouts.partials.table-toolbar', [
            'title' => 'All branches',
            'subtitle' => 'Search by branch name, prefix, email, phone, address, or registration number.',
            'placeholder' => 'Search branches',
        ])
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                    <tr>
                        <th>Branch</th>
                        <th>Prefix</th>
                        <th>Loan Prefix</th>
                        <th>Meeting Cycle</th>
                        <th>Contact</th>
                        <th>Excos</th>
                        <th>Branch User</th>
                        <th class="text-right">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($branches as $branch)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    @if ($branch->photo)
                                        <img
                                            src="{{ $storageUrl($branch->photo) }}"
                                            alt="{{ $branch->name }}"
                                            class="img-circle elevation-1 mr-2"
                                            style="width: 42px; height: 42px; object-fit: cover;"
                                        >
                                    @else
                                        <div class="bg-light border rounded-circle d-inline-flex align-items-center justify-content-center mr-2"
                                             style="width: 42px; height: 42px;">
                                            <i class="fas fa-building text-secondary"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <div class="font-weight-bold">{{ $branch->name }}</div>
                                        <small class="text-muted">{{ $branch->address }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $branch->prefix ?: 'N/A' }}</td>
                            <td>{{ $branch->id_prefix ?: 'N/A' }}</td>
                            <td>
                                <span class="badge badge-info text-uppercase">{{ $branch->branch_meeting_days ?: 'N/A' }}</span>
                            </td>
                            <td>
                                <div>{{ $branch->contact_email }}</div>
                                <small class="text-muted">{{ $branch->contact_phone ?: 'No phone' }}</small>
                            </td>
                            <td>
                                <span class="badge badge-secondary">{{ $branch->excos->count() }} current exco(s)</span>
                            </td>
                            <td>
                                @if ($branch->branchUser)
                                    <div>{{ $branch->branchUser->email }}</div>
                                    <small class="text-muted">Branch account user</small>
                                @else
                                    <span class="text-muted">Not linked</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <a href="{{ route('branches.show', $branch) }}" class="btn btn-sm btn-outline-info">
                                    <i class="fas fa-eye mr-1"></i>
                                    View
                                </a>
                                <a href="{{ route('branches.edit', $branch) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-edit mr-1"></i>
                                    Edit
                                </a>
                                <form action="{{ route('branches.destroy', $branch) }}" method="POST" class="d-inline" onsubmit="return confirm('Move this branch out of the active branch list?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash mr-1"></i>
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                {{ request()->filled('search') ? 'No branches matched your search.' : 'No branches have been created yet.' }}
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($branches->hasPages())
            <div class="card-footer clearfix">
                <div class="float-sm-right">
                    {{ $branches->links() }}
                </div>
            </div>
        @endif
    </div>
@endsection
