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
        $image = ltrim(trim((string) $this->getRawOriginal('image_url')), '/');

        if ($image === '') {
            return asset('img/banner/banner-main-1.jpg');
        }

        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
            return $image;
        }

        if (str_starts_with($image, 'storage/')) {
            return asset($image);
        }

        if (str_starts_with($image, 'upload/')) {
            return file_exists(public_path($image))
                ? asset($image)
                : asset('storage/' . $image);
        }

        if (str_starts_with($image, 'banner/')) {
            $publicPath = 'upload/' . $image;
            $storagePath = 'upload/' . $image;

            return file_exists(public_path($publicPath))
                ? asset($publicPath)
                : asset('storage/' . $storagePath);
        }

        $publicPath = 'upload/banner/' . $image;
        $storagePath = 'upload/banner/' . $image;

        return file_exists(public_path($publicPath))
            ? asset($publicPath)
            : asset('storage/' . $storagePath);
    }
}
