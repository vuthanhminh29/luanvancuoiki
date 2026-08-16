<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnRequestItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'return_request_id',
        'order_item_id',
        'quantity',
        'exchange_variant_id',
        'condition_note',
    ];

    public function returnRequest(): BelongsTo
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->belongsTo(ReturnRequest::class);
    }

    public function orderItem(): BelongsTo
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->belongsTo(OrderItem::class);
    }
}
