<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Promotion;
use App\Models\ProductVariant;
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

    public function index(): View
    {
        $cart = session('cart', []);
        $items = ProductVariant::query()
            ->with(['product.brand', 'color', 'lensSize'])
            ->whereIn('id', array_keys($cart))
            ->get()
            ->map(function (ProductVariant $variant) use ($cart) {
                $quantity = (int) ($cart[$variant->id] ?? 0);

                return [
                    'variant' => $variant,
                    'quantity' => $quantity,
                    'line_total' => $variant->display_price * $quantity,
                ];
            });
        $subtotal = (float) $items->sum('line_total');
        $promotion = $this->currentPromotion($subtotal);
        $discountAmount = $promotion?->discountFor($subtotal) ?? 0.0;

        $defaultAddress = Auth::user()?->addresses()
            ->orderByDesc('is_default')
            ->latest()
            ->first();

        return view('checkout.index', [
            'items' => $items,
            'defaultAddress' => $defaultAddress,
            'appliedPromotion' => $promotion,
            'discountAmount' => $discountAmount,
        ]);
    }

    public function applyPromotion(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'promotion_code' => ['required', 'string', 'max:20'],
        ], [
            'promotion_code.required' => 'Vui lòng nhập mã giảm giá.',
        ]);

        $cart = $this->normalizedCart();
        if ($cart === []) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng đang trống.');
        }

        if ($this->totalQuantity($cart) > self::MAX_CART_QUANTITY) {
            return redirect()->route('cart.index')->with('error', 'Mỗi đơn chỉ đặt tối đa ' . self::MAX_CART_QUANTITY . ' sản phẩm. Vui lòng giảm số lượng trong giỏ.');
        }

        $variants = $this->cartVariants($cart);
        $subtotal = $this->cartSubtotal($variants, $cart);
        [$promotion, $message] = $this->promotionFromCode((string) $data['promotion_code'], $subtotal);

        if (! $promotion) {
            session()->forget('checkout_promotion_code');

            return back()->withInput()->with('error', $message);
        }

        session()->put('checkout_promotion_code', $promotion->promotion_code);

        return redirect()
            ->route('checkout.index')
            ->withInput($request->except('_token'))
            ->with('success', 'Đã áp dụng mã giảm giá ' . $promotion->promotion_code . '.');
    }

    public function removePromotion(Request $request): RedirectResponse
    {
        session()->forget('checkout_promotion_code');

        return redirect()
            ->route('checkout.index')
            ->withInput($request->except(['_token', 'promotion_code']))
            ->with('success', 'Đã bỏ mã giảm giá.');
    }

    public function store(Request $request, VnPayService $vnPay, OrderConfirmationEmailService $orderConfirmationEmail): RedirectResponse
    {
        $data = $request->validate([
            'recipient_name' => ['required', 'string', 'max:100'],
            'recipient_phone' => ['required', 'regex:/^(03|05|07|08|09)\d{8}$/'],
            'address_detail' => ['required', 'string', 'max:200'],
            'city' => ['required', 'string', 'max:100'],
            'payment_method' => ['required', 'in:COD,VNPAY'],
            'note' => ['nullable', 'string', 'max:1000'],
        ], [
            'recipient_phone.required' => 'Số điện thoại không được để trống.',
            'recipient_phone.regex' => 'Số điện thoại không đúng định dạng.',
            'address_detail.required' => 'Địa chỉ chi tiết không được để trống.',
            'address_detail.max' => 'Địa chỉ chi tiết tối đa 200 ký tự.',
            'city.required' => 'Vui lòng chọn Tỉnh/Thành phố.',
            'payment_method.required' => 'Vui lòng chọn phương thức thanh toán.',
        ]);

        $cart = $this->normalizedCart();

        if ($cart === []) {
            return redirect()->route('cart.index')->with('success', 'Giỏ hàng đang trống.');
        }

        if ($this->totalQuantity($cart) > self::MAX_CART_QUANTITY) {
            return redirect()->route('cart.index')->with('error', 'Mỗi đơn chỉ đặt tối đa ' . self::MAX_CART_QUANTITY . ' sản phẩm. Vui lòng giảm số lượng trong giỏ.');
        }

        $variants = ProductVariant::query()
            ->with('product')
            ->whereIn('id', array_keys($cart))
            ->get();

        if ($variants->isEmpty() || $variants->count() !== count($cart)) {
            return redirect()->route('cart.index')->with('success', 'Sản phẩm trong giỏ hàng không còn hợp lệ.');
        }

        foreach ($variants as $variant) {
            $requestedQty = (int) ($cart[$variant->id] ?? 0);
            $stock = (int) DB::table('inventories')
                ->where('warehouse_id', 1)
                ->where('variant_id', $variant->id)
                ->value('quantity');

            if ($requestedQty > $stock) {
                $name = $variant->product?->name ?? 'Sản phẩm';
                if ($stock <= 0) {
                    return redirect()->route('cart.index')->with('error', 'Sản phẩm "' . $name . '" hiện đã hết hàng.');
                }

                return redirect()->route('cart.index')->with('error', 'Sản phẩm "' . $name . '" chỉ còn ' . $stock . ' sản phẩm trong kho. Vui lòng giảm số lượng trong giỏ.');
            }
        }

        $subtotal = $this->cartSubtotal($variants, $cart);
        [$promotion, $discountMessage] = $this->appliedPromotion($subtotal);

        if ($discountMessage !== null) {
            return back()->withInput()->with('error', $discountMessage);
        }

        $discountAmount = $promotion?->discountFor($subtotal) ?? 0.0;

        if ($data['payment_method'] === 'VNPAY' && ! $vnPay->isConfigured()) {
            return back()->withInput()->with('error', 'Chưa cấu hình VNPay. Vui lòng kiểm tra VNPAY_TMN_CODE và VNPAY_HASH_SECRET trong .env.');
        }

        $shippingAddress = collect([
            trim((string) $data['address_detail']),
            trim((string) $data['city']),
        ])->filter()->implode(', ');

        if ($data['payment_method'] === 'VNPAY') {
            try {
                $draft = $this->storePendingVnPayCheckout($data, $cart, $variants, $shippingAddress, $promotion, $discountAmount);
                $paymentOrder = new Order([
                    'order_code' => $draft['order_code'],
                    'total_amount' => $draft['total_amount'],
                ]);

                return redirect()->away($vnPay->createPaymentUrl($paymentOrder, $request));
            } catch (RuntimeException $exception) {
                return back()->withInput()->with('error', 'Không tạo được thanh toán VNPay: ' . $exception->getMessage());
            }
        }

        $order = $this->createOrder($data, $cart, $variants, $shippingAddress, $promotion, $discountAmount);
        $orderConfirmationEmail->send($order);

        session()->forget(['cart', 'checkout_promotion_code']);

        return redirect()->route('account.orders.show', $order)->with('success', 'Đặt hàng thành công.');
    }

    private function createOrder(array $data, array $cart, $variants, string $shippingAddress, ?Promotion $promotion, float $discountAmount): Order
    {
        return DB::transaction(function () use ($data, $cart, $variants, $shippingAddress, $promotion, $discountAmount) {
            $subtotal = $variants->sum(fn (ProductVariant $variant) => $variant->display_price * (int) $cart[$variant->id]);
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
                Promotion::whereKey($promotion->id)->increment('used_count');
            }

            foreach ($variants as $variant) {
                $quantity = (int) $cart[$variant->id];
                $unitPrice = $variant->display_price;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $variant->product_id,
                    'variant_id' => $variant->id,
                    'product_name' => $variant->product->name,
                    'sku' => $variant->sku,
                    'color_name' => $variant->color->name ?? null,
                    'lens_size_name' => $variant->lensSize->name ?? null,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount_amount' => 0,
                    'total_price' => $unitPrice * $quantity,
                ]);
            }

            return $order;
        });
    }

    private function storePendingVnPayCheckout(array $data, array $cart, $variants, string $shippingAddress, ?Promotion $promotion, float $discountAmount): array
    {
        $subtotal = $variants->sum(fn (ProductVariant $variant) => $variant->display_price * (int) $cart[$variant->id]);
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

    private function normalizedCart(): array
    {
        return collect(session('cart', []))
            ->mapWithKeys(fn ($quantity, $variantId) => [(int) $variantId => min(self::MAX_CART_QUANTITY, max(1, (int) $quantity))])
            ->all();
    }

    private function cartVariants(array $cart)
    {
        return ProductVariant::query()
            ->with('product')
            ->whereIn('id', array_keys($cart))
            ->get();
    }

    private function cartSubtotal($variants, array $cart): float
    {
        return (float) $variants->sum(fn (ProductVariant $variant) => $variant->display_price * (int) $cart[$variant->id]);
    }

    private function totalQuantity(array $cart): int
    {
        return array_sum(array_map('intval', $cart));
    }

    private function currentPromotion(float $subtotal): ?Promotion
    {
        [$promotion] = $this->appliedPromotion($subtotal);

        return $promotion;
    }

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
