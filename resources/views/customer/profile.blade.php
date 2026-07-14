@extends('layouts.customer')

@section('title', 'Profile')
@section('page_title', 'Profile')
@section('page_subtitle', 'Update your contact details, password, and review your documents.')

@section('content')
    <div class="row">
        <div class="col-lg-6 mb-3">
            <div class="card customer-card h-100">
                <div class="card-header">
                    <h3 class="card-title">Contact Details</h3>
                </div>
                <form method="POST" action="{{ route('customer.profile.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-4">Name</dt>
                            <dd class="col-sm-8">{{ $customer->name }}</dd>
                            <dt class="col-sm-4">Member No</dt>
                            <dd class="col-sm-8">{{ $customer->display_member_no ?: 'N/A' }}</dd>
                            <dt class="col-sm-4">Branch</dt>
                            <dd class="col-sm-8">{{ $customer->branch?->name ?: 'N/A' }}</dd>
                        </dl>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input
                                type="email"
                                name="email"
                                id="email"
                                class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email', $customer->email) }}"
                                required
                            >
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-0">
                            <label for="mobile">Phone Number</label>
                            <input
                                type="text"
                                name="mobile"
                                id="mobile"
                                class="form-control @error('mobile') is-invalid @enderror"
                                value="{{ old('mobile', $customer->detail?->mobile) }}"
                                required
                            >
                            @error('mobile')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-6 mb-3">
            <div class="card customer-card h-100">
                <div class="card-header">
                    <h3 class="card-title">Change Password</h3>
                </div>
                <form method="POST" action="{{ route('customer.password.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input
                                type="password"
                                name="current_password"
                                id="current_password"
                                class="form-control @error('current_password') is-invalid @enderror"
                                required
                                autocomplete="current-password"
                            >
                            @error('current_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="password">New Password</label>
                            <input
                                type="password"
                                name="password"
                                id="password"
                                class="form-control @error('password') is-invalid @enderror"
                                required
                                autocomplete="new-password"
                            >
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-0">
                            <label for="password_confirmation">Confirm New Password</label>
                            <input
                                type="password"
                                name="password_confirmation"
                                id="password_confirmation"
                                class="form-control"
                                required
                                autocomplete="new-password"
                            >
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-lock mr-1"></i> Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-12 mb-3">
            <div class="card customer-card h-100">
                <div class="card-header">
                    <h3 class="card-title">Documents</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <tbody>
                        @forelse ($customer->documents as $document)
                            <tr>
                                <td>{{ $document->name ?? 'Document' }}</td>
                                <td class="text-right">
                                    @if ($document->document ?? false)
                                        <a href="{{ asset('storage/' . $document->document) }}" target="_blank" class="btn btn-sm btn-outline-primary">View</a>
                                    @else
                                        <span class="text-muted">Unavailable</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="text-center text-muted py-4">No documents found.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
