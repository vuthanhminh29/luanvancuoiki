<?php

declare(strict_types=1);

namespace App\Services\Chatbot;

use App\Models\Order;
use Illuminate\Support\Collection;

/**
 * Đơn hàng của CHÍNH khách đang đăng nhập, để chatbot trả lời được câu
 * "đơn của tôi tới đâu rồi".
 *
 * Hai ràng buộc bắt buộc, và cả hai đều là ràng buộc bảo mật chứ không phải
 * lựa chọn thiết kế:
 *
 * 1. Luôn lọc theo user_id của phiên đăng nhập, KHÔNG bao giờ theo mã đơn khách
 *    gõ vào. Tra theo mã đơn nghĩa là bất kỳ ai đăng nhập cũng đọc được đơn của
 *    người khác chỉ bằng cách đoán mã — đúng lỗi IDOR kinh điển. Khách có hỏi
 *    "đơn ORD123 sao rồi" thì mã đó cũng chỉ dùng để lọc TRONG danh sách đơn của
 *    chính họ.
 *
 * 2. Chỉ đưa vào ngữ cảnh những trường thật sự cần để trả lời: mã đơn, trạng
 *    thái, tiền, ngày, tên sản phẩm. KHÔNG đưa số điện thoại và địa chỉ giao
 *    hàng. Khối ngữ cảnh này được gửi sang nhà cung cấp AI bên ngoài, nên mỗi
 *    trường thừa là một trường dữ liệu cá nhân bị mang ra khỏi hệ thống mà
 *    chẳng giúp trả lời tốt hơn.
 */
class CustomerOrderContext
{
    // Đủ để trả lời "đơn gần đây của tôi" mà không biến prompt thành bản sao
    // lịch sử mua hàng.
    private const MAX_ORDERS = 5;
    private const MAX_ITEMS_PER_ORDER = 5;

    // Danh sách này phải phủ hết giá trị CÓ THẬT trong bảng orders, không phải
    // giá trị đoán theo tên. Thiếu một trạng thái là chatbot đọc nguyên mã ENUM
    // cho khách nghe ("đơn của bạn đang AWAITING_PAYMENT").
    private const STATUS_LABELS = [
        'PENDING' => 'Chờ xác nhận',
        'AWAITING_PAYMENT' => 'Chờ thanh toán',
        'CONFIRMED' => 'Đã xác nhận',
        'DELAY' => 'Bị hoãn giao',
        'DELIVERING' => 'Đang giao',
        'DELIVERED' => 'Đã giao',
        'CANCELLED' => 'Đã hủy',
        'RETURN_PENDING' => 'Chờ hoàn/đổi',
        'RETURNED' => 'Đã hoàn',
        'EXCHANGED' => 'Đã đổi',
        'LOST_IN_TRANSIT' => 'Thất lạc khi vận chuyển',
    ];

    private const PAYMENT_STATUS_LABELS = [
        'UNPAID' => 'Chưa thanh toán',
        'PAID' => 'Đã thanh toán',
    ];

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function forUser(?int $userId): Collection
    {
        if ($userId === null || $userId <= 0) {
            return collect();
        }

        return Order::query()
            ->where('user_id', $userId)
            ->with(['items:id,order_id,product_name,quantity,total_price'])
            ->latest('id')
            ->limit(self::MAX_ORDERS)
            ->get(['id', 'order_code', 'status', 'payment_status', 'payment_method', 'total_amount', 'created_at', 'delivered_at'])
            ->map(fn (Order $order): array => [
                'code' => (string) $order->order_code,
                'status' => self::STATUS_LABELS[$order->status] ?? (string) $order->status,
                'payment' => self::PAYMENT_STATUS_LABELS[$order->payment_status] ?? (string) $order->payment_status,
                'payment_method' => $order->payment_method === 'VNPAY' ? 'VNPay' : 'COD',
                'total' => (float) $order->total_amount,
                'placed_at' => $order->created_at?->format('d/m/Y'),
                'delivered_at' => $order->delivered_at?->format('d/m/Y'),
                'items' => $order->items
                    ->take(self::MAX_ITEMS_PER_ORDER)
                    ->map(fn ($item): array => [
                        'name' => (string) $item->product_name,
                        'quantity' => (int) $item->quantity,
                    ])
                    ->values()
                    ->all(),
            ])
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $orders
     */
    public function render(Collection $orders): string
    {
        if ($orders->isEmpty()) {
            return '';
        }

        $lines = ['### ĐƠN HÀNG CỦA CHÍNH KHÁCH ĐANG CHAT (đã đăng nhập)'];

        foreach ($orders as $order) {
            $line = sprintf(
                '- Đơn %s | Đặt ngày %s | Trạng thái: %s | %s (%s) | Tổng: %s',
                $order['code'],
                $order['placed_at'] ?? 'không rõ',
                $order['status'],
                $order['payment'],
                $order['payment_method'],
                number_format($order['total'], 0, ',', '.') . 'đ',
            );

            if ($order['delivered_at'] !== null) {
                $line .= ' | Đã giao ngày ' . $order['delivered_at'];
            }

            $lines[] = $line;

            foreach ($order['items'] as $item) {
                $lines[] = sprintf('  + %s x%d', $item['name'], $item['quantity']);
            }
        }

        return implode("\n", $lines);
    }
}
