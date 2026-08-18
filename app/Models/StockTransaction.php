<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransaction extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'transaction_code',
        'type',
        'source_warehouse_id',
        'target_warehouse_id',
        'related_order_id',
        'status',
        'expected_date',
        'note',
        'created_by',
        'confirmed_by',
        'confirmed_at',
    ];

    protected $casts = [
        'expected_date' => 'date',
        'confirmed_at' => 'datetime',
    ];

    public function sourceWarehouse(): BelongsTo
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->belongsTo(Warehouse::class, 'source_warehouse_id');
    }

    public function targetWarehouse(): BelongsTo
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->belongsTo(Warehouse::class, 'target_warehouse_id');
    }

    public function items(): HasMany
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->hasMany(StockTransactionItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
