<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    // Các thông tin đơn hàng được phép lưu từ checkout hoặc admin cập nhật.
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

    // Ép các cột tiền về decimal và thời gian giao hàng về datetime.
    protected $casts = [
        'subtotal_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'delivered_at' => 'datetime',
        'cancel_requested_at' => 'datetime',
        'cancel_confirmed_at' => 'datetime',
    ];

    // Đơn hàng thuộc về một khách hàng.
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Một đơn hàng có nhiều dòng sản phẩm.
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // Một đơn hàng có thể phát sinh nhiều yêu cầu hoàn đổi.
    public function returnRequests(): HasMany
    {
        return $this->hasMany(ReturnRequest::class);
    }

    // Một đơn hàng có thể có nhiều bản ghi thanh toán, ví dụ thanh toán lại hoặc IPN.
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // Đơn hàng có thể áp dụng một mã khuyến mãi.
    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function hasReturnableStatus(): bool
    {
        return in_array($this->status, ['DELIVERED', 'RETURN_PENDING', 'RETURNED', 'EXCHANGED'], true);
    }
}
