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
    /**
     * Nhận service xử lý hủy đơn.
     */
    public function __construct(private readonly OrderCancellationService $cancellations)
    {
    }

    // GET /don-hang/{order}/xac-nhan-huy/{token}
    // Khách mở link trong email thì vào đây trước để xem lại toàn bộ thông tin đơn.
    // Route có middleware signed nên nếu link bị sửa order/token/expires thì Laravel chặn.
    /**
     * Hiển thị trang xác nhận hủy đơn.
     */
    public function show(Order $order, string $token): View
    {
        // Load user và items để trang xác nhận hiển thị khách hàng + danh sách sản phẩm.
        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $order->load(['user', 'items.product']);

        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('orders.cancel-confirmation', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'order' => $order,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'token' => $token,
            // Nếu token sai, hết hạn hoặc đơn không còn được hủy, view sẽ hiện lỗi.
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'error' => $this->cancellations->pendingCancellationError($order, $token),
            // confirmed=false nghĩa là khách mới đang xem trang, chưa bấm nút xác nhận.
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'confirmed' => false,
        ]);
    }

    // POST /don-hang/{order}/xac-nhan-huy/{token}
    // Khách bấm nút "Xác nhận hủy đơn" trên trang xác nhận thì vào hàm này.
    // Chỉ hàm này mới thật sự đổi status của đơn sang CANCELLED.
    /**
     * Xác nhận hủy đơn từ link email.
     */
    public function confirm(Request $request, Order $order, string $token): View|RedirectResponse
    {
        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $order->load(['user', 'items.product']);

        // Service kiểm tra lại token/trạng thái trong transaction rồi mới hủy.
        // Kiểm tra lại ở backend là bắt buộc vì người dùng có thể tự gửi request POST.
        // Luong: Gan ket qua xu ly vao bien $result.
        $result = $this->cancellations->confirmCancellation($order, $token);

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($result !== true) {
            // Luong: Tra ve view de hien thi giao dien cho request.
            return view('orders.cancel-confirmation', [
                // fresh() lấy lại dữ liệu mới nhất sau khi service vừa kiểm tra/xử lý.
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'order' => $order->fresh(['user', 'items.product']) ?? $order,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'token' => $token,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'error' => $result,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'confirmed' => false,
            ]);
        }

        // Luong: Gan ket qua xu ly vao bien $order.
        $order = $order->fresh(['user', 'items.product']);

        // Nếu khách đang đăng nhập đúng tài khoản chủ đơn thì đưa họ về trang chi tiết đơn.
        // Nếu họ bấm email khi chưa đăng nhập, vẫn cho xác nhận bằng signed link và hiện trang kết quả.
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($request->user()?->id === $order?->user_id) {
            // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
            return redirect()
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->route('account.orders.show', $order)
                // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
                ->with('success', 'Bạn đã xác nhận hủy đơn hàng thành công.');
        }

        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('orders.cancel-confirmation', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'order' => $order,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'token' => $token,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'error' => null,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'confirmed' => true,
        ]);
    }
}
