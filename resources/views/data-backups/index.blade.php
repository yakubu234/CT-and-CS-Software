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
                                <option value="pdf" @selected(old('format') === 'pdf')>PDF document (.pdf)</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-primary" type="submit"><i class="fas fa-download mr-1"></i> Generate & download</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card card-info">
                <div class="card-header"><h3 class="card-title">Automatic Google Drive backup</h3></div>
                <form id="automatic-backup-settings" method="POST" action="{{ route('data-backups.settings.update') }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="custom-control custom-switch mb-3">
                            <input type="checkbox" class="custom-control-input" id="enabled" name="enabled" value="1"
                                   @checked(old('enabled', $settings['enabled']))>
                            <label class="custom-control-label" for="enabled">Run automatically every 6 hours</label>
                        </div>

                        <div class="form-group">
                            <label>Formats</label>
                            @foreach (['xlsx' => 'Excel', 'pdf' => 'PDF'] as $format => $label)
                                <div class="custom-control custom-checkbox custom-control-inline">
                                    <input class="custom-control-input" type="checkbox" name="formats[]" value="{{ $format }}"
                                           id="auto-format-{{ $format }}" @checked(in_array($format, old('formats', $settings['formats']), true))>
                                    <label class="custom-control-label" for="auto-format-{{ $format }}">{{ $label }}</label>
                                </div>
                            @endforeach
                        </div>

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
                        <div class="form-group">
                            <label for="credentials">Google service-account JSON</label>
                            <input class="form-control-file" type="file" id="credentials" name="credentials" accept=".json,application/json">
                            @if ($settings['service_account_email'])
                                <small class="form-text text-success">Configured: {{ $settings['service_account_email'] }}</small>
                            @endif
                            <small class="form-text text-muted">Stored encrypted outside the public directory. Upload again only when changing credentials.</small>
                        </div>
                        <div class="form-group">
                            <label for="drive_folder_id">Destination Google Shared Drive folder ID</label>
                            <input class="form-control" type="text" id="drive_folder_id" name="drive_folder_id"
                                   value="{{ old('drive_folder_id', $settings['drive_folder_id']) }}" placeholder="The value after /folders/ in the Drive URL">
                            @if ($settings['service_account_email'])
                                <small class="form-text text-warning">
                                    Share this Shared Drive folder with <strong>{{ $settings['service_account_email'] }}</strong> as Content manager.
                                </small>
                            @endif
                            <small class="form-text text-muted">Google service accounts cannot own files. Use a folder inside a Google Workspace Shared Drive.</small>
                        </div>
                        <div class="form-group">
                            <label for="recipient_emails">Gmail recipients</label>
                            <textarea class="form-control" id="recipient_emails" name="recipient_emails" rows="3"
                                      placeholder="backup@example.com, auditor@gmail.com">{{ old('recipient_emails', implode(', ', $settings['recipient_emails'])) }}</textarea>
                            <small class="form-text text-muted">Separate addresses with commas, spaces, semicolons, or new lines. Each recipient receives read access and a Drive notification.</small>
                        </div>
                    </div>
                </form>
                    <div class="card-footer d-flex flex-wrap">
                        <button class="btn btn-info mr-2 mb-1" type="submit" form="automatic-backup-settings"><i class="fas fa-save mr-1"></i> Save automatic settings</button>
                        <form method="POST" action="{{ route('data-backups.drive.test') }}">
                            @csrf
                            <button class="btn btn-outline-secondary mb-1" type="submit"><i class="fab fa-google-drive mr-1"></i> Test Drive backup</button>
                        </form>
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
                    <tr>
                        <td>{{ $backup->created_at->format('d M Y, h:i A') }}</td>
                        <td>{{ ucfirst($backup->trigger) }}</td>
                        <td>{{ strtoupper($backup->format) }}</td>
                        <td>{{ collect($backup->modules)->map(fn ($key) => $modules[$key]['label'] ?? $key)->join(', ') }}</td>
                        <td>
                            <span class="badge badge-{{ $backup->status === 'completed' ? 'success' : ($backup->status === 'failed' ? 'danger' : 'warning') }}">
                                {{ ucfirst($backup->status) }}
                            </span>
                            @if ($backup->error_message)<small class="d-block text-danger">{{ $backup->error_message }}</small>@endif
                        </td>
                        <td>{{ $backup->file_size ? number_format($backup->file_size / 1024, 1).' KB' : '—' }}</td>
                        <td>{{ $backup->creator?->name ?? 'Scheduler' }}</td>
                        <td class="text-nowrap">
                            @if ($backup->status === 'completed')
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('data-backups.download', $backup) }}">Download</a>
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
