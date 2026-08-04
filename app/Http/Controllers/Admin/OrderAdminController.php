<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderAdminController extends Controller
{
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
        'LOST_IN_TRANSIT' => ['Mất hàng khi giao', 'lost', 'fa-exclamation-triangle'],
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
        return view('admin.orders.show', [
            'order' => $order->load(['user', 'items.product.images', 'items.variant.color', 'items.variant.lensSize']),
        ]);
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:PENDING,AWAITING_PAYMENT,CONFIRMED,DELIVERING,DELIVERED,CANCELLED,RETURN_PENDING,RETURNED,EXCHANGED,LOST_IN_TRANSIT'],
        ]);

        if ($data['status'] === 'CANCELLED' && ! $this->canCancelOrder($order)) {
            return back()->with('error', 'Không thể hủy đơn hàng ở trạng thái hiện tại.');
        }

        $order->update([
            'status' => $data['status'],
            'delivered_at' => $data['status'] === 'DELIVERED' ? now() : $order->delivered_at,
        ]);

        return back()->with('success', 'Đã cập nhật trạng thái đơn hàng.');
    }

    public function cancel(Order $order): RedirectResponse
    {
        if (! $this->canCancelOrder($order)) {
            return back()->with('error', 'Không thể hủy đơn hàng ở trạng thái hiện tại.');
        }

        $order->update(['status' => 'CANCELLED']);

        return back()->with('success', 'Đã hủy đơn hàng.');
    }

    private function canCancelOrder(Order $order): bool
    {
        return in_array($order->status, self::CANCELLABLE_STATUSES, true);
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
            // Đơn mới nhất lên trước.
            ->latest();

        // Trả dữ liệu sang view admin.orders.index.
        return view('admin.orders.index', [
            'orders' => $ordersQuery->paginate(15)->withQueryString(),
            'summary' => [
                'total' => Order::count(),
                'pending' => Order::where('status', 'PENDING')->count(),
                'shipping' => Order::where('status', 'DELIVERING')->count(),
                'completed' => Order::where('status', 'DELIVERED')->count(),
                'returning' => Order::whereIn('status', ['RETURN_PENDING', 'RETURNED', 'EXCHANGED'])->count(),
            ],
            'filters' => $filters,
            'isUnconfirmed' => $isUnconfirmed,
            'statusLabels' => self::STATUS_LABELS,
            // Mảng status_code => label tiếng Việt để view render filter.
            'statusOptions' => collect(self::STATUS_LABELS)->map(fn ($meta) => $meta[0])->all(),
            'cancellableStatuses' => self::CANCELLABLE_STATUSES,
        ]);
    }
}
