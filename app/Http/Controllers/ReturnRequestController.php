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
    public function index(): View
    {
        return view('returns.index', [
            // with(['order', 'reason']) load sẵn đơn hàng và lý do để view hiển thị.
            'requests' => ReturnRequest::with(['order', 'reason'])
                // Chỉ lấy yêu cầu của chính user đang đăng nhập.
                ->where('user_id', Auth::id())
                // Yêu cầu mới nhất lên trước.
                ->latest('requested_at')
                // Mỗi trang 10 yêu cầu.
                ->paginate(10),
        ]);
    }

    // Mở form tạo yêu cầu hoàn/đổi cho một đơn hàng cụ thể.
    // Route: GET /hoan-doi/don-hang/{order}.
    public function create(Request $request, Order $order): View|RedirectResponse
    {
        // Chặn truy cập nếu đơn hàng không thuộc tài khoản đang đăng nhập.
        abort_unless($order->user_id === Auth::id(), 403);

        // Kiểm tra trạng thái đơn có đủ điều kiện hoàn/đổi không.
        if (! $this->isOrderEligible($order)) {
            return redirect()
                ->route('account.orders.show', $order)
                ->with('error', $this->returnIneligibleMessage($order));
        }

        // Load các dòng sản phẩm trong đơn và quan hệ product.
        $order->load('items.product');

        // Lọc ra những dòng sản phẩm còn số lượng có thể hoàn/đổi.
        $returnableItems = $this->returnableItems($order);

        // Nếu tất cả sản phẩm đã được yêu cầu đủ số lượng thì không mở form nữa.
        if ($returnableItems->isEmpty()) {
            return redirect()
                ->route('account.orders.show', $order)
                ->with('error', 'Các sản phẩm trong đơn đã được yêu cầu hoàn/đổi đủ số lượng.');
        }

        $requestedItemId = (int) $request->query('item');
        $selectedItem = $returnableItems->firstWhere('id', $requestedItemId) ?? $returnableItems->first();

        // Mở view resources/views/returns/create.blade.php.
        return view('returns.create', [
            'order' => $order,
            'returnableItems' => $returnableItems,
            'selectedOrderItemId' => $selectedItem?->id,
            // remainingQuantities là mảng order_item_id => số lượng còn được yêu cầu.
            'remainingQuantities' => $returnableItems->mapWithKeys(fn (OrderItem $item) => [
                $item->id => $this->remainingReturnQuantity($order, $item),
            ]),
            // Chỉ hiển thị lý do đang ACTIVE để khách chọn.
            'reasons' => ReturnReason::active()->orderBy('name')->get(),
        ]);
    }

    // Lưu yêu cầu hoàn/đổi do khách gửi lên.
    // Route: POST /hoan-doi/don-hang/{order}.
    public function store(Request $request, Order $order): RedirectResponse
    {
        // Đơn phải thuộc đúng khách hàng hiện tại.
        abort_unless($order->user_id === Auth::id(), 403);

        // Đơn phải đang ở trạng thái được phép tạo yêu cầu.
        if (! $this->isOrderEligible($order)) {
            return redirect()
                ->route('account.orders.show', $order)
                ->with('error', $this->returnIneligibleMessage($order));
        }

        // Kiểm tra dữ liệu form hoàn/đổi.
        $data = $request->validate([
            // type chỉ được RETURN hoặc EXCHANGE theo báo cáo.
            'type' => ['required', Rule::in(['RETURN', 'EXCHANGE'])],
            // reason_id phải tồn tại trong return_reasons và đang ACTIVE.
            'reason_id' => ['required', Rule::exists('return_reasons', 'id')->where('status', 'ACTIVE')],
            // order_item_id là dòng sản phẩm khách muốn hoàn/đổi.
            'order_item_id' => ['required', 'exists:order_items,id'],
            // Số lượng yêu cầu tối thiểu 1.
            'quantity' => ['required', 'integer', 'min:1'],
            // Mô tả lý do tối đa 1000 ký tự.
            'reason_detail' => ['nullable', 'string', 'max:1000'],
            // Ghi chú tình trạng tối đa 500 ký tự.
            'condition_note' => ['nullable', 'string', 'max:500'],
        ], [
            'type.required' => 'Vui lòng chọn loại yêu cầu.',
            'type.in' => 'Loại yêu cầu không hợp lệ.',
            'reason_id.required' => 'Vui lòng chọn lý do hoàn/đổi.',
            'reason_id.exists' => 'Lý do hoàn/đổi không hợp lệ hoặc đã ngừng sử dụng.',
            'order_item_id.required' => 'Vui lòng chọn sản phẩm cần hoàn/đổi.',
            'order_item_id.exists' => 'Sản phẩm được chọn không hợp lệ.',
            'quantity.required' => 'Vui lòng nhập số lượng cần hoàn/đổi.',
            'quantity.integer' => 'Số lượng phải là số nguyên.',
            'quantity.min' => 'Số lượng yêu cầu tối thiểu là 1.',
            'reason_detail.max' => 'Mô tả lý do tối đa 1.000 ký tự.',
            'condition_note.max' => 'Ghi chú tình trạng tối đa 500 ký tự.',
        ]);

        // Lấy dòng sản phẩm từ chính quan hệ items của đơn.
        // Việc này đảm bảo sản phẩm được chọn thuộc đúng đơn hàng.
        $item = $order->items()->whereKey($data['order_item_id'])->firstOrFail();

        // Tính số lượng còn lại có thể hoàn/đổi của dòng sản phẩm này.
        $remainingQuantity = $this->remainingReturnQuantity($order, $item);

        // Nếu không còn số lượng nào thì từ chối.
        if ($remainingQuantity < 1) {
            return back()
                ->withErrors(['order_item_id' => 'Sản phẩm này đã được yêu cầu hoàn/đổi đủ số lượng.'])
                ->withInput();
        }

        // Không cho khách yêu cầu nhiều hơn số lượng còn được phép.
        if ((int) $data['quantity'] > $remainingQuantity) {
            return back()
                ->withErrors(['quantity' => 'Số lượng yêu cầu không được vượt quá số lượng còn có thể hoàn/đổi là ' . $remainingQuantity . '.'])
                ->withInput();
        }

        // Tạo yêu cầu và chi tiết yêu cầu trong transaction.
        $returnRequest = DB::transaction(function () use ($data, $order, $item) {
            // Tạo bản ghi chính trong bảng return_requests.
            $returnRequest = ReturnRequest::create([
                'return_code' => 'RTN' . now()->format('YmdHis') . Str::upper(Str::random(3)),
                'order_id' => $order->id,
                'user_id' => Auth::id(),
                'type' => $data['type'],
                'reason_id' => $data['reason_id'],
                'reason_detail' => $data['reason_detail'] ?? null,
                'status' => 'PENDING',
            ]);

            ReturnRequestItem::create([
                'return_request_id' => $returnRequest->id,
                'order_item_id' => $item->id,
                'quantity' => min((int) $data['quantity'], (int) $item->quantity),
                'condition_note' => $data['condition_note'] ?? null,
            ]);

            return $returnRequest;
        });

        return redirect()->route('returns.show', $returnRequest)->with('success', 'Đã gửi yêu cầu hoàn đổi.');
    }

    public function show(ReturnRequest $return): View
    {
        abort_unless($return->user_id === Auth::id(), 403);

        return view('returns.show', [
            'returnRequest' => $return->load(['order.items', 'items.orderItem', 'reason']),
        ]);
    }

    // Đơn chỉ gửi được yêu cầu hoàn/đổi khi đã ở một trong các trạng thái sau khi nhận hàng.
    private function isOrderEligible(Order $order): bool
    {
        return in_array($order->status, self::ELIGIBLE_ORDER_STATUSES, true);
    }

    // Câu giải thích hiển thị ở trang chi tiết đơn khi khách bấm hoàn/đổi không đúng lúc.
    // Nêu rõ lý do và bước tiếp theo, không dùng thông báo chung chung.
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
    private function remainingReturnQuantity(Order $order, OrderItem $item): int
    {
        $alreadyRequested = ReturnRequestItem::query()
            ->where('order_item_id', $item->id)
            ->whereHas('returnRequest', fn ($query) => $query->where('order_id', $order->id))
            ->exists();

        return $alreadyRequested ? 0 : (int) $item->quantity;
    }
}
