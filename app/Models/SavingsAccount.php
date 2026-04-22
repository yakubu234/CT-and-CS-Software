<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavingsAccount extends Model
{
    protected $fillable = [
        'account_number',
        'user_id',
        'savings_product_id',
        'status',
        'opening_balance',
        'balance',
        'description',
        'created_user_id',
        'updated_user_id',
        'is_branch_acount',
        'disabled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'opening_balance' => 'decimal:2',
            'balance' => 'decimal:2',
            'is_branch_acount' => 'boolean',
            'disabled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(SavingsProduct::class, 'savings_product_id');
    }
}
