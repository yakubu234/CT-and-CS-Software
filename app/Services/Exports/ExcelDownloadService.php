<?php

namespace App\Services\Exports;

use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExcelDownloadService
{
    public function __construct(
        protected Writer $writer,
    ) {
    }

    public function download(object $export, string $fileName, string $writerType = ExcelFormat::XLSX): BinaryFileResponse
    {
        $this->ensureDirectory(storage_path('app/exports'));
        $this->ensureDirectory(config('excel.temporary_files.local_path'));
        $this->cleanupOldFiles(storage_path('app/exports'));
        $this->cleanupOldFiles(config('excel.temporary_files.local_path'));

        $temporaryFile = $this->writer->export($export, $writerType);

        $downloadPath = storage_path('app/exports/' . $this->safeFileName($fileName));
        copy($temporaryFile->getLocalPath(), $downloadPath);

        return response()->download(
            $downloadPath,
            basename($downloadPath),
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]
        )->deleteFileAfterSend(true);
    }

    protected function ensureDirectory(string $path): void
    {
        if (! is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    protected function cleanupOldFiles(string $directory, int $olderThanHours = 24): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $cutoff = now()->subHours($olderThanHours)->getTimestamp();

        foreach (glob($directory . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
            if (! is_file($file)) {
                continue;
            }

            if (@filemtime($file) !== false && filemtime($file) < $cutoff) {
                @unlink($file);
            }
        }
    }

    protected function safeFileName(string $fileName): string
    {
        return preg_replace('/[\\\\\\/:*?"<>|]+/', ' ', $fileName) ?: 'report.xlsx';
    }
}
