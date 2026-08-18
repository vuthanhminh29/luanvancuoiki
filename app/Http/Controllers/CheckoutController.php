<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Promotion;
use App\Models\ProductVariant;
use App\Services\InventoryService;
use App\Services\OrderConfirmationEmailService;
use App\Services\VnPayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

class CheckoutController extends Controller
{
    private const MAX_CART_QUANTITY = 10;

    /**
     * Hiển thị trang thanh toán.
     */
    public function index(): View
    {
        // Luong: Gan ket qua xu ly vao bien $cart.
        $cart = session('cart', []);
        // Luong: Gan ket qua xu ly vao bien $cartLensOptions.
        $cartLensOptions = $this->normalizedCartLensOptions((array) session('cart_lens_options', []), $cart);
        // Luong: Gan ket qua xu ly vao bien $items.
        $items = ProductVariant::query()
            // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
            ->with(['product.brand', 'color', 'lensSize'])
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->whereIn('id', array_keys($cart))
            // Luong: Thuc thi truy van va lay ket qua tu CSDL.
            ->get()
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->map(function (ProductVariant $variant) use ($cart, $cartLensOptions) {
                // Luong: Gan ket qua xu ly vao bien $quantity.
                $quantity = (int) ($cart[$variant->id] ?? 0);
                // Luong: Gan ket qua xu ly vao bien $lensOption.
                $lensOption = $cartLensOptions[$variant->id] ?? null;
                // Luong: Gan ket qua xu ly vao bien $lensUnitPrice.
                $lensUnitPrice = (float) ($lensOption['price'] ?? 0);
                // Luong: Gan ket qua xu ly vao bien $unitPrice.
                $unitPrice = (float) $variant->display_price + $lensUnitPrice;

                // Luong: Tra ve ket qua cuoi cung cua ham.
                return [
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'variant' => $variant,
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'quantity' => $quantity,
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'lens_option' => $lensOption,
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'unit_price' => $unitPrice,
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'line_total' => $unitPrice * $quantity,
                ];
            });
        // Luong: Gan ket qua xu ly vao bien $subtotal.
        $subtotal = (float) $items->sum('line_total');
        // Luong: Gan ket qua xu ly vao bien $promotion.
        $promotion = $this->currentPromotion($subtotal);
        // Luong: Gan ket qua xu ly vao bien $discountAmount.
        $discountAmount = $promotion?->discountFor($subtotal) ?? 0.0;

        // Luong: Gan ket qua xu ly vao bien $defaultAddress.
        $defaultAddress = Auth::user()?->addresses()
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            ->orderByDesc('is_default')
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            ->latest()
            // Luong: Thuc thi truy van va lay ket qua tu CSDL.
            ->first();

        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('checkout.index', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'items' => $items,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'defaultAddress' => $defaultAddress,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'appliedPromotion' => $promotion,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'discountAmount' => $discountAmount,
        ]);
    }

    /**
     * Áp dụng mã giảm giá cho đơn hàng.
     */
    public function applyPromotion(Request $request): RedirectResponse
    {
        // Luong: Kiem tra va lay du lieu hop le tu request.
        $data = $request->validate([
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'promotion_code' => ['required', 'string', 'max:20'],
        ], [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'promotion_code.required' => 'Vui lòng nhập mã giảm giá.',
        ]);

        // Luong: Gan ket qua xu ly vao bien $cart.
        $cart = $this->normalizedCart();
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($cart === []) {
            // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng đang trống.');
        }

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($this->totalQuantity($cart) > self::MAX_CART_QUANTITY) {
            // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
            return redirect()->route('cart.index')->with('error', 'Mỗi đơn chỉ đặt tối đa ' . self::MAX_CART_QUANTITY . ' sản phẩm. Vui lòng giảm số lượng trong giỏ.');
        }

        // Luong: Gan ket qua xu ly vao bien $cartLensOptions.
        $cartLensOptions = $this->normalizedCartLensOptions((array) session('cart_lens_options', []), $cart);
        // Luong: Gan ket qua xu ly vao bien $variants.
        $variants = $this->cartVariants($cart);
        // Luong: Gan ket qua xu ly vao bien $subtotal.
        $subtotal = $this->cartSubtotal($variants, $cart, $cartLensOptions);
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        [$promotion, $message] = $this->promotionFromCode((string) $data['promotion_code'], $subtotal);

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! $promotion) {
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            session()->forget('checkout_promotion_code');

            // Luong: Quay lai trang truoc kem du lieu hoac thong bao can hien thi.
            return back()->withInput()->with('error', $message);
        }

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        session()->put('checkout_promotion_code', $promotion->promotion_code);

        // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
        return redirect()
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->route('checkout.index')
            // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
            ->withInput($request->except('_token'))
            // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
            ->with('success', 'Đã áp dụng mã giảm giá ' . $promotion->promotion_code . '.');
    }

    /**
     * Gỡ mã giảm giá đang áp dụng.
     */
    public function removePromotion(Request $request): RedirectResponse
    {
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        session()->forget('checkout_promotion_code');

        // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
        return redirect()
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->route('checkout.index')
            // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
            ->withInput($request->except(['_token', 'promotion_code']))
            // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
            ->with('success', 'Đã bỏ mã giảm giá.');
    }

    /**
     * Tạo đơn hàng hoặc chuyển sang thanh toán VNPay.
     */
    public function store(Request $request, VnPayService $vnPay, OrderConfirmationEmailService $orderConfirmationEmail, InventoryService $inventory): RedirectResponse
    {
        // Luong: Kiem tra va lay du lieu hop le tu request.
        $data = $request->validate([
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'recipient_name' => ['required', 'string', 'max:100'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'recipient_phone' => ['required', 'regex:/^(03|05|07|08|09)\d{8}$/'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'address_detail' => ['required', 'string', 'max:200'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'city' => ['required', 'string', 'max:100'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'payment_method' => ['required', 'in:COD,VNPAY'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'note' => ['nullable', 'string', 'max:1000'],
        ], [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'recipient_phone.required' => 'Số điện thoại không được để trống.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'recipient_phone.regex' => 'Số điện thoại không đúng định dạng.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'address_detail.required' => 'Địa chỉ chi tiết không được để trống.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'address_detail.max' => 'Địa chỉ chi tiết tối đa 200 ký tự.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'city.required' => 'Vui lòng chọn Tỉnh/Thành phố.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'payment_method.required' => 'Vui lòng chọn phương thức thanh toán.',
        ]);

        // Luong: Gan ket qua xu ly vao bien $cart.
        $cart = $this->normalizedCart();

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($cart === []) {
            // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
            return redirect()->route('cart.index')->with('success', 'Giỏ hàng đang trống.');
        }

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($this->totalQuantity($cart) > self::MAX_CART_QUANTITY) {
            // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
            return redirect()->route('cart.index')->with('error', 'Mỗi đơn chỉ đặt tối đa ' . self::MAX_CART_QUANTITY . ' sản phẩm. Vui lòng giảm số lượng trong giỏ.');
        }

        // Luong: Gan ket qua xu ly vao bien $variants.
        $variants = ProductVariant::query()
            // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
            ->with('product')
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->whereIn('id', array_keys($cart))
            // Luong: Thuc thi truy van va lay ket qua tu CSDL.
            ->get();

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($variants->isEmpty() || $variants->count() !== count($cart)) {
            // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
            return redirect()->route('cart.index')->with('success', 'Sản phẩm trong giỏ hàng không còn hợp lệ.');
        }

        // Luong: Lap qua tung phan tu de xu ly lan luot.
        foreach ($variants as $variant) {
            // Luong: Gan ket qua xu ly vao bien $requestedQty.
            $requestedQty = (int) ($cart[$variant->id] ?? 0);
            // Tính tồn theo mọi kho bán được, giống giỏ hàng và trang sản phẩm.
            // Trước đây chỗ này đọc cứng warehouse_id = 1 nên báo sai khi hàng
            // nằm ở kho khác.
            // Luong: Gan ket qua xu ly vao bien $stock.
            $stock = $inventory->sellableQuantityFor((int) $variant->id);

            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
            if ($requestedQty > $stock) {
                // Luong: Gan ket qua xu ly vao bien $name.
                $name = $variant->product?->name ?? 'Sản phẩm';
                // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
                if ($stock <= 0) {
                    // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
                    return redirect()->route('cart.index')->with('error', 'Sản phẩm "' . $name . '" hiện đã hết hàng.');
                }

                // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
                return redirect()->route('cart.index')->with('error', 'Sản phẩm "' . $name . '" chỉ còn ' . $stock . ' sản phẩm trong kho. Vui lòng giảm số lượng trong giỏ.');
            }
        }

        // Luong: Gan ket qua xu ly vao bien $cartLensOptions.
        $cartLensOptions = $this->normalizedCartLensOptions((array) session('cart_lens_options', []), $cart);
        // Luong: Gan ket qua xu ly vao bien $subtotal.
        $subtotal = $this->cartSubtotal($variants, $cart, $cartLensOptions);
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        [$promotion, $discountMessage] = $this->appliedPromotion($subtotal);

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($discountMessage !== null) {
            // Luong: Quay lai trang truoc kem du lieu hoac thong bao can hien thi.
            return back()->withInput()->with('error', $discountMessage);
        }

        // Luong: Gan ket qua xu ly vao bien $discountAmount.
        $discountAmount = $promotion?->discountFor($subtotal) ?? 0.0;

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($data['payment_method'] === 'VNPAY' && ! $vnPay->isConfigured()) {
            // Luong: Quay lai trang truoc kem du lieu hoac thong bao can hien thi.
            return back()->withInput()->with('error', 'Chưa cấu hình VNPay. Vui lòng kiểm tra VNPAY_TMN_CODE và VNPAY_HASH_SECRET trong .env.');
        }

        // Luong: Gan ket qua xu ly vao bien $shippingAddress.
        $shippingAddress = collect([
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            trim((string) $data['address_detail']),
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            trim((string) $data['city']),
        ])->filter()->implode(', ');

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($data['payment_method'] === 'VNPAY') {
            // Luong: Bat dau khoi xu ly co the phat sinh loi.
            try {
                // Luong: Gan ket qua xu ly vao bien $draft.
                $draft = $this->storePendingVnPayCheckout($data, $cart, $variants, $cartLensOptions, $shippingAddress, $promotion, $discountAmount);
                // Luong: Gan ket qua xu ly vao bien $paymentOrder.
                $paymentOrder = new Order([
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'order_code' => $draft['order_code'],
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'total_amount' => $draft['total_amount'],
                ]);

                // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
                return redirect()->away($vnPay->createPaymentUrl($paymentOrder, $request));
            // Luong: Bat va xu ly loi phat sinh trong khoi try.
            } catch (RuntimeException $exception) {
                // Luong: Quay lai trang truoc kem du lieu hoac thong bao can hien thi.
                return back()->withInput()->with('error', 'Không tạo được thanh toán VNPay: ' . $exception->getMessage());
            }
        }

        // createOrder() chạy trong transaction; nếu mã giảm giá vừa bị người khác
        // dùng hết lượt thì nó ném RuntimeException và toàn bộ đơn được rollback.
        // Luong: Bat dau khoi xu ly co the phat sinh loi.
        try {
            // Luong: Gan ket qua xu ly vao bien $order.
            $order = $this->createOrder($data, $cart, $variants, $cartLensOptions, $shippingAddress, $promotion, $discountAmount, $inventory);
        } catch (RuntimeException $exception) {
            // Luong: Quay lai trang truoc kem du lieu hoac thong bao can hien thi.
            return back()->withInput()->with('error', $exception->getMessage());
        }

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $orderConfirmationEmail->send($order);

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        session()->forget(['cart', 'cart_lens_options', 'checkout_promotion_code']);

        // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
        return redirect()->route('account.orders.show', $order)->with('success', 'Đặt hàng thành công.');
    }

    /**
     * Tạo đơn hàng và các dòng sản phẩm.
     */
    private function createOrder(array $data, array $cart, $variants, array $cartLensOptions, string $shippingAddress, ?Promotion $promotion, float $discountAmount, InventoryService $inventory): Order
    {
        return DB::transaction(function () use ($data, $cart, $variants, $cartLensOptions, $shippingAddress, $promotion, $discountAmount, $inventory) {
            // Kiểm tra tồn kho LẦN NỮA bên trong transaction và có khóa dòng.
            // Lần kiểm tra ở store() nằm ngoài transaction nên chỉ là kiểm tra sớm
            // để báo lỗi cho khách; hai khách bấm đặt cùng lúc vẫn có thể cùng vượt
            // qua nó. lockForUpdate() ở đây buộc request thứ hai phải chờ và đọc lại
            // số tồn thật sau khi request thứ nhất xong.
            foreach ($variants as $variant) {
                $requestedQty = (int) ($cart[$variant->id] ?? 0);
                $stock = $inventory->sellableQuantityFor((int) $variant->id, true);

                if ($requestedQty > $stock) {
                    $name = $variant->product?->name ?? 'Sản phẩm';

                    throw new RuntimeException($stock <= 0
                        ? 'Sản phẩm "' . $name . '" vừa hết hàng. Vui lòng cập nhật lại giỏ hàng.'
                        : 'Sản phẩm "' . $name . '" chỉ còn ' . $stock . ' sản phẩm trong kho. Vui lòng giảm số lượng trong giỏ.');
                }
            }

            $subtotal = $this->cartSubtotal($variants, $cart, $cartLensOptions);
            $shippingFee = 0;
            $discountAmount = min($discountAmount, (float) $subtotal);

            $order = Order::create([
                'order_code' => 'ORD' . now()->format('YmdHis') . Str::upper(Str::random(3)),
                'user_id' => Auth::id(),
                'recipient_name' => trim((string) $data['recipient_name']),
                'recipient_phone' => trim((string) $data['recipient_phone']),
                'shipping_address' => $shippingAddress,
                'payment_method' => (string) $data['payment_method'],
                'payment_status' => 'UNPAID',
                'status' => 'PENDING',
                'subtotal_amount' => $subtotal,
                'discount_amount' => $discountAmount,
                'shipping_fee' => $shippingFee,
                'total_amount' => max(0, $subtotal - $discountAmount) + $shippingFee,
                'promotion_id' => $promotion?->id,
                'note' => isset($data['note']) ? trim((string) $data['note']) : null,
            ]);

            if ($promotion) {
                $this->claimPromotionSlot($promotion->id);
            }

            foreach ($variants as $variant) {
                $quantity = (int) $cart[$variant->id];
                $lensOption = $cartLensOptions[$variant->id] ?? null;
                $unitPrice = (float) $variant->display_price + (float) ($lensOption['price'] ?? 0);
                $productName = Str::limit($variant->product->name . ($lensOption ? ' + Tròng kính: ' . $lensOption['name'] : ''), 200, '');

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $variant->product_id,
                    'variant_id' => $variant->id,
                    'product_name' => $productName,
                    'sku' => $variant->sku,
                    'color_name' => $variant->color->name ?? null,
                    'lens_size_name' => $variant->lensSize->name ?? null,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount_amount' => 0,
                    'total_price' => $unitPrice * $quantity,
                ]);
            }

            // Giữ hàng ngay khi tạo đơn. Nếu không đủ tồn, issue() ném lỗi và
            // toàn bộ transaction (đơn + dòng hàng + lượt mã giảm giá) bị rollback.
            $inventory->reserveForOrder($order);

            return $order;
        });
    }

    /**
     * Lưu tạm đơn VNPay trước khi thanh toán xong.
     */
    private function storePendingVnPayCheckout(array $data, array $cart, $variants, array $cartLensOptions, string $shippingAddress, ?Promotion $promotion, float $discountAmount): array
    {
        $subtotal = $this->cartSubtotal($variants, $cart, $cartLensOptions);
        $shippingFee = 0;
        $discountAmount = min($discountAmount, (float) $subtotal);
        $expiresAt = now()->addMinutes((int) config('vnpay.expire_time', 30));
        $orderCode = 'ORD' . now()->format('YmdHis') . Str::upper(Str::random(3));

        $draft = [
            'order_code' => $orderCode,
            'user_id' => Auth::id(),
            'data' => [
                'recipient_name' => trim((string) $data['recipient_name']),
                'recipient_phone' => trim((string) $data['recipient_phone']),
                'payment_method' => 'VNPAY',
                'note' => isset($data['note']) ? trim((string) $data['note']) : null,
            ],
            'cart' => $cart,
            'cart_lens_options' => $cartLensOptions,
            'shipping_address' => $shippingAddress,
            'subtotal_amount' => $subtotal,
            'discount_amount' => $discountAmount,
            'promotion_id' => $promotion?->id,
            'promotion_code' => $promotion?->promotion_code,
            'shipping_fee' => $shippingFee,
            'total_amount' => max(0, $subtotal - $discountAmount) + $shippingFee,
            'expires_at' => $expiresAt->toIso8601String(),
        ];

        session()->put("pending_vnpay_checkouts.{$orderCode}", $draft);
        Cache::put("pending_vnpay_checkout:{$orderCode}", $draft, $expiresAt);

        return $draft;
    }

    /**
     * Chiếm một lượt dùng của mã giảm giá, đảm bảo không vượt usage_limit.
     *
     * Trước đây chỗ này chỉ gọi increment('used_count') sau khi promotionFromCode()
     * đã đọc và kiểm tra used_count ở một truy vấn khác. Giữa lúc đọc và lúc tăng,
     * request khác có thể xen vào, nên mã giới hạn 10 lượt vẫn bị dùng quá.
     *
     * Cách sửa: gộp điều kiện vào chính câu UPDATE (giống InventoryService::issue).
     * Database sẽ tự khóa dòng khi update, nên chỉ đúng một request thắng.
     *
     * @throws RuntimeException khi mã đã hết lượt.
     */
    private function claimPromotionSlot(int $promotionId): void
    {
        $claimed = Promotion::whereKey($promotionId)
            ->where(function ($query): void {
                $query->whereNull('usage_limit')
                    ->orWhereColumn('used_count', '<', 'usage_limit');
            })
            ->increment('used_count');

        if ($claimed === 0) {
            throw new RuntimeException('Mã giảm giá đã hết lượt sử dụng. Vui lòng bỏ mã và đặt lại đơn.');
        }
    }

    /**
     * Chuẩn hóa giỏ hàng trong session.
     */
    private function normalizedCart(): array
    {
        return collect(session('cart', []))
            ->mapWithKeys(fn ($quantity, $variantId) => [(int) $variantId => min(self::MAX_CART_QUANTITY, max(1, (int) $quantity))])
            ->all();
    }

    /**
     * Lấy các biến thể sản phẩm trong giỏ.
     */
    private function cartVariants(array $cart)
    {
        return ProductVariant::query()
            ->with('product')
            ->whereIn('id', array_keys($cart))
            ->get();
    }

    /**
     * Tính tạm tính của giỏ hàng.
     */
    private function cartSubtotal($variants, array $cart, array $cartLensOptions = []): float
    {
        return (float) $variants->sum(function (ProductVariant $variant) use ($cart, $cartLensOptions) {
            $lensUnitPrice = (float) ($cartLensOptions[$variant->id]['price'] ?? 0);

            return ((float) $variant->display_price + $lensUnitPrice) * (int) $cart[$variant->id];
        });
    }

    /**
     * Tính tổng số lượng sản phẩm trong giỏ.
     */
    private function normalizedCartLensOptions(array $cartLensOptions, array $cart): array
    {
        // Luồng chọn tròng kính đang được tắt (xem commit "hide lens selector flow"),
        // nên hàm luôn trả về mảng rỗng và mọi đơn được tính giá không kèm tròng.
        //
        // Trước đây phần thân hàm cũ vẫn nằm nguyên bên dưới câu `return []`, tức là
        // ~30 dòng chết mà đọc lướt rất dễ tưởng là còn chạy. Đã xóa hẳn để không ai
        // sửa nhầm vào code không bao giờ được gọi. Khi mở lại tính năng tròng kính
        // thì viết lại phần chuẩn hóa ở đây (lọc code/name rỗng, ép price >= 0).
        return [];
    }

    private function totalQuantity(array $cart): int
    {
        return array_sum(array_map('intval', $cart));
    }

    /**
     * Lấy mã giảm giá đang áp dụng.
     */
    private function currentPromotion(float $subtotal): ?Promotion
    {
        [$promotion] = $this->appliedPromotion($subtotal);

        return $promotion;
    }

    /**
     * Kiểm tra mã giảm giá trong session.
     */
    private function appliedPromotion(float $subtotal): array
    {
        $code = (string) session('checkout_promotion_code', '');

        if ($code === '') {
            return [null, null];
        }

        [$promotion, $message] = $this->promotionFromCode($code, $subtotal);

        if (! $promotion) {
            session()->forget('checkout_promotion_code');

            return [null, $message];
        }

        return [$promotion, null];
    }

    /**
     * Tìm và kiểm tra điều kiện của mã giảm giá.
     */
    private function promotionFromCode(string $code, float $subtotal): array
    {
        $code = Str::upper(trim($code));

        if ($code === '') {
            return [null, 'Vui lòng nhập mã giảm giá.'];
        }

        $promotion = Promotion::query()
            ->whereRaw('UPPER(promotion_code) = ?', [$code])
            ->first();

        if (! $promotion) {
            return [null, 'Mã giảm giá không tồn tại.'];
        }

        if ($promotion->scope !== 'ORDER') {
            return [null, 'Mã giảm giá này chưa áp dụng cho toàn đơn hàng.'];
        }

        if ($promotion->status !== 'ACTIVE') {
            return [null, 'Mã giảm giá chưa được bật hoặc đã hết hiệu lực.'];
        }

        if ($promotion->start_at && $promotion->start_at->isFuture()) {
            return [null, 'Mã giảm giá chưa đến thời gian sử dụng.'];
        }

        if ($promotion->end_at && $promotion->end_at->isPast()) {
            return [null, 'Mã giảm giá đã hết hạn.'];
        }

        if ($promotion->usage_limit !== null && (int) $promotion->used_count >= (int) $promotion->usage_limit) {
            return [null, 'Mã giảm giá đã hết lượt sử dụng.'];
        }

        if ($promotion->usage_per_user !== null && Auth::check()) {
            $userUsedCount = Order::query()
                ->where('user_id', Auth::id())
                ->where('promotion_id', $promotion->id)
                ->where('status', '!=', 'CANCELLED')
                ->count();

            if ($userUsedCount >= (int) $promotion->usage_per_user) {
                return [null, 'Bạn đã dùng hết số lượt cho phép của mã giảm giá này.'];
            }
        }

        if ($subtotal < (float) $promotion->min_order_amount) {
            return [null, 'Đơn hàng chưa đạt giá trị tối thiểu để dùng mã này.'];
        }

        if ($promotion->discountFor($subtotal) <= 0) {
            return [null, 'Mã giảm giá chưa có giá trị giảm hợp lệ.'];
        }

        return [$promotion, null];
    }
}
