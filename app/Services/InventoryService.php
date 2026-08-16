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
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($quantity < 1) {
            // Luong: Tra ve ket qua cuoi cung cua ham.
            return;
        }

        // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
        $affected = DB::table('inventories')
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->where('warehouse_id', $warehouseId)
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->where('variant_id', $variantId)
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->where('quantity', '>=', $quantity)
            // Luong: Cap nhat cac ban ghi phu hop voi dieu kien da loc.
            ->update([
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'quantity' => DB::raw('quantity - ' . $quantity),
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'updated_at' => now(),
            ]);

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($affected === 0) {
            // Luong: Nem loi de dung luong khi dieu kien nghiep vu khong dat.
            throw new RuntimeException(
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'Không đủ tồn kho để xuất' . ($context !== '' ? ' (' . $context . ')' : '') . '.'
            );
        }
    }

    /**
     * Cộng tồn kho. Chưa có dòng thì tạo mới.
     */
    public function receive(int $warehouseId, int $variantId, int $quantity): void
    {
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($quantity < 1) {
            // Luong: Tra ve ket qua cuoi cung cua ham.
            return;
        }

        // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
        $affected = DB::table('inventories')
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->where('warehouse_id', $warehouseId)
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->where('variant_id', $variantId)
            // Luong: Cap nhat cac ban ghi phu hop voi dieu kien da loc.
            ->update([
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'quantity' => DB::raw('quantity + ' . $quantity),
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'updated_at' => now(),
            ]);

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($affected === 0) {
            // Luong: Tao ban ghi moi tu du lieu da chuan bi.
            Inventory::create([
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'warehouse_id' => $warehouseId,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'variant_id' => $variantId,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'quantity' => $quantity,
                // Luong: Thuc thi truy van va lay ket qua tu CSDL.
                'min_stock_level' => Warehouse::whereKey($warehouseId)->value('min_stock_level') ?? 10,
            ]);
        }
    }

    /**
     * Chuyển hàng giữa hai kho, ví dụ hàng lỗi sửa xong thì đưa về kho bán.
     */
    public function transfer(int $sourceWarehouseId, int $targetWarehouseId, int $variantId, int $quantity): void
    {
        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->issue($sourceWarehouseId, $variantId, $quantity, 'chuyển kho');
        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
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
        $warehouseId = Warehouse::query()
            ->where('status', 'ACTIVE')
            ->where('type', '<>', self::QUARANTINE_TYPE)
            ->orderByRaw("warehouse_code = 'KHOCANH' desc")
            ->orderBy('id')
            ->value('id');

        return (int) ($warehouseId ?: 1);
    }

    public function sellableWarehouseIdFor(int $variantId): int
    {
        $warehouseId = Inventory::query()
            ->join('warehouses', 'warehouses.id', '=', 'inventories.warehouse_id')
            ->where('inventories.variant_id', $variantId)
            ->where('inventories.quantity', '>', 0)
            ->where('warehouses.status', 'ACTIVE')
            ->where('warehouses.type', '<>', self::QUARANTINE_TYPE)
            ->orderByDesc('inventories.quantity')
            ->orderBy('warehouses.id')
            ->value('inventories.warehouse_id');

        return (int) ($warehouseId ?: $this->defaultSellableWarehouseId());
    }

    public function quarantineWarehouseId(): int
    {
        $warehouse = Warehouse::query()
            ->where(function ($query) {
                $query->where('type', self::QUARANTINE_TYPE)
                    ->orWhere('warehouse_code', 'KHOLOI');
            })
            ->orderByRaw("warehouse_code = 'KHOLOI' desc")
            ->orderBy('id')
            ->first();

        if (! $warehouse) {
            $warehouse = Warehouse::create([
                'warehouse_code' => 'KHOLOI',
                'name' => 'Kho hàng lỗi / chờ xử lý',
                'type' => self::QUARANTINE_TYPE,
                'capacity' => 10000,
                'address_detail' => 'Khu vực lưu hàng khách hoàn/đổi về, chưa bán lại được.',
                'min_stock_level' => 0,
                'status' => 'ACTIVE',
            ]);
        } elseif ($warehouse->status !== 'ACTIVE' || $warehouse->type !== self::QUARANTINE_TYPE) {
            $warehouse->update([
                'name' => 'Kho hàng lỗi / chờ xử lý',
                'type' => self::QUARANTINE_TYPE,
                'status' => 'ACTIVE',
            ]);
        }

        return (int) $warehouse->id;
    }
}
