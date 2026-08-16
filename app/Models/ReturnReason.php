<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnReason extends Model
{
    public $timestamps = false;

    protected $fillable = ['code', 'name', 'type', 'status'];

    public function scopeActive($query)
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $query->where('status', 'ACTIVE');
    }
}
