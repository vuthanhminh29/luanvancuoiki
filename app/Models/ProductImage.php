<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['product_id', 'variant_id', 'image_url', 'alt_text', 'sort_order', 'is_thumbnail'];

    protected $casts = ['is_thumbnail' => 'boolean'];

    public function product(): BelongsTo
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->belongsTo(Product::class);
    }

    public function getUrlAttribute(): string
    {
        // Luong: Gan ket qua xu ly vao bien $image.
        $image = trim((string) $this->image_url);

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($image === '') {
            // Luong: Tra ve ket qua cuoi cung cua ham.
            return asset('upload/no-image.jpg');
        }

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
            // Luong: Tra ve ket qua cuoi cung cua ham.
            return $image;
        }

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (str_starts_with($image, 'upload/')) {
            // Luong: Tra ve ket qua cuoi cung cua ham.
            return asset($image);
        }

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (str_starts_with($image, 'anh_san_pham/')) {
            // Luong: Tra ve ket qua cuoi cung cua ham.
            return asset('upload/' . $image);
        }

        // Luong: Tra ve ket qua cuoi cung cua ham.
        return asset('upload/anh_san_pham/' . $image);
    }
}
