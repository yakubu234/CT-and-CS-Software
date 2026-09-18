<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Reset Member Password</title><link rel="stylesheet" href="{{ asset('vendor/adminlte/dist/css/adminlte.min.css') }}"></head>
<body class="hold-transition login-page"><div class="login-box"><div class="card card-outline card-primary"><div class="card-body">
    <h1 class="h4 mb-3">Reset Member Password</h1>
    <p>Enter the email address on your member profile. If the account is active, we will send a reset link there.</p>
    @if (session('status'))<div class="alert alert-info">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('member-password.email') }}">@csrf
        <label for="email">Registered email</label><input id="email" name="email" type="email" class="form-control mb-3" value="{{ old('email') }}" required autocomplete="email">
        <button type="submit" class="btn btn-primary btn-block">Send reset link</button>
    </form>
    <a class="d-block mt-3" href="{{ route('login') }}">Back to sign in</a>
</div></div></div></body></html>
