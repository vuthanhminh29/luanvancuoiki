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
        return $this->hasMany(Order::class);
    }

    public function discountFor(float $subtotal): float
    {
        if ($subtotal <= 0) {
            return 0;
        }

        $discount = $this->discount_type === 'PERCENT'
            ? $subtotal * ((float) $this->discount_value / 100)
            : (float) $this->discount_value;

        if ($this->max_discount_amount !== null) {
            $discount = min($discount, (float) $this->max_discount_amount);
        }

        return round(min($discount, $subtotal), 0);
    }
}
