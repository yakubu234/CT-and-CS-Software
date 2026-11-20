<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

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

    /**
     * The day this rule fires in the given month. A chosen day that the month does not
     * have (29-31) falls back to the month's last day, e.g. 31 -> 30 Apr, 28/29 Feb.
     */
    public function effectiveDayOfMonth(Carbon $date): int
    {
        $day = max(1, min(31, (int) ($this->day_of_month ?: 1)));

        return min($day, $date->daysInMonth);
    }

    public function scheduleLabel(): string
    {
        $time = $this->schedule_time ? substr($this->schedule_time, 0, 5) : null;

        if ($this->event === self::EVENT_MONTHLY_STATEMENT) {
            $day = (int) ($this->day_of_month ?: 1);
            $label = 'Day ' . $day . ($day > 28 ? ' (last day in shorter months)' : '');

            return $label . ($time ? ' at ' . $time : '');
        }

        if ($this->event === self::EVENT_BIRTHDAY) {
            return 'On birthday' . ($time ? ' at ' . $time : '');
        }

        return 'Instantly';
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
