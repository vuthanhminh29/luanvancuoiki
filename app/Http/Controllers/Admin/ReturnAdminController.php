<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReturnDamageAssessment;
use App\Models\ReturnRequest;
use App\Models\StockTransaction;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class ReturnAdminController extends Controller
{
    private InventoryService $inventory;

    public function __construct(InventoryService $inventory)
    {
        $this->inventory = $inventory;
    }

    public function index(Request $request): View
    {
        return view('admin.returns.index', [
            'requests' => ReturnRequest::with(['order', 'user', 'reason'])
                ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
                ->when($request->filled('type'), fn ($query) => $query->where('type', $request->type))
                ->when($request->filled('keyword'), function ($query) use ($request) {
                    $keyword = '%' . $request->keyword . '%';
                    $query->where(function ($inner) use ($keyword) {
                        $inner->where('return_code', 'like', $keyword)
                            ->orWhereHas('order', fn ($order) => $order->where('order_code', 'like', $keyword))
                            ->orWhereHas('user', fn ($user) => $user->where('full_name', 'like', $keyword)->orWhere('email', 'like', $keyword))
                            ->orWhereHas('reason', fn ($reason) => $reason->where('name', 'like', $keyword));
                    });
                })
                ->latest('requested_at')
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function show(ReturnRequest $return): View
    {
        return view('admin.returns.show', [
            'returnRequest' => $return->load(['order.items', 'user', 'reason', 'items.orderItem.product', 'images', 'damageAssessments']),
            'damageParts' => $this->damagePartOptions(),
        ]);
    }

    public function update(Request $request, ReturnRequest $return): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:PENDING,APPROVED,REJECTED,RECEIVED,COMPLETED,CANCELLED'],
            'admin_note' => ['nullable', 'string', 'max:1000'],
            'damage' => ['nullable', 'array'],
            'damage.*.percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'damage.*.description' => ['nullable', 'string', 'max:1000'],
        ]);

        $oldStatus = $return->status;

        try {
            DB::transaction(function () use ($data, $return, $oldStatus) {
                $return->update([
                    'status' => $data['status'],
                    'admin_note' => $data['admin_note'] ?? null,
                    'reviewed_by' => Auth::id() ?? $return->reviewed_by,
                    'reviewed_at' => now(),
                    'completed_at' => $data['status'] === 'COMPLETED' ? now() : $return->completed_at,
                ]);

                $this->saveDamageAssessments($return, $data['damage'] ?? []);

                $this->processReturnStockMovement($return, $oldStatus, $data['status']);
            });
        } catch (RuntimeException $exception) {
            // Thiếu tồn kho để giao hàng đổi: giữ nguyên trạng thái cũ và báo cho admin,
            // thay vì để trang lỗi 500 mà không ai biết vì sao.
            return back()
                ->withErrors(['status' => $exception->getMessage()])
                ->withInput();
        }

        return back()->with('success', 'Đã cập nhật yêu cầu hoàn đổi.');
    }

    /**
     * Điều chuyển kho theo trạng thái yêu cầu hoàn/đổi.
     *
     * RECEIVED  : cửa hàng đã nhận lại hàng lỗi -> nhập vào KHO LỖI (không tính vào tồn bán).
     * COMPLETED : nếu là ĐỔI HÀNG thì xuất một sản phẩm mới từ KHO BÁN giao cho khách.
     *
     * Hai bước tách riêng vì đó là hai lần hàng di chuyển thật, không phải một.
     */
    private function processReturnStockMovement(ReturnRequest $return, string $oldStatus, string $newStatus): void
    {
        if ($oldStatus === $newStatus) {
            return;
        }

        // items.orderItem vì bảng return_request_items KHÔNG có cột variant_id;
        // biến thể phải lấy qua dòng đơn hàng gốc.
        $return->loadMissing(['items.orderItem', 'order']);

        $lines = $return->items
            ->map(fn ($item) => [
                'variant_id' => (int) ($item->orderItem->variant_id ?? 0),
                'quantity' => (int) $item->quantity,
                'name' => (string) ($item->orderItem->product_name ?? ''),
            ])
            ->filter(fn (array $line) => $line['variant_id'] > 0 && $line['quantity'] > 0)
            ->values();

        if ($lines->isEmpty()) {
            return;
        }

        if ($newStatus === 'RECEIVED' && $oldStatus !== 'RECEIVED') {
            $this->receiveFaultyGoods($return, $lines);

            return;
        }

        if ($newStatus === 'COMPLETED' && $return->type === 'EXCHANGE' && $oldStatus !== 'COMPLETED') {
            $this->issueExchangeGoods($return, $lines);
        }
    }

    /** Nhập hàng khách trả về vào kho lỗi. */
    private function receiveFaultyGoods(ReturnRequest $return, Collection $lines): void
    {
        $warehouseId = $this->inventory->quarantineWarehouseId();

        $transaction = StockTransaction::create([
            'transaction_code' => $this->nextTransactionCode('RET'),
            'type' => 'RETURN_IN',
            'target_warehouse_id' => $warehouseId,
            'related_order_id' => $return->order_id,
            'status' => 'COMPLETED',
            'note' => 'Nhập hàng hoàn đổi từ yêu cầu ' . $return->return_code,
            'created_by' => Auth::id(),
            'confirmed_by' => Auth::id(),
            'confirmed_at' => now(),
        ]);

        foreach ($lines as $line) {
            $transaction->items()->create([
                'variant_id' => $line['variant_id'],
                'ordered_quantity' => $line['quantity'],
                'actual_quantity' => $line['quantity'],
                'note' => 'Hàng khách trả về, chờ đánh giá',
            ]);

            $this->inventory->receive($warehouseId, $line['variant_id'], $line['quantity']);
        }
    }

    /** Xuất sản phẩm mới từ kho bán để giao đổi cho khách. */
    private function issueExchangeGoods(ReturnRequest $return, Collection $lines): void
    {
        $transaction = StockTransaction::create([
            'transaction_code' => $this->nextTransactionCode('EXC'),
            'type' => 'EXCHANGE_OUT',
            'source_warehouse_id' => $this->inventory->defaultSellableWarehouseId(),
            'related_order_id' => $return->order_id,
            'status' => 'COMPLETED',
            'note' => 'Xuất đổi hàng mới cho yêu cầu ' . $return->return_code,
            'created_by' => Auth::id(),
            'confirmed_by' => Auth::id(),
            'confirmed_at' => now(),
        ]);

        foreach ($lines as $line) {
            // Chọn kho bán theo từng biến thể, tránh trừ nhầm kho khi đơn có nhiều sản phẩm.
            $warehouseId = $this->inventory->sellableWarehouseIdFor($line['variant_id']);

            if (! $warehouseId) {
                throw new RuntimeException('Chưa có kho bán để xuất hàng đổi cho ' . ($line['name'] ?: 'sản phẩm') . '.');
            }

            $transaction->items()->create([
                'variant_id' => $line['variant_id'],
                'ordered_quantity' => $line['quantity'],
                'actual_quantity' => $line['quantity'],
                'note' => 'Hàng mới giao đổi',
            ]);

            $this->inventory->issue($warehouseId, $line['variant_id'], $line['quantity'], $line['name']);
        }
    }

    private function nextTransactionCode(string $prefix): string
    {
        do {
            $code = $prefix . now()->format('YmdHis') . random_int(100, 999);
        } while (StockTransaction::where('transaction_code', $code)->exists());

        return $code;
    }

    private function damagePartOptions(): array
    {
        return [
            'FRAME_LEFT' => 'Gọng trái',
            'FRAME_RIGHT' => 'Gọng phải',
            'LENS_LEFT' => 'Tròng trái',
            'LENS_RIGHT' => 'Tròng phải',
            'HINGE' => 'Bản lề / ốc vít',
            'NOSE_PAD' => 'Đệm mũi',
            'ACCESSORY' => 'Phụ kiện / hộp kính',
            'OTHER' => 'Khác',
        ];
    }

    private function saveDamageAssessments(ReturnRequest $return, array $damageRows): void
    {
        ReturnDamageAssessment::where('return_request_id', $return->id)->delete();

        foreach ($this->damagePartOptions() as $partCode => $partName) {
            $row = $damageRows[$partCode] ?? [];
            $rawPercent = $row['percent'] ?? null;
            $description = trim((string) ($row['description'] ?? ''));

            if (($rawPercent === null || $rawPercent === '') && $description === '') {
                continue;
            }

            $percent = max(0, min(100, (int) $rawPercent));

            ReturnDamageAssessment::create([
                'return_request_id' => $return->id,
                'part_code' => $partCode,
                'part_name' => $partName,
                'damage_percent' => $percent,
                'damage_level' => $this->damageLevelFromPercent($percent),
                'description' => $description,
                'assessed_by' => Auth::id() ?? 1,
                'assessed_at' => now(),
            ]);
        }
    }

    private function damageLevelFromPercent(int $percent): string
    {
        if ($percent === 0) {
            return 'NONE';
        }

        if ($percent <= 20) {
            return 'LIGHT';
        }

        if ($percent <= 50) {
            return 'MEDIUM';
        }

        if ($percent <= 80) {
            return 'HEAVY';
        }

        return 'SEVERE';
    }
}
