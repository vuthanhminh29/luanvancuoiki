<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ReturnReason;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReturnRequestController extends Controller
{
    // Chỉ các đơn ở những trạng thái này mới được khách gửi yêu cầu hoàn/đổi.
    // DELIVERED: đơn đã giao xong.
    // RETURN_PENDING: đơn đã có yêu cầu hoàn/đổi đang xử lý.
    // RETURNED/EXCHANGED: đơn đã xử lý một yêu cầu trước đó nhưng vẫn có thể còn sản phẩm/số lượng khác để tạo tiếp.
    private const ELIGIBLE_ORDER_STATUSES = ['DELIVERED', 'RETURN_PENDING', 'RETURNED', 'EXCHANGED'];

    // Hiển thị danh sách yêu cầu hoàn/đổi của khách đang đăng nhập.
    // Route: GET /hoan-doi.
    /**
     * Hiển thị danh sách yêu cầu đổi trả.
     */
    public function index(): View
    {
        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('returns.index', [
            // with(['order', 'reason']) load sẵn đơn hàng và lý do để view hiển thị.
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'requests' => ReturnRequest::with(['order', 'reason'])
                // Chỉ lấy yêu cầu của chính user đang đăng nhập.
                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                ->where('user_id', Auth::id())
                // Yêu cầu mới nhất lên trước.
                // Luong: Sap xep du lieu truoc khi tra ve ket qua.
                ->latest('requested_at')
                // Mỗi trang 10 yêu cầu.
                // Luong: Thuc thi truy van va lay ket qua tu CSDL.
                ->paginate(10),
        ]);
    }

    // Mở form tạo yêu cầu hoàn/đổi cho một đơn hàng cụ thể.
    // Route: GET /hoan-doi/don-hang/{order}.
    /**
     * Hiển thị form tạo yêu cầu đổi trả nếu đơn hợp lệ.
     */
    public function create(Request $request, Order $order): View|RedirectResponse
    {
        // Chặn truy cập nếu đơn hàng không thuộc tài khoản đang đăng nhập.
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        abort_unless($order->user_id === Auth::id(), 403);

        // Kiểm tra trạng thái đơn có đủ điều kiện hoàn/đổi không.
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! $this->isOrderEligible($order)) {
            // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
            return redirect()
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->route('account.orders.show', $order)
                // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
                ->with('error', $this->returnIneligibleMessage($order));
        }

        // Load các dòng sản phẩm trong đơn và quan hệ product.
        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $order->load('items.product');

        // Lọc ra những dòng sản phẩm còn số lượng có thể hoàn/đổi.
        // Luong: Gan ket qua xu ly vao bien $returnableItems.
        $returnableItems = $this->returnableItems($order);

        // Nếu tất cả sản phẩm đã được yêu cầu đủ số lượng thì không mở form nữa.
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($returnableItems->isEmpty()) {
            // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
            return redirect()
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->route('account.orders.show', $order)
                // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
                ->with('error', 'Các sản phẩm trong đơn đã được yêu cầu hoàn/đổi đủ số lượng.');
        }

        // Luong: Gan ket qua xu ly vao bien $requestedItemId.
        $requestedItemId = (int) $request->query('item');
        // Luong: Thuc thi truy van va lay ket qua tu CSDL.
        $selectedItem = $returnableItems->firstWhere('id', $requestedItemId) ?? $returnableItems->first();

        // Mở view resources/views/returns/create.blade.php.
        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('returns.create', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'order' => $order,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'returnableItems' => $returnableItems,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'selectedOrderItemId' => $selectedItem?->id,
            // remainingQuantities là mảng order_item_id => số lượng còn được yêu cầu.
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'remainingQuantities' => $returnableItems->mapWithKeys(fn (OrderItem $item) => [
                // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
                $item->id => $this->remainingReturnQuantity($order, $item),
            ]),
            // Chỉ hiển thị lý do đang ACTIVE để khách chọn.
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            'reasons' => ReturnReason::active()->orderBy('name')->get(),
        ]);
    }

    // Lưu yêu cầu hoàn/đổi do khách gửi lên.
    // Route: POST /hoan-doi/don-hang/{order}.
    /**
     * Lưu yêu cầu đổi trả mới.
     */
    public function store(Request $request, Order $order): RedirectResponse
    {
        // Đơn phải thuộc đúng khách hàng hiện tại.
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        abort_unless($order->user_id === Auth::id(), 403);

        // Đơn phải đang ở trạng thái được phép tạo yêu cầu.
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! $this->isOrderEligible($order)) {
            // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
            return redirect()
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->route('account.orders.show', $order)
                // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
                ->with('error', $this->returnIneligibleMessage($order));
        }

        // Kiểm tra dữ liệu form hoàn/đổi.
        // Luong: Kiem tra va lay du lieu hop le tu request.
        $data = $request->validate([
            // type chỉ được RETURN hoặc EXCHANGE theo báo cáo.
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'type' => ['required', Rule::in(['RETURN', 'EXCHANGE'])],
            // reason_id phải tồn tại trong return_reasons và đang ACTIVE.
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            'reason_id' => ['required', Rule::exists('return_reasons', 'id')->where('status', 'ACTIVE')],
            // order_item_id là dòng sản phẩm khách muốn hoàn/đổi.
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'order_item_id' => ['required', 'exists:order_items,id'],
            // Số lượng yêu cầu tối thiểu 1.
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'quantity' => ['required', 'integer', 'min:1'],
            // Mô tả lý do tối đa 1000 ký tự.
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'reason_detail' => ['nullable', 'string', 'max:1000'],
            // Ghi chú tình trạng tối đa 500 ký tự.
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'condition_note' => ['nullable', 'string', 'max:500'],
        ], [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'type.required' => 'Vui lòng chọn loại yêu cầu.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'type.in' => 'Loại yêu cầu không hợp lệ.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'reason_id.required' => 'Vui lòng chọn lý do hoàn/đổi.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'reason_id.exists' => 'Lý do hoàn/đổi không hợp lệ hoặc đã ngừng sử dụng.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'order_item_id.required' => 'Vui lòng chọn sản phẩm cần hoàn/đổi.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'order_item_id.exists' => 'Sản phẩm được chọn không hợp lệ.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'quantity.required' => 'Vui lòng nhập số lượng cần hoàn/đổi.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'quantity.integer' => 'Số lượng phải là số nguyên.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'quantity.min' => 'Số lượng yêu cầu tối thiểu là 1.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'reason_detail.max' => 'Mô tả lý do tối đa 1.000 ký tự.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'condition_note.max' => 'Ghi chú tình trạng tối đa 500 ký tự.',
        ]);

        $reason = ReturnReason::query()->whereKey($data['reason_id'])->first();
        if (! $reason || ! in_array($reason->type, ['BOTH', $data['type']], true)) {
            return back()
                ->withErrors(['reason_id' => 'Lý do đã chọn không phù hợp với loại yêu cầu.'])
                ->withInput();
        }

        if ($reason->code === 'OTHER' && trim((string) ($data['reason_detail'] ?? '')) === '') {
            return back()
                ->withErrors(['reason_detail' => 'Vui lòng ghi rõ lý do khác để nhân viên biết cần xử lý thế nào.'])
                ->withInput();
        }

        // Lấy dòng sản phẩm từ chính quan hệ items của đơn.
        // Việc này đảm bảo sản phẩm được chọn thuộc đúng đơn hàng.
        // Luong: Bo sung dieu kien loc du lieu cho truy van.
        $item = $order->items()->whereKey($data['order_item_id'])->firstOrFail();

        // Tính số lượng còn lại có thể hoàn/đổi của dòng sản phẩm này.
        // Luong: Gan ket qua xu ly vao bien $remainingQuantity.
        $remainingQuantity = $this->remainingReturnQuantity($order, $item);

        // Nếu không còn số lượng nào thì từ chối.
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($remainingQuantity < 1) {
            // Luong: Quay lai trang truoc kem du lieu hoac thong bao can hien thi.
            return back()
                // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
                ->withErrors(['order_item_id' => 'Sản phẩm này đã được yêu cầu hoàn/đổi đủ số lượng.'])
                // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
                ->withInput();
        }

        // Không cho khách yêu cầu nhiều hơn số lượng còn được phép.
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ((int) $data['quantity'] > $remainingQuantity) {
            // Luong: Quay lai trang truoc kem du lieu hoac thong bao can hien thi.
            return back()
                // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
                ->withErrors(['quantity' => 'Số lượng yêu cầu không được vượt quá số lượng còn có thể hoàn/đổi là ' . $remainingQuantity . '.'])
                // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
                ->withInput();
        }

        // Tạo yêu cầu và chi tiết yêu cầu trong transaction.
        // Luong: Mo transaction de cac thao tac CSDL cung thanh cong hoac cung rollback.
        $returnRequest = DB::transaction(function () use ($data, $order, $item) {
            // Tạo bản ghi chính trong bảng return_requests.
            // Luong: Tao ban ghi moi tu du lieu da chuan bi.
            $returnRequest = ReturnRequest::create([
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'return_code' => 'RTN' . now()->format('YmdHis') . Str::upper(Str::random(3)),
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'order_id' => $order->id,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'user_id' => Auth::id(),
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'type' => $data['type'],
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'reason_id' => $data['reason_id'],
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'reason_detail' => $data['reason_detail'] ?? null,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'status' => 'PENDING',
            ]);

            // Luong: Tao ban ghi moi tu du lieu da chuan bi.
            ReturnRequestItem::create([
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'return_request_id' => $returnRequest->id,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'order_item_id' => $item->id,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'quantity' => min((int) $data['quantity'], (int) $item->quantity),
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'condition_note' => $data['condition_note'] ?? null,
            ]);

            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
            if (in_array($order->status, ['DELIVERED', 'RETURNED', 'EXCHANGED'], true)) {
                // Luong: Cap nhat cac ban ghi phu hop voi dieu kien da loc.
                $order->update(['status' => 'RETURN_PENDING']);
            }

            // Luong: Tra ve ket qua cuoi cung cua ham.
            return $returnRequest;
        });

        // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
        return redirect()->route('returns.show', $returnRequest)->with('success', 'Đã gửi yêu cầu hoàn đổi.');
    }

    /**
     * Hiển thị chi tiết yêu cầu đổi trả.
     */
    public function show(ReturnRequest $return): View
    {
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        abort_unless($return->user_id === Auth::id(), 403);

        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('returns.show', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'returnRequest' => $return->load(['order.items', 'items.orderItem', 'reason']),
        ]);
    }

    // Đơn chỉ gửi được yêu cầu hoàn/đổi khi đã ở một trong các trạng thái sau khi nhận hàng.
    /**
     * Kiểm tra đơn hàng có được đổi trả không.
     */
    private function isOrderEligible(Order $order): bool
    {
        return in_array($order->status, self::ELIGIBLE_ORDER_STATUSES, true);
    }

    // Câu giải thích hiển thị ở trang chi tiết đơn khi khách bấm hoàn/đổi không đúng lúc.
    // Nêu rõ lý do và bước tiếp theo, không dùng thông báo chung chung.
    /**
     * Trả về lý do đơn không được đổi trả.
     */
    private function returnIneligibleMessage(Order $order): string
    {
        return match ($order->status) {
            'PENDING', 'AWAITING_PAYMENT', 'CONFIRMED', 'DELAY' =>
                'Đơn hàng chưa được giao nên chưa thể gửi yêu cầu hoàn/đổi. Vui lòng gửi lại sau khi nhận hàng.',
            'DELIVERING' =>
                'Đơn hàng đang trên đường giao. Vui lòng gửi yêu cầu sau khi bạn đã nhận được hàng.',
            'CANCELLED' =>
                'Đơn hàng đã hủy nên không thể gửi yêu cầu hoàn/đổi.',
            'LOST_IN_TRANSIT' =>
                'Đơn hàng bị thất lạc khi vận chuyển. Vui lòng gọi hotline 1900 6789 để được xử lý riêng.',
            default =>
                'Đơn hàng ở trạng thái hiện tại không thể gửi yêu cầu hoàn/đổi.',
        };
    }

    // Những dòng sản phẩm trong đơn còn có thể gửi yêu cầu hoàn/đổi.
    /**
     * Lấy các sản phẩm còn có thể đổi trả.
     */
    private function returnableItems(Order $order): Collection
    {
        return $order->items
            ->filter(fn (OrderItem $item) => $this->remainingReturnQuantity($order, $item) > 0)
            ->values();
    }

    // Số lượng còn được phép hoàn/đổi của một dòng sản phẩm.
    //
    // Bảng return_request_items đặt UNIQUE trên order_item_id, tức mỗi dòng sản phẩm
    // chỉ gắn được đúng MỘT yêu cầu. Vì vậy khi dòng đó đã nằm trong một yêu cầu bất kỳ
    // (kể cả yêu cầu bị từ chối) thì phải trả về 0 — nếu tính theo số lượng còn lại,
    // khách sẽ gửi được form rồi chết ở INSERT vì trùng khóa.
    // Khách vẫn hoàn/đổi được các dòng sản phẩm khác trong cùng đơn.
    /**
     * Tính số lượng còn được phép đổi trả.
     */
    private function remainingReturnQuantity(Order $order, OrderItem $item): int
    {
        $alreadyRequested = ReturnRequestItem::query()
            ->where('order_item_id', $item->id)
            ->whereHas('returnRequest', fn ($query) => $query->where('order_id', $order->id))
            ->exists();

        return $alreadyRequested ? 0 : (int) $item->quantity;
    }
}
