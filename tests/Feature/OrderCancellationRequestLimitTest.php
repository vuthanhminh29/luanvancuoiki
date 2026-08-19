<?php

namespace Tests\Feature;

use App\Mail\CustomerOrderCancellationReceiptMail;
use App\Models\Order;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\OrderCancellationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderCancellationRequestLimitTest extends TestCase
{
    use RefreshDatabase;

    private int $variantId;
    private int $warehouseId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->warehouseId = app(InventoryService::class)->defaultSellableWarehouseId();

        $productId = DB::table('products')->insertGetId([
            'name' => 'Kinh test huy don',
            'slug' => 'kinh-test-huy-don',
            'base_price' => 500000,
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->variantId = DB::table('product_variants')->insertGetId([
            'product_id' => $productId,
            'sku' => 'SKU-CANCEL-1',
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

    public function test_lan_gui_huy_thu_ba_tu_huy_don_va_hoan_ton(): void
    {
        $order = $this->makeReservedOrder(2);
        $service = app(OrderCancellationService::class);

        $this->assertSame(1, $this->stock());
        $this->assertTrue($service->requestCancellation($order, 'Khach bom hang'));

        $order = $order->fresh();
        $this->assertSame('PENDING', $order->status);
        $this->assertSame(1, $order->cancel_request_count);
        $this->assertSame(1, $this->stock());

        $this->assertTrue($service->requestCancellation($order, 'Khach bom hang'));

        $order = $order->fresh();
        $this->assertSame('PENDING', $order->status);
        $this->assertSame(2, $order->cancel_request_count);
        $this->assertSame(1, $this->stock());

        $this->assertSame(
            OrderCancellationService::AUTO_CANCELLED,
            $service->requestCancellation($order, 'Khach bom hang')
        );

        $order = $order->fresh();
        $this->assertSame('CANCELLED', $order->status);
        $this->assertSame(3, $order->cancel_request_count);
        $this->assertNull($order->cancel_confirmation_token_hash);
        $this->assertNull($order->stock_reserved_at);
        $this->assertStringContainsString('Khach bom hang', (string) $order->note);
        $this->assertSame(3, $this->stock());
    }

    public function test_khach_huy_don_chua_xac_nhan_duoc_gui_email_bien_nhan(): void
    {
        Mail::fake();

        $order = $this->makeReservedOrder(1);

        $this->assertTrue(
            app(OrderCancellationService::class)->requestCancellationFromCustomer($order, 'Khach doi y')
        );

        $order = $order->fresh();
        $this->assertSame('CANCELLED', $order->status);

        Mail::assertSent(CustomerOrderCancellationReceiptMail::class, function (CustomerOrderCancellationReceiptMail $mail) {
            $body = $mail->render();

            return $mail->hasTo('customer@example.com')
                && $mail->directCancelled === true
                && str_contains($mail->subjectLine(), 'đã được hủy')
                && str_contains($body, 'Đơn hàng của bạn đã được hủy theo yêu cầu.')
                && ! str_contains($body, 'xac-nhan-huy')
                && ! str_contains($body, 'http://')
                && ! str_contains($body, 'https://');
        });
    }

    public function test_khach_huy_don_da_xac_nhan_duoc_gui_email_ghi_nhan_yeu_cau(): void
    {
        Mail::fake();

        $order = $this->makeReservedOrder(1);
        $order->forceFill(['status' => 'CONFIRMED'])->save();

        $this->assertSame(
            OrderCancellationService::CUSTOMER_REVIEW_REQUESTED,
            app(OrderCancellationService::class)->requestCancellationFromCustomer($order, 'Khach muon huy')
        );

        $order = $order->fresh();
        $this->assertSame('CONFIRMED', $order->status);
        $this->assertNotNull($order->cancel_requested_at);
        $this->assertNull($order->cancel_confirmation_token_hash);

        Mail::assertSent(CustomerOrderCancellationReceiptMail::class, function (CustomerOrderCancellationReceiptMail $mail) {
            $body = $mail->render();

            return $mail->hasTo('customer@example.com')
                && $mail->directCancelled === false
                && str_contains($mail->subjectLine(), 'Đã ghi nhận yêu cầu hủy')
                && str_contains($body, 'Cửa hàng đã ghi nhận yêu cầu hủy đơn của bạn')
                && ! str_contains($body, 'xac-nhan-huy')
                && ! str_contains($body, 'http://')
                && ! str_contains($body, 'https://');
        });
    }

    private function stock(): int
    {
        return (int) DB::table('inventories')
            ->where('warehouse_id', $this->warehouseId)
            ->where('variant_id', $this->variantId)
            ->value('quantity');
    }

    private function makeReservedOrder(int $quantity): Order
    {
        $user = User::factory()->create(['email' => 'customer@example.com']);

        $orderId = DB::table('orders')->insertGetId([
            'order_code' => 'ORD' . uniqid(),
            'user_id' => $user->id,
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
            'product_name' => 'Kinh test huy don',
            'quantity' => $quantity,
            'unit_price' => 500000,
            'discount_amount' => 0,
            'total_price' => 500000 * $quantity,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $order = Order::findOrFail($orderId);
        app(InventoryService::class)->reserveForOrder($order);

        return $order->fresh();
    }
}
