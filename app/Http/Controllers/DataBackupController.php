<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDataBackupRequest;
use App\Http\Requests\UpdateDataBackupSettingsRequest;
use App\Models\DataBackup;
use App\Services\DataBackup\BackupSettingsService;
use App\Services\DataBackup\DataBackupService;
use App\Services\DataBackup\GoogleDriveBackupService;
use Illuminate\Http\RedirectResponse;
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

    public function store(StoreDataBackupRequest $request, DataBackupService $service): BinaryFileResponse
    {
        $backup = $service->create(
            $request->validated('modules'),
            $request->validated('format'),
            'manual',
            $request->user()->id
        );

        return response()->download(
            Storage::disk(config('data_backup.storage_disk'))->path($backup->storage_path),
            $backup->file_name
        );
    }

    public function download(DataBackup $dataBackup): BinaryFileResponse
    {
        abort_unless($dataBackup->status === 'completed' && $dataBackup->storage_path, 404);
        $disk = Storage::disk(config('data_backup.storage_disk'));
        abort_unless($disk->exists($dataBackup->storage_path), 404);

        return response()->download($disk->path($dataBackup->storage_path), $dataBackup->file_name);
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
                $configuration['formats'][0] ?? 'xlsx',
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
