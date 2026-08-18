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
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class ProductController extends Controller
{
    /**
     * Hiển thị danh sách sản phẩm và bộ lọc.
     */
    public function index(Request $request): View
    {
        // Luong: Gan ket qua xu ly vao bien $priceExpression.
        $priceExpression = 'COALESCE(sale_price, base_price)';

        // Luong: Gan ket qua xu ly vao bien $products.
        $products = Product::query()
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->active()
            // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
            ->with(['brand', 'category', 'categories', 'frameShape', 'frameMaterial', 'variants.color', 'variants.lensSize'])
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->when($request->filled('q'), function ($query) use ($request) {
                // Luong: Gan ket qua xu ly vao bien $keyword.
                $keyword = '%' . trim((string) $request->q) . '%';

                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                $query->where(function ($query) use ($keyword) {
                    // Luong: Bo sung dieu kien loc du lieu cho truy van.
                    $query->where('name', 'like', $keyword)
                        // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                        ->orWhere('product_code', 'like', $keyword);
                });
            })
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->when($request->filled('category'), fn ($query) => $query->inCategories($this->filterIds($request, 'category')))
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->when($request->filled('brand'), fn ($query) => $query->whereIn('brand_id', $this->filterIds($request, 'brand')))
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->when($request->filled('shape'), fn ($query) => $query->whereIn('frame_shape_id', $this->filterIds($request, 'shape')))
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->when($request->filled('material'), fn ($query) => $query->whereIn('frame_material_id', $this->filterIds($request, 'material')))
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->when($request->filled('uv'), fn ($query) => $query->whereIn('uv_protection', $this->filterStrings($request, 'uv')))
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->when($request->filled('color'), function ($query) use ($request) {
                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                $query->whereHas('variants', fn ($query) => $query->whereIn('color_id', $this->filterIds($request, 'color')));
            })
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->when($request->filled('size'), function ($query) use ($request) {
                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                $query->whereHas('variants', fn ($query) => $query->whereIn('lens_size_id', $this->filterIds($request, 'size')));
            })
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->when($request->boolean('sale'), fn ($query) => $query->whereNotNull('sale_price')->whereColumn('sale_price', '<', 'base_price'))
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->when($request->filled('from_price'), function ($query) use ($request, $priceExpression) {
                // Luong: Gan ket qua xu ly vao bien $fromPrice.
                $fromPrice = (int) preg_replace('/\D/', '', (string) $request->from_price);

                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                $query->whereRaw($priceExpression . ' >= ?', [$fromPrice]);
            })
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->when($request->filled('to_price'), function ($query) use ($request, $priceExpression) {
                // Luong: Gan ket qua xu ly vao bien $toPrice.
                $toPrice = (int) preg_replace('/\D/', '', (string) $request->to_price);

                // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
                if ($toPrice > 0) {
                    // Luong: Bo sung dieu kien loc du lieu cho truy van.
                    $query->whereRaw($priceExpression . ' <= ?', [$toPrice]);
                }
            });

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        match ($request->input('sort')) {
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            'price_asc' => $products->orderByRaw($priceExpression . ' ASC'),
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            'price_desc' => $products->orderByRaw($priceExpression . ' DESC'),
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            'popular' => $products->orderByDesc('view_count'),
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            'name_asc' => $products->orderBy('name'),
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            'sale' => $products->orderByRaw('(base_price - COALESCE(sale_price, base_price)) DESC'),
            // Luong: Danh dau mot nhanh xu ly trong cau truc switch.
            default => $products->latest(),
        };

        // Luong: Gan ket qua xu ly vao bien $priceRange.
        $priceRange = Product::active()
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->selectRaw('MIN(' . $priceExpression . ') as min_price, MAX(' . $priceExpression . ') as max_price')
            // Luong: Thuc thi truy van va lay ket qua tu CSDL.
            ->first();

        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('products.index', [
            // Luong: Thuc thi truy van va lay ket qua tu CSDL.
            'products' => $products->paginate(6)->withQueryString(),
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            'categories' => Category::active()->withCount('products')->orderBy('name')->get(),
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            'brands' => Brand::active()->withCount('products')->orderBy('name')->get(),
            // Luong: Thuc thi truy van va lay ket qua tu CSDL.
            'colors' => Color::orderBy('name')->get(),
            // Luong: Thuc thi truy van va lay ket qua tu CSDL.
            'lensSizes' => LensSize::orderBy('name')->get(),
            // Luong: Thuc thi truy van va lay ket qua tu CSDL.
            'frameShapes' => FrameShape::orderBy('name')->get(),
            // Luong: Thuc thi truy van va lay ket qua tu CSDL.
            'frameMaterials' => FrameMaterial::orderBy('name')->get(),
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            'uvOptions' => Product::active()->whereNotNull('uv_protection')->distinct()->pluck('uv_protection')->filter()->values(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'filters' => $request->only(['q', 'category', 'brand', 'color', 'size', 'shape', 'material', 'uv', 'sort', 'from_price', 'to_price', 'sale']),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'priceRange' => $priceRange,
            // Luong: Thuc thi truy van va lay ket qua tu CSDL.
            'productBanners' => Banner::visible('PRODUCT_BANNER')->get(),
        ]);
    }

    /**
     * Hiển thị chi tiết sản phẩm.
     */
    public function show(Product $product): View
    {
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        abort_unless($product->status === 'ACTIVE', 404);

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $product->increment('view_count');
        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $product->load(['brand', 'category', 'categories', 'frameShape', 'frameMaterial', 'images', 'variants.color', 'variants.lensSize']);
        // Luong: Gom danh muc chinh va danh muc phu de goi y san pham lien quan.
        $relatedCategoryIds = $product->categories
            ->pluck('id')
            ->push($product->category_id)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        // Luong: Gan ket qua xu ly vao bien $variantStock.
        $variantStock = Inventory::query()
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->whereIn('variant_id', $product->variants->pluck('id'))
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->whereHas('warehouse', fn ($query) => $query->where('status', 'ACTIVE')->where('type', '!=', \App\Services\InventoryService::QUARANTINE_TYPE))
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->selectRaw('variant_id, COALESCE(SUM(quantity), 0) as available_stock')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->groupBy('variant_id')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->pluck('available_stock', 'variant_id')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->map(fn ($stock) => max(0, (int) $stock));
        // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
        $visibleReviewsQuery = $product->visibleReviews()->with('user');
        // Luong: Gan ket qua xu ly vao bien $reviewStats.
        $reviewStats = [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'count' => (clone $visibleReviewsQuery)->count(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'average' => round((float) (clone $visibleReviewsQuery)->avg('rating'), 1),
        ];
        // Luong: Gan ket qua xu ly vao bien $visibleReviews.
        $visibleReviews = $visibleReviewsQuery
            // Luong: Thuc thi truy van va lay ket qua tu CSDL.
            ->paginate(5, ['*'], 'reviews_page')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->withQueryString()
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->fragment('reviews');

        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('products.show', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'product' => $product,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'visibleReviews' => $visibleReviews,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'reviewStats' => $reviewStats,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'variantStock' => $variantStock,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'relatedProducts' => Product::active()
                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                ->inCategories($relatedCategoryIds)
                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                ->whereKeyNot($product->getKey())
                // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
                ->with(['brand', 'category', 'categories', 'frameMaterial', 'variants'])
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->limit(4)
                // Luong: Thuc thi truy van va lay ket qua tu CSDL.
                ->get(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'tryOnPayload' => $this->tryOnPayload(
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                Product::active()
                    // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
                    ->with(['brand', 'category', 'categories', 'frameMaterial', 'variants'])
                    // Luong: Bo sung dieu kien loc du lieu cho truy van.
                    ->whereKey($product->getKey())
                    // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                    ->orWhere(function ($query) use ($product, $relatedCategoryIds) {
                        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
                        $query->active()
                            // Luong: Bo sung dieu kien loc du lieu cho truy van.
                            ->inCategories($relatedCategoryIds)
                            // Luong: Bo sung dieu kien loc du lieu cho truy van.
                            ->whereKeyNot($product->getKey());
                    })
                    // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                    ->limit(8)
                    // Luong: Thuc thi truy van va lay ket qua tu CSDL.
                    ->get()
                    // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                    ->sortByDesc(fn (Product $item) => $item->is($product))
                    // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                    ->values()
            ),
        ]);
    }

    /**
     * Lưu đánh giá của người dùng cho sản phẩm.
     */
    public function storeReview(Request $request, Product $product): RedirectResponse
    {
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        abort_unless($product->status === 'ACTIVE', 404);

        // Luong: Kiem tra va lay du lieu hop le tu request.
        $data = $request->validate([
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'rating' => ['required', 'integer', 'between:1,5'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'content' => ['required', 'string', 'max:2000'],
        ], [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'rating.required' => 'Vui lòng chọn số sao đánh giá.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'rating.between' => 'Số sao đánh giá phải từ 1 đến 5.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'content.required' => 'Vui lòng nhập nội dung bình luận.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'content.max' => 'Nội dung bình luận tối đa 2000 ký tự.',
        ]);

        // Luong: Gan ket qua xu ly vao bien $orderItem.
        $orderItem = $this->reviewOrderItemFor($product);

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! $orderItem) {
            // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
            return redirect(route('products.show', $product) . '#reviews')
                // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
                ->withErrors(['review' => 'Chỉ khách hàng đã mua và nhận sản phẩm này mới được đánh giá.'])
                // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
                ->withInput();
        }

        // Luong: Tao ban ghi moi tu du lieu da chuan bi.
        ProductReview::create([
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'user_id' => Auth::id(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'product_id' => $product->id,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'order_item_id' => $orderItem->id,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'rating' => (int) $data['rating'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'content' => trim((string) $data['content']),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'status' => 'VISIBLE',
        ]);

        // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
        return redirect(route('products.show', $product) . '#reviews')
            // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
            ->with('success', 'Đã đăng đánh giá của bạn.');
    }

    /**
     * Hiển thị trang thử kính AI.
     */
    public function tryOn(Request $request): View
    {
        // Luong: Gan ket qua xu ly vao bien $selectedProductId.
        $selectedProductId = $request->integer('id_sp');

        // Only show products that have a Jeeliz-compatible 3D model SKU
        // (SKU contains underscore, e.g. rayban_aviator_or_vert).
        // Exclude auto-generated codes like SP2026…
        // Luong: Gan ket qua xu ly vao bien $tryOnProducts.
        $tryOnProducts = Product::active()
            // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
            ->with(['brand', 'category', 'frameMaterial', 'variants'])
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->where('product_code', 'LIKE', '%_%')
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->where('product_code', 'NOT LIKE', 'SP20%')
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            ->latest()
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->limit(24)
            // Luong: Thuc thi truy van va lay ket qua tu CSDL.
            ->get();

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($selectedProductId > 0) {
            // Luong: Gan ket qua xu ly vao bien $selectedProduct.
            $selectedProduct = Product::active()
                // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
                ->with(['brand', 'category', 'frameMaterial', 'variants'])
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->find($selectedProductId);

            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
            if ($selectedProduct) {
                // Luong: Gan ket qua xu ly vao bien $tryOnProducts.
                $tryOnProducts = $tryOnProducts
                    // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                    ->reject(fn (Product $product) => $product->is($selectedProduct))
                    // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                    ->prepend($selectedProduct)
                    // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                    ->values();
            }
        }

        // Luong: Gan ket qua xu ly vao bien $tryOnPayload.
        $tryOnPayload = $this->tryOnPayload($tryOnProducts);

        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('tryon-ai', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'tryOnPayload' => $tryOnPayload,
            // Luong: Thuc thi truy van va lay ket qua tu CSDL.
            'firstTryOn' => $tryOnPayload->first(),
        ]);
    }

    /**
     * Kiểm tra sản phẩm có model thử kính không.
     */
    public function tryOnModelCheck(Request $request): JsonResponse
    {
        // Luong: Gan ket qua xu ly vao bien $sku.
        $sku = trim((string) $request->query('sku', ''));

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($sku === '') {
            // Luong: Tra ve du lieu JSON cho client goi API.
            return response()->json(['supported' => false]);
        }

        // Luong: Bat dau khoi xu ly co the phat sinh loi.
        try {
            // Luong: Gan ket qua xu ly vao bien $response.
            $response = Http::withoutVerifying()
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->timeout(5)
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->withUserAgent('Mozilla/5.0')
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->acceptJson()
                // Luong: Thuc thi truy van va lay ket qua tu CSDL.
                ->get('https://glassesdbcached.jeeliz.com/sku/' . rawurlencode($sku));

            // Luong: Gan ket qua xu ly vao bien $data.
            $data = $response->json();
            // Luong: Gan ket qua xu ly vao bien $isSupported.
            $isSupported = $response->ok()
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                && is_array($data)
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                && isset($data['intrinsic'])
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                && empty($data['error']);

            // Luong: Tra ve du lieu JSON cho client goi API.
            return response()->json(['supported' => $isSupported]);
        // Luong: Bat va xu ly loi phat sinh trong khoi try.
        } catch (\Throwable) {
            // Luong: Tra ve du lieu JSON cho client goi API.
            return response()->json(['supported' => false]);
        }
    }

    /**
     * Lưu ảnh kết quả thử kính của người dùng.
     */
    public function storeTryOnSnapshot(Request $request): JsonResponse
    {
        // Luong: Gan ket qua xu ly vao bien $user.
        $user = $request->user();

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        abort_unless($user, 401);

        // Luong: Gan ket qua xu ly vao bien $imagePath.
        $imagePath = null;

        // Luong: Bat dau khoi xu ly co the phat sinh loi.
        try {
        // Luong: Kiem tra va lay du lieu hop le tu request.
        $data = $request->validate([
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'product_id' => ['required', 'integer', 'exists:products,id'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'model_sku' => ['required', 'string', 'max:100'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'tryon_mode' => ['required', 'string', 'in:camera,image'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'image' => ['required', 'string', 'max:7000000'],
        ], [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'product_id.required' => 'Vui lòng chọn kính trước khi lưu kết quả.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'model_sku.required' => 'Sản phẩm này chưa có model thử kính.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'image.required' => 'Chưa có ảnh thử kính để lưu.',
        ]);

        // Luong: Gan ket qua xu ly vao bien $product.
        $product = Product::active()
            // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
            ->with('variants')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->findOrFail((int) $data['product_id']);

        // Luong: Gan ket qua xu ly vao bien $modelSku.
        $modelSku = trim((string) $product->product_code);
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($modelSku === '' || trim((string) $data['model_sku']) !== $modelSku) {
            // Luong: Tra ve du lieu JSON cho client goi API.
            return response()->json([
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'message' => 'Sản phẩm này chưa có model thử kính hợp lệ.',
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            ], 422);
        }

        // Luong: Gan ket qua xu ly vao bien $variant.
        $variant = null;
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! empty($data['variant_id'])) {
            // Luong: Gan ket qua xu ly vao bien $variant.
            $variant = ProductVariant::query()
                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                ->where('product_id', $product->id)
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->find((int) $data['variant_id']);

            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
            if (! $variant) {
                // Luong: Tra ve du lieu JSON cho client goi API.
                return response()->json([
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'message' => 'Biến thể kính không thuộc sản phẩm đang thử.',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                ], 422);
            }
        }

            // Luong: Gan ket qua xu ly vao bien $imagePath.
            $imagePath = $this->storeTryOnImage((string) $data['image']);

            // Luong: Tao ban ghi moi tu du lieu da chuan bi.
            $snapshot = TryOnSnapshot::create([
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'user_id' => $user->id,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'product_id' => $product->id,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'variant_id' => $variant?->id,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'user_name' => $user->full_name,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'user_email' => $user->email,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'product_name' => $product->name,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'model_sku' => $modelSku,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'price' => $variant?->display_price ?? $product->display_price,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'image_path' => $imagePath,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'tryon_mode' => $data['tryon_mode'],
            ]);
        // Luong: Bat va xu ly loi phat sinh trong khoi try.
        } catch (QueryException $exception) {
            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
            if ($imagePath !== null) {
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                File::delete(public_path($imagePath));
            }

            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            report($exception);

            // Luong: Tra ve du lieu JSON cho client goi API.
            return response()->json([
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'message' => 'Khong the luu du lieu thu kinh vao co so du lieu. Vui long kiem tra MySQL va migration.',
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            ], 500);
        // Luong: Bat va xu ly loi phat sinh trong khoi try.
        } catch (Throwable $exception) {
            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
            if ($imagePath !== null) {
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                File::delete(public_path($imagePath));
            }

            // Luong: Nem loi de dung luong khi dieu kien nghiep vu khong dat.
            throw $exception;
        }

        // Luong: Tra ve du lieu JSON cho client goi API.
        return response()->json([
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'message' => 'Đã lưu kết quả thử kính.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'snapshot' => [
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'id' => $snapshot->id,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'image_url' => $snapshot->image_url,
            ],
        ]);
    }

    /**
     * Chuẩn bị dữ liệu sản phẩm cho trang thử kính.
     */
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

    /**
     * Lấy danh sách id từ bộ lọc request.
     */
    private function filterIds(Request $request, string $key): array
    {
        return collect((array) $request->input($key, []))
            ->map(fn ($value) => (int) $value)
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Đổi mô tả HTML thành chữ thường dễ đọc.
     */
    private function plainDescription(?string $description): string
    {
        $description = (string) $description;
        $description = preg_replace('/<\/(h2|h3|h4|p|li)>/i', '$0 ', $description);
        $description = preg_replace('/<(br|br\/)>/i', ' ', (string) $description);
        $description = trim(preg_replace('/\s+/', ' ', strip_tags((string) $description)));

        return $description !== '' ? $description : 'Kiểu dáng dễ đeo, phù hợp sử dụng hằng ngày.';
    }

    /**
     * Lưu ảnh thử kính từ dữ liệu base64.
     */
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

    /**
     * Lấy danh sách chuỗi từ bộ lọc request.
     */
    private function filterStrings(Request $request, string $key): array
    {
        return collect((array) $request->input($key, []))
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Tìm đơn hàng phù hợp để gắn đánh giá.
     */
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

public function getDisplayPriceAttribute(): float
{
    // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
    if ($this->product && $this->product->sale_price) {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return (float) $this->product->sale_price;
    }

    // Luong: Tra ve ket qua cuoi cung cua ham.
    return (float) ($this->variant_price ?: $this->product?->base_price ?: 0);
}
}
