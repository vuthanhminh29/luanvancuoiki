<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    protected $fillable = ['category_id', 'title', 'slug', 'thumbnail_url', 'summary', 'content', 'status', 'created_by', 'published_at'];

    protected $casts = ['published_at' => 'datetime'];

    public function scopePublished($query)
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $query->where('status', 'PUBLISHED')->latest('published_at');
    }

    public function category(): BelongsTo
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->belongsTo(PostCategory::class, 'category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getAuthorNameAttribute(): string
    {
        return (string) ($this->creator?->full_name ?: $this->creator?->email ?: 'ADMIN');
    }

    public function getImageUrlAttribute(): string
    {
        // Luong: Gan ket qua xu ly vao bien $image.
        $image = trim((string) $this->thumbnail_url);

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($image === '') {
            // Luong: Tra ve ket qua cuoi cung cua ham.
            return asset('img/blog/blog-1.jpg');
        }

        // Luong: Tra ve ket qua cuoi cung cua ham.
        return asset(str_starts_with($image, 'upload/') ? $image : 'upload/BaiViet/' . $image);
    }
}
