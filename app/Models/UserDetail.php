<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDetail extends Model
{
    protected $fillable = [
        'branch_id',
        'user_id',
        'country_code',
        'mobile',
        'date_of_birth',
        'business_name',
        'member_no',
        'gender',
        'city',
        'state',
        'zip',
        'address',
        'credit_source',
        'custom_fields',
        'occupation',
        'account_number',
        'account_name',
        'bank_name',
    ];

    protected function casts(): array
    {
        return [
            'custom_fields' => 'array',
            'date_of_birth' => 'date',
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
}
