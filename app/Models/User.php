<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'last_name',
        'email',
        'password',
        'user_type',
        'role_id',
        'branch_id',
        'status',
        'profile_picture',
        'society_role',
        'society_exco',
        'date_added_as_exco',
        'former_exco',
        'date_removed_as_exco',
        'user_level',
        'branch_account',
        'is_verified',
        'two_factor_code',
        'two_factor_expires_at',
        'two_factor_code_count',
        'otp',
        'otp_expires_at',
        'otp_count',
        'provider',
        'provider_id',
        'signature',
        'member_no',
        'designation',
        'former_designation',
        'assigned_branch',
        'last_branch_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'society_exco' => 'boolean',
            'former_exco' => 'boolean',
            'branch_account' => 'boolean',
            'is_verified' => 'boolean',
            'email_verified_at' => 'datetime',
            'date_added_as_exco' => 'datetime',
            'date_removed_as_exco' => 'datetime',
            'two_factor_expires_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'last_branch_id' => 'integer',
        ];
    }

    protected function name(): Attribute
    {
        return Attribute::get(fn ($value): string => trim(($value ?? '') . ' ' . ($this->last_name ?? '')) ?: ($this->email ?: 'Staff User'));
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'user_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function savingsAccounts(): HasMany
    {
        return $this->hasMany(SavingsAccount::class);
    }

    public function detail(): HasOne
    {
        return $this->hasOne(UserDetail::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(MemberDocument::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class, 'borrower_id');
    }

    public function loanDetails(): HasMany
    {
        return $this->hasMany(LoanDetail::class, 'borrower_id');
    }

    public function smsMessages(): HasMany
    {
        return $this->hasMany(SmsMessage::class);
    }
}
