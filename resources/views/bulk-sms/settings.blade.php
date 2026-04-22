@extends('layouts.admin')

@section('title', 'SMS Settings')
@section('page_title', 'SMS Settings')

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title mb-0">Provider Configuration</h3>
        </div>
        <form method="POST" action="{{ route('bulk-sms.settings.update') }}">
            @csrf
            @method('PUT')

            <div class="card-body">
                <div class="alert alert-info">
                    Configure one active provider at a time. This module currently supports Nigeria-friendly presets for
                    <strong>Termii</strong> and <strong>BulkSMSNigeria</strong>, plus a <strong>Generic HTTP</strong> option.
                </div>

                @if ($balanceResult)
                    <div class="alert alert-{{ $balanceResult['successful'] ? 'success' : 'danger' }}">
                        @if ($balanceResult['successful'])
                            Current SMS balance:
                            <strong>{{ $balanceResult['currency'] ?: 'NGN' }} {{ number_format((float) ($balanceResult['balance'] ?? 0), 2) }}</strong>
                        @else
                            {{ $balanceResult['message'] ?? 'Unable to fetch SMS balance.' }}
                        @endif
                    </div>
                @endif

                @if ($testSendResult)
                    <div class="alert alert-{{ $testSendResult['successful'] ? 'success' : 'danger' }}">
                        {{ $testSendResult['message'] ?? '' }}
                        @if (! empty($testSendResult['external_id']))
                            <div><small>Reference: {{ $testSendResult['external_id'] }}</small></div>
                        @endif
                    </div>
                @endif

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="active_provider">Active Provider</label>
                            <select name="active_provider" id="active_provider" class="form-control select2" data-placeholder="Choose provider">
                                <option value="">Disabled</option>
                                @foreach ($providers as $value => $label)
                                    <option value="{{ $value }}" @selected(old('active_provider', $activeProvider) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="sender_id">Sender ID</label>
                            <input type="text" name="sender_id" id="sender_id" class="form-control" value="{{ old('sender_id', $senderId) }}" placeholder="e.g. OREOLUWAPO">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="callback_url">Callback URL</label>
                            <input type="url" name="callback_url" id="callback_url" class="form-control" value="{{ old('callback_url', $callbackUrl) }}" placeholder="https://your-domain.com/webhooks/sms">
                        </div>
                    </div>
                </div>

                <hr>
                <h5 class="mb-3">Termii</h5>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Base URL</label>
                            <input type="url" name="termii[base_url]" class="form-control" value="{{ old('termii.base_url', $termii['base_url'] ?? '') }}" placeholder="https://api.ng.termii.com">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Send Endpoint</label>
                            <input type="text" name="termii[endpoint]" class="form-control" value="{{ old('termii.endpoint', $termii['endpoint'] ?? '/api/sms/send') }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Balance Endpoint</label>
                            <input type="text" name="termii[balance_endpoint]" class="form-control" value="{{ old('termii.balance_endpoint', $termii['balance_endpoint'] ?? '') }}" placeholder="/api/get-balance">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>API Key</label>
                            <input type="text" name="termii[api_key]" class="form-control" value="{{ old('termii.api_key', $termii['api_key'] ?? '') }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Channel</label>
                            <input type="text" name="termii[channel]" class="form-control" value="{{ old('termii.channel', $termii['channel'] ?? 'generic') }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Type</label>
                            <input type="text" name="termii[type]" class="form-control" value="{{ old('termii.type', $termii['type'] ?? 'plain') }}">
                        </div>
                    </div>
                </div>

                <hr>
                <h5 class="mb-3">BulkSMSNigeria</h5>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Base URL</label>
                            <input type="url" name="bulksmsnigeria[base_url]" class="form-control" value="{{ old('bulksmsnigeria.base_url', $bulkSmsNigeria['base_url'] ?? 'https://www.bulksmsnigeria.com') }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Send Endpoint</label>
                            <input type="text" name="bulksmsnigeria[endpoint]" class="form-control" value="{{ old('bulksmsnigeria.endpoint', $bulkSmsNigeria['endpoint'] ?? '/api/v2/sms') }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Balance Endpoint</label>
                            <input type="text" name="bulksmsnigeria[balance_endpoint]" class="form-control" value="{{ old('bulksmsnigeria.balance_endpoint', $bulkSmsNigeria['balance_endpoint'] ?? '/api/v2/balance') }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>API Token</label>
                            <input type="text" name="bulksmsnigeria[api_token]" class="form-control" value="{{ old('bulksmsnigeria.api_token', $bulkSmsNigeria['api_token'] ?? '') }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Gateway</label>
                            <input type="text" name="bulksmsnigeria[gateway]" class="form-control" value="{{ old('bulksmsnigeria.gateway', $bulkSmsNigeria['gateway'] ?? '') }}" placeholder="direct-refund, direct-corporate, otp">
                        </div>
                    </div>
                </div>

                <hr>
                <h5 class="mb-3">Generic HTTP Provider</h5>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Base URL</label>
                            <input type="url" name="generic[base_url]" class="form-control" value="{{ old('generic.base_url', $generic['base_url'] ?? '') }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Send Endpoint</label>
                            <input type="text" name="generic[endpoint]" class="form-control" value="{{ old('generic.endpoint', $generic['endpoint'] ?? '') }}" placeholder="/send-sms">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Balance Endpoint</label>
                            <input type="text" name="generic[balance_endpoint]" class="form-control" value="{{ old('generic.balance_endpoint', $generic['balance_endpoint'] ?? '') }}" placeholder="/balance">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>API Key</label>
                            <input type="text" name="generic[api_key]" class="form-control" value="{{ old('generic.api_key', $generic['api_key'] ?? '') }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Auth Mode</label>
                            <select name="generic[auth_mode]" class="form-control select2">
                                @foreach (['bearer' => 'Bearer', 'header' => 'Header', 'body' => 'Body', 'none' => 'None'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('generic.auth_mode', $generic['auth_mode'] ?? 'bearer') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Auth Header Name</label>
                            <input type="text" name="generic[auth_header_name]" class="form-control" value="{{ old('generic.auth_header_name', $generic['auth_header_name'] ?? 'X-API-KEY') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Message Field</label>
                            <input type="text" name="generic[message_field]" class="form-control" value="{{ old('generic.message_field', $generic['message_field'] ?? 'message') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Phone Field</label>
                            <input type="text" name="generic[phone_field]" class="form-control" value="{{ old('generic.phone_field', $generic['phone_field'] ?? 'to') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Sender Field</label>
                            <input type="text" name="generic[sender_field]" class="form-control" value="{{ old('generic.sender_field', $generic['sender_field'] ?? 'from') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Callback Field</label>
                            <input type="text" name="generic[callback_field]" class="form-control" value="{{ old('generic.callback_field', $generic['callback_field'] ?? 'callback_url') }}">
                        </div>
                    </div>
                </div>

                <hr>
                <div class="row">
                    <div class="col-lg-5">
                        <div class="card bg-light h-100">
                            <div class="card-header">
                                <strong>Check Balance</strong>
                            </div>
                            <div class="card-body">
                                <p class="text-muted mb-3">Use the currently active provider to fetch available SMS credit.</p>
                                <form method="POST" action="{{ route('bulk-sms.settings.balance') }}">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-primary">
                                        <i class="fas fa-wallet mr-1"></i>
                                        Check SMS Balance
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-7 mt-3 mt-lg-0">
                        <div class="card bg-light h-100">
                            <div class="card-header">
                                <strong>Test Send</strong>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="{{ route('bulk-sms.settings.test-send') }}">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="test_phone">Test Phone</label>
                                                <input type="text" name="test_phone" id="test_phone" class="form-control" value="{{ old('test_phone') }}" placeholder="2348012345678" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="test_sender_id">Sender ID Override</label>
                                                <input type="text" name="test_sender_id" id="test_sender_id" class="form-control" value="{{ old('test_sender_id', $senderId) }}" placeholder="Optional">
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-group mb-0">
                                                <label for="test_message">Test Message</label>
                                                <textarea name="test_message" id="test_message" rows="3" class="form-control" required>{{ old('test_message', 'Test SMS from Oreoluwapo CT&CS.') }}</textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-3">
                                        <button type="submit" class="btn btn-outline-success">
                                            <i class="fas fa-paper-plane mr-1"></i>
                                            Send Test SMS
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Save SMS Settings</button>
            </div>
        </form>
    </div>
@endsection
