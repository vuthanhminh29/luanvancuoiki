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
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

class ReturnAdminController extends Controller
{
    private InventoryService $inventory;

    /**
     * Nhận service tồn kho cho xử lý đổi trả.
     */
    public function __construct(InventoryService $inventory)
    {
        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->inventory = $inventory;
    }

    /**
     * Hiển thị danh sách yêu cầu đổi trả.
     */
    public function index(Request $request): View
    {
        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('admin.returns.index', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'requests' => ReturnRequest::with(['order', 'user', 'reason'])
                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                ->when($request->filled('type'), fn ($query) => $query->where('type', $request->type))
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->when($request->filled('keyword'), function ($query) use ($request) {
                    // Luong: Gan ket qua xu ly vao bien $keyword.
                    $keyword = '%' . $request->keyword . '%';
                    // Luong: Bo sung dieu kien loc du lieu cho truy van.
                    $query->where(function ($inner) use ($keyword) {
                        // Luong: Bo sung dieu kien loc du lieu cho truy van.
                        $inner->where('return_code', 'like', $keyword)
                            // Luong: Bo sung dieu kien loc du lieu cho truy van.
                            ->orWhereHas('order', fn ($order) => $order->where('order_code', 'like', $keyword))
                            // Luong: Bo sung dieu kien loc du lieu cho truy van.
                            ->orWhereHas('user', fn ($user) => $user->where('full_name', 'like', $keyword)->orWhere('email', 'like', $keyword))
                            // Luong: Bo sung dieu kien loc du lieu cho truy van.
                            ->orWhereHas('reason', fn ($reason) => $reason->where('name', 'like', $keyword));
                    });
                })
                // Luong: Sap xep du lieu truoc khi tra ve ket qua.
                ->latest('requested_at')
                // Luong: Thuc thi truy van va lay ket qua tu CSDL.
                ->paginate(15)
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->withQueryString(),
        ]);
    }

    /**
     * Hiển thị chi tiết yêu cầu đổi trả.
     */
    public function show(ReturnRequest $return): View
    {
        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('admin.returns.show', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'returnRequest' => $return->load(['order.items', 'user', 'reason', 'items.orderItem.product', 'images', 'damageAssessments']),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'damageParts' => $this->damagePartOptions(),
        ]);
    }

    /**
     * Cập nhật trạng thái yêu cầu đổi trả.
     */
    public function update(Request $request, ReturnRequest $return): RedirectResponse
    {
        // Luong: Kiem tra va lay du lieu hop le tu request.
        $data = $request->validate([
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'status' => ['required', 'in:PENDING,APPROVED,REJECTED,RECEIVED,COMPLETED,CANCELLED'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'admin_note' => ['nullable', 'string', 'max:1000'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'damage' => ['nullable', 'array'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'damage.*.percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'damage.*.description' => ['nullable', 'string', 'max:1000'],
        ]);

        // Luong: Gan ket qua xu ly vao bien $oldStatus.
        $oldStatus = $return->status;

        // Luong: Bat dau khoi xu ly co the phat sinh loi.
        try {
            // Luong: Mo transaction de cac thao tac CSDL cung thanh cong hoac cung rollback.
            DB::transaction(function () use ($data, $return, $oldStatus) {
                // Luong: Cap nhat cac ban ghi phu hop voi dieu kien da loc.
                $return->update([
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'status' => $data['status'],
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'admin_note' => $data['admin_note'] ?? null,
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'reviewed_by' => Auth::id() ?? $return->reviewed_by,
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'reviewed_at' => now(),
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'completed_at' => $data['status'] === 'COMPLETED' ? now() : $return->completed_at,
                ]);

                // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
                $this->saveDamageAssessments($return, $data['damage'] ?? []);

                // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
                $this->processReturnStockMovement($return, $oldStatus, $data['status']);
                // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
                $this->syncOrderReturnStatus($return);
            });
        // Luong: Bat va xu ly loi phat sinh trong khoi try.
        } catch (RuntimeException $exception) {
            // Thiếu tồn kho để giao hàng đổi: giữ nguyên trạng thái cũ và báo cho admin,
            // thay vì để trang lỗi 500 mà không ai biết vì sao.
            // Luong: Quay lai trang truoc kem du lieu hoac thong bao can hien thi.
            return back()
                // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
                ->withErrors(['status' => $exception->getMessage()])
                // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
                ->withInput();
        }

        // Luong: Quay lai trang truoc kem du lieu hoac thong bao can hien thi.
        return back()->with('success', 'Đã cập nhật yêu cầu hoàn đổi.');
    }

    /**
     * Xử lý tồn kho khi đổi trả đổi trạng thái.
     */
    private function processReturnStockMovement(ReturnRequest $return, string $oldStatus, string $newStatus): void
    {
        if ($oldStatus === $newStatus) {
            return;
        }

        // items.orderItem vì bảng return_request_items KHÔNG có cột variant_id;
        // biến thể phải lấy qua dòng đơn hàng gốc.
        $return->loadMissing(['items.orderItem', 'order', 'reason']);

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
            if ($this->shouldReturnToSellableStock($return)) {
                $this->receiveSellableReturnedGoods($return, $lines);
            } else {
                $this->receiveFaultyGoods($return, $lines);
            }

            return;
        }

        if ($newStatus === 'COMPLETED' && $return->type === 'EXCHANGE' && $oldStatus !== 'COMPLETED') {
            $this->issueExchangeGoods($return, $lines);
        }
    }

    /**
     * Đồng bộ trạng thái đơn hàng theo trạng thái yêu cầu hoàn/đổi mới nhất.
     */
    private function syncOrderReturnStatus(ReturnRequest $return): void
    {
        $return->loadMissing('order');

        if (! $return->order || $return->order->status === 'CANCELLED') {
            return;
        }

        $requests = ReturnRequest::query()
            ->where('order_id', $return->order_id)
            ->get(['status', 'type', 'requested_at']);

        if ($requests->whereIn('status', ['PENDING', 'APPROVED', 'RECEIVED'])->isNotEmpty()) {
            $targetStatus = 'RETURN_PENDING';
        } else {
            $completed = $requests
                ->where('status', 'COMPLETED')
                ->sortByDesc('requested_at')
                ->first();

            $targetStatus = match ($completed?->type) {
                'RETURN' => 'RETURNED',
                'EXCHANGE' => 'EXCHANGED',
                default => 'DELIVERED',
            };
        }

        if ($return->order->status !== $targetStatus) {
            $return->order->update(['status' => $targetStatus]);
        }
    }

    /**
     * Nhập hàng lỗi vào kho cách ly.
     */
    private function receiveFaultyGoods(ReturnRequest $return, Collection $lines): void
    {
        $warehouseId = $this->inventory->quarantineWarehouseId();
        $reasonText = $this->returnReasonText($return);

        $transaction = StockTransaction::create([
            'transaction_code' => $this->nextTransactionCode('RET'),
            'type' => 'RETURN_IN',
            'target_warehouse_id' => $warehouseId,
            'related_order_id' => $return->order_id,
            'status' => 'COMPLETED',
            'note' => 'Nhập hàng hoàn/đổi vào kho lỗi từ yêu cầu ' . $return->return_code . '. Lý do: ' . $reasonText,
            'created_by' => Auth::id(),
            'confirmed_by' => Auth::id(),
            'confirmed_at' => now(),
        ]);

        foreach ($lines as $line) {
            $transaction->items()->create([
                'variant_id' => $line['variant_id'],
                'ordered_quantity' => $line['quantity'],
                'actual_quantity' => $line['quantity'],
                'note' => 'Hàng khách trả về, chờ đánh giá. Lý do: ' . $reasonText,
            ]);

            $this->inventory->receive($warehouseId, $line['variant_id'], $line['quantity']);
        }
    }

    /**
     * Nhập lại kho bán khi khách trả vì không còn nhu cầu mua, không phải lỗi sản phẩm.
     */
    private function receiveSellableReturnedGoods(ReturnRequest $return, Collection $lines): void
    {
        $warehouseId = $this->inventory->defaultSellableWarehouseId();
        $reasonText = $this->returnReasonText($return);

        $transaction = StockTransaction::create([
            'transaction_code' => $this->nextTransactionCode('RS'),
            'type' => 'RETURN_IN',
            'target_warehouse_id' => $warehouseId,
            'related_order_id' => $return->order_id,
            'status' => 'COMPLETED',
            'note' => 'Nhập lại kho bán từ yêu cầu ' . $return->return_code . '. Lý do: ' . $reasonText,
            'created_by' => Auth::id(),
            'confirmed_by' => Auth::id(),
            'confirmed_at' => now(),
        ]);

        foreach ($lines as $line) {
            $transaction->items()->create([
                'variant_id' => $line['variant_id'],
                'ordered_quantity' => $line['quantity'],
                'actual_quantity' => $line['quantity'],
                'note' => 'Hàng khách trả lại, đủ điều kiện nhập kho bán. Lý do: ' . $reasonText,
            ]);

            $this->inventory->receive($warehouseId, $line['variant_id'], $line['quantity']);
        }
    }

    /** Xuất sản phẩm mới từ kho bán để giao đổi cho khách. */
    /**
     * Xuất hàng đổi cho khách.
     */
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

    /**
     * Chỉ lý do khách không còn nhu cầu mua mới được nhập thẳng lại kho bán.
     */
    private function shouldReturnToSellableStock(ReturnRequest $return): bool
    {
        if ($return->type !== 'RETURN') {
            return false;
        }

        $code = strtoupper((string) ($return->reason?->code ?? ''));
        $name = $this->normalizeVietnamese($return->reason?->name ?? '');

        return in_array($code, ['NOT_WANTED', 'CHANGE_MIND', 'NO_LONGER_NEEDED'], true)
            || str_contains($name, 'khong mua nua')
            || str_contains($name, 'doi y')
            || str_contains($name, 'khong con nhu cau');
    }

    private function returnReasonText(ReturnRequest $return): string
    {
        $reason = trim((string) ($return->reason?->name ?? 'Không rõ lý do'));
        $detail = trim((string) $return->reason_detail);

        return $detail !== '' ? $reason . ' - ' . $detail : $reason;
    }

    private function normalizeVietnamese(string $value): string
    {
        return Str::ascii(Str::lower($value));
    }

    /**
     * Tạo mã giao dịch kho cho đổi trả.
     */
    private function nextTransactionCode(string $prefix): string
    {
        do {
            $code = $prefix . now()->format('YmdHis') . random_int(100, 999);
        } while (StockTransaction::where('transaction_code', $code)->exists());

        return $code;
    }

    /**
     * Lấy danh sách bộ phận cần đánh giá lỗi.
     */
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

    /**
     * Lưu đánh giá hư hỏng của sản phẩm trả về.
     */
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

    /**
     * Đổi phần trăm hư hỏng thành mức độ lỗi.
     */
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
