<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Color;
use App\Models\FrameMaterial;
use App\Models\FrameShape;
use App\Models\LensSize;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        ]);
    }

    public function show(Product $product): View
    {
        abort_unless($product->status === 'ACTIVE', 404);

        $product->increment('view_count');
        $product->load(['brand', 'category', 'frameShape', 'frameMaterial', 'images', 'variants.color', 'variants.lensSize']);
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

        $tryOnProducts = Product::active()
            ->with(['brand', 'category', 'frameMaterial', 'variants'])
            ->latest()
            ->limit(12)
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
