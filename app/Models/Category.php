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
        return $this->hasMany(Product::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    public function getImageSrcAttribute(): string
    {
        $image = trim((string) $this->getRawOriginal('image_url'));

        if ($image === '') {
            return asset('upload/no-image.jpg');
        }

        return asset(str_starts_with($image, 'upload/') ? $image : 'upload/' . $image);
    }
}
