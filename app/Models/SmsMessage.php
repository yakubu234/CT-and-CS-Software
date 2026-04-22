<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SmsMessage extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'campaign_id',
        'automation_rule_id',
        'user_id',
        'branch_id',
        'phone',
        'recipient_name',
        'message',
        'provider',
        'sender_id',
        'status',
        'external_id',
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
        return $this->belongsTo(SmsCampaign::class, 'campaign_id');
    }

    public function automationRule(): BelongsTo
    {
        return $this->belongsTo(SmsAutomationRule::class, 'automation_rule_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }
}
