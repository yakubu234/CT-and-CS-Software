<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login | Oreoluwapo CT&CU</title>
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/plugins/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/dist/css/adminlte.min.css') }}">
</head>
<body class="hold-transition login-page">
<div class="login-box">
    <div class="login-logo">
        <a href="#"><b>Oreoluwapo CT &amp; CU</b></a>
    </div>

    <div class="card card-outline card-primary">
        <div class="card-header text-center">
            <p class="h4 mb-0">Cooperative Management Panel</p>
        </div>
        <div class="card-body">
            <p class="login-box-msg">Sign in with your email address or member number.</p>

            <form action="{{ route('login.store') }}" method="POST">
                @csrf

                <div class="input-group mb-3">
                    <input
                        type="text"
                        name="login"
                        class="form-control @error('login') is-invalid @enderror"
                        placeholder="Email or Member Number"
                        value="{{ old('login') }}"
                        required
                        autofocus
                    >
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-user"></span>
                        </div>
                    </div>
                    @error('login')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="input-group mb-3">
                    <input
                        type="password"
                        name="password"
                        id="password"
                        class="form-control"
                        placeholder="Password"
                        required
                    >
                    <div class="input-group-append">
                        <button type="button" class="btn btn-outline-secondary toggle-password-visibility" data-target="password" aria-label="Show password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="row">
                    <div class="col-7">
                        <div class="icheck-primary">
                            <input type="checkbox" id="remember" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                            <label for="remember">Remember me</label>
                        </div>
                    </div>
                    <div class="col-5">
                        <button type="submit" class="btn btn-primary btn-block">Sign In</button>
                    </div>
                </div>
            </form>

            <div class="alert alert-light border mt-4 mb-0">
                <strong>Welcome:</strong> staff and members can sign in from this same page.
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('vendor/adminlte/plugins/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('vendor/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('vendor/adminlte/dist/js/adminlte.min.js') }}"></script>
<script>
    (function () {
        const passwordToggleButton = document.querySelector('.toggle-password-visibility');

        if (! passwordToggleButton) {
            return;
        }

        passwordToggleButton.addEventListener('click', function () {
            const input = document.getElementById(passwordToggleButton.dataset.target || '');
            const icon = passwordToggleButton.querySelector('i');

            if (! input || ! icon) {
                return;
            }

            const shouldShow = input.type === 'password';
            input.type = shouldShow ? 'text' : 'password';
            icon.classList.toggle('fa-eye', ! shouldShow);
            icon.classList.toggle('fa-eye-slash', shouldShow);
            passwordToggleButton.setAttribute('aria-label', shouldShow ? 'Hide password' : 'Show password');
        });
    })();
</script>
</body>
</html>
