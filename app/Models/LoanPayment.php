<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoanPayment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'loan_id',
        'paid_at',
        'late_penalties',
        'interest',
        'repayment_amount',
        'total_amount',
        'remarks',
        'user_id',
        'transaction_id',
        'repayment_id',
        'balance',
        'interest_rate',
        'is_interest_paid',
        'transation_type',
        'interest_paid',
        'applied_amount',
        'is_approved',
        'is_calculated',
        'total_outstanding',
        'loan_details_id',
        'interest_transaction_id',
        'outstanding_interest',
        'release_date',
        'former_carry_forward_id',
        'carry_forward',
        'carry_forward_by',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'date',
            'late_penalties' => 'decimal:2',
            'interest' => 'decimal:2',
            'repayment_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'is_approved' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function detail(): BelongsTo
    {
        return $this->belongsTo(LoanDetail::class, 'loan_details_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function principalTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function interestTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'interest_transaction_id');
    }
}
