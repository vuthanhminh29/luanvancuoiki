<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'image_url',
        'description',
        'status',
    ];

    public function products(): HasMany
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->hasMany(Product::class);
    }

    public function scopeActive($query)
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $query->where('status', 'ACTIVE');
    }

    public function getImageSrcAttribute(): string
    {
        // Luong: Gan ket qua xu ly vao bien $image.
        $image = trim((string) $this->getRawOriginal('image_url'));

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($image === '') {
            // Luong: Tra ve ket qua cuoi cung cua ham.
            return asset('upload/no-image.jpg');
        }

        // Luong: Tra ve ket qua cuoi cung cua ham.
        return asset(str_starts_with($image, 'upload/') ? $image : 'upload/' . $image);
    }
}
