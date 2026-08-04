<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    // CÃ¡c thÃ´ng tin Ä‘Æ¡n hÃ ng Ä‘Æ°á»£c phÃ©p lÆ°u tá»« checkout hoáº·c admin cáº­p nháº­t.
    protected $fillable = [
        'order_code',
        'user_id',
        'address_id',
        'recipient_name',
        'recipient_phone',
        'shipping_address',
        'payment_method',
        'payment_status',
        'status',
        'subtotal_amount',
        'discount_amount',
        'shipping_fee',
        'total_amount',
        'promotion_id',
        'note',
        'cancel_confirmation_token_hash',
        'cancel_reason',
        'cancel_requested_at',
        'cancel_confirmed_at',
    ];

    // Ã‰p cÃ¡c cá»™t tiá»n vá» decimal vÃ  thá»i gian giao hÃ ng vá» datetime.
    protected $casts = [
        'subtotal_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'delivered_at' => 'datetime',
        'cancel_requested_at' => 'datetime',
        'cancel_confirmed_at' => 'datetime',
    ];

    // ÄÆ¡n hÃ ng thuá»™c vá» má»™t khÃ¡ch hÃ ng.
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Má»™t Ä‘Æ¡n hÃ ng cÃ³ nhiá»u dÃ²ng sáº£n pháº©m.
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // Má»™t Ä‘Æ¡n hÃ ng cÃ³ thá»ƒ phÃ¡t sinh nhiá»u yÃªu cáº§u hoÃ n Ä‘á»•i.
    public function returnRequests(): HasMany
    {
        return $this->hasMany(ReturnRequest::class);
    }

    // Má»™t Ä‘Æ¡n hÃ ng cÃ³ thá»ƒ cÃ³ nhiá»u báº£n ghi thanh toÃ¡n, vÃ­ dá»¥ thanh toÃ¡n láº¡i hoáº·c IPN.
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // ÄÆ¡n hÃ ng cÃ³ thá»ƒ Ã¡p dá»¥ng má»™t mÃ£ khuyáº¿n mÃ£i.
    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }
}
