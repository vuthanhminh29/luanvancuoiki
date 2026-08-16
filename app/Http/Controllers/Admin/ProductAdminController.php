<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Color;
use App\Models\FrameMaterial;
use App\Models\FrameShape;
use App\Models\LensSize;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductAdminController extends Controller
{
    /**
     * Hiển thị danh sách sản phẩm.
     */
    public function index(Request $request): View
    {
        // Luong: Gan ket qua xu ly vao bien $products.
        $products = Product::with(['category', 'brand', 'frameShape'])
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->withCount('variants')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->select('products.*')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->selectRaw("(SELECT COALESCE(SUM(inventories.quantity), 0)
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                FROM product_variants
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                LEFT JOIN inventories ON inventories.variant_id = product_variants.id
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                LEFT JOIN warehouses ON warehouses.id = inventories.warehouse_id
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                WHERE product_variants.product_id = products.id
                  // Luong: Xu ly dong logic tiep theo trong ham public nay.
                  AND (warehouses.type IS NULL OR warehouses.type != 'QUARANTINE')) as quantity")
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->selectRaw("(SELECT COALESCE(SUM(order_items.quantity), 0)
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                FROM order_items
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                JOIN orders ON orders.id = order_items.order_id
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                WHERE order_items.product_id = products.id
                  // Luong: Xu ly dong logic tiep theo trong ham public nay.
                  AND orders.status NOT IN ('CANCELLED')) as sold_quantity")
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->selectRaw("(SELECT COALESCE(MAX(COALESCE(product_variants.variant_price, products.base_price)), COALESCE(products.sale_price, products.base_price, 0))
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                FROM product_variants
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                WHERE product_variants.product_id = products.id) as max_price")
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->where('products.status', 'ACTIVE')
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->when($request->filled('q'), fn ($query) => $query->where(function ($subQuery) use ($request) {
                // Luong: Gan ket qua xu ly vao bien $keyword.
                $keyword = '%' . trim((string) $request->q) . '%';
                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                $subQuery->where('products.name', 'like', $keyword)
                    // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                    ->orWhere('products.product_code', 'like', $keyword);
            }))
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->when($request->filled('search_cate'), fn ($query) => $query->where('products.category_id', $request->search_cate))
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            ->latest('products.id')
            // Luong: Thuc thi truy van va lay ket qua tu CSDL.
            ->paginate(15)
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->withQueryString();

        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('admin.products.index', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'products' => $products,
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            'categories' => Category::active()->orderBy('name')->get(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'totalRecycle' => Product::whereIn('status', ['INACTIVE', 'DISCONTINUED', 'DRAFT'])->count(),
        ]);
    }

    /**
     * Hiển thị form thêm sản phẩm.
     */
    public function create(): View
    {
        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('admin.products.form', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'title' => 'Thêm sản phẩm kính',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'subtitle' => 'Tạo sản phẩm cùng các biến thể màu kính và size tròng.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'action' => route('admin.products.store'),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'submitLabel' => 'Lưu sản phẩm',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'backRoute' => route('admin.products.index'),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'product' => null,
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            ...$this->formLookups(),
        ]);
    }

    /**
     * Lưu sản phẩm mới.
     */
    public function store(Request $request): RedirectResponse
    {
        // Luong: Gan ket qua xu ly vao bien $data.
        $data = $this->validateProduct($request);

        // Luong: Mo transaction de cac thao tac CSDL cung thanh cong hoac cung rollback.
        DB::transaction(function () use ($request, $data) {
            // Luong: Tao ban ghi moi tu du lieu da chuan bi.
            $product = Product::create($this->prepareProductData($request, $data));
            // Luong: Cap nhat cac ban ghi phu hop voi dieu kien da loc.
            $product->update(['slug' => Str::slug($data['name']) . '-' . $product->id]);
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $this->syncVariants($request, $product);
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $this->storeGalleryImages($request, $product);
        });

        // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
        return redirect()->route('admin.products.index')->with('success', 'Đã thêm sản phẩm.');
    }

    /**
     * Hiển thị sản phẩm đã ẩn.
     */
    public function recycle(): View
    {
        // Luong: Gan ket qua xu ly vao bien $products.
        $products = Product::with(['category', 'brand'])
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->withCount('variants')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->select('products.*')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->selectRaw("(SELECT COALESCE(SUM(inventories.quantity), 0)
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                FROM product_variants
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                LEFT JOIN inventories ON inventories.variant_id = product_variants.id
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                WHERE product_variants.product_id = products.id) as quantity")
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->whereIn('products.status', ['INACTIVE', 'DISCONTINUED', 'DRAFT'])
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            ->latest('products.id')
            // Luong: Thuc thi truy van va lay ket qua tu CSDL.
            ->paginate(15);

        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('admin.products.recycle', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'products' => $products,
        ]);
    }

    /**
     * Xuất sản phẩm ra Excel.
     */
    public function exportExcel(Request $request): Response
    {
        // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
        $rows = DB::table('products as p')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->leftJoin('categories as c', 'c.id', '=', 'p.category_id')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->leftJoin('brands as b', 'b.id', '=', 'p.brand_id')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->leftJoin('frame_shapes as fs', 'fs.id', '=', 'p.frame_shape_id')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->leftJoin('frame_materials as fm', 'fm.id', '=', 'p.frame_material_id')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->leftJoin('product_variants as pv', 'pv.product_id', '=', 'p.id')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->leftJoin('colors as co', 'co.id', '=', 'pv.color_id')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->leftJoin('lens_sizes as ls', 'ls.id', '=', 'pv.lens_size_id')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->select([
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'p.name',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'p.product_code',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'c.name as category_name',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'b.name as brand_name',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'fs.name as frame_shape_name',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'fm.name as frame_material_name',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'p.uv_protection',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'p.import_price',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'p.base_price',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'p.sale_price',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'p.status',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'p.view_count',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'p.created_at',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'p.updated_at',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'co.name as color_name',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'ls.name as lens_size_name',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'pv.variant_price',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'pv.status as variant_status',
            ])
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->selectRaw('(SELECT COALESCE(SUM(i.quantity), 0) FROM inventories i WHERE i.variant_id = pv.id) as stock_quantity')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->selectRaw("(SELECT COALESCE(SUM(oi.quantity), 0)
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                FROM order_items oi
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                JOIN orders o ON o.id = oi.order_id
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                WHERE oi.product_id = p.id
                  // Luong: Xu ly dong logic tiep theo trong ham public nay.
                  AND o.status <> 'CANCELLED'
                  // Luong: Xu ly dong logic tiep theo trong ham public nay.
                  AND ((pv.id IS NULL AND oi.variant_id IS NULL) OR oi.variant_id = pv.id)
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            ) as sold_quantity")
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->when($request->filled('q'), function ($query) use ($request) {
                // Luong: Gan ket qua xu ly vao bien $keyword.
                $keyword = '%' . trim((string) $request->q) . '%';
                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                $query->where(function ($sub) use ($keyword) {
                    // Luong: Bo sung dieu kien loc du lieu cho truy van.
                    $sub->where('p.name', 'like', $keyword)
                        // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                        ->orWhere('p.product_code', 'like', $keyword);
                });
            })
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->when($request->filled('search_cate'), fn ($query) => $query->where('p.category_id', $request->search_cate))
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->where('p.status', 'ACTIVE')
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            ->orderByDesc('p.id')
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            ->orderBy('pv.id')
            // Luong: Thuc thi truy van va lay ket qua tu CSDL.
            ->get();

        // Luong: Gan ket qua xu ly vao bien $fileName.
        $fileName = 'danh-sach-san-pham-' . now()->format('Ymd-His') . '.xls';
        // Luong: Gan ket qua xu ly vao bien $content.
        $content = view('admin.products.export-excel', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'rows' => $rows,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'generatedAt' => now(),
        ])->render();

        // Luong: Tra ve ket qua cuoi cung cua ham.
        return response("\xEF\xBB\xBF" . $content, 200, [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'Cache-Control' => 'max-age=0, no-cache, must-revalidate, proxy-revalidate',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'Pragma' => 'public',
        ]);
    }

    /**
     * Hiển thị form sửa sản phẩm.
     */
    public function edit(Product $product): View
    {
        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $product->load(['variants.color', 'variants.lensSize', 'images']);

        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('admin.products.form', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'title' => 'Cập nhật sản phẩm kính',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'subtitle' => $product->name,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'action' => route('admin.products.update', $product),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'method' => 'PUT',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'submitLabel' => 'Lưu thay đổi',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'backRoute' => route('admin.products.index'),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'product' => $product,
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            ...$this->formLookups(),
        ]);
    }

    /**
     * Cập nhật sản phẩm.
     */
    public function update(Request $request, Product $product): RedirectResponse
    {
        // Luong: Gan ket qua xu ly vao bien $data.
        $data = $this->validateProduct($request);
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        $data['slug'] = Str::slug($data['name']) . '-' . $product->id;

        // Luong: Mo transaction de cac thao tac CSDL cung thanh cong hoac cung rollback.
        DB::transaction(function () use ($request, $data, $product) {
            // Luong: Cap nhat cac ban ghi phu hop voi dieu kien da loc.
            $product->update($this->prepareProductData($request, $data, $product));
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $this->syncVariants($request, $product);
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $this->storeGalleryImages($request, $product);
        });

        // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
        return redirect()->route('admin.products.index')->with('success', 'Đã cập nhật sản phẩm.');
    }

    /**
     * Ẩn sản phẩm.
     */
    public function hidden(Product $product): RedirectResponse
    {
        // Luong: Cap nhat cac ban ghi phu hop voi dieu kien da loc.
        $product->update(['status' => 'INACTIVE']);

        // Luong: Quay lai trang truoc kem du lieu hoac thong bao can hien thi.
        return back()->with('success', 'Đã ẩn sản phẩm.');
    }

    /**
     * Khôi phục sản phẩm.
     */
    public function restore(Product $product): RedirectResponse
    {
        // Luong: Cap nhat cac ban ghi phu hop voi dieu kien da loc.
        $product->update(['status' => 'ACTIVE']);

        // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
        return redirect()->route('admin.products.index')->with('success', 'Đã khôi phục sản phẩm.');
    }

    /**
     * Lưu ảnh từ trình soạn thảo.
     */
    public function uploadEditorImage(Request $request): JsonResponse
    {
        // Luong: Kiem tra va lay du lieu hop le tu request.
        $request->validate(['upload' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096']]);

        // Luong: Tra ve du lieu JSON cho client goi API.
        return response()->json([
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'url' => asset('upload/' . $this->storeUpload($request, 'upload', 'anh_san_pham')),
        ]);
    }

    /**
     * Kiểm tra dữ liệu sản phẩm.
     */
    private function validateProduct(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'category_id' => ['required', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'frame_shape_id' => ['required', 'exists:frame_shapes,id'],
            'frame_material_id' => ['nullable', 'exists:frame_materials,id'],
            'uv_protection' => ['required', 'in:UV380,UV400,NONE'],
            'import_price' => ['nullable', 'numeric', 'min:0'],
            'base_price' => ['nullable', 'numeric', 'min:0', 'gte:import_price'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'lte:base_price'],
            'thumbnail_url' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'image1' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'image2' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'image3' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'status' => ['required', 'in:DRAFT,ACTIVE,INACTIVE,DISCONTINUED'],
            'description' => ['nullable', 'string'],
            'variant_color_id' => ['nullable', 'array'],
            'variant_color_id.*' => ['nullable', 'exists:colors,id'],
            'variant_id' => ['nullable', 'array'],
            'variant_id.*' => ['nullable', 'exists:product_variants,id'],
            'variant_lens_size_id' => ['nullable', 'array'],
            'variant_lens_size_id.*' => ['nullable', 'exists:lens_sizes,id'],
            'variant_price' => ['nullable', 'array'],
            'variant_price.*' => ['nullable', 'numeric', 'min:0'],
            'variant_status' => ['nullable', 'array'],
            'variant_status.*' => ['nullable', 'in:ACTIVE,OUT_OF_STOCK,DISCONTINUED'],
        ], [
            'base_price.gte' => 'Giá bán niêm yết không được thấp hơn giá nhập.',
            'sale_price.lte' => 'Giá khuyến mãi không được lớn hơn giá niêm yết.',
        ]);
    }

    /**
     * Chuẩn bị dữ liệu sản phẩm trước khi lưu.
     */
    private function prepareProductData(Request $request, array $data, ?Product $product = null): array
    {
        if ($request->hasFile('thumbnail_url')) {
            $data['thumbnail_url'] = $this->storeUpload($request, 'thumbnail_url', 'anh_san_pham');
        } else {
            unset($data['thumbnail_url']);
        }

        $data['import_price'] = (float) ($data['import_price'] ?? 0);
        
        if (blank($data['base_price'] ?? null) && $data['import_price'] > 0) {
            $roundTo = (int) config('pricing.round_to', 1000);
            $markup = (float) config('pricing.markup', 1.45);
            $data['base_price'] = (int) (ceil(($data['import_price'] * $markup) / $roundTo) * $roundTo);
        } else {
            $data['base_price'] = (float) ($data['base_price'] ?? 0);
        }

        $data['slug'] = $product ? Str::slug($data['name']) . '-' . $product->id : Str::slug($data['name']) . '-' . time();

        if (! $product) {
            $data['product_code'] = 'SP' . now()->format('YmdHis');
            $data['view_count'] = 0;
        }

        return $data;
    }

    /**
     * Lấy dữ liệu cho form sản phẩm.
     */
    private function formLookups(): array
    {
        return [
            'categories' => Category::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
            'frameShapes' => FrameShape::orderBy('name')->get(),
            'frameMaterials' => FrameMaterial::orderBy('name')->get(),
            'colors' => Color::orderBy('name')->get(),
            'lensSizes' => LensSize::orderBy('name')->get(),
        ];
    }

    /**
     * Xóa cache liên quan đến sản phẩm.
     */
    private function clearProductCaches(): void
    {
        Cache::forget('admin.product.form_lookups');
        Cache::forget('products.index.price_range');
        Cache::forget('products.index.filter_lookups');
        Cache::forget('layout.header_categories.v2');
        Cache::forget('home.payload');
    }

    /**
     * Đồng bộ các biến thể của sản phẩm.
     */
    private function syncVariants(Request $request, Product $product): void
    {
        $colors = $request->input('variant_color_id', []);
        $variantIds = $request->input('variant_id', []);
        $sizes = $request->input('variant_lens_size_id', []);
        $prices = $request->input('variant_price', []);
        $statuses = $request->input('variant_status', []);

        if (count(array_filter($colors)) === 0 && count(array_filter($sizes)) === 0) {
            return;
        }

        foreach ($colors as $index => $colorId) {
            $sizeId = $sizes[$index] ?? null;

            if (! $colorId && ! $sizeId) {
                continue;
            }

            ProductVariant::updateOrCreate([
                'id' => $variantIds[$index] ?? null,
                'product_id' => $product->id,
            ], [
                'sku' => ($product->product_code ?: 'SP' . $product->id) . '-' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                'color_id' => $colorId ?: null,
                'lens_size_id' => $sizeId ?: null,
                'variant_price' => ($prices[$index] ?? '') !== '' ? $prices[$index] : null,
                'status' => $statuses[$index] ?? 'ACTIVE',
            ]);
        }
    }

    /**
     * Lưu ảnh thư viện của sản phẩm.
     */
    private function storeGalleryImages(Request $request, Product $product): void
    {
    $nextSortOrder = max(2, (int) $product->images()->max('sort_order') + 1);

    foreach ((array) $request->file('gallery_images', []) as $file) {
        ProductImage::create([
            'product_id' => $product->id,
            'image_url' => $this->storeUploadedFile($file, 'anh_san_pham'),
            'alt_text' => $product->name,
            'sort_order' => $nextSortOrder++,
            'is_thumbnail' => false,
        ]);
    }
    }

    /**
     * Lưu file upload và trả về đường dẫn.
     */
    private function storeUpload($file, string $folder): string
    {
    $name = (string) Str::uuid() . '.' . $file->extension();
    $path = public_path('upload/' . $folder);

    if (! is_dir($path)) {
        mkdir($path, 0777, true);
    }

    $file->move($path, $name);

    return $folder . '/' . $name;
    }
}
