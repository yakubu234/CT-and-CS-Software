<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmsAutomationRule extends Model
{
    public const EVENT_TRANSACTION_CREDIT = 'transaction_credit';
    public const EVENT_TRANSACTION_DEBIT = 'transaction_debit';
    public const EVENT_LOAN_APPROVED = 'loan_approved';
    public const EVENT_BIRTHDAY = 'birthday';
    public const EVENT_MONTHLY_STATEMENT = 'monthly_statement';

    protected $fillable = [
        'branch_id',
        'template_id',
        'name',
        'event',
        'status',
        'schedule_time',
        'day_of_month',
        'filters',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'filters' => 'array',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(SmsTemplate::class, 'template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SmsMessage::class, 'automation_rule_id');
    }
}
