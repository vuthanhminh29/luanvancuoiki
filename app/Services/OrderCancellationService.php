<?php

namespace App\Services;

use App\Models\Order;
use App\Support\QueuedRawMail as Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class OrderCancellationService
{
    // Chỉ các trạng thái này được phép bắt đầu luồng hủy.
    // Đơn đang giao/đã giao/hoàn đổi thì không gửi email hủy nữa để tránh sai quy trình.
    private const CANCELLABLE_STATUSES = ['PENDING', 'AWAITING_PAYMENT', 'CONFIRMED'];

    // Bước 1 của nghiệp vụ:
    // Admin bấm hủy -> hệ thống tạo token, lưu lý do hủy, gửi email cho khách.
    // Hàm này chưa đổi status sang CANCELLED, vì khách phải xác nhận trước.
    public function requestCancellation(Order $order, ?string $reason = null): true|string
    {
        // Token thật chỉ gửi qua email. Database chỉ lưu token đã hash.
        // Nếu database bị lộ, người khác cũng không lấy được link xác nhận thật.
        $token = Str::random(72);
        $tokenHash = hash('sha256', $token);
        $reason = $this->normalizeReason($reason);

        $result = DB::transaction(function () use ($order, $reason, $tokenHash): array|string {
            // lockForUpdate khóa dòng order để tránh 2 admin/request cùng xử lý một đơn một lúc.
            $lockedOrder = Order::query()
                ->with(['user', 'items'])
                ->lockForUpdate()
                ->find($order->id);

            if (! $lockedOrder) {
                return 'Không tìm thấy đơn hàng cần xử lý.';
            }

            if (! $this->canCancel($lockedOrder)) {
                return 'Không thể yêu cầu hủy đơn hàng ở trạng thái hiện tại.';
            }

            // Email lấy từ user của đơn hàng vì khách cần nhận link xác nhận hủy.
            $email = $this->customerEmail($lockedOrder);

            if ($email === null) {
                return 'Đơn hàng này chưa có email khách hàng để gửi xác nhận hủy.';
            }

            // Lưu trạng thái "đang chờ khách xác nhận hủy".
            // status vẫn giữ nguyên để báo cáo không tính là đã hủy sớm.
            $lockedOrder->forceFill([
                'cancel_confirmation_token_hash' => $tokenHash,
                'cancel_reason' => $reason,
                'cancel_requested_at' => now(),
                'cancel_confirmed_at' => null,
            ])->save();

            return [
                'email' => $email,
                'order' => $lockedOrder->fresh(['user', 'items']),
            ];
        });

        if (is_string($result)) {
            return $result;
        }

        // Signed URL có expires + signature.
        // Khách không cần đăng nhập vẫn xác nhận được, nhưng không được sửa id/token trong URL.
        $url = URL::temporarySignedRoute(
            'orders.cancel-confirm.show',
            now()->addDays(3),
            [
                'order' => $result['order']->id,
                'token' => $token,
            ]
        );

        try {
            // Mail::raw dùng email text đơn giản, giống style đang có trong AuthController.
            // Nội dung email được gom trong emailBody() để hàm chính dễ đọc.
            Mail::raw(
                $this->emailBody($result['order'], $url),
                fn ($message) => $message
                    ->to($result['email'])
                    ->subject('Xác nhận hủy đơn hàng ' . ($result['order']->order_code ?: '#' . $result['order']->id))
            );
        } catch (\Throwable $exception) {
            // Nếu gửi email lỗi thì xóa token vừa lưu.
            // Như vậy admin có thể sửa SMTP rồi bấm gửi lại, tránh giữ yêu cầu hủy không ai nhận được.
            Order::whereKey($order->id)
                ->where('cancel_confirmation_token_hash', $tokenHash)
                ->update([
                    'cancel_confirmation_token_hash' => null,
                    'cancel_requested_at' => null,
                ]);

            Log::error('Order cancellation confirmation email could not be sent.', [
                'order_id' => $order->id,
                'message' => $exception->getMessage(),
            ]);

            return 'Chưa gửi được email xác nhận hủy. Vui lòng kiểm tra cấu hình SMTP trong file .env.';
        }

        return true;
    }

    // Dùng cho trang GET và POST xác nhận hủy.
    // Trả null nghĩa là link còn hợp lệ; trả chuỗi nghĩa là có lỗi để view hiển thị cho khách.
    public function pendingCancellationError(Order $order, string $token): ?string
    {
        if ($order->status === 'CANCELLED') {
            return 'Đơn hàng này đã được hủy trước đó.';
        }

        if (! $this->canCancel($order)) {
            return 'Đơn hàng hiện không còn ở trạng thái được phép hủy.';
        }

        // So sánh token khách gửi lên với hash đang lưu trong database.
        // hash_equals giúp tránh so sánh chuỗi theo kiểu dễ bị timing attack.
        if (! $order->cancel_confirmation_token_hash || ! hash_equals($order->cancel_confirmation_token_hash, hash('sha256', $token))) {
            return 'Liên kết xác nhận hủy không hợp lệ.';
        }

        // Link tự hết hạn sau 3 ngày kể từ lúc admin gửi yêu cầu hủy.
        if (! $order->cancel_requested_at || $order->cancel_requested_at->lt(now()->subDays(3))) {
            return 'Liên kết xác nhận hủy đã hết hạn. Vui lòng liên hệ cửa hàng để được hỗ trợ.';
        }

        return null;
    }

    // Bước 2 của nghiệp vụ:
    // Khách bấm xác nhận -> kiểm tra token lần cuối -> đổi status sang CANCELLED.
    public function confirmCancellation(Order $order, string $token): true|string
    {
        return DB::transaction(function () use ($order, $token): true|string {
            // Khóa order trong lúc xác nhận để tránh khách/admin thao tác trùng thời điểm.
            $lockedOrder = Order::query()
                ->lockForUpdate()
                ->find($order->id);

            if (! $lockedOrder) {
                return 'Không tìm thấy đơn hàng cần hủy.';
            }

            $error = $this->pendingCancellationError($lockedOrder, $token);

            if ($error !== null) {
                return $error;
            }

            // Chỉ tới đây đơn mới thật sự bị hủy.
            // Xóa token sau khi dùng để link email không thể dùng lại lần hai.
            $lockedOrder->forceFill([
                'status' => 'CANCELLED',
                'cancel_confirmed_at' => now(),
                'cancel_confirmation_token_hash' => null,
                'note' => $this->cancelNote($lockedOrder->note, $lockedOrder->cancel_reason),
            ])->save();

            return true;
        });
    }

    public function canCancel(Order $order): bool
    {
        // Hàm này được AdminController và service dùng chung để thống nhất điều kiện hủy.
        return in_array($order->status, self::CANCELLABLE_STATUSES, true);
    }

    private function customerEmail(Order $order): ?string
    {
        // Trim để tránh email toàn khoảng trắng vẫn bị xem là hợp lệ.
        $email = trim((string) ($order->user?->email ?? ''));

        return $email === '' ? null : $email;
    }

    private function normalizeReason(?string $reason): ?string
    {
        $reason = trim((string) $reason);

        return $reason === '' ? null : $reason;
    }

    private function cancelNote(?string $currentNote, ?string $cancelReason): ?string
    {
        // Khi đơn được hủy thật sự, lý do hủy được nối thêm vào note để admin xem lịch sử.
        $cancelReason = $this->normalizeReason($cancelReason);

        if ($cancelReason === null) {
            return $currentNote;
        }

        $line = '[Hủy đơn ' . now()->format('d/m/Y H:i') . '] ' . $cancelReason;
        $currentNote = trim((string) $currentNote);

        return $currentNote === '' ? $line : $currentNote . PHP_EOL . $line;
    }

    private function emailBody(Order $order, string $url): string
    {
        // Email ghi đủ thông tin đơn hàng để khách biết chính xác đơn nào đang được yêu cầu hủy.
        // Dùng dữ liệu snapshot trong order_items, không phụ thuộc tên/giá sản phẩm hiện tại.
        $lines = [
            'Xin chào ' . ($order->user?->full_name ?: $order->recipient_name) . ',',
            '',
            'Cửa hàng gửi yêu cầu xác nhận hủy đơn hàng dưới đây. Nếu bạn đồng ý hủy, vui lòng bấm vào liên kết xác nhận ở cuối email.',
            '',
            'THÔNG TIN ĐƠN HÀNG',
            'Mã đơn: ' . ($order->order_code ?: '#' . $order->id),
            'Ngày đặt: ' . $order->created_at?->format('d/m/Y H:i'),
            'Trạng thái hiện tại: ' . $this->statusLabel($order->status),
            'Phương thức thanh toán: ' . $this->paymentLabel($order->payment_method),
            'Trạng thái thanh toán: ' . ($order->payment_status ?: '-'),
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
            'Lý do hủy từ cửa hàng: ' . ($order->cancel_reason ?: '-'),
            '',
            'Bấm vào liên kết sau để xem lại và xác nhận hủy đơn:',
            $url,
            '',
            'Liên kết có hiệu lực trong 3 ngày. Nếu bạn không đồng ý hủy, vui lòng bỏ qua email này hoặc liên hệ cửa hàng.',
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
            'LOST_IN_TRANSIT' => 'Không hoàn tất',
            default => $status ?: '-',
        };
    }
}
