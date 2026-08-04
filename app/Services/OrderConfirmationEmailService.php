<?php

namespace App\Services;

use App\Models\Order;
use App\Support\QueuedRawMail as Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderConfirmationEmailService
{
    // Gửi email xác nhận đơn hàng thành công cho cả COD và VNPay.
    // Email này chỉ thông báo thông tin đơn hàng, không có link xác nhận hay link thao tác.
    public function send(Order $order): bool
    {
        return DB::transaction(function () use ($order): bool {
            // Khóa order để VNPay return và IPN không gửi trùng email cùng lúc.
            $lockedOrder = Order::query()
                ->with(['user', 'items'])
                ->lockForUpdate()
                ->find($order->id);

            if (! $lockedOrder) {
                return false;
            }

            // Nếu đã gửi rồi thì bỏ qua, coi như thành công.
            if ($lockedOrder->order_confirmation_email_sent_at !== null) {
                return true;
            }

            // Lấy email từ tài khoản user gắn với đơn hàng.
            // Đơn hàng checkout bắt buộc đăng nhập nên user thường luôn có email.
            $email = $this->customerEmail($lockedOrder);

            if ($email === null) {
                Log::warning('Order confirmation email skipped because customer email is missing.', [
                    'order_id' => $lockedOrder->id,
                ]);

                return false;
            }

            try {
                Mail::raw(
                    $this->emailBody($lockedOrder),
                    fn ($message) => $message
                        ->to($email)
                        ->subject('Xác nhận đơn hàng thành công ' . ($lockedOrder->order_code ?: '#' . $lockedOrder->id))
                );
            } catch (\Throwable $exception) {
                // Không rollback đơn hàng nếu mail lỗi; chỉ log để admin kiểm tra SMTP.
                Log::error('Order confirmation email could not be sent.', [
                    'order_id' => $lockedOrder->id,
                    'email' => $email,
                    'message' => $exception->getMessage(),
                ]);

                return false;
            }

            // Đánh dấu đã gửi email để các lần gọi sau không gửi lại.
            $lockedOrder->forceFill([
                'order_confirmation_email_sent_at' => now(),
            ])->save();

            return true;
        });
    }

    private function customerEmail(Order $order): ?string
    {
        // Trim để tránh trường hợp email chỉ toàn khoảng trắng.
        $email = trim((string) ($order->user?->email ?? ''));

        return $email === '' ? null : $email;
    }

    private function emailBody(Order $order): string
    {
        // Nội dung email gồm đầy đủ snapshot đơn hàng.
        // Snapshot lấy từ order_items nên sản phẩm đổi tên/giá sau này cũng không làm email sai.
        $lines = [
            'Xin chào ' . ($order->user?->full_name ?: $order->recipient_name) . ',',
            '',
            'Đơn hàng của bạn đã được ghi nhận thành công tại ' . config('app.name') . '.',
            'Dưới đây là toàn bộ thông tin đơn hàng:',
            '',
            'THÔNG TIN ĐƠN HÀNG',
            'Mã đơn: ' . ($order->order_code ?: '#' . $order->id),
            'Ngày đặt: ' . $order->created_at?->format('d/m/Y H:i'),
            'Trạng thái đơn: ' . $this->statusLabel($order->status),
            'Phương thức thanh toán: ' . $this->paymentLabel($order->payment_method),
            'Trạng thái thanh toán: ' . $this->paymentStatusLabel($order->payment_status),
            '',
            'THÔNG TIN GIAO HÀNG',
            'Người nhận: ' . $order->recipient_name,
            'Số điện thoại: ' . $order->recipient_phone,
            'Địa chỉ: ' . $order->shipping_address,
            'Ghi chú: ' . ($order->note ?: '-'),
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
            'THANH TOÁN',
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
        // Định dạng tiền Việt Nam giống các màn hình đơn hàng.
        return number_format((float) $amount, 0, ',', '.') . 'đ';
    }

    private function paymentLabel(?string $method): string
    {
        // Chuyển mã phương thức trong database thành chữ dễ hiểu trong email.
        return match ($method) {
            'COD' => 'Thanh toán khi nhận hàng',
            'VNPAY' => 'VNPay',
            default => $method ?: '-',
        };
    }

    private function paymentStatusLabel(?string $status): string
    {
        // COD lúc mới đặt là UNPAID, VNPay thành công là PAID.
        return match ($status) {
            'PAID' => 'Đã thanh toán',
            'UNPAID' => 'Chưa thanh toán',
            default => $status ?: '-',
        };
    }

    private function statusLabel(?string $status): string
    {
        // Chuyển mã trạng thái đơn hàng thành tiếng Việt để khách đọc email dễ hiểu.
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
            'LOST_IN_TRANSIT' => 'Không hoàn tất',
            default => $status ?: '-',
        };
    }
}
