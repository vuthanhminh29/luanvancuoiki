<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminOrderDeliveryFailedTest extends TestCase
{
    use RefreshDatabase;

    private int $variantId;

    private int $warehouseId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->warehouseId = app(InventoryService::class)->defaultSellableWarehouseId();

        $productId = DB::table('products')->insertGetId([
            'name' => 'Kinh giao that bai',
            'slug' => 'kinh-giao-that-bai',
            'base_price' => 500000,
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->variantId = DB::table('product_variants')->insertGetId([
            'product_id' => $productId,
            'sku' => 'SKU-DELIVERY-FAILED',
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

    public function test_admin_cap_nhat_don_dang_giao_sang_giao_that_bai(): void
    {
        $admin = $this->adminUser();
        $order = $this->reservedDeliveringOrder(2);

        $this->assertSame(1, $this->stock());

        $response = $this->actingAs($admin)->put(route('admin.orders.status', $order), [
            'status' => 'DELIVERY_FAILED',
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHas('success', 'Đã cập nhật đơn hàng là giao thất bại.');

        $order = $order->fresh();

        $this->assertSame('CANCELLED', $order->status);
        $this->assertSame('Giao thất bại', $order->cancel_reason);
        $this->assertNotNull($order->cancel_requested_at);
        $this->assertNotNull($order->cancel_confirmed_at);
        $this->assertNull($order->stock_reserved_at);
        $this->assertSame(3, $this->stock());
    }

    public function test_admin_thay_option_giao_that_bai_khi_don_dang_giao(): void
    {
        $admin = $this->adminUser();
        $order = $this->reservedDeliveringOrder(1);

        $response = $this->actingAs($admin)->get(route('admin.orders.show', $order));

        $response
            ->assertOk()
            ->assertSee('Giao thất bại');
    }

    private function adminUser(): User
    {
        $admin = User::factory()->create();
        $roleId = DB::table('roles')->insertGetId([
            'code' => 'ADMIN',
            'name' => 'Admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('user_roles')->insert([
            'user_id' => $admin->id,
            'role_id' => $roleId,
        ]);

        return $admin;
    }

    private function reservedDeliveringOrder(int $quantity): Order
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
            'product_name' => 'Kinh giao that bai',
            'quantity' => $quantity,
            'unit_price' => 500000,
            'discount_amount' => 0,
            'total_price' => 500000 * $quantity,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $order = Order::findOrFail($orderId);
        app(InventoryService::class)->reserveForOrder($order);

        $order->forceFill(['status' => 'DELIVERING'])->save();

        return $order->fresh();
    }

    private function stock(): int
    {
        return (int) DB::table('inventories')
            ->where('warehouse_id', $this->warehouseId)
            ->where('variant_id', $this->variantId)
            ->value('quantity');
    }
}
