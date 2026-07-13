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
        return $this->belongsTo(Product::class);
    }

    public function getUrlAttribute(): string
    {
        $image = trim((string) $this->image_url);

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
