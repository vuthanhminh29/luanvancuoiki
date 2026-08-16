<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReturnRequest extends Model
{
    public const CREATED_AT = 'requested_at';
    public const UPDATED_AT = null;

    protected $fillable = [
        'return_code',
        'order_id',
        'user_id',
        'type',
        'reason_id',
        'reason_detail',
        'status',
        'admin_note',
        'reviewed_by',
        'reviewed_at',
        'completed_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->belongsTo(User::class);
    }

    public function reason(): BelongsTo
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->belongsTo(ReturnReason::class, 'reason_id');
    }

    public function items(): HasMany
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->hasMany(ReturnRequestItem::class);
    }

    public function images(): HasMany
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->hasMany(ReturnRequestImage::class);
    }

    public function damageAssessments(): HasMany
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->hasMany(ReturnDamageAssessment::class);
    }
}
