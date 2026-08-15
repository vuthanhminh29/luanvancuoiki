<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\LensOption;
use App\Models\ProductVariant;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CartController extends Controller
{
    private const MAX_TOTAL_QUANTITY = 10;

    /**
     * Hiển thị giỏ hàng hiện tại.
     */
    public function index(): View
    {
        return view('cart.index', [
            'items' => $this->cartItems(),
        ]);
    }

    /**
     * Thêm sản phẩm vào giỏ hàng.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:' . self::MAX_TOTAL_QUANTITY],
            'lens_option_code' => ['nullable', 'string', Rule::exists('lens_options', 'code')->where('status', 'ACTIVE')],
        ]);

        $variant = ProductVariant::active()->findOrFail($data['variant_id']);
        $cart = $this->limitedCart($this->normalizedCart(session('cart', [])));
        $cartLensOptions = $this->normalizedCartLensOptions((array) session('cart_lens_options', []), $cart);
        $requestedQuantity = (int) ($data['quantity'] ?? 1);
        $remainingQuantity = self::MAX_TOTAL_QUANTITY - $this->totalQuantity($cart);
        $availableStock = $this->sellableStockFor($variant->id);
        $remainingStock = $availableStock - (int) ($cart[$variant->id] ?? 0);

        if ($availableStock <= 0 || $remainingStock <= 0) {
            session(['cart' => $cart, 'cart_lens_options' => $cartLensOptions]);

            return back()->with('error', 'Sản phẩm này hiện đã hết hàng.');
        }

        if ($remainingQuantity <= 0) {
            session(['cart' => $cart, 'cart_lens_options' => $cartLensOptions]);

            return back()->with('error', 'Mỗi đơn chỉ đặt tối đa ' . self::MAX_TOTAL_QUANTITY . ' sản phẩm. Vui lòng giảm số lượng trong giỏ.');
        }

        $quantity = min($requestedQuantity, $remainingQuantity, $remainingStock);
        $cart[$variant->id] = ($cart[$variant->id] ?? 0) + $quantity;

        if ($request->has('lens_option_code') && empty($data['lens_option_code'])) {
            unset($cartLensOptions[$variant->id]);
        }

        if (! empty($data['lens_option_code'])) {
            $lensOption = LensOption::active()
                ->where('code', $data['lens_option_code'])
                ->firstOrFail();

            $cartLensOptions[$variant->id] = [
                'code' => $lensOption->code,
                'name' => $lensOption->name,
                'price' => (float) $lensOption->price,
            ];
        }

        session([
            'cart' => $cart,
            'cart_lens_options' => $this->normalizedCartLensOptions($cartLensOptions, $cart),
        ]);

        if ($quantity < $requestedQuantity) {
            return back()->with('success', 'Chỉ thêm được ' . $quantity . ' sản phẩm vì giỏ hàng đã chạm giới hạn hoặc tồn kho không đủ.');
        }

        return back()->with('success', 'Đã thêm sản phẩm vào giỏ hàng.');
    }

    /**
     * Cập nhật số lượng trong giỏ hàng.
     */
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'quantities' => ['array'],
            'quantities.*' => ['integer', 'min:0', 'max:' . self::MAX_TOTAL_QUANTITY],
        ]);

        $cart = $this->limitedCart($this->normalizedCart(session('cart', [])));
        $cartLensOptions = $this->normalizedCartLensOptions((array) session('cart_lens_options', []), $cart);
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
            session(['cart' => $cart, 'cart_lens_options' => $cartLensOptions]);

            return back()->with('error', 'Mỗi đơn chỉ đặt tối đa ' . self::MAX_TOTAL_QUANTITY . ' sản phẩm. Vui lòng giảm số lượng trong giỏ.');
        }

        $stockByVariant = $this->sellableStockForMany(array_keys($updatedCart));

        foreach ($updatedCart as $variantId => $quantity) {
            if ($quantity > (int) ($stockByVariant[$variantId] ?? 0)) {
                session(['cart' => $cart, 'cart_lens_options' => $cartLensOptions]);

                return back()->with('error', 'Số lượng cập nhật vượt quá tồn kho hiện có.');
            }
        }

        session([
            'cart' => $updatedCart,
            'cart_lens_options' => $this->normalizedCartLensOptions($cartLensOptions, $updatedCart),
        ]);

        return back()->with('success', 'Giỏ hàng đã được cập nhật.');
    }

    /**
     * Xóa một sản phẩm khỏi giỏ hàng.
     */
    public function destroy(int $variant): RedirectResponse
    {
        $cart = session('cart', []);
        $cartLensOptions = (array) session('cart_lens_options', []);
        unset($cart[$variant]);
        unset($cartLensOptions[$variant]);
        session(['cart' => $cart, 'cart_lens_options' => $cartLensOptions]);

        return back()->with('success', 'Đã xóa sản phẩm khỏi giỏ hàng.');
    }

    /**
     * Lấy thông tin chi tiết các sản phẩm trong giỏ.
     */
    private function cartItems()
    {
        $cart = $this->limitedCart($this->normalizedCart(session('cart', [])));
        $cartLensOptions = $this->normalizedCartLensOptions((array) session('cart_lens_options', []), $cart);
        session(['cart' => $cart, 'cart_lens_options' => $cartLensOptions]);

        if ($cart === []) {
            return collect();
        }

        return ProductVariant::query()
            ->with(['product.brand', 'color', 'lensSize'])
            ->whereIn('id', array_keys($cart))
            ->get()
            ->map(function (ProductVariant $variant) use ($cart, $cartLensOptions) {
                $quantity = (int) ($cart[$variant->id] ?? 0);
                $originalUnitPrice = (float) ($variant->variant_price ?: $variant->product?->base_price ?: 0);
                $currentUnitPrice = (float) $variant->display_price;
                $lensOption = $cartLensOptions[$variant->id] ?? null;
                $lensUnitPrice = (float) ($lensOption['price'] ?? 0);
                $originalConfiguredPrice = $originalUnitPrice + $lensUnitPrice;
                $configuredPrice = $currentUnitPrice + $lensUnitPrice;

                return [
                    'variant' => $variant,
                    'quantity' => $quantity,
                    'lens_option' => $lensOption,
                    'frame_unit_price' => $currentUnitPrice,
                    'lens_unit_price' => $lensUnitPrice,
                    'unit_price' => $configuredPrice,
                    'original_line_total' => $originalConfiguredPrice * $quantity,
                    'line_total' => $configuredPrice * $quantity,
                    'discount_total' => max(0, ($originalUnitPrice - $currentUnitPrice) * $quantity),
                ];
            });
    }

    /**
     * Chuẩn hóa dữ liệu giỏ hàng.
     */
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

    /**
     * Giới hạn tổng số lượng sản phẩm trong giỏ.
     */
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

    /**
     * Tính tổng số lượng sản phẩm trong giỏ.
     */
    private function normalizedCartLensOptions(array $cartLensOptions, array $cart): array
    {
        return [];

        $normalized = [];

        foreach (array_keys($cart) as $variantId) {
            $option = $cartLensOptions[$variantId] ?? $cartLensOptions[(string) $variantId] ?? null;

            if (! is_array($option)) {
                continue;
            }

            $code = trim((string) ($option['code'] ?? ''));
            $name = trim((string) ($option['name'] ?? ''));
            $price = max(0, (float) ($option['price'] ?? 0));

            if ($code === '' || $name === '') {
                continue;
            }

            $normalized[(int) $variantId] = [
                'code' => $code,
                'name' => $name,
                'price' => $price,
            ];
        }

        return $normalized;
    }

    private function totalQuantity(array $cart): int
    {
        return array_sum(array_map('intval', $cart));
    }

    private function sellableStockFor(int $variantId): int
    {
        return (int) ($this->sellableStockForMany([$variantId])[$variantId] ?? 0);
    }

    private function sellableStockForMany(array $variantIds): array
    {
        $variantIds = collect($variantIds)
            ->map(fn ($variantId) => filter_var($variantId, FILTER_VALIDATE_INT))
            ->filter(fn ($variantId) => $variantId !== false)
            ->unique()
            ->values()
            ->all();

        if ($variantIds === []) {
            return [];
        }

        return Inventory::query()
            ->whereIn('variant_id', $variantIds)
            ->whereHas('warehouse', fn ($query) => $query
                ->where('status', 'ACTIVE')
                ->where('type', '!=', InventoryService::QUARANTINE_TYPE))
            ->selectRaw('variant_id, COALESCE(SUM(quantity), 0) as available_stock')
            ->groupBy('variant_id')
            ->pluck('available_stock', 'variant_id')
            ->map(fn ($stock) => max(0, (int) $stock))
            ->all();
    }
}
