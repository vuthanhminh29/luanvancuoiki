<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Color extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['name', 'code', 'hex_code'];
}
