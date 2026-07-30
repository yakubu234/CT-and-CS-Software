<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataBackup extends Model
{
    protected $fillable = [
        'created_by',
        'trigger',
        'format',
        'modules',
        'status',
        'queued_at',
        'processing_started_at',
        'file_name',
        'storage_path',
        'file_size',
        'google_drive_file_id',
        'google_drive_url',
        'error_message',
        'completed_at',
        'downloaded_at',
    ];

    protected function casts(): array
    {
        return [
            'modules' => 'array',
            'completed_at' => 'datetime',
            'queued_at' => 'datetime',
            'processing_started_at' => 'datetime',
            'downloaded_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
