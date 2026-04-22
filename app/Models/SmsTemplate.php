<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmsTemplate extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'category',
        'description',
        'body',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(SmsCampaign::class, 'template_id');
    }

    public function automationRules(): HasMany
    {
        return $this->hasMany(SmsAutomationRule::class, 'template_id');
    }
}
