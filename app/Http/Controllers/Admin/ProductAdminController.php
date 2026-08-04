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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductAdminController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::with(['category', 'brand', 'frameShape'])
            ->withCount('variants')
            ->select('products.*')
            ->selectRaw("(SELECT COALESCE(SUM(inventories.quantity), 0)
                FROM product_variants
                LEFT JOIN inventories ON inventories.variant_id = product_variants.id
                WHERE product_variants.product_id = products.id) as quantity")
            ->selectRaw("(SELECT COALESCE(SUM(order_items.quantity), 0)
                FROM order_items
                JOIN orders ON orders.id = order_items.order_id
                WHERE order_items.product_id = products.id
                  AND orders.status NOT IN ('CANCELLED')) as sold_quantity")
            ->selectRaw("(SELECT COALESCE(MAX(COALESCE(product_variants.variant_price, products.base_price)), COALESCE(products.sale_price, products.base_price, 0))
                FROM product_variants
                WHERE product_variants.product_id = products.id) as max_price")
            ->where('products.status', 'ACTIVE')
            ->when($request->filled('q'), fn ($query) => $query->where(function ($subQuery) use ($request) {
                $keyword = '%' . trim((string) $request->q) . '%';
                $subQuery->where('products.name', 'like', $keyword)
                    ->orWhere('products.product_code', 'like', $keyword);
            }))
            ->when($request->filled('search_cate'), fn ($query) => $query->where('products.category_id', $request->search_cate))
            ->latest('products.id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'categories' => Category::active()->orderBy('name')->get(),
            'totalRecycle' => Product::whereIn('status', ['INACTIVE', 'DISCONTINUED', 'DRAFT'])->count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.products.form', [
            'title' => 'Thêm sản phẩm kính',
            'subtitle' => 'Tạo sản phẩm cùng các biến thể màu kính và size tròng.',
            'action' => route('admin.products.store'),
            'submitLabel' => 'Lưu sản phẩm',
            'backRoute' => route('admin.products.index'),
            'product' => null,
            ...$this->formLookups(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateProduct($request);

        DB::transaction(function () use ($request, $data) {
            $product = Product::create($this->prepareProductData($request, $data));
            $product->update(['slug' => Str::slug($data['name']) . '-' . $product->id]);
            $this->syncVariants($request, $product);
            $this->storeGalleryImages($request, $product);
        });

        return redirect()->route('admin.products.index')->with('success', 'Đã thêm sản phẩm.');
    }

    public function recycle(): View
    {
        $products = Product::with(['category', 'brand'])
            ->withCount('variants')
            ->select('products.*')
            ->selectRaw("(SELECT COALESCE(SUM(inventories.quantity), 0)
                FROM product_variants
                LEFT JOIN inventories ON inventories.variant_id = product_variants.id
                WHERE product_variants.product_id = products.id) as quantity")
            ->whereIn('products.status', ['INACTIVE', 'DISCONTINUED', 'DRAFT'])
            ->latest('products.id')
            ->paginate(15);

        return view('admin.products.recycle', [
            'products' => $products,
        ]);
    }

    public function edit(Product $product): View
    {
        $product->load(['variants.color', 'variants.lensSize', 'images']);

        return view('admin.products.form', [
            'title' => 'Cập nhật sản phẩm kính',
            'subtitle' => $product->name,
            'action' => route('admin.products.update', $product),
            'method' => 'PUT',
            'submitLabel' => 'Lưu thay đổi',
            'backRoute' => route('admin.products.index'),
            'product' => $product,
            ...$this->formLookups(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validateProduct($request);
        $data['slug'] = Str::slug($data['name']) . '-' . $product->id;

        DB::transaction(function () use ($request, $data, $product) {
            $product->update($this->prepareProductData($request, $data, $product));
            $this->syncVariants($request, $product);
            $this->storeGalleryImages($request, $product);
        });

        return redirect()->route('admin.products.index')->with('success', 'Đã cập nhật sản phẩm.');
    }

    public function hidden(Product $product): RedirectResponse
    {
        $product->update(['status' => 'INACTIVE']);

        return back()->with('success', 'Đã ẩn sản phẩm.');
    }

    public function restore(Product $product): RedirectResponse
    {
        $product->update(['status' => 'ACTIVE']);

        return redirect()->route('admin.products.index')->with('success', 'Đã khôi phục sản phẩm.');
    }

    public function uploadEditorImage(Request $request): JsonResponse
    {
        $request->validate(['upload' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096']]);

        return response()->json([
            'url' => asset('upload/' . $this->storeUpload($request, 'upload', 'anh_san_pham')),
        ]);
    }

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
            'base_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
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
        ]);
    }

    private function prepareProductData(Request $request, array $data, ?Product $product = null): array
    {
        if ($request->hasFile('thumbnail_url')) {
            $data['thumbnail_url'] = $this->storeUpload($request, 'thumbnail_url', 'anh_san_pham');
        } else {
            unset($data['thumbnail_url']);
        }

        $data['import_price'] = $data['import_price'] ?? 0;
        $data['slug'] = $product ? Str::slug($data['name']) . '-' . $product->id : Str::slug($data['name']) . '-' . time();

        if (! $product) {
            $data['product_code'] = 'SP' . now()->format('YmdHis');
            $data['view_count'] = 0;
        }

        return $data;
    }

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

    private function clearProductCaches(): void
    {
        Cache::forget('admin.product.form_lookups');
        Cache::forget('products.index.price_range');
        Cache::forget('products.index.filter_lookups');
        Cache::forget('layout.header_categories');
        Cache::forget('home.payload');
    }

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

    private function storeGalleryImages(Request $request, Product $product): void
    {
        foreach (['image1', 'image2', 'image3'] as $index => $field) {
            if (! $request->hasFile($field)) {
                continue;
            }

            ProductImage::create([
                'product_id' => $product->id,
                'image_url' => $this->storeUpload($request, $field, 'anh_san_pham'),
                'alt_text' => $product->name,
                'sort_order' => $index + 2,
                'is_thumbnail' => false,
            ]);
        }
    }

    private function storeUpload(Request $request, string $field, string $folder): string
    {
        $file = $request->file($field);
        $name = (string) Str::uuid() . '.' . $file->extension();
        $path = public_path('upload/' . $folder);

        if (! is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $file->move($path, $name);

        return $folder . '/' . $name;
    }
}
