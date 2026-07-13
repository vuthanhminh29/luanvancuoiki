<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnReason extends Model
{
    public $timestamps = false;

    protected $fillable = ['code', 'name', 'type', 'status'];

    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }
}
