<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomField extends Model
{
    use HasFactory;

    public const TYPE_TEXT = 'text';
    public const TYPE_NUMBER = 'number';
    public const TYPE_SELECT = 'select';
    public const TYPE_TEXTAREA = 'textarea';
    public const TYPE_FILE = 'file';

    protected $fillable = [
        'field_name',
        'field_type',
        'default_value',
        'options',
        'field_width',
        'max_size',
        'is_required',
        'table',
        'allow_for_signup',
        'allow_to_list_view',
        'status',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'allow_for_signup' => 'boolean',
            'allow_to_list_view' => 'boolean',
        ];
    }

    public function scopeForUsers($query)
    {
        return $query->where('table', 'users');
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function optionsList(): array
    {
        if (! $this->options) {
            return [];
        }

        return collect(preg_split('/[\r\n,]+/', $this->options) ?: [])
            ->map(static fn (string $option): string => trim($option))
            ->filter()
            ->values()
            ->all();
    }
}
