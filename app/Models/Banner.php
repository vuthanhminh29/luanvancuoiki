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
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $query
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->where('status', 'ACTIVE')
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->when($position, fn ($query) => $query->where('position', $position))
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->where('start_at', '<=', now())
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->where(fn ($query) => $query->whereNull('end_at')->orWhere('end_at', '>=', now()))
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            ->orderBy('priority');
    }

    public function getImageSrcAttribute(): string
    {
        // Luong: Gan ket qua xu ly vao bien $image.
        $image = ltrim(trim((string) $this->getRawOriginal('image_url')), '/');

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($image === '') {
            // Luong: Tra ve ket qua cuoi cung cua ham.
            return asset('img/banner/banner-main-1.jpg');
        }

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
            // Luong: Tra ve ket qua cuoi cung cua ham.
            return $image;
        }

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (str_starts_with($image, 'storage/')) {
            // Luong: Tra ve ket qua cuoi cung cua ham.
            return asset($image);
        }

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (str_starts_with($image, 'upload/')) {
            // Luong: Tra ve ket qua cuoi cung cua ham.
            return file_exists(public_path($image))
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                ? asset($image)
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                : asset('storage/' . $image);
        }

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (str_starts_with($image, 'banner/')) {
            // Luong: Gan ket qua xu ly vao bien $publicPath.
            $publicPath = 'upload/' . $image;
            // Luong: Gan ket qua xu ly vao bien $storagePath.
            $storagePath = 'upload/' . $image;

            // Luong: Tra ve ket qua cuoi cung cua ham.
            return file_exists(public_path($publicPath))
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                ? asset($publicPath)
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                : asset('storage/' . $storagePath);
        }

        // Luong: Gan ket qua xu ly vao bien $publicPath.
        $publicPath = 'upload/banner/' . $image;
        // Luong: Gan ket qua xu ly vao bien $storagePath.
        $storagePath = 'upload/banner/' . $image;

        // Luong: Tra ve ket qua cuoi cung cua ham.
        return file_exists(public_path($publicPath))
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            ? asset($publicPath)
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            : asset('storage/' . $storagePath);
    }
}
