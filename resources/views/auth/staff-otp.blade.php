<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Staff Verification | Oreoluwapo CT&CU</title>
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/plugins/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/dist/css/adminlte.min.css') }}">
</head>
<body class="hold-transition login-page">
<div class="login-box">
    <div class="login-logo"><a href="#"><b>Oreoluwapo CT &amp; CU</b></a></div>
    <div class="card card-outline card-primary">
        <div class="card-header text-center"><p class="h4 mb-0">Staff Verification</p></div>
        <div class="card-body">
            <p class="login-box-msg">Enter the six-digit code sent to <strong>{{ $maskedEmail }}</strong>.</p>

            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            <form action="{{ route('staff-otp.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <input type="text" name="code" inputmode="numeric" autocomplete="one-time-code"
                           pattern="[0-9]{6}" maxlength="6"
                           class="form-control form-control-lg text-center @error('code') is-invalid @enderror"
                           style="letter-spacing:.6rem;font-weight:700" placeholder="000000" required autofocus>
                    @error('code')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <button class="btn btn-primary btn-block" type="submit">Verify and sign in</button>
            </form>

            <div class="d-flex justify-content-between align-items-center mt-3">
                <form action="{{ route('staff-otp.resend') }}" method="POST">
                    @csrf
                    <button class="btn btn-link p-0" type="submit">Resend code</button>
                </form>
                <a href="{{ route('login') }}">Back to login</a>
            </div>
            <p class="text-muted small mt-3 mb-0">
                Code expires {{ $expiresAt->diffForHumans() }}. Resending is available once per minute.
            </p>
        </div>
    </div>
</div>
<script src="{{ asset('vendor/adminlte/plugins/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('vendor/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('vendor/adminlte/dist/js/adminlte.min.js') }}"></script>
</body>
</html>
