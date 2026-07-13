<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnDamageAssessment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'return_request_id',
        'part_code',
        'part_name',
        'damage_percent',
        'damage_level',
        'description',
        'assessed_by',
        'assessed_at',
    ];

    protected $casts = [
        'assessed_at' => 'datetime',
    ];

    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(ReturnRequest::class);
    }
}
