<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    protected $fillable = [
        'warehouse_code',
        'name',
        'type',
        'capacity',
        'province_name',
        'district_name',
        'ward_name',
        'address_detail',
        'min_stock_level',
        'status',
    ];

    public function inventories(): HasMany
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->hasMany(Inventory::class);
    }

    public function scopeActive($query)
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $query->where('status', 'ACTIVE');
    }
}
