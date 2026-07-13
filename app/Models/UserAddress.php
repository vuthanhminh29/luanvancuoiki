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
        return $this->belongsTo(User::class);
    }

    public function getFullAddressAttribute(): string
    {
        return collect([
            $this->address_detail,
            $this->ward_name,
            $this->district_name,
            $this->province_name,
        ])->filter()->implode(', ');
    }
}
