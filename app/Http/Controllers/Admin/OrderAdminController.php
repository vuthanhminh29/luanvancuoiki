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
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class OrderAdminController extends Controller
{
    private OrderCancellationService $cancellations;

    private InventoryService $inventory;

    public function __construct(OrderCancellationService $cancellations, InventoryService $inventory)
    {
        $this->cancellations = $cancellations;
        $this->inventory = $inventory;
    }

    private const VALID_STATUSES = [
        'PENDING',
        'AWAITING_PAYMENT',
        'CONFIRMED',
        'DELIVERING',
        'DELIVERED',
        'CANCELLED',
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

    private const STATUS_TRANSITIONS = [
        'PENDING' => ['CONFIRMED', 'CANCELLED'],
        'AWAITING_PAYMENT' => ['CONFIRMED', 'CANCELLED'],
        'CONFIRMED' => ['DELIVERING', 'CANCELLED'],
        'DELIVERING' => ['DELIVERED'],
        'DELIVERED' => ['RETURN_PENDING'],
        'RETURN_PENDING' => ['RETURNED', 'EXCHANGED', 'DELIVERED'],
        'CANCELLED' => [],
        'RETURNED' => [],
        'EXCHANGED' => [],
    ];

    private const CANCELLABLE_STATUSES = ['PENDING', 'AWAITING_PAYMENT', 'CONFIRMED'];

    public function index(Request $request): View
    {
        return $this->orderList($request, false);
    }

    public function unconfirmed(Request $request): View
    {
        $request->merge(['status' => $request->input('status', 'PENDING')]);

        return $this->orderList($request, true);
    }

    public function show(Order $order): View
    {
        $order->load(['user', 'items.product.images', 'items.variant.color', 'items.variant.lensSize']);

        return view('admin.orders.show', [
            'order' => $order,
            'statusLabels' => self::STATUS_LABELS,
            'statusOptions' => $this->availableStatusOptions($order),
            'canCancelOrder' => $this->canCancelOrder($order),
        ]);
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(self::VALID_STATUSES)],
            'cancel_reason' => ['nullable', 'string', 'max:500'],
        ], [
            'status.required' => 'Vui lòng chọn trạng thái mới cho đơn hàng.',
            'status.in' => 'Trạng thái đơn hàng không hợp lệ.',
            'cancel_reason.max' => 'Lý do hủy đơn tối đa 500 ký tự.',
        ]);

        if ($data['status'] === 'CANCELLED') {
            return $this->cancel($request, $order);
        }

        $result = $this->changeStatus($order, $data['status'], $data['cancel_reason'] ?? null);

        if ($result !== true) {
            return back()->withErrors(['status' => $result])->withInput();
        }

        return back()->with('success', 'Đã cập nhật trạng thái đơn hàng.');
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'cancel_reason' => ['nullable', 'string', 'max:500'],
        ], [
            'cancel_reason.max' => 'Lý do hủy đơn tối đa 500 ký tự.',
        ]);

        $result = $this->cancellations->requestCancellation($order, $data['cancel_reason'] ?? null);

        if ($result !== true) {
            return back()->withErrors(['status' => $result])->withInput();
        }

        return back()->with('success', 'Đã gửi email xác nhận hủy đơn cho khách hàng. Đơn hàng chỉ được hủy sau khi khách bấm xác nhận.');
    }

    /**
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

    private function changeStatusInTransaction(Order $order, string $newStatus, ?string $cancelReason = null)
    {
        return DB::transaction(function () use ($order, $newStatus, $cancelReason) {
            $lockedOrder = Order::query()->lockForUpdate()->find($order->id);

            if (! $lockedOrder) {
                return 'Không tìm thấy đơn hàng cần xử lý.';
            }

            if ($lockedOrder->status === $newStatus) {
                return 'Vui lòng chọn trạng thái mới khác trạng thái hiện tại.';
            }

            if (! in_array($newStatus, $this->nextStatuses($lockedOrder), true)) {
                return 'Không thể chuyển đơn từ trạng thái hiện tại sang trạng thái đã chọn.';
            }

            if ($newStatus === 'CANCELLED' && ! $this->canCancelOrder($lockedOrder)) {
                return 'Không thể hủy đơn hàng ở trạng thái hiện tại.';
            }

            $lockedOrder->forceFill([
                'status' => $newStatus,
                'delivered_at' => $newStatus === 'DELIVERED'
                    ? ($lockedOrder->delivered_at ?: now())
                    : $lockedOrder->delivered_at,
                'note' => $newStatus === 'CANCELLED'
                    ? $this->cancelNote($lockedOrder->note, $cancelReason)
                    : $lockedOrder->note,
            ])->save();

            if ($newStatus === 'DELIVERING') {
                $this->createSaleOutTransaction($lockedOrder);
            }

            return true;
        });
    }

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

            // issue() ném lỗi nếu không đủ tồn -> transaction rollback, đơn giữ nguyên
            // trạng thái cũ. Trước đây decrement() có thể đẩy tồn xuống âm hoặc
            // âm thầm không trừ gì khi chưa có dòng tồn kho.
            $this->inventory->issue($warehouseId, $variantId, $quantity, trim((string) $item->product_name));
        }
    }

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

    private function stockTransactionsHaveRelatedOrderId(): bool
    {
        static $hasColumn = null;

        return $hasColumn ??= Schema::hasColumn('stock_transactions', 'related_order_id');
    }

    private function nextSaleOutTransactionCode(): string
    {
        do {
            $code = 'SALE_OUT' . now()->format('YmdHis') . random_int(100, 999);
        } while (StockTransaction::query()->where('transaction_code', $code)->exists());

        return $code;
    }

    private function saleOutTransactionNote(Order $order): string
    {
        return 'Xuất bán khi cập nhật đơn #' . $order->id . ' sang DELIVERING';
    }

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

    private function canCancelOrder(Order $order): bool
    {
        return $this->cancellations->canCancel($order);
    }

    private function nextStatuses(Order $order): array
    {
        return self::STATUS_TRANSITIONS[$order->status] ?? [];
    }

    private function availableStatusOptions(Order $order): array
    {
        return collect(self::STATUS_LABELS)
            ->only($this->nextStatuses($order))
            ->all();
    }

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
        ]);
    }
}
