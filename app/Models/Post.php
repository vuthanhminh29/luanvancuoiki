<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    protected $fillable = ['category_id', 'title', 'slug', 'thumbnail_url', 'summary', 'content', 'status', 'published_at'];

    protected $casts = ['published_at' => 'datetime'];

    public function scopePublished($query)
    {
        return $query->where('status', 'PUBLISHED')->latest('published_at');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PostCategory::class, 'category_id');
    }

    public function getImageUrlAttribute(): string
    {
        $image = trim((string) $this->thumbnail_url);

        if ($image === '') {
            return asset('img/blog/blog-1.jpg');
        }

        return asset(str_starts_with($image, 'upload/') ? $image : 'upload/BaiViet/' . $image);
    }
}
