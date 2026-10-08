<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberMerge extends Model
{
    protected $fillable = [
        'canonical_user_id',
        'merged_user_id',
        'primary_membership_id',
        'merged_by',
        'selected_email',
        'selected_mobile',
        'canonical_snapshot',
        'merged_snapshot',
        'records_moved',
    ];

    protected function casts(): array
    {
        return [
            'canonical_snapshot' => 'array',
            'merged_snapshot' => 'array',
            'records_moved' => 'array',
        ];
    }
}
