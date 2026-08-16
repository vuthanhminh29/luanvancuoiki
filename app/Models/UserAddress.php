<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAddress extends Model
{
    protected $fillable = [
        'user_id',
        'recipient_name',
        'phone',
        'province_code',
        'province_name',
        'district_code',
        'district_name',
        'ward_code',
        'ward_name',
        'address_detail',
        'is_default',
    ];

    protected $casts = ['is_default' => 'boolean'];

    public function user(): BelongsTo
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->belongsTo(User::class);
    }

    public function getFullAddressAttribute(): string
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return collect([
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $this->address_detail,
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $this->ward_name,
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $this->district_name,
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $this->province_name,
        ])->filter()->implode(', ');
    }
}
