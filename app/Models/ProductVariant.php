<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'sku',
        'color_id',
        'lens_size_id',
        'variant_price',
        'status',
    ];

    protected $casts = [
        'variant_price' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    public function lensSize(): BelongsTo
    {
        return $this->belongsTo(LensSize::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    public function getDisplayPriceAttribute(): float
    {
        return (float) ($this->variant_price ?: $this->product?->display_price ?: 0);
    }
}
