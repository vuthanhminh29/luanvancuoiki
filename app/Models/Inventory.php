<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    public const CREATED_AT = null;

    protected $fillable = ['warehouse_id', 'variant_id', 'quantity', 'min_stock_level'];

    public function warehouse(): BelongsTo
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->belongsTo(Warehouse::class);
    }

    public function variant(): BelongsTo
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->belongsTo(ProductVariant::class);
    }
}
