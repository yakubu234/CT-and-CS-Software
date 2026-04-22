@extends('layouts.admin')

@section('title', 'Branch Details')
@section('page_title', 'Branch Details')

@php
    $storageUrl = fn (?string $path) => $path ? \Illuminate\Support\Facades\Storage::url($path) : null;
    $branchAccount = $branch->branchUser?->savingsAccounts?->firstWhere('is_branch_acount', true);
@endphp

@section('content')
    @if (session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif

    <div class="row mb-3">
        <div class="col-md-8">
            <div class="d-flex align-items-center">
                @if ($branch->photo)
                    <img src="{{ $storageUrl($branch->photo) }}" alt="{{ $branch->name }}" class="img-circle elevation-1 mr-3" style="width: 68px; height: 68px; object-fit: cover;">
                @else
                    <div class="bg-light border rounded-circle d-inline-flex align-items-center justify-content-center mr-3" style="width: 68px; height: 68px;">
                        <i class="fas fa-building text-secondary fa-lg"></i>
                    </div>
                @endif
                <div>
                    <h2 class="h4 mb-1">{{ $branch->name }}</h2>
                    <div class="text-muted">{{ $branch->address }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 text-md-right mt-3 mt-md-0">
            <a href="{{ route('branches.edit', $branch) }}" class="btn btn-primary">
                <i class="fas fa-edit mr-1"></i>
                Edit branch
            </a>
            <a href="{{ route('branches.index') }}" class="btn btn-light">Back</a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Branch profile</h3>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Branch prefix</dt>
                        <dd class="col-sm-7">{{ $branch->prefix ?: 'N/A' }}</dd>

                        <dt class="col-sm-5">Loan prefix</dt>
                        <dd class="col-sm-7">{{ $branch->id_prefix ?: 'N/A' }}</dd>

                        <dt class="col-sm-5">Meeting cycle</dt>
                        <dd class="col-sm-7 text-uppercase">{{ $branch->branch_meeting_days ?: 'N/A' }}</dd>

                        <dt class="col-sm-5">Contact email</dt>
                        <dd class="col-sm-7">{{ $branch->contact_email ?: 'N/A' }}</dd>

                        <dt class="col-sm-5">Contact phone</dt>
                        <dd class="col-sm-7">{{ $branch->contact_phone ?: 'N/A' }}</dd>

                        <dt class="col-sm-5">Registration number</dt>
                        <dd class="col-sm-7">{{ $branch->registration_number ?: 'N/A' }}</dd>

                        <dt class="col-sm-5">Year of registration</dt>
                        <dd class="col-sm-7">{{ $branch->year_of_registration ?: 'N/A' }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Branch account</h3>
                </div>
                <div class="card-body">
                    @if ($branch->branchUser)
                        <dl class="row mb-0">
                            <dt class="col-sm-4">User name</dt>
                            <dd class="col-sm-8">{{ $branch->branchUser->name }}</dd>

                            <dt class="col-sm-4">Email</dt>
                            <dd class="col-sm-8">{{ $branch->branchUser->email }}</dd>

                            <dt class="col-sm-4">Savings account</dt>
                            <dd class="col-sm-8">{{ $branchAccount?->account_number ?: 'Not found' }}</dd>

                            <dt class="col-sm-4">Status</dt>
                            <dd class="col-sm-8">{{ $branch->branchUser->status ? 'Active' : 'Inactive' }}</dd>
                        </dl>
                    @else
                        <p class="text-muted mb-0">No branch-account user is linked to this branch.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Current excos</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Designation</th>
                        <th>Assumed office</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($branch->excos as $exco)
                        <tr>
                            <td>{{ $exco->name }}</td>
                            <td>{{ $exco->designation ?: 'N/A' }}</td>
                            <td>{{ optional($exco->date_added_as_exco)->format('M d, Y h:i A') ?: 'N/A' }}</td>
                            <td><span class="badge badge-success">Serving</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">No active excos are currently attached to this branch.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Former excos</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Former designation</th>
                        <th>Assumed office</th>
                        <th>Ended office</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($branch->formerExcos as $exco)
                        <tr>
                            <td>{{ $exco->name }}</td>
                            <td>{{ $exco->former_designation ?: 'N/A' }}</td>
                            <td>{{ optional($exco->date_added_as_exco)->format('M d, Y h:i A') ?: 'N/A' }}</td>
                            <td>{{ optional($exco->date_removed_as_exco)->format('M d, Y h:i A') ?: 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">No former exco history has been recorded for this branch yet.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
