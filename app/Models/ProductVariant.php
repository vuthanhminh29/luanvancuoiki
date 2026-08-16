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
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->belongsTo(Product::class);
    }

    public function color(): BelongsTo
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->belongsTo(Color::class);
    }

    public function lensSize(): BelongsTo
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->belongsTo(LensSize::class);
    }

    public function scopeActive($query)
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $query->where('status', 'ACTIVE');
    }

    public function getDisplayPriceAttribute(): float
    {
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($this->product && $this->product->sale_price) {
            // Luong: Tra ve ket qua cuoi cung cua ham.
            return (float) $this->product->sale_price;
        }

        // Luong: Tra ve ket qua cuoi cung cua ham.
        return (float) ($this->variant_price ?: $this->product?->base_price ?: 0);
    }
}
