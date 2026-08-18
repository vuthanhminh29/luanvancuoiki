<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * /vnpay/return và /vnpay/ipn là hai endpoint công khai, không đăng nhập, nhưng
 * lại đổi trạng thái thanh toán của đơn hàng. Bộ test này khóa lại hai điều:
 *  1. Request có chữ ký sai TUYỆT ĐỐI không được làm thay đổi dữ liệu.
 *  2. Số tiền VNPay trả về phải khớp với đơn phía mình mới được ghi nhận.
 */
class VnPayCallbackTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'TEST_HASH_SECRET_0123456789';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('vnpay.tmn_code', 'TESTTMN');
        config()->set('vnpay.hash_secret', self::SECRET);
    }

    /**
     * Ký tham số đúng cách VnPayService kiểm tra: sắp xếp khóa, urlencode, HMAC-SHA512.
     */
    private function sign(array $params): array
    {
        ksort($params);

        $parts = [];
        foreach ($params as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $parts[] = urlencode((string) $key) . '=' . urlencode((string) $value);
        }

        $params['vnp_SecureHash'] = hash_hmac('sha512', implode('&', $parts), self::SECRET);

        return $params;
    }

    private function callbackParams(string $txnRef, int $amountVnd): array
    {
        return [
            'vnp_Amount' => $amountVnd * 100,
            'vnp_BankCode' => 'NCB',
            'vnp_ResponseCode' => '00',
            'vnp_TransactionStatus' => '00',
            'vnp_TransactionNo' => '14200001',
            'vnp_TxnRef' => $txnRef,
        ];
    }

    private function putDraft(string $orderCode, int $total): void
    {
        Cache::put("pending_vnpay_checkout:{$orderCode}", [
            'order_code' => $orderCode,
            'user_id' => null,
            'data' => [
                'recipient_name' => 'Nguyen Van A',
                'recipient_phone' => '0911111111',
                'payment_method' => 'VNPAY',
                'note' => null,
            ],
            'cart' => [],
            'cart_lens_options' => [],
            'shipping_address' => '1 Nguyen Trai, TP.HCM',
            'subtotal_amount' => $total,
            'discount_amount' => 0,
            'promotion_id' => null,
            'shipping_fee' => 0,
            'total_amount' => $total,
            'expires_at' => now()->addMinutes(15)->toIso8601String(),
        ], now()->addMinutes(15));
    }

    /**
     * HỒI QUY: trước đây return() gọi forgetPendingDraft() TRƯỚC khi kiểm tra chữ ký.
     * vnp_TxnRef do người gửi tự đặt và cache dùng chung toàn hệ thống, nên bất kỳ ai
     * cũng đoán được mã đơn rồi gửi chữ ký rác để xóa draft của khách khác — khách
     * trả tiền xong quay về thì không còn draft để tạo đơn, mất tiền mà không có đơn.
     */
    public function test_chu_ky_sai_khong_duoc_xoa_draft_cua_khach_khac(): void
    {
        $orderCode = 'ORD20260818120000ABC';
        $this->putDraft($orderCode, 500000);

        $response = $this->get('/vnpay/return?' . http_build_query([
            'vnp_TxnRef' => $orderCode,
            'vnp_Amount' => 50000000,
            'vnp_ResponseCode' => '00',
            'vnp_TransactionStatus' => '00',
            'vnp_SecureHash' => str_repeat('f', 128),
        ]));

        $response->assertRedirect(route('checkout.index'));
        $this->assertNotNull(
            Cache::get("pending_vnpay_checkout:{$orderCode}"),
            'Draft bi xoa boi request co chu ky sai — lo hong da quay tro lai.'
        );
    }

    public function test_chu_ky_sai_o_ipn_tra_ve_ma_97_va_khong_doi_don(): void
    {
        $order = $this->makeUnpaidOrder('ORD20260818120001XYZ', 500000);

        $response = $this->post('/vnpay/ipn', [
            'vnp_TxnRef' => $order->order_code,
            'vnp_Amount' => 50000000,
            'vnp_ResponseCode' => '00',
            'vnp_TransactionStatus' => '00',
            'vnp_SecureHash' => str_repeat('a', 128),
        ]);

        $response->assertJson(['RspCode' => '97']);
        $this->assertSame('UNPAID', $order->fresh()->payment_status);
    }

    public function test_chu_ky_dung_thi_don_duoc_danh_dau_da_thanh_toan(): void
    {
        $order = $this->makeUnpaidOrder('ORD20260818120002DEF', 500000);

        $response = $this->post('/vnpay/ipn', $this->sign($this->callbackParams($order->order_code, 500000)));

        $response->assertJson(['RspCode' => '00']);
        $this->assertSame('PAID', $order->fresh()->payment_status);
    }

    /**
     * Số tiền phải đối chiếu với đơn phía mình, không tin số VNPay gửi sang.
     */
    public function test_so_tien_khong_khop_thi_khong_ghi_nhan_da_tra(): void
    {
        $order = $this->makeUnpaidOrder('ORD20260818120003GHI', 500000);

        $response = $this->post('/vnpay/ipn', $this->sign($this->callbackParams($order->order_code, 1000)));

        $response->assertJson(['RspCode' => '04']);
        $this->assertNotSame('PAID', $order->fresh()->payment_status);
    }

    public function test_khong_tim_thay_don_thi_tra_ve_ma_01(): void
    {
        $response = $this->post('/vnpay/ipn', $this->sign($this->callbackParams('ORD_KHONG_TON_TAI', 500000)));

        $response->assertJson(['RspCode' => '01']);
    }

    private function makeUnpaidOrder(string $code, int $total): Order
    {
        $id = DB::table('orders')->insertGetId([
            'order_code' => $code,
            'user_id' => null,
            'recipient_name' => 'Nguyen Van A',
            'recipient_phone' => '0911111111',
            'shipping_address' => '1 Nguyen Trai, TP.HCM',
            'payment_method' => 'VNPAY',
            'payment_status' => 'UNPAID',
            'status' => 'PENDING',
            'subtotal_amount' => $total,
            'discount_amount' => 0,
            'shipping_fee' => 0,
            'total_amount' => $total,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Order::findOrFail($id);
    }
}
