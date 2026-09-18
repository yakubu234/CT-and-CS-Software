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
                            <div class="input-group">
                                <input
                                    type="password"
                                    name="password"
                                    id="member_password"
                                    class="form-control @error('password') is-invalid @enderror"
                                    required
                                    autocomplete="new-password"
                                >
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary" data-password-toggle="member_password" aria-label="Show new password" aria-controls="member_password" aria-pressed="false" title="Show password">
                                        <i class="fas fa-eye" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                            @error('password')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="member_password_confirmation">Confirm New Password</label>
                            <div class="input-group">
                                <input
                                    type="password"
                                    name="password_confirmation"
                                    id="member_password_confirmation"
                                    class="form-control"
                                    required
                                    autocomplete="new-password"
                                >
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary" data-password-toggle="member_password_confirmation" aria-label="Show confirmation password" aria-controls="member_password_confirmation" aria-pressed="false" title="Show password">
                                        <i class="fas fa-eye" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
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

@push('scripts')
    <script>
        document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
            button.addEventListener('click', function () {
                const input = document.getElementById(button.dataset.passwordToggle);
                if (!input) return;

                const isVisible = input.type === 'password';
                input.type = isVisible ? 'text' : 'password';
                button.setAttribute('aria-pressed', String(isVisible));
                button.setAttribute('aria-label', (isVisible ? 'Hide ' : 'Show ') + (input.id === 'member_password' ? 'new password' : 'confirmation password'));
                button.title = isVisible ? 'Hide password' : 'Show password';
                button.querySelector('i').classList.toggle('fa-eye', !isVisible);
                button.querySelector('i').classList.toggle('fa-eye-slash', isVisible);
            });
        });
    </script>
@endpush
