<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EmailMessage extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'campaign_id',
        'user_id',
        'branch_id',
        'email',
        'recipient_name',
        'subject',
        'body',
        'mailer',
        'smtp_account_id',
        'status',
        'error_message',
        'related_type',
        'related_id',
        'reference_key',
        'scheduled_for',
        'processed_at',
        'sent_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_for' => 'datetime',
            'processed_at' => 'datetime',
            'sent_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(EmailCampaign::class, 'campaign_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function smtpAccount(): BelongsTo
    {
        return $this->belongsTo(EmailSmtpAccount::class, 'smtp_account_id');
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }
}
