<?php

namespace App\Services;

use App\Mail\CustomerOrderCancellationReceiptMail;
use App\Models\Order;
use App\Support\QueuedRawMail as Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail as LaravelMail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class OrderCancellationService
{
    public const AUTO_CANCELLED = 'AUTO_CANCELLED';

    public const CUSTOMER_REVIEW_REQUESTED = 'CUSTOMER_REVIEW_REQUESTED';

    private const CUSTOMER_CANCEL_NOTE_PREFIX = '[Khách yêu cầu hủy đơn';

    // Chỉ các trạng thái này được phép bắt đầu luồng hủy.
    // Đơn đang giao/đã giao/hoàn đổi thì không gửi email hủy nữa để tránh sai quy trình.
    private const CANCELLABLE_STATUSES = ['PENDING', 'AWAITING_PAYMENT', 'CONFIRMED'];

    private const MAX_CANCELLATION_REQUESTS = 3;

    public function __construct(private readonly InventoryService $inventory)
    {
    }

    // Bước 1 của nghiệp vụ:
    // Admin bấm hủy -> hệ thống tạo token, lưu lý do hủy, gửi email cho khách.
    // Hàm này chưa đổi status sang CANCELLED, vì khách phải xác nhận trước.
    public function requestCancellation(Order $order, ?string $reason = null): true|string
    {
        // Token thật chỉ gửi qua email. Database chỉ lưu token đã hash.
        // Nếu database bị lộ, người khác cũng không lấy được link xác nhận thật.
        // Luong: Gan ket qua xu ly vao bien $token.
        $token = Str::random(72);
        // Luong: Gan ket qua xu ly vao bien $tokenHash.
        $tokenHash = hash('sha256', $token);
        // Luong: Gan ket qua xu ly vao bien $reason.
        $reason = $this->normalizeReason($reason);

        // Luong: Mo transaction de cac thao tac CSDL cung thanh cong hoac cung rollback.
        $result = DB::transaction(function () use ($order, $reason, $tokenHash): array|string {
            // lockForUpdate khóa dòng order để tránh 2 admin/request cùng xử lý một đơn một lúc.
            // Luong: Gan ket qua xu ly vao bien $lockedOrder.
            $lockedOrder = Order::query()
                // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
                ->with(['user', 'items'])
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->lockForUpdate()
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->find($order->id);

            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
            if (! $lockedOrder) {
                // Luong: Tra ve ket qua cuoi cung cua ham.
                return 'Không tìm thấy đơn hàng cần xử lý.';
            }

            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
            if (! $this->canCancel($lockedOrder)) {
                // Luong: Tra ve ket qua cuoi cung cua ham.
                return 'Không thể yêu cầu hủy đơn hàng ở trạng thái hiện tại.';
            }

            // Email lấy từ user của đơn hàng vì khách cần nhận link xác nhận hủy.
            // Luong: Gan ket qua xu ly vao bien $email.
            $email = $this->customerEmail($lockedOrder);
            $effectiveReason = $reason ?? $this->normalizeReason($lockedOrder->cancel_reason);

            $requestCount = min((int) $lockedOrder->cancel_request_count + 1, self::MAX_CANCELLATION_REQUESTS);

            if ($requestCount >= self::MAX_CANCELLATION_REQUESTS) {
                $lockedOrder->forceFill([
                    'status' => 'CANCELLED',
                    'cancel_request_count' => $requestCount,
                    'cancel_reason' => $effectiveReason,
                    'cancel_requested_at' => now(),
                    'cancel_confirmed_at' => null,
                    'cancel_confirmation_token_hash' => null,
                    'note' => $this->autoCancelNote($lockedOrder->note, $effectiveReason),
                ])->save();

                $this->inventory->releaseForOrder($lockedOrder);

                return [
                    'auto_cancelled' => true,
                    'request_count' => $requestCount,
                ];
            }

            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
            if ($email === null) {
                // Luong: Tra ve ket qua cuoi cung cua ham.
                return 'Đơn hàng này chưa có email khách hàng để gửi xác nhận hủy.';
            }

            // Lưu trạng thái "đang chờ khách xác nhận hủy".
            // status vẫn giữ nguyên để báo cáo không tính là đã hủy sớm.
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $lockedOrder->forceFill([
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'cancel_confirmation_token_hash' => $tokenHash,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'cancel_reason' => $effectiveReason,
                'cancel_request_count' => $requestCount,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'cancel_requested_at' => now(),
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'cancel_confirmed_at' => null,
            ])->save();

            // Luong: Tra ve ket qua cuoi cung cua ham.
            return [
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'email' => $email,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'order' => $lockedOrder->fresh(['user', 'items']),
                'request_count' => $requestCount,
            ];
        });

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (is_string($result)) {
            // Luong: Tra ve ket qua cuoi cung cua ham.
            return $result;
        }

        if (($result['auto_cancelled'] ?? false) === true) {
            return self::AUTO_CANCELLED;
        }

        // Signed URL có expires + signature.
        // Khách không cần đăng nhập vẫn xác nhận được, nhưng không được sửa id/token trong URL.
        // Luong: Gan ket qua xu ly vao bien $url.
        $url = URL::temporarySignedRoute(
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            'orders.cancel-confirm.show',
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            now()->addDays(3),
            [
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'order' => $result['order']->id,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'token' => $token,
            ]
        );

        // Luong: Bat dau khoi xu ly co the phat sinh loi.
        try {
            // Mail::raw dùng email text đơn giản, giống style đang có trong AuthController.
            // Nội dung email được gom trong emailBody() để hàm chính dễ đọc.
            // Luong: Gui email dang text theo noi dung da tao.
            Mail::raw(
                // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
                $this->emailBody($result['order'], $url),
                // Luong: Dinh nghia callback ngan gon cho thao tac hien tai.
                fn ($message) => $message
                    // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                    ->to($result['email'])
                    // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                    ->subject('Xác nhận hủy đơn hàng ' . ($result['order']->order_code ?: '#' . $result['order']->id))
            );
        // Luong: Bat va xu ly loi phat sinh trong khoi try.
        } catch (\Throwable $exception) {
            // Nếu gửi email lỗi thì xóa token vừa lưu.
            // Như vậy admin có thể sửa SMTP rồi bấm gửi lại, tránh giữ yêu cầu hủy không ai nhận được.
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            Order::whereKey($order->id)
                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                ->where('cancel_confirmation_token_hash', $tokenHash)
                // Luong: Cap nhat cac ban ghi phu hop voi dieu kien da loc.
                ->update([
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'cancel_confirmation_token_hash' => null,
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'cancel_requested_at' => null,
                    'cancel_request_count' => max(((int) ($result['request_count'] ?? 1)) - 1, 0),
                ]);

            // Luong: Ghi log de theo doi va chan doan qua trinh xu ly.
            Log::error('Order cancellation confirmation email could not be sent.', [
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'order_id' => $order->id,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'message' => $exception->getMessage(),
            ]);

            // Luong: Tra ve ket qua cuoi cung cua ham.
            return 'Chưa gửi được email xác nhận hủy. Vui lòng kiểm tra cấu hình SMTP trong file .env.';
        }

        // Luong: Tra ve ket qua cuoi cung cua ham.
        return true;
    }

    // Dùng cho trang GET và POST xác nhận hủy.
    // Trả null nghĩa là link còn hợp lệ; trả chuỗi nghĩa là có lỗi để view hiển thị cho khách.
    public function pendingCancellationError(Order $order, string $token): ?string
    {
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($order->status === 'CANCELLED') {
            // Luong: Tra ve ket qua cuoi cung cua ham.
            return 'Đơn hàng này đã được hủy trước đó.';
        }

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! $this->canCancel($order)) {
            // Luong: Tra ve ket qua cuoi cung cua ham.
            return 'Đơn hàng hiện không còn ở trạng thái được phép hủy.';
        }

        // So sánh token khách gửi lên với hash đang lưu trong database.
        // hash_equals giúp tránh so sánh chuỗi theo kiểu dễ bị timing attack.
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! $order->cancel_confirmation_token_hash || ! hash_equals($order->cancel_confirmation_token_hash, hash('sha256', $token))) {
            // Luong: Tra ve ket qua cuoi cung cua ham.
            return 'Liên kết xác nhận hủy không hợp lệ.';
        }

        // Link tự hết hạn sau 3 ngày kể từ lúc admin gửi yêu cầu hủy.
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! $order->cancel_requested_at || $order->cancel_requested_at->lt(now()->subDays(3))) {
            // Luong: Tra ve ket qua cuoi cung cua ham.
            return 'Liên kết xác nhận hủy đã hết hạn. Vui lòng liên hệ cửa hàng để được hỗ trợ.';
        }

        // Luong: Tra ve ket qua cuoi cung cua ham.
        return null;
    }

    // Bước 2 của nghiệp vụ:
    // Khách bấm xác nhận -> kiểm tra token lần cuối -> đổi status sang CANCELLED.
    public function confirmCancellation(Order $order, string $token): true|string
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return DB::transaction(function () use ($order, $token): true|string {
            // Khóa order trong lúc xác nhận để tránh khách/admin thao tác trùng thời điểm.
            // Luong: Gan ket qua xu ly vao bien $lockedOrder.
            $lockedOrder = Order::query()
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->lockForUpdate()
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->find($order->id);

            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
            if (! $lockedOrder) {
                // Luong: Tra ve ket qua cuoi cung cua ham.
                return 'Không tìm thấy đơn hàng cần hủy.';
            }

            // Luong: Gan ket qua xu ly vao bien $error.
            $error = $this->pendingCancellationError($lockedOrder, $token);

            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
            if ($error !== null) {
                // Luong: Tra ve ket qua cuoi cung cua ham.
                return $error;
            }

            // Chỉ tới đây đơn mới thật sự bị hủy.
            // Xóa token sau khi dùng để link email không thể dùng lại lần hai.
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $lockedOrder->forceFill([
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'status' => 'CANCELLED',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'cancel_confirmed_at' => now(),
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'cancel_confirmation_token_hash' => null,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'note' => $this->cancelNote($lockedOrder->note, $lockedOrder->cancel_reason),
            ])->save();

            // Đơn bị hủy thì trả hàng đã giữ về kho. releaseForOrder() tự bỏ qua
            // đơn cũ chưa từng giữ hàng (stock_reserved_at = NULL) nên không làm
            // phồng tồn kho của dữ liệu có sẵn.
            $this->inventory->releaseForOrder($lockedOrder);

            // Luong: Tra ve ket qua cuoi cung cua ham.
            return true;
        });
    }

    public function canCancel(Order $order): bool
    {
        // Hàm này được AdminController và service dùng chung để thống nhất điều kiện hủy.
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return in_array($order->status, self::CANCELLABLE_STATUSES, true);
    }

    public function requestCancellationFromCustomer(Order $order, ?string $reason = null): true|string
    {
        $reason = $this->normalizeReason($reason) ?: 'Khách hàng yêu cầu hủy đơn.';

        $result = DB::transaction(function () use ($order, $reason): array|string {
            $lockedOrder = Order::query()
                ->with(['user', 'items'])
                ->lockForUpdate()
                ->find($order->id);

            if (! $lockedOrder) {
                return 'Không tìm thấy đơn hàng cần hủy.';
            }

            if (in_array($lockedOrder->status, ['PENDING', 'AWAITING_PAYMENT'], true)) {
                $lockedOrder->forceFill([
                    'status' => 'CANCELLED',
                    'cancel_reason' => $reason,
                    'cancel_requested_at' => now(),
                    'cancel_confirmed_at' => now(),
                    'cancel_confirmation_token_hash' => null,
                    'note' => $this->customerCancelNote($lockedOrder->note, $reason, true),
                ])->save();

                $this->inventory->releaseForOrder($lockedOrder);

                return [
                    'direct_cancelled' => true,
                    'email' => $this->customerEmail($lockedOrder),
                    'order' => $lockedOrder->fresh(['user', 'items']),
                ];
            }

            if ($lockedOrder->status === 'CONFIRMED') {
                if ($lockedOrder->cancel_requested_at !== null && $lockedOrder->cancel_confirmation_token_hash !== null) {
                    return 'Đơn hàng này đang có yêu cầu hủy, vui lòng kiểm tra email xác nhận hủy.';
                }

                if ($this->hasCustomerCancellationRequest($lockedOrder)) {
                    return 'Đơn hàng này đã gửi yêu cầu hủy và đang chờ admin xử lý.';
                }

                $lockedOrder->forceFill([
                    'cancel_reason' => $reason,
                    'cancel_requested_at' => now(),
                    'cancel_confirmed_at' => null,
                    'cancel_confirmation_token_hash' => null,
                    'note' => $this->customerCancelNote($lockedOrder->note, $reason, false),
                ])->save();

                return [
                    'customer_review_requested' => true,
                    'email' => $this->customerEmail($lockedOrder),
                    'order' => $lockedOrder->fresh(['user', 'items']),
                ];
            }

            return 'Không thể hủy đơn hàng ở trạng thái hiện tại.';
        });

        if (is_string($result)) {
            return $result;
        }

        if (($result['customer_review_requested'] ?? false) === true) {
            $this->sendCustomerCancellationReceipt($result['order'] ?? null, $result['email'] ?? null, false);

            return self::CUSTOMER_REVIEW_REQUESTED;
        }

        if (($result['direct_cancelled'] ?? false) === true) {
            $this->sendCustomerCancellationReceipt($result['order'] ?? null, $result['email'] ?? null, true);
        }

        return true;
    }

    public function hasCustomerCancellationRequest(Order $order): bool
    {
        return $order->status === 'CONFIRMED'
            && $order->cancel_requested_at !== null
            && str_contains((string) $order->note, self::CUSTOMER_CANCEL_NOTE_PREFIX);
    }

    private function sendCustomerCancellationReceipt(?Order $order, ?string $email, bool $directCancelled): void
    {
        if (! $order || ! $email) {
            return;
        }

        try {
            LaravelMail::to($email)->send(new CustomerOrderCancellationReceiptMail($order, $directCancelled));
        } catch (\Throwable $exception) {
            Log::error('Customer cancellation receipt email could not be sent.', [
                'order_id' => $order->id,
                'message' => $exception->getMessage(),
            ]);
        }
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

    private function autoCancelNote(?string $currentNote, ?string $cancelReason): string
    {
        $cancelReason = $this->normalizeReason($cancelReason);
        $line = '[Tự hủy đơn ' . now()->format('d/m/Y H:i') . '] Khách chưa xác nhận sau '
            . self::MAX_CANCELLATION_REQUESTS . ' lần gửi yêu cầu hủy.';

        if ($cancelReason !== null) {
            $line .= ' Lý do: ' . $cancelReason;
        }

        $currentNote = trim((string) $currentNote);

        return $currentNote === '' ? $line : $currentNote . PHP_EOL . $line;
    }

    private function customerCancelNote(?string $currentNote, string $reason, bool $directCancelled): string
    {
        $action = $directCancelled ? 'đã tự hủy khi đơn chưa xác nhận' : 'đang chờ admin xử lý';
        $line = self::CUSTOMER_CANCEL_NOTE_PREFIX . ' ' . now()->format('d/m/Y H:i') . '] ' . $action . '. Lý do: ' . $reason;
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
