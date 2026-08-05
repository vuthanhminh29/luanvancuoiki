<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Color;
use App\Models\FrameMaterial;
use App\Models\FrameShape;
use App\Models\Inventory;
use App\Models\LensSize;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use App\Models\TryOnSnapshot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $priceExpression = 'COALESCE(sale_price, base_price)';

        $products = Product::query()
            ->active()
            ->with(['brand', 'category', 'frameShape', 'frameMaterial', 'variants.color', 'variants.lensSize'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $keyword = '%' . trim((string) $request->q) . '%';

                $query->where(function ($query) use ($keyword) {
                    $query->where('name', 'like', $keyword)
                        ->orWhere('product_code', 'like', $keyword);
                });
            })
            ->when($request->filled('category'), fn ($query) => $query->whereIn('category_id', $this->filterIds($request, 'category')))
            ->when($request->filled('brand'), fn ($query) => $query->whereIn('brand_id', $this->filterIds($request, 'brand')))
            ->when($request->filled('shape'), fn ($query) => $query->whereIn('frame_shape_id', $this->filterIds($request, 'shape')))
            ->when($request->filled('material'), fn ($query) => $query->whereIn('frame_material_id', $this->filterIds($request, 'material')))
            ->when($request->filled('uv'), fn ($query) => $query->whereIn('uv_protection', $this->filterStrings($request, 'uv')))
            ->when($request->filled('color'), function ($query) use ($request) {
                $query->whereHas('variants', fn ($query) => $query->whereIn('color_id', $this->filterIds($request, 'color')));
            })
            ->when($request->filled('size'), function ($query) use ($request) {
                $query->whereHas('variants', fn ($query) => $query->whereIn('lens_size_id', $this->filterIds($request, 'size')));
            })
            ->when($request->boolean('sale'), fn ($query) => $query->whereNotNull('sale_price')->whereColumn('sale_price', '<', 'base_price'))
            ->when($request->filled('from_price'), function ($query) use ($request, $priceExpression) {
                $fromPrice = (int) preg_replace('/\D/', '', (string) $request->from_price);

                $query->whereRaw($priceExpression . ' >= ?', [$fromPrice]);
            })
            ->when($request->filled('to_price'), function ($query) use ($request, $priceExpression) {
                $toPrice = (int) preg_replace('/\D/', '', (string) $request->to_price);

                if ($toPrice > 0) {
                    $query->whereRaw($priceExpression . ' <= ?', [$toPrice]);
                }
            });

        match ($request->input('sort')) {
            'price_asc' => $products->orderByRaw($priceExpression . ' ASC'),
            'price_desc' => $products->orderByRaw($priceExpression . ' DESC'),
            'popular' => $products->orderByDesc('view_count'),
            'name_asc' => $products->orderBy('name'),
            'sale' => $products->orderByRaw('(base_price - COALESCE(sale_price, base_price)) DESC'),
            default => $products->latest(),
        };

        $priceRange = Product::active()
            ->selectRaw('MIN(' . $priceExpression . ') as min_price, MAX(' . $priceExpression . ') as max_price')
            ->first();

        return view('products.index', [
            'products' => $products->paginate(6)->withQueryString(),
            'categories' => Category::active()->withCount('products')->orderBy('name')->get(),
            'brands' => Brand::active()->withCount('products')->orderBy('name')->get(),
            'colors' => Color::orderBy('name')->get(),
            'lensSizes' => LensSize::orderBy('name')->get(),
            'frameShapes' => FrameShape::orderBy('name')->get(),
            'frameMaterials' => FrameMaterial::orderBy('name')->get(),
            'uvOptions' => Product::active()->whereNotNull('uv_protection')->distinct()->pluck('uv_protection')->filter()->values(),
            'filters' => $request->only(['q', 'category', 'brand', 'color', 'size', 'shape', 'material', 'uv', 'sort', 'from_price', 'to_price', 'sale']),
            'priceRange' => $priceRange,
            'productBanners' => Banner::visible('PRODUCT_BANNER')->get(),
        ]);
    }

    public function show(Product $product): View
    {
        abort_unless($product->status === 'ACTIVE', 404);

        $product->increment('view_count');
        $product->load(['brand', 'category', 'frameShape', 'frameMaterial', 'images', 'variants.color', 'variants.lensSize']);
        $variantStock = Inventory::query()
            ->whereIn('variant_id', $product->variants->pluck('id'))
            ->whereHas('warehouse', fn ($query) => $query->where('status', 'ACTIVE')->where('type', '!=', \App\Services\InventoryService::QUARANTINE_TYPE))
            ->selectRaw('variant_id, COALESCE(SUM(quantity), 0) as available_stock')
            ->groupBy('variant_id')
            ->pluck('available_stock', 'variant_id')
            ->map(fn ($stock) => max(0, (int) $stock));
        $visibleReviewsQuery = $product->visibleReviews()->with('user');
        $reviewStats = [
            'count' => (clone $visibleReviewsQuery)->count(),
            'average' => round((float) (clone $visibleReviewsQuery)->avg('rating'), 1),
        ];
        $visibleReviews = $visibleReviewsQuery
            ->paginate(5, ['*'], 'reviews_page')
            ->withQueryString()
            ->fragment('reviews');

        return view('products.show', [
            'product' => $product,
            'visibleReviews' => $visibleReviews,
            'reviewStats' => $reviewStats,
            'variantStock' => $variantStock,
            'relatedProducts' => Product::active()
                ->where('category_id', $product->category_id)
                ->whereKeyNot($product->getKey())
                ->with(['brand', 'category', 'frameMaterial', 'variants'])
                ->limit(4)
                ->get(),
            'tryOnPayload' => $this->tryOnPayload(
                Product::active()
                    ->with(['brand', 'category', 'frameMaterial', 'variants'])
                    ->whereKey($product->getKey())
                    ->orWhere(function ($query) use ($product) {
                        $query->active()
                            ->where('category_id', $product->category_id)
                            ->whereKeyNot($product->getKey());
                    })
                    ->limit(8)
                    ->get()
                    ->sortByDesc(fn (Product $item) => $item->is($product))
                    ->values()
            ),
        ]);
    }

    public function storeReview(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->status === 'ACTIVE', 404);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'content' => ['required', 'string', 'max:2000'],
        ], [
            'rating.required' => 'Vui lòng chọn số sao đánh giá.',
            'rating.between' => 'Số sao đánh giá phải từ 1 đến 5.',
            'content.required' => 'Vui lòng nhập nội dung bình luận.',
            'content.max' => 'Nội dung bình luận tối đa 2000 ký tự.',
        ]);

        $orderItem = $this->reviewOrderItemFor($product);

        ProductReview::create([
            'user_id' => Auth::id(),
            'product_id' => $product->id,
            'order_item_id' => $orderItem?->id,
            'rating' => (int) $data['rating'],
            'content' => trim((string) $data['content']),
            'status' => 'VISIBLE',
        ]);

        return redirect(route('products.show', $product) . '#reviews')
            ->with('success', 'Đã đăng đánh giá của bạn.');
    }

    public function tryOn(Request $request): View
    {
        $selectedProductId = $request->integer('id_sp');

        // Only show products that have a Jeeliz-compatible 3D model SKU
        // (SKU contains underscore, e.g. rayban_aviator_or_vert).
        // Exclude auto-generated codes like SP2026…
        $tryOnProducts = Product::active()
            ->with(['brand', 'category', 'frameMaterial', 'variants'])
            ->where('product_code', 'LIKE', '%_%')
            ->where('product_code', 'NOT LIKE', 'SP20%')
            ->latest()
            ->limit(24)
            ->get();

        if ($selectedProductId > 0) {
            $selectedProduct = Product::active()
                ->with(['brand', 'category', 'frameMaterial', 'variants'])
                ->find($selectedProductId);

            if ($selectedProduct) {
                $tryOnProducts = $tryOnProducts
                    ->reject(fn (Product $product) => $product->is($selectedProduct))
                    ->prepend($selectedProduct)
                    ->values();
            }
        }

        $tryOnPayload = $this->tryOnPayload($tryOnProducts);

        return view('tryon-ai', [
            'tryOnPayload' => $tryOnPayload,
            'firstTryOn' => $tryOnPayload->first(),
        ]);
    }

    public function tryOnModelCheck(Request $request): JsonResponse
    {
        $sku = trim((string) $request->query('sku', ''));

        if ($sku === '') {
            return response()->json(['supported' => false]);
        }

        try {
            $response = Http::withoutVerifying()
                ->timeout(5)
                ->withUserAgent('Mozilla/5.0')
                ->acceptJson()
                ->get('https://glassesdbcached.jeeliz.com/sku/' . rawurlencode($sku));

            $data = $response->json();
            $isSupported = $response->ok()
                && is_array($data)
                && isset($data['intrinsic'])
                && empty($data['error']);

            return response()->json(['supported' => $isSupported]);
        } catch (\Throwable) {
            return response()->json(['supported' => false]);
        }
    }

    public function storeTryOnSnapshot(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($user, 401);

        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'model_sku' => ['required', 'string', 'max:100'],
            'tryon_mode' => ['required', 'string', 'in:camera,image'],
            'image' => ['required', 'string', 'max:7000000'],
        ], [
            'product_id.required' => 'Vui lòng chọn kính trước khi lưu kết quả.',
            'model_sku.required' => 'Sản phẩm này chưa có model thử kính.',
            'image.required' => 'Chưa có ảnh thử kính để lưu.',
        ]);

        $product = Product::active()
            ->with('variants')
            ->findOrFail((int) $data['product_id']);

        $modelSku = trim((string) $product->product_code);
        if ($modelSku === '' || trim((string) $data['model_sku']) !== $modelSku) {
            return response()->json([
                'message' => 'Sản phẩm này chưa có model thử kính hợp lệ.',
            ], 422);
        }

        $variant = null;
        if (! empty($data['variant_id'])) {
            $variant = ProductVariant::query()
                ->where('product_id', $product->id)
                ->find((int) $data['variant_id']);

            if (! $variant) {
                return response()->json([
                    'message' => 'Biến thể kính không thuộc sản phẩm đang thử.',
                ], 422);
            }
        }

        $imagePath = $this->storeTryOnImage((string) $data['image']);

        $snapshot = TryOnSnapshot::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'variant_id' => $variant?->id,
            'user_name' => $user->full_name,
            'user_email' => $user->email,
            'product_name' => $product->name,
            'model_sku' => $modelSku,
            'price' => $variant?->display_price ?? $product->display_price,
            'image_path' => $imagePath,
            'tryon_mode' => $data['tryon_mode'],
        ]);

        return response()->json([
            'message' => 'Đã lưu kết quả thử kính.',
            'snapshot' => [
                'id' => $snapshot->id,
                'image_url' => $snapshot->image_url,
            ],
        ]);
    }

    private function tryOnPayload($products)
    {
        return $products->map(function (Product $product) {
            $firstVariant = $product->variants->firstWhere('status', 'ACTIVE') ?? $product->variants->first();

            return [
                'id' => $product->id,
                'variantId' => $firstVariant?->id,
                'sku' => trim((string) $product->product_code),
                'hasModel' => trim((string) $product->product_code) !== '',
                'name' => $product->name,
                'price' => $product->display_price,
                'priceText' => number_format($product->display_price, 0, ',', '.') . 'd',
                'productImage' => $product->image_url,
                'cartImage' => $product->thumbnail_url,
                'detailUrl' => route('products.show', $product),
                'description' => $this->plainDescription($product->description),
                'brand' => $product->brand->name ?? '',
                'material' => $product->frameMaterial->name ?? '',
            ];
        })->values();
    }

    private function filterIds(Request $request, string $key): array
    {
        return collect((array) $request->input($key, []))
            ->map(fn ($value) => (int) $value)
            ->filter()
            ->values()
            ->all();
    }

    private function plainDescription(?string $description): string
    {
        $description = (string) $description;
        $description = preg_replace('/<\/(h2|h3|h4|p|li)>/i', '$0 ', $description);
        $description = preg_replace('/<(br|br\/)>/i', ' ', (string) $description);
        $description = trim(preg_replace('/\s+/', ' ', strip_tags((string) $description)));

        return $description !== '' ? $description : 'Kiểu dáng dễ đeo, phù hợp sử dụng hằng ngày.';
    }

    private function storeTryOnImage(string $imageData): string
    {
        if (! preg_match('/^data:image\/(png|jpe?g);base64,/', $imageData, $matches)) {
            throw ValidationException::withMessages([
                'image' => 'Định dạng ảnh chụp không hợp lệ.',
            ]);
        }

        $extension = $matches[1] === 'png' ? 'png' : 'jpg';
        $base64 = substr($imageData, strpos($imageData, ',') + 1);
        $binary = base64_decode($base64, true);

        if ($binary === false || strlen($binary) < 1000) {
            throw ValidationException::withMessages([
                'image' => 'Ảnh chụp không đọc được.',
            ]);
        }

        if (strlen($binary) > 5 * 1024 * 1024) {
            throw ValidationException::withMessages([
                'image' => 'Ảnh chụp tối đa 5 MB.',
            ]);
        }

        $directory = 'upload/tryons/' . now()->format('Y/m');
        File::ensureDirectoryExists(public_path($directory));

        $path = $directory . '/' . Str::uuid() . '.' . $extension;
        File::put(public_path($path), $binary);

        return $path;
    }

    private function filterStrings(Request $request, string $key): array
    {
        return collect((array) $request->input($key, []))
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->values()
            ->all();
    }

    private function reviewOrderItemFor(Product $product): ?OrderItem
    {
        if (!Auth::check()) {
            return null;
        }

        return OrderItem::query()
            ->where('product_id', $product->id)
            ->whereHas('order', function ($query) {
                $query->where('user_id', Auth::id())
                    ->where('status', 'DELIVERED');
            })
            ->whereDoesntHave('review')
            ->latest('id')
            ->first();
    }
}
