<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'trans_date',
        'savings_account_id',
        'charge',
        'amount',
        'gateway_amount',
        'dr_cr',
        'type',
        'attachment',
        'method',
        'status',
        'note',
        'description',
        'loan_id',
        'ref_id',
        'parent_id',
        'gateway_id',
        'created_user_id',
        'updated_user_id',
        'branch_id',
        'transaction_details',
        'tracking_id',
        'detail_id',
        'is_branch',
        'loan_details_id',
        'loan_repayment_id',
        'batch_id',
    ];

    protected function casts(): array
    {
        return [
            'trans_date' => 'datetime',
            'amount' => 'decimal:2',
            'charge' => 'decimal:2',
            'gateway_amount' => 'decimal:2',
            'status' => 'integer',
            'is_branch' => 'boolean',
            'transaction_details' => 'array',
            'deleted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(SavingsAccount::class, 'savings_account_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function mirrors(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_user_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_user_id');
    }
}
