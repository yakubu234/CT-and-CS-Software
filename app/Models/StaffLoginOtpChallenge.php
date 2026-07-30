<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffLoginOtpChallenge extends Model
{
    protected $fillable = [
        'user_id',
        'public_token_hash',
        'code_hash',
        'encrypted_code',
        'attempts',
        'expires_at',
        'last_sent_at',
        'consumed_at',
        'request_ip',
        'user_agent',
    ];

    protected $hidden = [
        'public_token_hash',
        'code_hash',
        'encrypted_code',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'expires_at' => 'datetime',
            'last_sent_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
