<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SavingsProduct extends Model
{
    protected $fillable = [
        'name',
        'account_number_prefix',
        'starting_account_number',
        'currency_id',
        'interest_rate',
        'interest_method',
        'interest_period',
        'interest_posting_period',
        'min_bal_interest_rate',
        'allow_withdraw',
        'minimum_account_balance',
        'minimum_deposit_amount',
        'maintenance_fee',
        'maintenance_fee_posting_period',
        'status',
        'default_account',
        'type',
    ];

    public function accounts(): HasMany
    {
        return $this->hasMany(SavingsAccount::class);
    }
}
