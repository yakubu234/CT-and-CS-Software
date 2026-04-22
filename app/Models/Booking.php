<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    protected $table = 'bookings';

    protected $fillable = [
        'bookign_id',
        'user_id',
        'date_start',
        'time_start',
        'time_end',
        'event_type',
        'number_of_guest',
        'message',
        'remarks',
        'status',
        'admin_id',
        'tax',
        'total_amount',
        'discount',
        'date_of_application',
        'customer_contact_person_phone',
        'customer_contact_person_fullname',
        'customer_address',
        'customer_phone',
        'customer_fullname',
        'customer_email',
        'payment_status',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'booking_id', 'bookign_id');
    }
}
