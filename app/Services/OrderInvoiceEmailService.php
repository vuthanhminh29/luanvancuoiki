<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class OrderInvoiceEmailService
{
    public function send(Order $order): bool
    {
        $order->loadMissing(['user', 'items']);
        $email = $this->customerEmail($order);

        if ($email === null) {
            Log::warning('Order invoice email skipped because customer email is missing.', [
                'order_id' => $order->id,
            ]);

            return false;
        }

        try {
            Mail::raw(
                $this->emailBody($order),
                fn ($message) => $message
                    ->to($email)
                    ->subject('Hóa đơn đơn hàng ' . ($order->order_code ?: '#' . $order->id))
            );
        } catch (\Throwable $exception) {
            Log::error('Order invoice email could not be sent.', [
                'order_id' => $order->id,
                'email' => $email,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }

        return true;
    }

    private function customerEmail(Order $order): ?string
    {
        $email = trim((string) ($order->user?->email ?? ''));

        return $email === '' ? null : $email;
    }

    private function emailBody(Order $order): string
    {
        $invoiceUrl = URL::route('account.orders.invoice', $order);
        $lines = [
            'Xin chào ' . ($order->user?->full_name ?: $order->recipient_name) . ',',
            '',
            'Cửa hàng gửi bạn hóa đơn cho đơn hàng ' . ($order->order_code ?: '#' . $order->id) . '.',
            'Bạn có thể mở hóa đơn để in hoặc tải PDF tại đây:',
            $invoiceUrl,
            '',
            'THÔNG TIN HÓA ĐƠN',
            'Mã đơn: ' . ($order->order_code ?: '#' . $order->id),
            'Ngày đặt: ' . $order->created_at?->format('d/m/Y H:i'),
            'Trạng thái đơn: ' . $this->statusLabel($order->status),
            'Phương thức thanh toán: ' . $this->paymentLabel($order->payment_method),
            '',
            'THÔNG TIN KHÁCH HÀNG',
            'Khách hàng: ' . ($order->user?->full_name ?: $order->recipient_name),
            'Email: ' . ($order->user?->email ?: '-'),
            'Số điện thoại: ' . $order->recipient_phone,
            '',
            'THÔNG TIN NHẬN HÀNG',
            'Người nhận: ' . $order->recipient_name,
            'Địa chỉ: ' . $order->shipping_address,
            '',
            'SẢN PHẨM',
        ];

        foreach ($order->items as $item) {
            $variant = trim(implode(' ', array_filter([$item->color_name, $item->lens_size_name])));

            $lines[] = '- ' . $item->product_name;

            if ($item->sku) {
                $lines[] = '  SKU: ' . $item->sku;
            }

            if ($variant !== '') {
                $lines[] = '  Phân loại: ' . $variant;
            }

            $lines[] = '  Số lượng: ' . $item->quantity;
            $lines[] = '  Đơn giá: ' . $this->money($item->unit_price);
            $lines[] = '  Thành tiền: ' . $this->money($item->total_price);
        }

        return implode("\n", array_merge($lines, [
            '',
            'TỔNG THANH TOÁN',
            'Tổng tiền hàng: ' . $this->money($order->subtotal_amount),
            'Giảm giá: ' . $this->money($order->discount_amount),
            'Phí vận chuyển: ' . $this->money($order->shipping_fee),
            'Tổng thanh toán: ' . $this->money($order->total_amount),
            '',
            'Cảm ơn bạn đã mua hàng tại ' . config('app.name') . '.',
        ]));
    }

    private function money(mixed $amount): string
    {
        return number_format((float) $amount, 0, ',', '.') . 'đ';
    }

    private function paymentLabel(?string $method): string
    {
        return match ($method) {
            'COD' => 'Thanh toán khi nhận hàng',
            'VNPAY' => 'VNPay',
            default => $method ?: '-',
        };
    }

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            'PENDING' => 'Chờ xác nhận',
            'AWAITING_PAYMENT' => 'Chờ thanh toán',
            'CONFIRMED' => 'Đã xác nhận',
            'DELIVERING' => 'Đang giao',
            'DELIVERED' => 'Giao thành công',
            'CANCELLED' => 'Đã hủy',
            'RETURN_PENDING' => 'Chờ hoàn/đổi',
            'RETURNED' => 'Đã hoàn trả',
            'EXCHANGED' => 'Đã đổi hàng',
            'LOST_IN_TRANSIT' => 'Mất hàng khi giao',
            default => $status ?: '-',
        };
    }
}
