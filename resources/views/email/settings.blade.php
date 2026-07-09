@extends('layouts.admin')

@section('title', 'Email Settings')
@section('page_title', 'Email Settings')

@section('content')
    <div class="card card-outline card-primary mb-4">
        <div class="card-header">
            <h3 class="card-title">Configured Mailer</h3>
        </div>
        <div class="card-body">
            @if ($testSendResult)
                <div class="alert alert-{{ $testSendResult['successful'] ? 'success' : 'danger' }}">
                    {{ $testSendResult['message'] }}
                </div>
            @endif

            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="small text-muted">Mailer</div>
                    <div class="font-weight-bold">{{ $summary['mailer'] ?: 'Not configured' }}</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="small text-muted">From Address</div>
                    <div class="font-weight-bold">{{ $summary['from_address'] ?: 'Not configured' }}</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="small text-muted">From Name</div>
                    <div class="font-weight-bold">{{ $summary['from_name'] ?: 'Not configured' }}</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="small text-muted">Host</div>
                    <div class="font-weight-bold">{{ $summary['host'] ?: 'N/A' }}</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="small text-muted">Port</div>
                    <div class="font-weight-bold">{{ $summary['port'] ?: 'N/A' }}</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="small text-muted">Encryption</div>
                    <div class="font-weight-bold">{{ $summary['encryption'] ?: 'None' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Send Test Email</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('email.settings.test-send') }}">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="email">Recipient Email</label>
                            <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <input type="text" name="subject" id="subject" class="form-control @error('subject') is-invalid @enderror" value="{{ old('subject', 'Email module test') }}" required>
                            @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label for="body">Body</label>
                            <textarea name="body" id="body" rows="5" class="form-control @error('body') is-invalid @enderror" required>{{ old('body', 'This is a test email from the cooperative email module.') }}</textarea>
                            @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane mr-1"></i>
                    Send Test Email
                </button>
            </form>
        </div>
    </div>
@endsection
