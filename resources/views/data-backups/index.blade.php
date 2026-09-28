@extends('layouts.admin')

@section('title', 'Data Backups')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-1">Data Backups</h1>
            <p class="text-muted mb-0">Export selected business modules or send scheduled copies to Google Drive.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Please correct the following:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div id="manual-backup-progress" class="alert alert-info d-none" role="status" aria-live="polite">
        <i class="fas fa-spinner fa-spin mr-1"></i>
        <span id="manual-backup-progress-message">Checking backup status…</span>
    </div>
    <div id="backup-schedule-summary" class="alert alert-light border" role="status" aria-live="polite">
        <i class="far fa-clock mr-1"></i> Loading backup schedule...
    </div>

    @if (auth()->user()->hasPermission('data-backups.manage'))
    <div class="row">
        <div class="col-lg-5">
            <div class="card card-primary">
                <div class="card-header"><h3 class="card-title">Manual export</h3></div>
                <form method="POST" action="{{ route('data-backups.store') }}">
                    @csrf
                    <div class="card-body">
                        <p class="text-muted">Each selected module includes all of its relevant business tables. Sensitive credential fields are excluded.</p>
                        <div class="form-group">
                            <label>Modules</label>
                            @foreach ($modules as $key => $module)
                                <div class="custom-control custom-checkbox mb-2">
                                    <input class="custom-control-input" type="checkbox" name="modules[]" value="{{ $key }}"
                                           id="manual-module-{{ $key }}" @checked(in_array($key, old('modules', []), true))>
                                    <label class="custom-control-label" for="manual-module-{{ $key }}">{{ $module['label'] }}</label>
                                    <small class="d-block text-muted">{{ implode(', ', $module['tables']) }}</small>
                                </div>
                            @endforeach
                        </div>
                        <div class="form-group">
                            <label for="format">Export format</label>
                            <select class="form-control" id="format" name="format" required>
                                <option value="xlsx">Excel workbook (.xlsx)</option>
                                <option value="csv">CSV archive (.zip)</option>
                                <option value="pdf" @selected(old('format') === 'pdf')>PDF document (.pdf)</option>
                                <option value="sql" @selected(old('format') === 'sql')>Importable SQL dump (.sql)</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-primary" type="submit"><i class="fas fa-clock mr-1"></i> Queue backup</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card card-info">
                <div class="card-header"><h3 class="card-title">Automatic Google Drive backup</h3></div>
                <form id="automatic-backup-settings" method="POST" action="{{ route('data-backups.settings.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="custom-control custom-switch mb-3">
                            <input type="checkbox" class="custom-control-input" id="enabled" name="enabled" value="1"
                                   @checked(old('enabled', $settings['enabled'])) @disabled($settings['google_connections'] === [])>
                            <label class="custom-control-label" for="enabled">Run automatically every 6 hours</label>
                        </div>
                        @if ($settings['google_connections'] === [])
                            <small class="form-text text-warning mb-3">Connect at least one Google Drive destination before enabling automatic backups.</small>
                        @endif

                        <div class="form-group">
                            <label>Formats</label>
                            @foreach (['xlsx' => 'Excel', 'csv' => 'CSV archive', 'pdf' => 'PDF', 'sql' => 'SQL dump'] as $format => $label)
                                <div class="custom-control custom-checkbox custom-control-inline">
                                    <input class="custom-control-input" type="checkbox" name="formats[]" value="{{ $format }}"
                                           id="auto-format-{{ $format }}" @checked(in_array($format, old('formats', $settings['formats']), true))>
                                    <label class="custom-control-label" for="auto-format-{{ $format }}">{{ $label }}</label>
                                </div>
                            @endforeach
                        </div>
                        <small class="form-text text-warning mb-3">SQL dumps are intended for full restoration and contain the selected tables exactly, including encrypted credentials and password hashes. Restrict access carefully.</small>

                        <div class="form-group">
                            <label>Modules</label>
                            <div class="row">
                                @foreach ($modules as $key => $module)
                                    <div class="col-md-6">
                                        <div class="custom-control custom-checkbox mb-2">
                                            <input class="custom-control-input" type="checkbox" name="modules[]" value="{{ $key }}"
                                                   id="auto-module-{{ $key }}" @checked(in_array($key, old('modules', $settings['modules']), true))>
                                            <label class="custom-control-label" for="auto-module-{{ $key }}">{{ $module['label'] }}</label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <hr>
                        <div class="form-group mb-4">
                            <label>Google Drive destinations</label>
                            @if ($settings['google_connections'] !== [])
                                @foreach ($settings['google_connections'] as $connection)
                                    <div class="alert alert-success d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <strong>{{ $connection['account_email'] ?: ($connection['account_name'] ?: 'Google account') }}</strong>
                                            <div class="small mt-1">
                                                Folder: {{ $connection['folder_name'] ?: 'System Backups' }}
                                            </div>
                                        </div>
                                        <button class="btn btn-sm btn-outline-danger" type="submit"
                                                form="disconnect-google-{{ $connection['id'] }}"
                                                aria-label="Disconnect {{ $connection['account_email'] ?: 'Google account' }}">
                                            <i class="fas fa-unlink mr-1"></i> Disconnect
                                        </button>
                                    </div>
                                @endforeach
                                <small class="form-text text-muted mb-2">Each backup is uploaded separately to every account listed above. OAuth credentials are encrypted.</small>
                            @else
                                <div class="alert alert-secondary mb-2">
                                    No Google Drive destination is connected.
                                </div>
                            @endif
                            <a class="btn btn-outline-danger" href="{{ route('data-backups.google.connect') }}">
                                <i class="fab fa-google-drive mr-1"></i> {{ $settings['google_connections'] === [] ? 'Connect Google Drive' : 'Add another Google account' }}
                            </a>
                        </div>
                        <div class="form-group">
                            <label for="recipient_emails">Optional shared viewers</label>
                            <textarea class="form-control" id="recipient_emails" name="recipient_emails" rows="3"
                                      placeholder="backup@example.com, auditor@gmail.com">{{ old('recipient_emails', implode(', ', $settings['recipient_emails'])) }}</textarea>
                            <small class="form-text text-muted">These addresses receive read access and a Drive notification; they are not separate backup destinations. Connect an account above to store an independent copy in that account.</small>
                        </div>
                    </div>
                </form>
                    <div class="card-footer d-flex flex-wrap">
                        <button class="btn btn-info mr-2 mb-1" type="submit" form="automatic-backup-settings"><i class="fas fa-save mr-1"></i> Save automatic settings</button>
                        @if ($settings['google_connections'] !== [])
                            <form method="POST" action="{{ route('data-backups.drive.test') }}" class="mr-2">
                                @csrf
                                <button class="btn btn-outline-secondary mb-1" type="submit"><i class="fab fa-google-drive mr-1"></i> Test connection</button>
                            </form>
                            <form method="POST" action="{{ route('data-backups.drive.run-now') }}" class="mr-2">
                                @csrf
                                <button class="btn btn-success mb-1" type="submit"><i class="fas fa-cloud-upload-alt mr-1"></i> Back up to Drive now</button>
                            </form>
                            @foreach ($settings['google_connections'] as $connection)
                                <form id="disconnect-google-{{ $connection['id'] }}" method="POST"
                                      action="{{ route('data-backups.google.disconnect', $connection['id']) }}"
                                      onsubmit="return confirm('Disconnect this Google Drive destination?');">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            @endforeach
                        @endif
                    </div>
            </div>
        </div>
    </div>
    @endif

    <div class="card">
        <div class="card-header"><h3 class="card-title">Backup history</h3></div>
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead><tr><th>Date</th><th>Trigger</th><th>Format</th><th>Modules</th><th>Status</th><th>Size</th><th>Created by</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse ($backups as $backup)
                    <tr data-backup-id="{{ $backup->id }}">
                        <td>{{ $backup->created_at->format('d M Y, h:i A') }}</td>
                        <td>{{ match ($backup->trigger) {
                            'manual_drive' => 'Drive now',
                            'automatic' => 'Automatic Drive',
                            default => ucfirst($backup->trigger),
                        } }}</td>
                        <td>{{ strtoupper($backup->format) }}</td>
                        <td>{{ collect($backup->modules)->map(fn ($key) => $modules[$key]['label'] ?? $key)->join(', ') }}</td>
                        <td class="backup-status-cell">
                            <span class="backup-status-badge badge badge-{{ $backup->status === 'completed' ? 'success' : ($backup->status === 'failed' ? 'danger' : ($backup->status === 'uploading' ? 'info' : 'warning')) }}">
                                {{ ucfirst($backup->status) }}
                            </span>
                            <small class="backup-status-message d-block {{ $backup->error_message ? 'text-danger' : 'text-muted' }}">
                                @if ($backup->downloaded_at)
                                    Downloaded; server copy deleted.
                                @elseif ($backup->error_message)
                                    {{ $backup->error_message }}
                                @endif
                            </small>
                        </td>
                        <td>{{ $backup->file_size ? number_format($backup->file_size / 1024, 1).' KB' : '—' }}</td>
                        <td>{{ $backup->creator?->name ?? 'Scheduler' }}</td>
                        <td class="backup-actions text-nowrap">
                            @if ($backup->status === 'completed' && ! $backup->downloaded_at)
                                <a class="backup-download btn btn-sm btn-outline-primary" href="{{ route('data-backups.download', $backup) }}">Download once</a>
                            @endif
                            @if ($backup->google_drive_url)
                                <a class="btn btn-sm btn-outline-success" href="{{ $backup->google_drive_url }}" target="_blank" rel="noopener">Drive</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No backups have been generated yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($backups->hasPages())<div class="card-footer">{{ $backups->links() }}</div>@endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const statusUrl = @json(route('data-backups.statuses'));
    const banner = document.getElementById('manual-backup-progress');
    const bannerMessage = document.getElementById('manual-backup-progress-message');
    const scheduleSummary = document.getElementById('backup-schedule-summary');
    let serverOffset = 0;
    let backupStates = [];
    let nextAutomaticRunAt = null;
    let automaticEnabled = false;

    function remainingSeconds(queuedAt) {
        const elapsed = Math.max(0, (Date.now() + serverOffset - new Date(queuedAt).getTime()) / 1000);
        let estimate = 300;

        if (elapsed >= estimate) {
            estimate += Math.ceil((elapsed - estimate + 1) / 120) * 120;
        }

        return Math.max(0, Math.ceil(estimate - elapsed));
    }

    function duration(seconds) {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor(seconds / 60);
        const remainder = seconds % 60;
        return hours > 0
            ? `${hours}h ${String(minutes % 60).padStart(2, '0')}m`
            : `${minutes}m ${String(remainder).padStart(2, '0')}s`;
    }

    function render() {
        const active = backupStates.find(backup => ['uploading', 'processing', 'generated', 'pending'].includes(backup.status));

        if (active) {
            const remaining = active.estimated_seconds_remaining || remainingSeconds(active.queued_at);
            let prefix = `Backup #${active.id} is queued`;
            if (active.status === 'processing') prefix = `Backup #${active.id} (${active.format.toUpperCase()}) is generating`;
            if (active.status === 'generated') prefix = `Backup #${active.id} is waiting to upload`;
            if (active.status === 'uploading') {
                prefix = `Backup #${active.id} is uploading to Drive (${active.drive_completed_count}/${active.drive_destination_count} destinations)`;
            }
            banner.classList.remove('d-none', 'alert-success', 'alert-danger');
            banner.classList.add('alert-info');
            bannerMessage.textContent = `${prefix}. Estimated time remaining: ${duration(remaining)}.`;
        } else {
            banner.classList.add('d-none');
        }

        if (nextAutomaticRunAt) {
            const seconds = Math.max(0, Math.ceil((new Date(nextAutomaticRunAt).getTime() - Date.now() - serverOffset) / 1000));
            scheduleSummary.innerHTML = `<i class="far fa-clock mr-1"></i> Next automatic backup: ${new Date(nextAutomaticRunAt).toLocaleString()} (in ${duration(seconds)}). Estimates use recent backup durations.`;
        } else if (! automaticEnabled) {
            scheduleSummary.innerHTML = '<i class="far fa-clock mr-1"></i> Automatic Drive backups are disabled. You can still use “Back up to Drive now”.';
        }

        backupStates.forEach(function (backup) {
            const row = document.querySelector(`[data-backup-id="${backup.id}"]`);
            if (! row) return;

            const badge = row.querySelector('.backup-status-badge');
            const message = row.querySelector('.backup-status-message');
            const actions = row.querySelector('.backup-actions');
            badge.textContent = backup.status.charAt(0).toUpperCase() + backup.status.slice(1);
            badge.className = `backup-status-badge badge badge-${backup.status === 'completed' ? 'success' : (backup.status === 'failed' ? 'danger' : (backup.status === 'uploading' ? 'info' : 'warning'))}`;

            if (['pending', 'processing', 'generated', 'uploading'].includes(backup.status)) {
                message.className = 'backup-status-message d-block text-muted';
                if (backup.status === 'pending') {
                    message.textContent = `Estimated start: ${new Date(backup.estimated_start_at).toLocaleString()}; duration about ${duration(backup.estimated_seconds_remaining)}.`;
                } else if (backup.status === 'uploading') {
                    message.textContent = `Uploading: ${backup.drive_completed_count}/${backup.drive_destination_count} destinations; about ${duration(backup.estimated_seconds_remaining)} remaining.`;
                } else {
                    message.textContent = `${backup.status === 'generated' ? 'Waiting to upload' : 'Generating'}; about ${duration(backup.estimated_seconds_remaining)} remaining.`;
                }
            } else if (backup.status === 'failed') {
                message.className = 'backup-status-message d-block text-danger';
                message.textContent = backup.error_message || 'Backup generation failed.';
            } else if (backup.downloaded_at) {
                message.className = 'backup-status-message d-block text-muted';
                message.textContent = 'Downloaded; server copy deleted.';
                row.querySelector('.backup-download')?.remove();
            } else {
                message.className = 'backup-status-message d-block text-success';
                message.textContent = 'Backup is now available. Click to download.';
                if (backup.download_url && ! row.querySelector('.backup-download')) {
                    const link = document.createElement('a');
                    link.className = 'backup-download btn btn-sm btn-outline-primary';
                    link.href = backup.download_url;
                    link.textContent = 'Download once';
                    actions.prepend(link);
                }
            }
        });
    }

    async function refreshStatuses() {
        try {
            const response = await fetch(statusUrl, {
                headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                credentials: 'same-origin'
            });
            if (! response.ok) return;
            const payload = await response.json();
            serverOffset = new Date(payload.server_time).getTime() - Date.now();
            backupStates = payload.backups;
            automaticEnabled = payload.automatic_enabled;
            nextAutomaticRunAt = payload.next_automatic_run_at;
            render();
        } catch (error) {
            // Keep the last known countdown visible during a temporary network error.
        }
    }

    refreshStatuses();
    setInterval(render, 1000);
    setInterval(refreshStatuses, 10000);
});
</script>
@endpush
