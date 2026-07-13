<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransactionItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'stock_transaction_id',
        'variant_id',
        'ordered_quantity',
        'actual_quantity',
        'unit_cost',
        'note',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(StockTransaction::class, 'stock_transaction_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
