<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_CONFIRMED = 'CONFIRMED';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_CANCELLED = 'CANCELLED';
    public const STATUS_NO_SHOW = 'NO_SHOW';

    public const ACTIVE_SLOT_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
    ];

    public const RESCHEDULABLE_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
    ];

    public const MAX_RESCHEDULE_COUNT = 1;
    public const RESCHEDULE_NOTICE_HOURS = 24;

    protected $fillable = [
        'user_id',
        'code',
        'service_code',
        'service_name',
        'price',
        'appointment_date',
        'appointment_time',
        'slot_lock_key',
        'customer_name',
        'customer_phone',
        'customer_email',
        'note',
        'status',
        'confirmed_at',
        'cancelled_at',
        'completed_at',
        'no_show_at',
        'cancel_reason',
        'admin_note',
        'reschedule_count',
        'last_rescheduled_at',
        'reschedule_reason',
        'reminder_email_sent_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'appointment_date' => 'date',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'completed_at' => 'datetime',
        'no_show_at' => 'datetime',
        'reschedule_count' => 'integer',
        'last_rescheduled_at' => 'datetime',
        'reminder_email_sent_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->belongsTo(User::class);
    }

    public function scheduledAt(): ?Carbon
    {
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! $this->appointment_date || ! $this->appointment_time) {
            // Luong: Tra ve ket qua cuoi cung cua ham.
            return null;
        }

        // Luong: Tra ve ket qua cuoi cung cua ham.
        return Carbon::parse($this->appointment_date->format('Y-m-d') . ' ' . $this->appointment_time);
    }

    public static function slotLockKeyFor(string $date, string $time): string
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return Carbon::parse($date)->format('Y-m-d') . '|' . trim($time);
    }

    public function statusLabel(): string
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return match ($this->status) {
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            self::STATUS_PENDING => 'Chờ xác nhận',
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            self::STATUS_CONFIRMED => 'Đã xác nhận',
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            self::STATUS_COMPLETED => 'Hoàn tất',
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            self::STATUS_CANCELLED => 'Đã hủy',
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            self::STATUS_NO_SHOW => 'Khách không đến',
            // Luong: Danh dau mot nhanh xu ly trong cau truc switch.
            default => $this->status ?: '-',
        };
    }

    public function isActiveSlot(): bool
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return in_array($this->status, self::ACTIVE_SLOT_STATUSES, true);
    }

    public function canConfirm(): bool
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->status === self::STATUS_PENDING;
    }

    public function canCancel(): bool
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED], true);
    }

    public function canComplete(): bool
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function canMarkNoShow(): bool
    {
        // Luong: Gan ket qua xu ly vao bien $scheduledAt.
        $scheduledAt = $this->scheduledAt();

        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->status === self::STATUS_CONFIRMED
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            && $scheduledAt !== null
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            && $scheduledAt->isPast();
    }

    public function canReschedule(): bool
    {
        // Luong: Gan ket qua xu ly vao bien $scheduledAt.
        $scheduledAt = $this->scheduledAt();

        // Luong: Tra ve ket qua cuoi cung cua ham.
        return in_array($this->status, self::RESCHEDULABLE_STATUSES, true)
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            && $this->reschedule_count < self::MAX_RESCHEDULE_COUNT
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            && $scheduledAt !== null
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            && $scheduledAt->gt(now()->addHours(self::RESCHEDULE_NOTICE_HOURS));
    }
}
