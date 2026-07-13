<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $fillable = [
        'title',
        'image_url',
        'link_url',
        'platform',
        'position',
        'priority',
        'start_at',
        'end_at',
        'status',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function scopeVisible($query, ?string $position = null)
    {
        return $query
            ->where('status', 'ACTIVE')
            ->when($position, fn ($query) => $query->where('position', $position))
            ->where('start_at', '<=', now())
            ->where(fn ($query) => $query->whereNull('end_at')->orWhere('end_at', '>=', now()))
            ->orderBy('priority');
    }

    public function getImageSrcAttribute(): string
    {
        $image = trim((string) $this->getRawOriginal('image_url'));

        if ($image === '') {
            return asset('img/banner/banner-main-1.jpg');
        }

        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
            return $image;
        }

        if (str_starts_with($image, 'upload/')) {
            return asset($image);
        }

        if (str_starts_with($image, 'banner/')) {
            return asset('upload/' . $image);
        }

        return asset('upload/banner/' . $image);
    }
}
