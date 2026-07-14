@extends('layouts.admin')

@section('title', 'Edit Member')
@section('page_title', 'Edit Member')

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Update member details</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('members.update', $member) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('members._form', ['submitLabel' => 'Update Member'])
            </form>
        </div>
    </div>

    <div class="card card-outline card-warning mt-4">
        <div class="card-header">
            <h3 class="card-title">Member Password</h3>
        </div>
        <form action="{{ route('members.password.update', $member) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="card-body">
                <div class="alert alert-light border">
                    Use this section to update only the member's portal password. The member details above will not be changed.
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="member_password">New Password</label>
                            <input
                                type="password"
                                name="password"
                                id="member_password"
                                class="form-control @error('password') is-invalid @enderror"
                                required
                                autocomplete="new-password"
                            >
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="member_password_confirmation">Confirm New Password</label>
                            <input
                                type="password"
                                name="password_confirmation"
                                id="member_password_confirmation"
                                class="form-control"
                                required
                                autocomplete="new-password"
                            >
                        </div>
                    </div>
                </div>

                <div class="form-check">
                    <input
                        type="checkbox"
                        name="must_change_password"
                        value="1"
                        id="must_change_password"
                        class="form-check-input"
                        @checked(old('must_change_password', $member->must_change_password))
                    >
                    <label for="must_change_password" class="form-check-label">
                        Require member to change this password after login
                    </label>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-warning">
                    Update Password
                </button>
            </div>
        </form>
    </div>
@endsection
