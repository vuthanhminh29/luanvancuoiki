<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderCancellationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderCancellationController extends Controller
{
    // Controller này xử lý link khách bấm từ email xác nhận hủy.
    // Admin không đi qua controller này; admin chỉ tạo yêu cầu hủy và gửi email.
    public function __construct(private readonly OrderCancellationService $cancellations)
    {
    }

    // GET /don-hang/{order}/xac-nhan-huy/{token}
    // Khách mở link trong email thì vào đây trước để xem lại toàn bộ thông tin đơn.
    // Route có middleware signed nên nếu link bị sửa order/token/expires thì Laravel chặn.
    public function show(Order $order, string $token): View
    {
        // Load user và items để trang xác nhận hiển thị khách hàng + danh sách sản phẩm.
        $order->load(['user', 'items']);

        return view('orders.cancel-confirmation', [
            'order' => $order,
            'token' => $token,
            // Nếu token sai, hết hạn hoặc đơn không còn được hủy, view sẽ hiện lỗi.
            'error' => $this->cancellations->pendingCancellationError($order, $token),
            // confirmed=false nghĩa là khách mới đang xem trang, chưa bấm nút xác nhận.
            'confirmed' => false,
        ]);
    }

    // POST /don-hang/{order}/xac-nhan-huy/{token}
    // Khách bấm nút "Xác nhận hủy đơn" trên trang xác nhận thì vào hàm này.
    // Chỉ hàm này mới thật sự đổi status của đơn sang CANCELLED.
    public function confirm(Request $request, Order $order, string $token): View|RedirectResponse
    {
        $order->load(['user', 'items']);

        // Service kiểm tra lại token/trạng thái trong transaction rồi mới hủy.
        // Kiểm tra lại ở backend là bắt buá»™c vì người dùng có thể tự gửi request POST.
        $result = $this->cancellations->confirmCancellation($order, $token);

        if ($result !== true) {
            return view('orders.cancel-confirmation', [
                // fresh() lấy lại dữ liệu mới nhất sau khi service vừa kiểm tra/xử lý.
                'order' => $order->fresh(['user', 'items']) ?? $order,
                'token' => $token,
                'error' => $result,
                'confirmed' => false,
            ]);
        }

        $order = $order->fresh(['user', 'items']);

        // N?u kh?ch ?ang ??ng nh?p ??ng t?i kho?n ch? ??n th? ??a h? v? trang chi ti?t ??n.
        // Nếu họ bấm email khi chưa đăng nhập, vẫn cho xác nhận bằng signed link và hiện trang kết quả.
        if ($request->user()?->id === $order?->user_id) {
            return redirect()
                ->route('account.orders.show', $order)
                ->with('success', 'Bạn đã xác nhận hủy đơn hàng thành công.');
        }

        return view('orders.cancel-confirmation', [
            'order' => $order,
            'token' => $token,
            'error' => null,
            'confirmed' => true,
        ]);
    }
}
