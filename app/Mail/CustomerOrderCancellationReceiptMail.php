<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomerOrderCancellationReceiptMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Order $order,
        public bool $directCancelled
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject($this->subjectLine())
            ->view('emails.customer-order-cancellation-receipt')
            ->with([
                'customerName' => $this->customerName(),
                'noteLine' => $this->noteLine(),
                'orderCode' => $this->orderCode(),
                'statusLabel' => $this->statusLabel(),
                'statusLine' => $this->statusLine(),
                'subjectLine' => $this->subjectLine(),
                'totalAmount' => $this->money($this->order->total_amount),
            ]);
    }

    public function subjectLine(): string
    {
        return $this->directCancelled
            ? 'Đơn hàng ' . $this->orderCode() . ' đã được hủy'
            : 'Đã ghi nhận yêu cầu hủy đơn hàng ' . $this->orderCode();
    }

    public function orderCode(): string
    {
        return $this->order->order_code ?: '#' . $this->order->id;
    }

    public function customerName(): string
    {
        return $this->order->user?->full_name
            ?: $this->order->recipient_name
            ?: 'quý khách';
    }

    public function statusLine(): string
    {
        return $this->directCancelled
            ? 'Đơn hàng của bạn đã được hủy theo yêu cầu.'
            : 'Cửa hàng đã ghi nhận yêu cầu hủy đơn của bạn và sẽ chuyển đến admin xử lý.';
    }

    public function noteLine(): string
    {
        return $this->directCancelled
            ? 'Email này chỉ là thông báo xác nhận đơn đã hủy, không cần bấm thêm liên kết nào.'
            : 'Email này chỉ là thông báo đã ghi nhận yêu cầu, không cần bấm thêm liên kết nào.';
    }

    public function statusLabel(): string
    {
        return match ($this->order->status) {
            'PENDING' => 'Chờ xác nhận',
            'AWAITING_PAYMENT' => 'Chờ thanh toán',
            'CONFIRMED' => 'Đã xác nhận',
            'DELIVERING' => 'Đang giao',
            'DELIVERED' => 'Giao thành công',
            'CANCELLED' => 'Đã hủy',
            'RETURN_PENDING' => 'Chờ hoàn/đổi',
            'RETURNED' => 'Đã hoàn trả',
            'EXCHANGED' => 'Đã đổi hàng',
            'LOST_IN_TRANSIT' => 'Không hoàn tất',
            default => $this->order->status ?: '-',
        };
    }

    public function money(mixed $amount): string
    {
        return number_format((float) $amount, 0, ',', '.') . 'đ';
    }
}
