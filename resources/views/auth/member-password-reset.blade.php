<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Set New Member Password</title><link rel="stylesheet" href="{{ asset('vendor/adminlte/dist/css/adminlte.min.css') }}"></head>
<body class="hold-transition login-page"><div class="login-box"><div class="card card-outline card-primary"><div class="card-body">
    <h1 class="h4 mb-3">Set a New Password</h1>
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('member-password.update') }}">@csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <label for="email">Registered email</label><input id="email" name="email" type="email" class="form-control mb-3" value="{{ old('email', $email) }}" required autocomplete="email">
        <label for="password">New password</label><input id="password" name="password" type="password" class="form-control mb-3" required autocomplete="new-password" minlength="8">
        <label for="password_confirmation">Confirm new password</label><input id="password_confirmation" name="password_confirmation" type="password" class="form-control mb-3" required autocomplete="new-password" minlength="8">
        <button type="submit" class="btn btn-primary btn-block">Reset password</button>
    </form>
    <a class="d-block mt-3" href="{{ route('login') }}">Back to sign in</a>
</div></div></div></body></html>
