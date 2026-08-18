<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

/**
 * Trước đây tồn kho chỉ bị trừ khi admin chuyển đơn sang DELIVERING, nên từ lúc
 * khách đặt tới lúc giao, hàng vẫn được tính là còn bán -> bán vượt tồn.
 * Bộ test này khóa lại hành vi mới: trừ khi tạo đơn, hoàn khi hủy, và không
 * bao giờ trừ/hoàn hai lần.
 */
class OrderStockReservationTest extends TestCase
{
    use RefreshDatabase;

    private int $variantId;
    private int $warehouseId;

    protected function setUp(): void
    {
        parent::setUp();

        // Migration đã tạo sẵn kho bán, dùng đúng kho mà InventoryService sẽ chọn
        // thay vì tự chèn kho mới (id 1 đã tồn tại).
        $this->warehouseId = app(InventoryService::class)->defaultSellableWarehouseId();

        $productId = DB::table('products')->insertGetId([
            'name' => 'Kính test',
            'slug' => 'kinh-test',
            'base_price' => 500000,
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->variantId = DB::table('product_variants')->insertGetId([
            'product_id' => $productId,
            'sku' => 'SKU-TEST-1',
            'variant_price' => 500000,
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('inventories')->insert([
            'warehouse_id' => $this->warehouseId,
            'variant_id' => $this->variantId,
            'quantity' => 3,
            'min_stock_level' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function stock(): int
    {
        return (int) DB::table('inventories')
            ->where('warehouse_id', $this->warehouseId)
            ->where('variant_id', $this->variantId)
            ->value('quantity');
    }

    private function makeOrder(int $quantity): Order
    {
        $orderId = DB::table('orders')->insertGetId([
            'order_code' => 'ORD' . uniqid(),
            'recipient_name' => 'Nguyen Van A',
            'recipient_phone' => '0911111111',
            'shipping_address' => '1 Nguyen Trai',
            'payment_method' => 'COD',
            'payment_status' => 'UNPAID',
            'status' => 'PENDING',
            'subtotal_amount' => 500000 * $quantity,
            'discount_amount' => 0,
            'shipping_fee' => 0,
            'total_amount' => 500000 * $quantity,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('order_items')->insert([
            'order_id' => $orderId,
            'variant_id' => $this->variantId,
            'product_name' => 'Kính test',
            'quantity' => $quantity,
            'unit_price' => 500000,
            'discount_amount' => 0,
            'total_price' => 500000 * $quantity,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Order::findOrFail($orderId);
    }

    public function test_tao_don_thi_tru_ton_kho_ngay(): void
    {
        $order = $this->makeOrder(2);

        app(InventoryService::class)->reserveForOrder($order);

        $this->assertSame(1, $this->stock(), 'Ton kho phai giam ngay khi tao don.');
        $this->assertNotNull($order->fresh()->stock_reserved_at);
    }

    public function test_khong_tru_ton_hai_lan_cho_cung_mot_don(): void
    {
        $order = $this->makeOrder(2);
        $service = app(InventoryService::class);

        $service->reserveForOrder($order);
        // IPN và return của VNPay có thể cùng chạy trên một đơn.
        $service->reserveForOrder($order->fresh());

        $this->assertSame(1, $this->stock(), 'Ton kho bi tru hai lan.');
    }

    public function test_khong_du_ton_thi_nem_loi_va_khong_doi_kho(): void
    {
        $order = $this->makeOrder(5);

        $this->expectException(RuntimeException::class);

        try {
            app(InventoryService::class)->reserveForOrder($order);
        } finally {
            $this->assertSame(3, $this->stock(), 'Ton kho khong duoc thay doi khi khong du hang.');
            $this->assertNull($order->fresh()->stock_reserved_at);
        }
    }

    public function test_huy_don_thi_hoan_ton_kho(): void
    {
        $order = $this->makeOrder(2);
        $service = app(InventoryService::class);

        $service->reserveForOrder($order);
        $this->assertSame(1, $this->stock());

        $service->releaseForOrder($order->fresh());

        $this->assertSame(3, $this->stock(), 'Huy don phai tra hang ve kho.');
        $this->assertNull($order->fresh()->stock_reserved_at);
    }

    public function test_khong_hoan_hai_lan(): void
    {
        $order = $this->makeOrder(2);
        $service = app(InventoryService::class);

        $service->reserveForOrder($order);
        $service->releaseForOrder($order->fresh());
        $service->releaseForOrder($order->fresh());

        $this->assertSame(3, $this->stock(), 'Ton kho bi cong tra hai lan.');
    }

    /**
     * Đơn tạo TRƯỚC khi có cơ chế giữ hàng có stock_reserved_at = NULL.
     * Hủy chúng mà vẫn cộng trả sẽ thổi phồng tồn kho bằng hàng chưa từng bị trừ.
     */
    public function test_don_cu_chua_tung_giu_hang_thi_huy_khong_cong_ton(): void
    {
        $order = $this->makeOrder(2);

        app(InventoryService::class)->releaseForOrder($order);

        $this->assertSame(3, $this->stock(), 'Don cu khong duoc cong tra ton kho.');
    }
}
