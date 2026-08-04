<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    private const MAX_TOTAL_QUANTITY = 10;

    public function index(): View
    {
        return view('cart.index', [
            'items' => $this->cartItems(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:' . self::MAX_TOTAL_QUANTITY],
        ]);

        $variant = ProductVariant::active()->findOrFail($data['variant_id']);
        $cart = $this->limitedCart($this->normalizedCart(session('cart', [])));
        $requestedQuantity = (int) ($data['quantity'] ?? 1);
        $remainingQuantity = self::MAX_TOTAL_QUANTITY - $this->totalQuantity($cart);

        if ($remainingQuantity <= 0) {
            session(['cart' => $cart]);

            return back()->with('error', 'Mỗi đơn chỉ đặt tối đa ' . self::MAX_TOTAL_QUANTITY . ' sản phẩm. Vui lòng giảm số lượng trong giỏ.');
        }

        $quantity = min($requestedQuantity, $remainingQuantity);
        $cart[$variant->id] = ($cart[$variant->id] ?? 0) + $quantity;
        session(['cart' => $cart]);

        if ($quantity < $requestedQuantity) {
            return back()->with('success', 'Chỉ thêm được ' . $quantity . ' sản phẩm vì mỗi đơn chỉ đặt tối đa ' . self::MAX_TOTAL_QUANTITY . ' sản phẩm.');
        }

        return back()->with('success', 'Đã thêm sản phẩm vào giỏ hàng.');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'quantities' => ['array'],
            'quantities.*' => ['integer', 'min:0', 'max:' . self::MAX_TOTAL_QUANTITY],
        ]);

        $cart = $this->limitedCart($this->normalizedCart(session('cart', [])));
        $updatedCart = $cart;

        foreach ($data['quantities'] ?? [] as $variantId => $quantity) {
            $variantId = filter_var($variantId, FILTER_VALIDATE_INT);

            if ($variantId === false || ! array_key_exists($variantId, $updatedCart)) {
                continue;
            }

            if ((int) $quantity <= 0) {
                unset($updatedCart[$variantId]);
                continue;
            }

            $updatedCart[$variantId] = (int) $quantity;
        }

        if ($this->totalQuantity($updatedCart) > self::MAX_TOTAL_QUANTITY) {
            session(['cart' => $cart]);

            return back()->with('error', 'Mỗi đơn chỉ đặt tối đa ' . self::MAX_TOTAL_QUANTITY . ' sản phẩm. Vui lòng giảm số lượng trong giỏ.');
        }

        session(['cart' => $updatedCart]);

        return back()->with('success', 'Giỏ hàng đã được cập nhật.');
    }

    public function destroy(int $variant): RedirectResponse
    {
        $cart = session('cart', []);
        unset($cart[$variant]);
        session(['cart' => $cart]);

        return back()->with('success', 'Đã xóa sản phẩm khỏi giỏ hàng.');
    }

    private function cartItems()
    {
        $cart = $this->limitedCart($this->normalizedCart(session('cart', [])));
        session(['cart' => $cart]);

        if ($cart === []) {
            return collect();
        }

        return ProductVariant::query()
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
    }

    private function normalizedCart(array $cart): array
    {
        $normalized = [];

        foreach ($cart as $variantId => $quantity) {
            $variantId = filter_var($variantId, FILTER_VALIDATE_INT);
            $quantity = max(0, min(self::MAX_TOTAL_QUANTITY, (int) $quantity));

            if ($variantId === false || $quantity <= 0) {
                continue;
            }

            $normalized[$variantId] = $quantity;
        }

        return $normalized;
    }

    private function limitedCart(array $cart): array
    {
        $limited = [];
        $remainingQuantity = self::MAX_TOTAL_QUANTITY;

        foreach ($cart as $variantId => $quantity) {
            if ($remainingQuantity <= 0) {
                break;
            }

            $quantity = min((int) $quantity, $remainingQuantity);
            $limited[$variantId] = $quantity;
            $remainingQuantity -= $quantity;
        }

        return $limited;
    }

    private function totalQuantity(array $cart): int
    {
        return array_sum(array_map('intval', $cart));
    }
}
