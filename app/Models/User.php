<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'email',
        'password_hash',
        'full_name',
        'phone',
        'avatar_url',
        'gender',
        'date_of_birth',
        'provider',
        'google_id',
        'email_verified_at',
        'status',
        'failed_login_count',
        'last_failed_login_at',
        'locked_until',
        'last_login_at',
    ];

    protected $hidden = ['password_hash'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'date_of_birth' => 'date',
            'last_failed_login_at' => 'datetime',
            'locked_until' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    public function getAuthPassword(): string
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return (string) $this->password_hash;
    }

    public function getNameAttribute(): string
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return (string) ($this->full_name ?: $this->email);
    }

    public function addresses(): HasMany
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->hasMany(UserAddress::class);
    }

    public function orders(): HasMany
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->hasMany(Order::class);
    }

    public function productReviews(): HasMany
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->hasMany(ProductReview::class);
    }

    public function returnRequests(): HasMany
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->hasMany(ReturnRequest::class);
    }

    public function roles(): BelongsToMany
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->belongsToMany(Role::class, 'user_roles');
    }
}
