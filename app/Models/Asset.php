<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use SoftDeletes;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_UNDER_REPAIR = 'under_repair';
    public const STATUS_DISPOSED = 'disposed';

    protected $fillable = [
        'branch_id',
        'name',
        'category',
        'purchase_date',
        'purchase_cost',
        'supplier',
        'status',
        'depreciation_rate',
        'depreciation_note',
        'disposed_at',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'purchase_cost' => 'decimal:2',
            'depreciation_rate' => 'decimal:2',
            'disposed_at' => 'date',
            'deleted_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
