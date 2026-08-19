<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\StockTransaction;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Services\OrderCancellationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class OrderAdminController extends Controller
{
    private OrderCancellationService $cancellations;

    private InventoryService $inventory;

    /**
     * Nhận các service xử lý đơn hàng.
     */
    public function __construct(OrderCancellationService $cancellations, InventoryService $inventory)
    {
        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->cancellations = $cancellations;
        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->inventory = $inventory;
    }

    private const VALID_STATUSES = [
        'PENDING',
        'AWAITING_PAYMENT',
        'CONFIRMED',
        'DELIVERING',
        'DELIVERED',
        'CANCELLED',
        'DELIVERY_FAILED',
        'RETURN_PENDING',
        'RETURNED',
        'EXCHANGED',
    ];

    private const STATUS_LABELS = [
        'PENDING' => ['Chờ xác nhận', 'warning', 'fa-clock'],
        'AWAITING_PAYMENT' => ['Chờ thanh toán', 'warning', 'fa-credit-card'],
        'CONFIRMED' => ['Đã xác nhận', 'info', 'fa-clipboard-check'],
        'DELIVERING' => ['Đang giao', 'moving', 'fa-truck'],
        'DELIVERED' => ['Giao thành công', 'success', 'fa-check-circle'],
        'CANCELLED' => ['Đã hủy', 'danger', 'fa-times-circle'],
        'RETURN_PENDING' => ['Chờ hoàn/đổi', 'return', 'fa-rotate-left'],
        'RETURNED' => ['Đã hoàn trả', 'dark', 'fa-undo'],
        'EXCHANGED' => ['Đã đổi hàng', 'success', 'fa-exchange-alt'],
    ];

    private const STATUS_UPDATE_LABELS = [
        'DELIVERY_FAILED' => ['Giao thất bại', 'danger', 'fa-truck'],
    ];

    private const STATUS_TRANSITIONS = [
        'PENDING' => ['CONFIRMED', 'CANCELLED'],
        'AWAITING_PAYMENT' => ['CONFIRMED', 'CANCELLED'],
        'CONFIRMED' => ['DELIVERING', 'CANCELLED'],
        'DELIVERING' => ['DELIVERED', 'DELIVERY_FAILED'],
        'DELIVERED' => ['RETURN_PENDING'],
        'RETURN_PENDING' => ['RETURNED', 'EXCHANGED', 'DELIVERED'],
        'CANCELLED' => [],
        'RETURNED' => [],
        'EXCHANGED' => [],
    ];

    private const CANCELLABLE_STATUSES = ['PENDING', 'AWAITING_PAYMENT', 'CONFIRMED'];

    /**
     * Hiển thị danh sách đơn hàng đã xác nhận.
     */
    public function index(Request $request): View
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->orderList($request, false);
    }

    /**
     * Hiển thị danh sách đơn hàng chờ xác nhận.
     */
    public function unconfirmed(Request $request): View
    {
        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $request->merge(['status' => $request->input('status', 'PENDING')]);

        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->orderList($request, true);
    }

    /**
     * Hiển thị chi tiết đơn hàng cho admin.
     */
    public function show(Order $order): View
    {
        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $order->load(['user', 'items.product.images', 'items.variant.color', 'items.variant.lensSize']);

        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('admin.orders.show', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'order' => $order,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'statusLabels' => self::STATUS_LABELS,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'statusOptions' => $this->availableStatusOptions($order),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'canCancelOrder' => $this->canCancelOrder($order),
        ]);
    }

    /**
     * Cập nhật trạng thái đơn hàng.
     */
    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        // Luong: Kiem tra va lay du lieu hop le tu request.
        $data = $request->validate([
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'status' => ['required', Rule::in(self::VALID_STATUSES)],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'cancel_reason' => ['nullable', 'string', 'max:500'],
        ], [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'status.required' => 'Vui lòng chọn trạng thái mới cho đơn hàng.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'status.in' => 'Trạng thái đơn hàng không hợp lệ.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'cancel_reason.max' => 'Lý do hủy đơn tối đa 500 ký tự.',
        ]);

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($data['status'] === 'CANCELLED') {
            // Luong: Tra ve ket qua cuoi cung cua ham.
            return $this->cancel($request, $order);
        }

        if ($data['status'] === 'DELIVERY_FAILED') {
            $result = $this->changeStatus($order, 'DELIVERY_FAILED', 'Giao thất bại');

            if ($result !== true) {
                return back()->withErrors(['status' => $result])->withInput();
            }

            return back()->with('success', 'Đã cập nhật đơn hàng là giao thất bại.');
        }

        // Luong: Gan ket qua xu ly vao bien $result.
        $result = $this->changeStatus($order, $data['status'], $data['cancel_reason'] ?? null);

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($result !== true) {
            // Luong: Quay lai trang truoc kem du lieu hoac thong bao can hien thi.
            return back()->withErrors(['status' => $result])->withInput();
        }

        // Luong: Quay lai trang truoc kem du lieu hoac thong bao can hien thi.
        return back()->with('success', 'Đã cập nhật trạng thái đơn hàng.');
    }

    /**
     * Gửi yêu cầu hủy đơn cho khách xác nhận.
     */
    public function cancel(Request $request, Order $order): RedirectResponse
    {
        // Luong: Kiem tra va lay du lieu hop le tu request.
        $data = $request->validate([
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'cancel_reason' => ['nullable', 'string', 'max:500'],
        ], [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'cancel_reason.max' => 'Lý do hủy đơn tối đa 500 ký tự.',
        ]);

        // Luong: Gan ket qua xu ly vao bien $result.
        $result = $this->cancellations->requestCancellation($order, $data['cancel_reason'] ?? null);

        if ($result === OrderCancellationService::AUTO_CANCELLED) {
            return back()->with('success', 'Đã tự hủy đơn sau 3 lần gửi yêu cầu hủy mà khách chưa xác nhận.');
        }

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($result !== true) {
            // Luong: Quay lai trang truoc kem du lieu hoac thong bao can hien thi.
            return back()->withErrors(['status' => $result])->withInput();
        }

        // Luong: Quay lai trang truoc kem du lieu hoac thong bao can hien thi.
        return back()->with('success', 'Đã gửi email xác nhận hủy đơn cho khách hàng. Đơn hàng chỉ được hủy sau khi khách bấm xác nhận.');
    }

    /**
     * Đổi trạng thái đơn hàng và trả lỗi nếu có.
     *
     * @return true|string
     */
    private function changeStatus(Order $order, string $newStatus, ?string $cancelReason = null)
    {
        try {
            return $this->changeStatusInTransaction($order, $newStatus, $cancelReason);
        } catch (RuntimeException $exception) {
            // Không đủ tồn kho để xuất bán: transaction đã rollback, đơn giữ nguyên
            // trạng thái cũ. Trả message để hiển thị cho admin thay vì trang lỗi 500.
            return $exception->getMessage();
        }
    }

    /**
     * Đổi trạng thái đơn hàng trong transaction.
     */
    private function changeStatusInTransaction(Order $order, string $newStatus, ?string $cancelReason = null)
    {
        return DB::transaction(function () use ($order, $newStatus, $cancelReason) {
            $lockedOrder = Order::query()->lockForUpdate()->find($order->id);
            $storedStatus = $newStatus === 'DELIVERY_FAILED' ? 'CANCELLED' : $newStatus;

            if (! $lockedOrder) {
                return 'Không tìm thấy đơn hàng cần xử lý.';
            }

            if ($lockedOrder->status === $storedStatus) {
                return 'Vui lòng chọn trạng thái mới khác trạng thái hiện tại.';
            }

            if (! in_array($newStatus, $this->nextStatuses($lockedOrder), true)) {
                return 'Không thể chuyển đơn từ trạng thái hiện tại sang trạng thái đã chọn.';
            }

            if ($storedStatus === 'CANCELLED' && $newStatus !== 'DELIVERY_FAILED' && ! $this->canCancelOrder($lockedOrder)) {
                return 'Không thể hủy đơn hàng ở trạng thái hiện tại.';
            }

            $lockedOrder->forceFill([
                'status' => $storedStatus,
                'cancel_reason' => $storedStatus === 'CANCELLED'
                    ? ($this->normalizeAdminCancelReason($cancelReason) ?: $lockedOrder->cancel_reason)
                    : $lockedOrder->cancel_reason,
                'cancel_requested_at' => $newStatus === 'DELIVERY_FAILED' ? now() : $lockedOrder->cancel_requested_at,
                'cancel_confirmed_at' => $newStatus === 'DELIVERY_FAILED' ? now() : $lockedOrder->cancel_confirmed_at,
                'cancel_confirmation_token_hash' => $newStatus === 'DELIVERY_FAILED' ? null : $lockedOrder->cancel_confirmation_token_hash,
                'delivered_at' => $storedStatus === 'DELIVERED'
                    ? ($lockedOrder->delivered_at ?: now())
                    : $lockedOrder->delivered_at,
                'note' => $storedStatus === 'CANCELLED'
                    ? $this->cancelNote($lockedOrder->note, $cancelReason)
                    : $lockedOrder->note,
            ])->save();

            // Admin hủy đơn trực tiếp thì cũng phải trả hàng đã giữ về kho.
            if ($storedStatus === 'CANCELLED') {
                $this->inventory->releaseForOrder($lockedOrder);
            }

            if ($storedStatus === 'DELIVERING') {
                $this->createSaleOutTransaction($lockedOrder);
            }

            return true;
        });
    }

    /**
     * Tạo giao dịch xuất kho cho đơn hàng.
     */
    private function createSaleOutTransaction(Order $order): void
    {
        $order->loadMissing('items');

        $items = $order->items
            ->filter(fn ($item) => (int) $item->variant_id > 0 && (int) $item->quantity > 0)
            ->values();

        if ($items->isEmpty() || $this->saleOutTransactionExists($order)) {
            return;
        }

        $payload = [
            'transaction_code' => $this->nextSaleOutTransactionCode(),
            'type' => 'SALE_OUT',
            'source_warehouse_id' => $this->inventory->defaultSellableWarehouseId(),
            'target_warehouse_id' => null,
            'status' => 'COMPLETED',
            'expected_date' => null,
            'note' => $this->saleOutTransactionNote($order),
            'created_by' => Auth::id(),
            'confirmed_by' => Auth::id(),
            'confirmed_at' => now(),
        ];

        if ($this->stockTransactionsHaveRelatedOrderId()) {
            $payload['related_order_id'] = $order->id;
        }

        $transaction = StockTransaction::create($payload);

        foreach ($items as $item) {
            $variantId = (int) $item->variant_id;
            $quantity = (int) $item->quantity;

            // Chọn kho theo từng biến thể: đơn có nhiều sản phẩm nằm ở các kho khác
            // nhau vẫn trừ đúng chỗ, thay vì trừ hết vào một kho chung.
            $warehouseId = $this->inventory->sellableWarehouseIdFor($variantId);

            if (! $warehouseId) {
                throw new RuntimeException(
                    'Chưa có kho bán nào để xuất hàng cho ' . (trim((string) $item->product_name) ?: 'sản phẩm') . '.'
                );
            }

            $transaction->items()->create([
                'variant_id' => $variantId,
                'ordered_quantity' => $quantity,
                'actual_quantity' => $quantity,
                'unit_cost' => null,
                'note' => trim((string) $item->product_name) ?: null,
            ]);

            // KHÔNG trừ tồn kho ở đây nữa. Tồn đã bị trừ ngay lúc tạo đơn
            // (InventoryService::reserveForOrder), nếu trừ thêm lần nữa ở bước
            // DELIVERING thì mỗi đơn sẽ bị trừ hai lần. Phiếu xuất kho bên dưới
            // vẫn được lập để giữ nguyên chứng từ cho nghiệp vụ kho.
        }
    }

    /**
     * Kiểm tra đơn đã có giao dịch xuất kho chưa.
     */
    private function saleOutTransactionExists(Order $order): bool
    {
        if ($this->stockTransactionsHaveRelatedOrderId()) {
            $existsByOrder = StockTransaction::query()
                ->where('type', 'SALE_OUT')
                ->where('related_order_id', $order->id)
                ->exists();

            if ($existsByOrder) {
                return true;
            }
        }

        return StockTransaction::query()
            ->where('type', 'SALE_OUT')
            ->where('note', $this->saleOutTransactionNote($order))
            ->exists();
    }

    /**
     * Kiểm tra bảng giao dịch kho có cột đơn hàng không.
     */
    private function stockTransactionsHaveRelatedOrderId(): bool
    {
        static $hasColumn = null;

        return $hasColumn ??= Schema::hasColumn('stock_transactions', 'related_order_id');
    }

    /**
     * Tạo mã xuất kho bán hàng mới.
     */
    private function nextSaleOutTransactionCode(): string
    {
        do {
            $code = 'SALE_OUT' . now()->format('YmdHis') . random_int(100, 999);
        } while (StockTransaction::query()->where('transaction_code', $code)->exists());

        return $code;
    }

    /**
     * Tạo ghi chú xuất kho cho đơn hàng.
     */
    private function saleOutTransactionNote(Order $order): string
    {
        return 'Xuất bán khi cập nhật đơn #' . $order->id . ' sang DELIVERING';
    }

    /**
     * Tìm kho đủ hàng để xuất cho đơn.
     */
    private function saleOutSourceWarehouseId(Order $order): ?int
    {
        $variantIds = $order->items
            ->pluck('variant_id')
            ->filter()
            ->unique()
            ->values();

        if ($variantIds->isNotEmpty()) {
            $warehouseId = Inventory::query()
                ->whereIn('variant_id', $variantIds)
                ->whereHas('warehouse', fn ($query) => $query->where('status', 'ACTIVE'))
                ->orderByDesc('quantity')
                ->value('warehouse_id');

            if ($warehouseId) {
                return (int) $warehouseId;
            }

            $warehouseId = Inventory::query()
                ->whereIn('variant_id', $variantIds)
                ->orderByDesc('quantity')
                ->value('warehouse_id');

            if ($warehouseId) {
                return (int) $warehouseId;
            }
        }

        $warehouseId = Warehouse::active()->orderBy('id')->value('id')
            ?: Warehouse::query()->orderBy('id')->value('id');

        return $warehouseId ? (int) $warehouseId : null;
    }

    /**
     * Kiểm tra đơn hàng có thể hủy không.
     */
    private function canCancelOrder(Order $order): bool
    {
        return $this->cancellations->canCancel($order);
    }

    /**
     * Lấy các trạng thái tiếp theo hợp lệ.
     */
    private function nextStatuses(Order $order): array
    {
        return self::STATUS_TRANSITIONS[$order->status] ?? [];
    }

    /**
     * Lấy các trạng thái admin có thể chọn.
     */
    private function availableStatusOptions(Order $order): array
    {
        return collect(array_replace(self::STATUS_LABELS, self::STATUS_UPDATE_LABELS))
            ->only($this->nextStatuses($order))
            ->all();
    }

    private function normalizeAdminCancelReason(?string $cancelReason): ?string
    {
        $cancelReason = trim((string) $cancelReason);

        return $cancelReason === '' ? null : $cancelReason;
    }

    /**
     * Thêm lý do hủy vào ghi chú đơn.
     */
    private function cancelNote(?string $currentNote, ?string $cancelReason): ?string
    {
        $cancelReason = trim((string) $cancelReason);

        if ($cancelReason === '') {
            return $currentNote;
        }

        $line = '[Hủy đơn ' . now()->format('d/m/Y H:i') . '] ' . $cancelReason;
        $currentNote = trim((string) $currentNote);

        return $currentNote === '' ? $line : $currentNote . PHP_EOL . $line;
    }

    /**
     * Lấy danh sách đơn hàng cho trang admin.
     */
    private function orderList(Request $request, bool $isUnconfirmed): View
    {
        $filters = $request->only(['status', 'payment_method', 'keyword', 'date_from', 'date_to']);

        $ordersQuery = Order::query()
            ->with('user')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('payment_method'), fn ($query) => $query->where('payment_method', $request->payment_method))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('created_at', '<=', $request->date_to))
            ->when($request->filled('keyword'), function ($query) use ($request) {
                $keyword = '%' . trim((string) $request->keyword) . '%';
                $query->where(function ($subQuery) use ($keyword) {
                    $subQuery
                        ->where('order_code', 'like', $keyword)
                        ->orWhere('recipient_name', 'like', $keyword)
                        ->orWhere('recipient_phone', 'like', $keyword)
                        ->orWhereHas('user', fn ($userQuery) => $userQuery
                            ->where('full_name', 'like', $keyword)
                            ->orWhere('email', 'like', $keyword));
                });
            })
            ->latest();

        $summaryRow = Order::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) as pending")
            ->selectRaw("SUM(CASE WHEN status = 'DELIVERING' THEN 1 ELSE 0 END) as shipping")
            ->selectRaw("SUM(CASE WHEN status = 'DELIVERED' THEN 1 ELSE 0 END) as completed")
            ->selectRaw("SUM(CASE WHEN status IN ('RETURN_PENDING', 'RETURNED', 'EXCHANGED') THEN 1 ELSE 0 END) as returning")
            ->first();

        return view('admin.orders.index', [
            'orders' => $ordersQuery->paginate(20)->withQueryString(),
            'summary' => [
                'total' => (int) ($summaryRow->total ?? 0),
                'pending' => (int) ($summaryRow->pending ?? 0),
                'shipping' => (int) ($summaryRow->shipping ?? 0),
                'completed' => (int) ($summaryRow->completed ?? 0),
                'returning' => (int) ($summaryRow->returning ?? 0),
            ],
            'filters' => $filters,
            'isUnconfirmed' => $isUnconfirmed,
            'statusLabels' => self::STATUS_LABELS,
            'statusOptions' => collect(self::STATUS_LABELS)->map(fn ($meta) => $meta[0])->all(),
            'cancellableStatuses' => self::CANCELLABLE_STATUSES,
            'customerCancellationRequests' => $this->customerCancellationRequests(),
        ]);
    }

    private function customerCancellationRequests(): Collection
    {
        return Order::query()
            ->with('user')
            ->where('status', 'CONFIRMED')
            ->whereNotNull('cancel_requested_at')
            ->whereNull('cancel_confirmation_token_hash')
            ->where('note', 'like', '%[Khách yêu cầu hủy đơn%')
            ->latest('cancel_requested_at')
            ->limit(5)
            ->get();
    }
}
