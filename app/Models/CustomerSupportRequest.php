<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerSupportRequest extends Model
{
    protected $fillable = [
        'user_id',
        'branch_id',
        'subject',
        'category',
        'message',
        'status',
        'admin_response',
        'resolved_at',
        'created_by',
        'recipient_user_id',
        'conversation_type',
        'priority',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
            'last_message_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id')->withTrashed();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportRequestMessage::class, 'support_request_id')->oldest('id');
    }
}
