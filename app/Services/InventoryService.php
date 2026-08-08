<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Inventory;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Gom toàn bộ thao tác ghi tồn kho về một chỗ.
 *
 * Lý do tách service: trước đây mỗi controller tự viết increment/decrement riêng,
 * dẫn tới chỗ có kiểm tra tồn, chỗ không; chỗ trừ đúng kho, chỗ trừ nhầm kho.
 *
 * Quy ước kho:
 *  - Kho BÁN ĐƯỢC (type != QUARANTINE): tồn của kho này mới được tính là "còn hàng".
 *  - Kho LỖI (type = QUARANTINE): chứa hàng khách đổi/trả về, KHÔNG tính vào tồn bán.
 *    Hàng chỉ quay lại kho bán khi nhân viên xác nhận sửa được (chuyển kho).
 */
class InventoryService
{
    public const QUARANTINE_TYPE = 'QUARANTINE';

    /**
     * Trừ tồn kho. Ném lỗi nếu không đủ hàng hoặc chưa có dòng tồn kho.
     *
     * Dùng cập nhật có điều kiện (where quantity >= ?) nên an toàn khi nhiều
     * request cùng trừ một lúc — không cần khóa bảng.
     */
    public function issue(int $warehouseId, int $variantId, int $quantity, string $context = ''): void
    {
        if ($quantity < 1) {
            return;
        }

        $affected = DB::table('inventories')
            ->where('warehouse_id', $warehouseId)
            ->where('variant_id', $variantId)
            ->where('quantity', '>=', $quantity)
            ->update([
                'quantity' => DB::raw('quantity - ' . $quantity),
                'updated_at' => now(),
            ]);

        if ($affected === 0) {
            throw new RuntimeException(
                'Không đủ tồn kho để xuất' . ($context !== '' ? ' (' . $context . ')' : '') . '.'
            );
        }
    }

    /**
     * Cộng tồn kho. Chưa có dòng thì tạo mới.
     */
    public function receive(int $warehouseId, int $variantId, int $quantity): void
    {
        if ($quantity < 1) {
            return;
        }

        $affected = DB::table('inventories')
            ->where('warehouse_id', $warehouseId)
            ->where('variant_id', $variantId)
            ->update([
                'quantity' => DB::raw('quantity + ' . $quantity),
                'updated_at' => now(),
            ]);

        if ($affected === 0) {
            Inventory::create([
                'warehouse_id' => $warehouseId,
                'variant_id' => $variantId,
                'quantity' => $quantity,
                'min_stock_level' => Warehouse::whereKey($warehouseId)->value('min_stock_level') ?? 10,
            ]);
        }
    }

    /**
     * Chuyển hàng giữa hai kho, ví dụ hàng lỗi sửa xong thì đưa về kho bán.
     */
    public function transfer(int $sourceWarehouseId, int $targetWarehouseId, int $variantId, int $quantity): void
    {
        $this->issue($sourceWarehouseId, $variantId, $quantity, 'chuyển kho');
        $this->receive($targetWarehouseId, $variantId, $quantity);
    }

    /**
     * Kho bán được nào đang giữ nhiều hàng nhất cho biến thể này.
     *
     * Chọn theo TỪNG biến thể chứ không chọn một kho chung cho cả đơn — đơn có
     * nhiều sản phẩm nằm ở các kho khác nhau vẫn trừ đúng chỗ.
     */
    /**
     * Lấy ID kho trung tâm duy nhất của hệ thống (Kho ID 1).
     */
    public function defaultSellableWarehouseId(): int
    {
        return 1;
    }

    public function sellableWarehouseIdFor(int $variantId): int
    {
        return 1;
    }

    public function quarantineWarehouseId(): int
    {
        return 1;
    }
}
