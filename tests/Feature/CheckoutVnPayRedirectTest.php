<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CheckoutVnPayRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_with_vnpay_redirects_to_sandbox_payment_url(): void
    {
        config()->set('vnpay.tmn_code', 'TESTTMN');
        config()->set('vnpay.hash_secret', 'TEST_HASH_SECRET_0123456789');
        config()->set('vnpay.environment', 'sandbox');
        config()->set('vnpay.return_url', 'http://127.0.0.1:8000/vnpay/return');

        $user = User::factory()->create();
        $warehouseId = app(InventoryService::class)->defaultSellableWarehouseId();

        $productId = DB::table('products')->insertGetId([
            'name' => 'Kinh VNPay test',
            'slug' => 'kinh-vnpay-test',
            'base_price' => 500000,
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $variantId = DB::table('product_variants')->insertGetId([
            'product_id' => $productId,
            'sku' => 'SKU-VNPAY-1',
            'variant_price' => 500000,
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('inventories')->insert([
            'warehouse_id' => $warehouseId,
            'variant_id' => $variantId,
            'quantity' => 2,
            'min_stock_level' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['cart' => [$variantId => 1]])
            ->post('/thanh-toan', [
                'recipient_name' => 'Nguyen Van A',
                'recipient_phone' => '0911111111',
                'address_detail' => '1 Nguyen Trai',
                'city' => 'Hồ Chí Minh',
                'payment_method' => 'VNPAY',
            ]);

        $response->assertRedirect();
        $this->assertStringStartsWith(
            'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html?',
            $response->headers->get('Location')
        );
    }
}
