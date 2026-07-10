@extends('layouts.admin')

@section('title', 'Email Settings')
@section('page_title', 'Email Settings')

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            <h5 class="mb-2"><i class="icon fas fa-ban"></i> Please fix the highlighted fields.</h5>
            <ul class="mb-0 pl-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card card-outline card-primary mb-4">
        <div class="card-header">
            <h3 class="card-title">Fallback Mailer</h3>
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
            <div class="alert alert-light border mb-0">
                Campaign delivery uses the SMTP pool below. The fallback mailer is shown for reference only.
            </div>
        </div>
    </div>

    <div class="card card-outline card-primary mb-4">
        <div class="card-header">
            <h3 class="card-title">SMTP Rotation Pool</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('email.settings.smtp.store') }}" class="border rounded p-3 mb-4">
                @csrf
                <h4 class="h6 font-weight-bold mb-3">Add SMTP Account</h4>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="smtp-name">Name</label>
                        <input type="text" name="name" id="smtp-name" class="form-control" value="{{ old('name') }}" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="smtp-host">Host</label>
                        <input type="text" name="host" id="smtp-host" class="form-control" value="{{ old('host') }}" required>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="smtp-port">Port</label>
                        <input type="number" name="port" id="smtp-port" class="form-control" value="{{ old('port', 587) }}" required>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="smtp-encryption">Encryption</label>
                        <select name="encryption" id="smtp-encryption" class="form-control">
                            <option value="">None</option>
                            <option value="tls" @selected(old('encryption') === 'tls')>TLS</option>
                            <option value="ssl" @selected(old('encryption') === 'ssl')>SSL</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="smtp-username">Username</label>
                        <input type="text" name="username" id="smtp-username" class="form-control" value="{{ old('username') }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="smtp-password">Password</label>
                        <input type="password" name="password" id="smtp-password" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="smtp-hourly-limit">Hourly Limit</label>
                        <input type="number" min="1" name="hourly_limit" id="smtp-hourly-limit" class="form-control" value="{{ old('hourly_limit', 100) }}" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="smtp-from-address">From Address</label>
                        <input type="email" name="from_address" id="smtp-from-address" class="form-control" value="{{ old('from_address') }}" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="smtp-from-name">From Name</label>
                        <input type="text" name="from_name" id="smtp-from-name" class="form-control" value="{{ old('from_name') }}">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="smtp-is-active">Status</label>
                        <select name="is_active" id="smtp-is-active" class="form-control">
                            <option value="1" @selected(old('is_active', '1') === '1')>Active</option>
                            <option value="0" @selected(old('is_active') === '0')>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="smtp-paused-until">Pause Until</label>
                        <input type="datetime-local" name="paused_until" id="smtp-paused-until" class="form-control" value="{{ old('paused_until') }}">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus mr-1"></i>
                    Add SMTP Account
                </button>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                    <tr>
                        <th>Account</th>
                        <th>Host</th>
                        <th>From</th>
                        <th>Window Usage</th>
                        <th>Status</th>
                        <th style="width: 260px;">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($smtpAccounts as $account)
                        <tr>
                            <td>
                                <div class="font-weight-bold">{{ $account->name }}</div>
                                <small class="text-muted">{{ $account->username ?: 'No username' }}</small>
                            </td>
                            <td>{{ $account->host }}:{{ $account->port }} {{ $account->encryption ? strtoupper($account->encryption) : '' }}</td>
                            <td>
                                <div>{{ $account->from_address }}</div>
                                <small class="text-muted">{{ $account->from_name ?: 'Default name' }}</small>
                            </td>
                            <td>
                                <div>{{ number_format((int) $account->sent_in_window) }} / {{ number_format((int) $account->hourly_limit) }}</div>
                                <small class="text-muted">{{ $account->window_started_at ? 'Since ' . $account->window_started_at->format('d M Y h:i A') : 'Fresh window' }}</small>
                            </td>
                            <td>
                                <span class="badge badge-{{ $account->is_active ? 'success' : 'secondary' }}">{{ $account->is_active ? 'Active' : 'Inactive' }}</span>
                                @if ($account->paused_until && $account->paused_until->isFuture())
                                    <span class="badge badge-warning">Paused until {{ $account->paused_until->format('d M Y h:i A') }}</span>
                                @endif
                            </td>
                            <td>
                                <details>
                                    <summary class="btn btn-sm btn-outline-primary mb-2">Edit</summary>
                                    <form method="POST" action="{{ route('email.settings.smtp.update', $account) }}" class="border rounded p-2 mt-2">
                                        @csrf
                                        @method('PUT')
                                        <input type="text" name="name" class="form-control form-control-sm mb-2" value="{{ $account->name }}" required>
                                        <input type="text" name="host" class="form-control form-control-sm mb-2" value="{{ $account->host }}" required>
                                        <input type="number" name="port" class="form-control form-control-sm mb-2" value="{{ $account->port }}" required>
                                        <select name="encryption" class="form-control form-control-sm mb-2">
                                            <option value="">None</option>
                                            <option value="tls" @selected($account->encryption === 'tls')>TLS</option>
                                            <option value="ssl" @selected($account->encryption === 'ssl')>SSL</option>
                                        </select>
                                        <input type="text" name="username" class="form-control form-control-sm mb-2" value="{{ $account->username }}" placeholder="Username">
                                        <input type="password" name="password" class="form-control form-control-sm mb-2" placeholder="Leave blank to keep password">
                                        <input type="email" name="from_address" class="form-control form-control-sm mb-2" value="{{ $account->from_address }}" required>
                                        <input type="text" name="from_name" class="form-control form-control-sm mb-2" value="{{ $account->from_name }}" placeholder="From name">
                                        <input type="number" name="hourly_limit" class="form-control form-control-sm mb-2" value="{{ $account->hourly_limit }}" required>
                                        <input type="datetime-local" name="paused_until" class="form-control form-control-sm mb-2" value="{{ $account->paused_until ? $account->paused_until->format('Y-m-d\TH:i') : '' }}">
                                        <select name="is_active" class="form-control form-control-sm mb-2">
                                            <option value="1" @selected($account->is_active)>Active</option>
                                            <option value="0" @selected(! $account->is_active)>Inactive</option>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-primary">Save</button>
                                    </form>
                                </details>
                                <form method="POST" action="{{ route('email.settings.smtp.destroy', $account) }}" class="d-inline" onsubmit="return confirm('Delete this SMTP account?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No SMTP accounts have been added yet.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card card-outline card-primary mb-4">
        <div class="card-header">
            <h3 class="card-title">Email Pause Rules</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('email.settings.preferences.store') }}" class="border rounded p-3 mb-4">
                @csrf
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="preference-scope">Scope</label>
                        <select name="scope" id="preference-scope" class="form-control">
                            <option value="branch">Branch</option>
                            <option value="member">Selected Member</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3 preference-branch">
                        <label for="preference-branch-id">Branch</label>
                        <select name="branch_id" id="preference-branch-id" class="form-control select2">
                            <option value="">Choose branch</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3 preference-member d-none">
                        <label for="preference-user-id">Member</label>
                        <select name="user_id" id="preference-user-id" class="form-control select2">
                            <option value="">Choose member</option>
                            @foreach ($members as $member)
                                <option value="{{ $member->id }}">{{ $member->display_member_no ?: 'N/A' }} - {{ $member->name }} ({{ $member->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="preference-email-enabled">Email</label>
                        <select name="email_enabled" id="preference-email-enabled" class="form-control">
                            <option value="0">Paused</option>
                            <option value="1">Enabled</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="preference-paused-until">Pause Until</label>
                        <input type="datetime-local" name="paused_until" id="preference-paused-until" class="form-control">
                    </div>
                    <div class="col-12 mb-3">
                        <label for="preference-reason">Reason</label>
                        <input type="text" name="reason" id="preference-reason" class="form-control" placeholder="Optional note">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Save Pause Rule</button>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                    <tr>
                        <th>Scope</th>
                        <th>Status</th>
                        <th>Pause Until</th>
                        <th>Reason</th>
                        <th style="width: 90px;">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($preferences as $preference)
                        <tr>
                            <td>
                                @if ($preference->branch)
                                    Branch: {{ $preference->branch->name }}
                                @else
                                    Member: {{ $preference->user?->display_member_no ?: 'N/A' }} - {{ $preference->user?->name ?: 'N/A' }}
                                @endif
                            </td>
                            <td><span class="badge badge-{{ $preference->email_enabled ? 'success' : 'warning' }}">{{ $preference->email_enabled ? 'Enabled' : 'Paused' }}</span></td>
                            <td>{{ optional($preference->paused_until)->format('d M Y h:i A') ?: 'Until changed' }}</td>
                            <td>{{ $preference->reason ?: 'N/A' }}</td>
                            <td>
                                <form method="POST" action="{{ route('email.settings.preferences.destroy', $preference) }}" onsubmit="return confirm('Remove this email preference?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No branch or member pause rules have been added.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $preferences->links() }}</div>
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

@push('scripts')
    <script>
        (() => {
            const scope = document.getElementById('preference-scope');
            const branchWrap = document.querySelector('.preference-branch');
            const memberWrap = document.querySelector('.preference-member');

            const syncScope = () => {
                const member = scope?.value === 'member';
                branchWrap?.classList.toggle('d-none', member);
                memberWrap?.classList.toggle('d-none', ! member);
            };

            scope?.addEventListener('change', syncScope);
            syncScope();
        })();
    </script>
@endpush
