<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PostCategory extends Model
{
    protected $fillable = ['name', 'slug', 'status'];

    public function posts(): HasMany
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->hasMany(Post::class, 'category_id');
    }
}
