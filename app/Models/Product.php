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
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function frameShape(): BelongsTo
    {
        return $this->belongsTo(FrameShape::class);
    }

    public function frameMaterial(): BelongsTo
    {
        return $this->belongsTo(FrameMaterial::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function visibleReviews(): HasMany
    {
        return $this->reviews()->whereIn('status', ['VISIBLE', 'PENDING'])->latest();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    public function getDisplayPriceAttribute(): float
    {
        return (float) ($this->sale_price ?: $this->base_price);
    }

    public function getImageUrlAttribute(): string
    {
        $image = trim((string) $this->thumbnail_url);

        if ($image === '') {
            return asset('upload/no-image.jpg');
        }

        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
            return $image;
        }

        if (str_starts_with($image, 'upload/')) {
            return asset($image);
        }

        if (str_starts_with($image, 'anh_san_pham/')) {
            return asset('upload/' . $image);
        }

        return asset('upload/anh_san_pham/' . $image);
    }
}
