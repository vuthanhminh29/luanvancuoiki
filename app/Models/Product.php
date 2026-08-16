<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'product_code',
        'name',
        'slug',
        'brand_id',
        'category_id',
        'frame_shape_id',
        'frame_material_id',
        'uv_protection',
        'description',
        'import_price',
        'base_price',
        'sale_price',
        'thumbnail_url',
        'status',
        'view_count',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
    ];

    public function category(): BelongsTo
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->belongsTo(Brand::class);
    }

    public function frameShape(): BelongsTo
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->belongsTo(FrameShape::class);
    }

    public function frameMaterial(): BelongsTo
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->belongsTo(FrameMaterial::class);
    }

    public function variants(): HasMany
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->hasMany(ProductVariant::class);
    }

    public function images(): HasMany
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function reviews(): HasMany
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->hasMany(ProductReview::class);
    }

    public function visibleReviews(): HasMany
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->reviews()->whereIn('status', ['VISIBLE', 'PENDING'])->latest();
    }

    public function scopeActive($query)
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $query->where('status', 'ACTIVE');
    }

    public function getDisplayPriceAttribute(): float
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return (float) ($this->sale_price ?: $this->base_price);
    }

    public function getImageUrlAttribute(): string
    {
        // Luong: Gan ket qua xu ly vao bien $image.
        $image = trim((string) $this->thumbnail_url);

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
