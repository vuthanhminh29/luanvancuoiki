<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    protected $fillable = ['name', 'logo_url', 'description', 'status'];

    public function products(): HasMany
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->hasMany(Product::class);
    }

    public function scopeActive($query)
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $query->where('status', 'ACTIVE');
    }
}
