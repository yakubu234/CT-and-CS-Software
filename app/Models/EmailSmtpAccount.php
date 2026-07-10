<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class EmailSmtpAccount extends Model
{
    protected $fillable = [
        'name',
        'host',
        'port',
        'encryption',
        'username',
        'password',
        'from_address',
        'from_name',
        'hourly_limit',
        'sent_in_window',
        'window_started_at',
        'paused_until',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'port' => 'integer',
            'hourly_limit' => 'integer',
            'sent_in_window' => 'integer',
            'window_started_at' => 'datetime',
            'paused_until' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function setPasswordAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $this->attributes['password'] = Crypt::encryptString($value);
    }

    public function getDecryptedPasswordAttribute(): ?string
    {
        if (empty($this->attributes['password'])) {
            return null;
        }

        return Crypt::decryptString($this->attributes['password']);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
