<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDataBackupRequest;
use App\Http\Requests\UpdateDataBackupSettingsRequest;
use App\Models\DataBackup;
use App\Services\DataBackup\BackupSettingsService;
use App\Services\DataBackup\DataBackupService;
use App\Services\DataBackup\GoogleDriveBackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
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
            $settings->update(
                $request->safe()->except('credentials'),
                $request->file('credentials')
            );
        } catch (\Throwable $exception) {
            return back()->withInput()->withErrors(['credentials' => $exception->getMessage()]);
        }

        return back()->with('success', 'Automatic backup settings updated successfully.');
    }

    public function testDrive(
        BackupSettingsService $settings,
        DataBackupService $backupService,
        GoogleDriveBackupService $drive
    ): RedirectResponse {
        $configuration = $settings->get();

        try {
            $backup = $backupService->create(
                $configuration['modules'],
                $configuration['formats'][0] ?? 'csv',
                'test',
                request()->user()->id
            );
            $drive->upload($backup);
        } catch (\Throwable $exception) {
            return back()->withErrors(['drive' => 'Google Drive test failed: ' . $exception->getMessage()]);
        }

        return back()->with('success', 'Test backup uploaded and shared successfully.');
    }
}
