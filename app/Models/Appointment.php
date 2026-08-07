<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    protected $fillable = [
        'user_id',
        'code',
        'service_code',
        'service_name',
        'price',
        'appointment_date',
        'appointment_time',
        'customer_name',
        'customer_phone',
        'customer_email',
        'note',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'appointment_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
