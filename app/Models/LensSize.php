<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LensSize extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['name', 'bridge_width', 'temple_length', 'lens_width', 'lens_height'];
}
