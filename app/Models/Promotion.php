<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promotion extends Model
{
    protected $fillable = [
        'promotion_code',
        'name',
        'description',
        'discount_type',
        'discount_value',
        'max_discount_amount',
        'min_order_amount',
        'scope',
        'start_at',
        'end_at',
        'usage_limit',
        'usage_per_user',
        'used_count',
        'stackable',
        'status',
        'created_by',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'stackable' => 'boolean',
    ];

    public function orders(): HasMany
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->hasMany(Order::class);
    }

    public function discountFor(float $subtotal): float
    {
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($subtotal <= 0) {
            // Luong: Tra ve ket qua cuoi cung cua ham.
            return 0;
        }

        // Luong: Gan ket qua xu ly vao bien $discount.
        $discount = $this->discount_type === 'PERCENT'
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            ? $subtotal * ((float) $this->discount_value / 100)
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            : (float) $this->discount_value;

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($this->max_discount_amount !== null) {
            // Luong: Gan ket qua xu ly vao bien $discount.
            $discount = min($discount, (float) $this->max_discount_amount);
        }

        // Luong: Tra ve ket qua cuoi cung cua ham.
        return round(min($discount, $subtotal), 0);
    }
}
