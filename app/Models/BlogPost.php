<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'status',
        'published_at',
        'meta_title',
        'meta_description',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopePublished($query)
    {
        return $query
            ->where('status', true)
            ->where(function ($builder): void {
                $builder
                    ->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    public function getImageUrlAttribute(): string
    {
        if (! $this->featured_image) {
            return asset('frontend/images/blogs/agro-farmer.jpeg');
        }

        if (Str::startsWith($this->featured_image, ['frontend/', 'http://', 'https://'])) {
            return Str::startsWith($this->featured_image, ['http://', 'https://'])
                ? $this->featured_image
                : asset($this->featured_image);
        }

        return asset('storage/' . ltrim($this->featured_image, '/'));
    }

    public function getExcerptTextAttribute(): string
    {
        $excerpt = trim((string) $this->excerpt);

        if ($excerpt !== '') {
            return $excerpt;
        }

        return Str::limit(
            preg_replace('/\s+/', ' ', strip_tags((string) $this->content)) ?: '',
            180
        );
    }

    public function getPublishedLabelAttribute(): string
    {
        return optional($this->published_at ?? $this->created_at)->format('M d, Y') ?? '';
    }
}
