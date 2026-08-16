<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LensOption extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'price',
        'icon',
        'groups',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'groups' => 'array',
        'price' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $query->where('status', 'ACTIVE');
    }
}
