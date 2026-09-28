<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDataBackupRequest;
use App\Http\Requests\UpdateDataBackupSettingsRequest;
use App\Models\DataBackup;
use App\Services\DataBackup\BackupSettingsService;
use App\Services\DataBackup\DataBackupService;
use App\Services\DataBackup\GoogleDriveBackupService;
use App\Services\DataBackup\GoogleDriveOAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DataBackupController extends Controller
{
    public function __construct()
    {
        $this->middleware('module:data-backups');
    }

    public function index(BackupSettingsService $settings)
    {
        return view('data-backups.index', [
            'modules' => config('data_backup.modules', []),
            'settings' => $settings->get(),
            'backups' => DataBackup::query()->with('creator')->latest()->paginate(20),
        ]);
    }

    public function store(StoreDataBackupRequest $request, DataBackupService $service): RedirectResponse
    {
        $backup = $service->queue(
            $request->validated('modules'),
            $request->validated('format'),
            'manual',
            $request->user()->id
        );

        return redirect()
            ->route('data-backups.index')
            ->with('success', "Backup request #{$backup->id} has been queued and should be ready in about 5 minutes.");
    }

    public function download(DataBackup $dataBackup): BinaryFileResponse
    {
        abort_unless(
            $dataBackup->status === 'completed'
            && ($dataBackup->trigger !== 'manual' || ! $dataBackup->downloaded_at)
            && $dataBackup->storage_path,
            404
        );
        $disk = Storage::disk(config('data_backup.storage_disk'));
        abort_unless($disk->exists($dataBackup->storage_path), 404);

        $response = response()->download($disk->path($dataBackup->storage_path), $dataBackup->file_name);

        if ($dataBackup->trigger === 'manual') {
            $dataBackup->update(['downloaded_at' => now()]);
            $response->deleteFileAfterSend(true);
        }

        return $response;
    }

    public function statuses(): JsonResponse
    {
        $backups = DataBackup::query()
            ->where('trigger', 'manual')
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (DataBackup $backup): array => [
                'id' => $backup->id,
                'status' => $backup->status,
                'queued_at' => $backup->queued_at?->toIso8601String(),
                'completed_at' => $backup->completed_at?->toIso8601String(),
                'downloaded_at' => $backup->downloaded_at?->toIso8601String(),
                'error_message' => $backup->error_message,
                'download_url' => $backup->status === 'completed' && ! $backup->downloaded_at
                    ? route('data-backups.download', $backup)
                    : null,
            ]);

        return response()->json([
            'server_time' => now()->toIso8601String(),
            'backups' => $backups,
        ]);
    }

    public function updateSettings(
        UpdateDataBackupSettingsRequest $request,
        BackupSettingsService $settings
    ): RedirectResponse {
        try {
            if ($request->boolean('enabled') && ! $settings->isGoogleConnected()) {
                return back()->withInput()->withErrors([
                    'google' => 'Connect a Google account before enabling automatic Drive backups.',
                ]);
            }

            $settings->update($request->validated());
        } catch (\Throwable $exception) {
            return back()->withInput()->withErrors(['google' => $exception->getMessage()]);
        }

        return back()->with('success', 'Automatic backup settings updated successfully.');
    }

    public function testDrive(
        GoogleDriveBackupService $drive
    ): RedirectResponse {
        $this->authorizeBackupManagement(request());

        try {
            $drive->testConnection();
        } catch (\Throwable $exception) {
            return back()->withErrors(['drive' => 'Google Drive test failed: ' . $exception->getMessage()]);
        }

        return back()->with('success', 'Google Drive connection tested successfully. The temporary test file was removed.');
    }

    public function connectGoogle(Request $request, GoogleDriveOAuthService $oauth): RedirectResponse
    {
        $this->authorizeBackupManagement($request);

        try {
            $state = Str::random(64);
            $request->session()->put('google_drive_oauth_state', $state);

            return redirect()->away($oauth->authorizationUrl($state));
        } catch (\Throwable $exception) {
            return redirect()->route('data-backups.index')->withErrors(['google' => $exception->getMessage()]);
        }
    }

    public function googleCallback(Request $request, GoogleDriveOAuthService $oauth): RedirectResponse
    {
        $this->authorizeBackupManagement($request);

        $expectedState = (string) $request->session()->pull('google_drive_oauth_state', '');
        $actualState = (string) $request->query('state', '');

        if ($expectedState === '' || $actualState === '' || ! hash_equals($expectedState, $actualState)) {
            return redirect()->route('data-backups.index')->withErrors([
                'google' => 'The Google authorization response could not be verified. Please connect again.',
            ]);
        }

        if ($request->filled('error')) {
            return redirect()->route('data-backups.index')->withErrors([
                'google' => 'Google Drive connection was cancelled or denied.',
            ]);
        }

        $code = (string) $request->query('code', '');

        if ($code === '') {
            return redirect()->route('data-backups.index')->withErrors([
                'google' => 'Google did not return an authorization code. Please connect again.',
            ]);
        }

        try {
            $oauth->connect($code);
        } catch (\Throwable $exception) {
            return redirect()->route('data-backups.index')->withErrors(['google' => $exception->getMessage()]);
        }

        return redirect()->route('data-backups.index')->with('success', 'Google Drive connected successfully.');
    }

    public function disconnectGoogle(Request $request, GoogleDriveOAuthService $oauth): RedirectResponse
    {
        $this->authorizeBackupManagement($request);
        $oauth->disconnect();

        return redirect()->route('data-backups.index')->with('success', 'Google Drive disconnected. Automatic backups were disabled.');
    }

    private function authorizeBackupManagement(Request $request): void
    {
        abort_unless($request->user()?->hasPermission('data-backups.manage'), 403);
    }
}
