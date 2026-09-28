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

    public function statuses(BackupSettingsService $settings): JsonResponse
    {
        $averageDuration = (int) round(DataBackup::query()
            ->whereNotNull('processing_started_at')
            ->whereNotNull('completed_at')
            ->latest('completed_at')
            ->limit(20)
            ->get()
            ->avg(fn (DataBackup $backup): int => max(1, $backup->processing_started_at->diffInSeconds($backup->completed_at))));
        $averageDuration = min(3600, max(60, $averageDuration ?: 300));
        $estimatedCursor = now();

        $records = DataBackup::query()
            ->latest()
            ->limit(50)
            ->get();
        $activeStatuses = ['pending', 'processing', 'generated', 'uploading'];
        $backups = $records
            ->filter(fn (DataBackup $backup) => in_array($backup->status, $activeStatuses, true))
            ->sortBy('queued_at')
            ->concat($records->reject(fn (DataBackup $backup) => in_array($backup->status, $activeStatuses, true)))
            ->map(function (DataBackup $backup) use (&$estimatedCursor, $averageDuration): array {
                $active = in_array($backup->status, ['pending', 'processing', 'generated', 'uploading'], true);
                $estimatedStart = $backup->processing_started_at ?: ($active ? $estimatedCursor->copy() : null);
                $elapsed = $backup->processing_started_at
                    ? $backup->processing_started_at->diffInSeconds(now())
                    : 0;
                $remaining = $active ? max(10, $averageDuration - $elapsed) : 0;

                if ($backup->status === 'uploading' && $backup->drive_destination_count > 0) {
                    $remaining = max(10, (int) round(
                        ($averageDuration * .35) *
                        (($backup->drive_destination_count - $backup->drive_completed_count) / $backup->drive_destination_count)
                    ));
                }

                if ($active) {
                    $estimatedCursor->addSeconds($remaining);
                }

                return [
                    'id' => $backup->id,
                    'trigger' => $backup->trigger,
                    'format' => $backup->format,
                    'status' => $backup->status,
                    'queued_at' => $backup->queued_at?->toIso8601String(),
                    'scheduled_for' => $backup->scheduled_for?->toIso8601String(),
                    'estimated_start_at' => $estimatedStart?->toIso8601String(),
                    'estimated_seconds_remaining' => $remaining,
                    'upload_started_at' => $backup->upload_started_at?->toIso8601String(),
                    'drive_destination_count' => $backup->drive_destination_count,
                    'drive_completed_count' => $backup->drive_completed_count,
                    'completed_at' => $backup->completed_at?->toIso8601String(),
                    'downloaded_at' => $backup->downloaded_at?->toIso8601String(),
                    'error_message' => $backup->error_message,
                    'download_url' => $backup->status === 'completed' && ! $backup->downloaded_at
                        ? route('data-backups.download', $backup)
                        : null,
                ];
            })->values();

        $configuration = $settings->get();
        $automaticEnabled = $configuration['enabled'] && $settings->isGoogleConnected();
        $hoursUntilNextRun = 6 - (now()->hour % 6);
        $nextAutomaticRun = $automaticEnabled
            ? now()->addHours($hoursUntilNextRun)->startOfHour()
            : null;

        return response()->json([
            'server_time' => now()->toIso8601String(),
            'average_duration_seconds' => $averageDuration,
            'automatic_enabled' => $automaticEnabled,
            'next_automatic_run_at' => $nextAutomaticRun?->toIso8601String(),
            'backups' => $backups,
        ]);
    }

    public function runDriveNow(
        Request $request,
        BackupSettingsService $settings,
        DataBackupService $backups
    ): RedirectResponse {
        $this->authorizeBackupManagement($request);

        if (! $settings->isGoogleConnected()) {
            return back()->withErrors(['drive' => 'Connect at least one Google Drive destination first.']);
        }

        $configuration = $settings->get();

        foreach ($configuration['formats'] as $format) {
            $backups->queue(
                $configuration['modules'],
                $format,
                'manual_drive',
                $request->user()->id
            );
        }

        return back()->with('success', count($configuration['formats']) . ' Drive backup job(s) queued for immediate processing.');
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

        return back()->with('success', 'All Google Drive destinations were tested successfully. Temporary test files were removed.');
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

        return redirect()->route('data-backups.index')->with('success', 'Google Drive destination connected successfully.');
    }

    public function disconnectGoogle(
        Request $request,
        string $connectionId,
        GoogleDriveOAuthService $oauth
    ): RedirectResponse
    {
        $this->authorizeBackupManagement($request);

        try {
            $oauth->disconnect($connectionId);
        } catch (\Throwable $exception) {
            return redirect()->route('data-backups.index')->withErrors(['google' => $exception->getMessage()]);
        }

        return redirect()->route('data-backups.index')->with('success', 'Google Drive destination disconnected.');
    }

    private function authorizeBackupManagement(Request $request): void
    {
        abort_unless($request->user()?->hasPermission('data-backups.manage'), 403);
    }
}
