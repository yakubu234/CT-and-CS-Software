@extends('layouts.customer')

@section('title', 'Change Password')
@section('page_title', 'Change Password')
@section('page_subtitle', 'Set your personal password before continuing to the member portal.')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card customer-card">
                <div class="card-header">
                    <h3 class="card-title">Required Password Update</h3>
                </div>
                <form method="POST" action="{{ route('customer.password.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="alert alert-info">
                            Your account is using the temporary password. Please create your own password to continue.
                        </div>

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
    </div>
@endsection
