<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'id_prefix',
        'number_count',
        'prefix',
        'loan_count',
        'registration_number',
        'year_of_registration',
        'contact_email',
        'contact_phone',
        'address',
        'descriptions',
        'photo',
        'signature',
        'status',
        'branch_meeting_days',
        'branch_user_id',
    ];

    public function branchUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'branch_user_id');
    }

    public function excos(): HasMany
    {
        return $this->hasMany(User::class, 'branch_id')
            ->where('branch_account', false)
            ->where('society_exco', true);
    }

    public function formerExcos(): HasMany
    {
        return $this->hasMany(User::class, 'branch_id')
            ->where('branch_account', false)
            ->where('former_exco', true);
    }

    public function smsCampaigns(): HasMany
    {
        return $this->hasMany(SmsCampaign::class);
    }

    public function smsMessages(): HasMany
    {
        return $this->hasMany(SmsMessage::class);
    }
}
