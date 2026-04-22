<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LoanDetail extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_DECLINED = 'declined';

    protected $fillable = [
        'applied_amount',
        'amount_repayed',
        'created_user_id',
        'loan_id',
        'borrower_id',
        'branch_id',
        'release_date',
        'due_date',
        'late_payment_penalties',
        'remarks',
        'interest_rate',
        'custom_fields',
        'interest',
        'status',
        'repayment_status',
        'transaction_id',
        'former_applied_amount',
        'former_total_payable',
        'former_amount_due',
        'former_balanace',
        'former_total_paid',
        'former_first_payment_date',
        'former_remarks',
        'former_interest_rate',
        'former_recent_added_amount',
        'former_custom_fields',
        'former_late_payment_penalties',
        'interest_week_interval',
        'decision_status',
        'attachment',
        'approved_at',
        'approved_by',
        'declined_at',
        'declined_by',
        'decline_reason',
    ];

    protected function casts(): array
    {
        return [
            'applied_amount' => 'decimal:2',
            'amount_repayed' => 'decimal:2',
            'late_payment_penalties' => 'decimal:2',
            'interest_rate' => 'decimal:2',
            'interest' => 'decimal:2',
            'status' => 'boolean',
            'repayment_status' => 'boolean',
            'release_date' => 'date',
            'due_date' => 'date',
            'approved_at' => 'datetime',
            'declined_at' => 'datetime',
            'custom_fields' => 'array',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function borrower(): BelongsTo
    {
        return $this->belongsTo(User::class, 'borrower_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function decliner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'declined_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(LoanPayment::class, 'loan_details_id');
    }

    public function disbursementTransaction(): HasOne
    {
        return $this->hasOne(Transaction::class, 'loan_details_id')
            ->where('tracking_id', 'loan')
            ->where('is_branch', true);
    }

    public function totalRepaymentMade(): float
    {
        $paymentsTotal = (float) $this->payments()->sum('repayment_amount');
        $storedTotal = (float) ($this->amount_repayed ?? 0);

        return round(max($paymentsTotal, $storedTotal), 2);
    }

    public function hasRepayments(): bool
    {
        return $this->payments()->exists() || (float) ($this->amount_repayed ?? 0) > 0;
    }

    public function canBeDeleted(): bool
    {
        if ($this->decision_status === self::STATUS_PENDING) {
            return true;
        }

        return $this->decision_status === self::STATUS_APPROVED && ! $this->hasRepayments();
    }

    public function canBeEdited(): bool
    {
        return in_array($this->decision_status, [self::STATUS_PENDING, self::STATUS_APPROVED], true);
    }
}
