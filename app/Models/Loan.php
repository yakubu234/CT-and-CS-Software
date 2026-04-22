<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends Model
{
    protected $fillable = [
        'loan_id',
        'loan_product_id',
        'borrower_id',
        'first_payment_date',
        'release_date',
        'applied_amount',
        'total_payable',
        'due_date',
        'interest_rate',
        'interest',
        'total_paid',
        'late_payment_penalties',
        'attachment',
        'description',
        'remarks',
        'status',
        'approved_date',
        'approved_user_id',
        'created_user_id',
        'updated_user_id',
        'branch_id',
        'custom_fields',
        'repayment_status',
        'recent_added_amount',
        'amount_due',
        'balanace',
        'interest_calculation',
    ];

    protected function casts(): array
    {
        return [
            'first_payment_date' => 'date',
            'release_date' => 'date',
            'approved_date' => 'date',
            'custom_fields' => 'array',
        ];
    }

    public function borrower(): BelongsTo
    {
        return $this->belongsTo(User::class, 'borrower_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(LoanDetail::class)->latest('id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_user_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_user_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(LoanPayment::class)->latest('paid_at')->latest('id');
    }
}
