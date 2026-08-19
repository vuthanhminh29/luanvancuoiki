<?php

declare(strict_types=1);

namespace App\Services\Chatbot;

use App\Models\Inventory;
use App\Models\LensOption;
use App\Models\Product;
use App\Models\Promotion;
use App\Services\InventoryService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Tầng Retrieval của kiến trúc RAG: dựng ngữ cảnh thật từ database trước khi
 * hỏi model.
 *
 * Lý do bắt buộc phải có bước này: model ngôn ngữ không biết gì về kho hàng của
 * shop. Nếu hỏi thẳng "gọng titan giá bao nhiêu" thì nó sẽ bịa ra một con số
 * nghe rất hợp lý — và khách sẽ tin. Mọi con số về giá, màu, size, tồn kho, mã
 * giảm giá mà chatbot nói ra đều phải đi qua đây trước.
 */
class ProductContextBuilder
{
    public function __construct(
        private readonly MessageClassifier $classifier,
        private readonly CustomerOrderContext $customerOrders,
    ) {}

    /**
     * @param  list<array{role: string, content: string}>  $history
     * @param  int|null  $userId  id của khách ĐANG ĐĂNG NHẬP, lấy từ phiên chứ
     *                            không bao giờ từ dữ liệu khách gửi lên
     */
    public function build(string $message, array $history = [], ?int $userId = null): ProductContext
    {
        $normalized = $this->classifier->normalize($message);

        // "Sản phẩm nào đang giảm giá" không tra được bằng từ khóa: chữ "giảm
        // giá" không nằm trong tên hay mô tả sản phẩm nào cả. Phải đổi hẳn tiêu
        // chí truy xuất sang lọc theo sale_price, nếu không cả hai đường (AI và
        // database) đều nhận về ngữ cảnh sai rồi trả lời sai theo.
        $keywords = $this->extractKeywords($message, $history);
        $products = $this->classifier->asksForDiscountedProducts($normalized)
            ? $this->retrieveDiscountedProducts()
            : $this->retrieveProducts($keywords);
        $promotions = $this->retrievePromotions();
        $lensOptions = $this->retrieveLensOptions();
        $orders = $this->customerOrders->forUser($userId);

        return new ProductContext(
            $products,
            $promotions,
            $lensOptions,
            $orders,
            $this->renderText($products, $promotions, $lensOptions, $orders),
        );
    }

    /**
     * Từ khóa của lượt hiện tại nặng gấp 3 lần từ khóa trong lịch sử.
     *
     * Chênh lệch trọng số này là thứ xử lý được câu hỏi nối tiếp: khách hỏi
     * "gọng Titan có mẫu nào" rồi hỏi tiếp "còn màu gì?". Câu sau gần như không
     * có từ khóa sản phẩm nào, nhưng "titan" trong lịch sử vẫn đủ điểm để kéo
     * đúng nhóm sản phẩm cũ lên đầu.
     *
     * @param  list<array{role: string, content: string}>  $history
     * @return array<string, int>
     */
    private function extractKeywords(string $message, array $history): array
    {
        $keywords = [];

        foreach ($this->classifier->tokenize($message) as $token) {
            $keywords[$token] = ($keywords[$token] ?? 0) + 3;
        }

        $window = (int) config('chatbot.context.history_keyword_window', 6);

        foreach (array_slice($history, -$window) as $entry) {
            // Chỉ lấy từ khóa từ lời khách. Câu trả lời của bot luôn chứa tên
            // hàng loạt sản phẩm đã liệt kê ở lượt trước, đưa vào đây thì lượt
            // nào cũng khớp đúng nhóm cũ và chatbot không đổi chủ đề được nữa.
            if (($entry['role'] ?? '') !== 'user') {
                continue;
            }

            foreach ($this->classifier->tokenize((string) ($entry['content'] ?? '')) as $token) {
                $keywords[$token] = ($keywords[$token] ?? 0) + 1;
            }
        }

        arsort($keywords);

        return array_slice($keywords, 0, 12, true);
    }

    /**
     * @param  array<string, int>  $keywords
     * @return Collection<int, array<string, mixed>>
     */
    private function retrieveProducts(array $keywords): Collection
    {
        $limit = (int) config('chatbot.context.max_products', 8);

        $query = $this->baseProductQuery();

        if ($keywords !== []) {
            // Lọc thô ở SQL trước rồi mới chấm điểm trong PHP. Nếu bỏ bước lọc
            // này thì mỗi tin nhắn phải nạp toàn bộ bảng products kèm variants
            // lên bộ nhớ chỉ để chọn ra 8 dòng.
            $query->where(function ($outer) use ($keywords): void {
                foreach (array_keys($keywords) as $keyword) {
                    // Ép về chuỗi: khóa mảng dạng số đã bị PHP đổi thành int.
                    $like = '%' . (string) $keyword . '%';

                    $outer->orWhere('products.name', 'like', $like)
                        ->orWhere('products.product_code', 'like', $like)
                        ->orWhere('products.description', 'like', $like)
                        ->orWhereHas('brand', fn ($brand) => $brand->where('name', 'like', $like))
                        ->orWhereHas('category', fn ($category) => $category->where('name', 'like', $like))
                        ->orWhereHas('frameShape', fn ($shape) => $shape->where('name', 'like', $like))
                        ->orWhereHas('frameMaterial', fn ($material) => $material->where('name', 'like', $like))
                        ->orWhereHas('variants.color', fn ($color) => $color->where('name', 'like', $like));
                }
            });
        }

        $candidates = $query->orderByDesc('view_count')->limit(40)->get();

        // Không khớp từ khóa nào (khách hỏi chung chung "shop có gì hay") thì
        // trả về hàng đang được xem nhiều nhất thay vì ngữ cảnh rỗng.
        if ($candidates->isEmpty()) {
            $candidates = $this->baseProductQuery()
                ->orderByDesc('view_count')
                ->limit($limit)
                ->get();
        }

        $ranked = $candidates
            ->map(fn (Product $product): array => [
                'product' => $product,
                'score' => $this->scoreProduct($product, $keywords),
            ])
            ->sortByDesc(fn (array $row): int => $row['score'])
            ->take($limit)
            ->map(fn (array $row): Product => $row['product'])
            ->values();

        $stockByVariant = $this->sellableStockFor($ranked);

        return $ranked->map(fn (Product $product): array => $this->presentProduct($product, $stockByVariant))->values();
    }

    /**
     * Hàng đang giảm giá trực tiếp (có sale_price thấp hơn giá gốc).
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function retrieveDiscountedProducts(): Collection
    {
        $products = $this->baseProductQuery()
            ->whereNotNull('sale_price')
            ->whereColumn('sale_price', '<', 'base_price')
            // Sắp theo số tiền giảm tuyệt đối chứ không theo phần trăm: tính
            // phần trăm phải chia cho base_price, mà cột đó mặc định 0 nên gặp
            // dòng dữ liệu lỗi là chia cho 0.
            ->orderByDesc(DB::raw('base_price - sale_price'))
            ->limit((int) config('chatbot.context.max_products', 8))
            ->get();

        $stockByVariant = $this->sellableStockFor($products);

        return $products->map(fn (Product $product): array => $this->presentProduct($product, $stockByVariant))->values();
    }

    /**
     * Truy vấn nền: chỉ hàng đang bán, nạp sẵn mọi quan hệ mà ngữ cảnh cần.
     *
     * @return \Illuminate\Database\Eloquent\Builder<Product>
     */
    private function baseProductQuery()
    {
        return Product::query()
            ->active()
            ->with([
                'brand:id,name',
                'category:id,name',
                'frameShape:id,name',
                'frameMaterial:id,name',
                // Không đụng tới lens_sizes.size_label ở đây. Cột đó có trong
                // migration nhưng KHÔNG có trong database thật đang chạy (bản
                // import từ file .sql), nên nêu tên nó trong danh sách cột làm
                // cả truy vấn chết với lỗi "Unknown column". ImportGlassesSku
                // cũng đang phải kiểm tra hasColumn() vì lý do y hệt; cột `name`
                // thì luôn được ghi ở cả hai schema.
                'variants' => fn ($variants) => $variants->active()
                    ->with(['color:id,name', 'lensSize:id,name,lens_width']),
            ]);
    }

    /**
     * @param  array<string, int>  $keywords
     */
    private function scoreProduct(Product $product, array $keywords): int
    {
        if ($keywords === []) {
            return 0;
        }

        // Gộp mọi trường mô tả sản phẩm thành một chuỗi đã bỏ dấu rồi mới đếm.
        // Nhờ vậy khách gõ "gong kim loai" vẫn khớp "Gọng Kim Loại".
        $haystack = $this->classifier->normalize(implode(' ', array_filter([
            $product->name,
            $product->product_code,
            $product->brand?->name,
            $product->category?->name,
            $product->frameShape?->name,
            $product->frameMaterial?->name,
            $product->uv_protection,
            $product->variants->map(fn ($variant) => $variant->color?->name)->implode(' '),
        ])));

        $score = 0;

        foreach ($keywords as $keyword => $weight) {
            // PHP tự ép khóa mảng dạng số về int, nên token toàn số ("52" trong
            // "size 52 có không", "999" trong một mã đơn) đi tới đây thành int
            // và str_contains() ném TypeError -> cả endpoint chat trả 500.
            $keyword = (string) $keyword;

            if (str_contains($haystack, $keyword)) {
                // Khớp trong tên sản phẩm đáng giá hơn khớp trong mô tả chung.
                $inName = str_contains($this->classifier->normalize((string) $product->name), $keyword);

                $score += $weight * ($inName ? 3 : 1);
            }
        }

        return $score;
    }

    /**
     * Tồn kho bán được của tất cả biến thể trong MỘT truy vấn gộp.
     *
     * Gọi InventoryService::sellableQuantityFor() cho từng biến thể thì đúng về
     * mặt logic nhưng thành N+1: 8 sản phẩm x 6 biến thể là 48 truy vấn cho mỗi
     * tin nhắn chat. Điều kiện lọc kho ở đây phải khớp đúng với service đó.
     *
     * @param  Collection<int, Product>  $products
     * @return array<int, int>
     */
    private function sellableStockFor(Collection $products): array
    {
        $variantIds = $products
            ->flatMap(fn (Product $product) => $product->variants->pluck('id'))
            ->filter()
            ->unique()
            ->values();

        if ($variantIds->isEmpty()) {
            return [];
        }

        return Inventory::query()
            ->join('warehouses', 'warehouses.id', '=', 'inventories.warehouse_id')
            ->whereIn('inventories.variant_id', $variantIds)
            ->where('warehouses.status', 'ACTIVE')
            ->where('warehouses.type', '<>', InventoryService::QUARANTINE_TYPE)
            ->groupBy('inventories.variant_id')
            ->selectRaw('inventories.variant_id, SUM(inventories.quantity) as total')
            ->pluck('total', 'inventories.variant_id')
            ->map(fn ($total): int => max(0, (int) $total))
            ->all();
    }

    /**
     * @param  array<int, int>  $stockByVariant
     * @return array<string, mixed>
     */
    private function presentProduct(Product $product, array $stockByVariant): array
    {
        $variantLimit = (int) config('chatbot.context.max_variants_per_product', 6);

        $variants = $product->variants
            ->take($variantLimit)
            // Gắn sẵn quan hệ ngược product vào từng biến thể. Accessor
            // display_price của ProductVariant có đọc $this->product để ưu tiên
            // sale_price; không gắn ở đây thì mỗi biến thể tự query lại sản phẩm
            // cha mà nó vừa được nạp ra từ đó.
            ->each(fn ($variant) => $variant->setRelation('product', $product))
            ->map(fn ($variant): array => [
                'sku' => (string) $variant->sku,
                'color' => $variant->color?->name,
                'size' => $variant->lensSize?->name,
                'price' => (float) $variant->display_price,
                'stock' => $stockByVariant[$variant->id] ?? 0,
            ])
            ->values();

        return [
            'id' => $product->id,
            'name' => (string) $product->name,
            'code' => (string) $product->product_code,
            'brand' => $product->brand?->name,
            'category' => $product->category?->name,
            'frame_shape' => $product->frameShape?->name,
            'frame_material' => $product->frameMaterial?->name,
            'uv_protection' => $product->uv_protection,
            'base_price' => (float) $product->base_price,
            'sale_price' => $product->sale_price !== null ? (float) $product->sale_price : null,
            'display_price' => (float) $product->display_price,
            // Accessor image_url tự lo đủ mọi kiểu đường dẫn đang có trong dữ
            // liệu (URL tuyệt đối, upload/..., anh_san_pham/...) và trả ảnh
            // no-image khi trống, nên thẻ sản phẩm không bao giờ vỡ layout.
            'image' => $product->image_url,
            'url' => $product->slug ? route('products.show', $product->slug) : route('products.index'),
            'total_stock' => (int) $variants->sum('stock'),
            'variants' => $variants,
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function retrievePromotions(): Collection
    {
        $now = Carbon::now();

        return Promotion::query()
            ->where('status', 'ACTIVE')
            ->where(fn ($query) => $query->whereNull('start_at')->orWhere('start_at', '<=', $now))
            ->where(fn ($query) => $query->whereNull('end_at')->orWhere('end_at', '>=', $now))
            // Mã đã dùng hết lượt vẫn nằm trong bảng với status ACTIVE. Đưa vào
            // ngữ cảnh thì chatbot mời khách dùng một mã chắc chắn bị từ chối ở
            // bước thanh toán.
            ->where(fn ($query) => $query->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit'))
            ->orderByDesc('discount_value')
            ->limit((int) config('chatbot.context.max_promotions', 5))
            ->get()
            ->map(fn (Promotion $promotion): array => [
                'code' => (string) ($promotion->promotion_code ?: $promotion->code),
                'name' => (string) $promotion->name,
                'discount_type' => (string) $promotion->discount_type,
                'discount_value' => (float) $promotion->discount_value,
                'max_discount_amount' => $promotion->max_discount_amount !== null ? (float) $promotion->max_discount_amount : null,
                'min_order_amount' => (float) $promotion->min_order_amount,
                'end_at' => $promotion->end_at?->format('d/m/Y'),
            ])
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function retrieveLensOptions(): Collection
    {
        return LensOption::query()
            ->active()
            ->orderBy('sort_order')
            ->limit((int) config('chatbot.context.max_lens_options', 8))
            ->get()
            ->map(fn (LensOption $option): array => [
                'code' => (string) $option->code,
                'name' => (string) $option->name,
                'description' => (string) $option->description,
                'price' => (float) $option->price,
            ])
            ->values();
    }

    /**
     * Ghép dữ liệu đã truy xuất thành khối văn bản nhét vào prompt.
     *
     * Định dạng cố tình dùng gạch đầu dòng phẳng thay vì JSON: model bám theo
     * text có nhãn tiếng Việt tốt hơn, và tốn ít token hơn JSON có dấu ngoặc.
     *
     * @param  Collection<int, array<string, mixed>>  $products
     * @param  Collection<int, array<string, mixed>>  $promotions
     * @param  Collection<int, array<string, mixed>>  $lensOptions
     * @param  Collection<int, array<string, mixed>>  $orders
     */
    private function renderText(Collection $products, Collection $promotions, Collection $lensOptions, Collection $orders): string
    {
        $lines = [];

        // Đơn hàng đặt lên đầu: khi khách đã đăng nhập và đang hỏi về đơn thì đó
        // là thứ quan trọng nhất trong cả khối ngữ cảnh.
        if ($orders->isNotEmpty()) {
            $lines[] = $this->customerOrders->render($orders);
            $lines[] = '';
        }

        if ($products->isNotEmpty()) {
            $lines[] = '### SẢN PHẨM ĐANG BÁN (dữ liệu thật từ hệ thống)';

            foreach ($products as $product) {
                $head = sprintf(
                    '- %s | Mã: %s | Thương hiệu: %s | Danh mục: %s | Dáng: %s | Chất liệu: %s | Giá: %s',
                    $product['name'],
                    $product['code'] ?: 'chưa có',
                    $product['brand'] ?: 'chưa có',
                    $product['category'] ?: 'chưa có',
                    $product['frame_shape'] ?: 'chưa có',
                    $product['frame_material'] ?: 'chưa có',
                    $this->money($product['display_price']),
                );

                if ($product['sale_price'] !== null && $product['sale_price'] < $product['base_price']) {
                    $head .= ' (giá gốc ' . $this->money($product['base_price']) . ')';
                }

                $lines[] = $head;
                $lines[] = '  Link: ' . $product['url'];

                foreach ($product['variants'] as $variant) {
                    $lines[] = sprintf(
                        '  + Màu %s, size %s, giá %s, còn %d cái',
                        $variant['color'] ?: 'chưa phân loại',
                        $variant['size'] ?: 'chưa phân loại',
                        $this->money($variant['price']),
                        $variant['stock'],
                    );
                }

                if ($product['total_stock'] === 0) {
                    $lines[] = '  (Toàn bộ biến thể đang hết hàng)';
                }
            }
        }

        if ($promotions->isNotEmpty()) {
            $lines[] = '';
            $lines[] = '### MÃ GIẢM GIÁ ĐANG CHẠY';

            foreach ($promotions as $promotion) {
                $value = $promotion['discount_type'] === 'PERCENT'
                    ? rtrim(rtrim(number_format($promotion['discount_value'], 2, ',', '.'), '0'), ',') . '%'
                    : $this->money($promotion['discount_value']);

                $line = sprintf('- %s (%s): giảm %s', $promotion['code'], $promotion['name'], $value);

                if ($promotion['max_discount_amount'] !== null) {
                    $line .= ', tối đa ' . $this->money($promotion['max_discount_amount']);
                }

                if ($promotion['min_order_amount'] > 0) {
                    $line .= ', đơn từ ' . $this->money($promotion['min_order_amount']);
                }

                if ($promotion['end_at'] !== null) {
                    $line .= ', hạn dùng ' . $promotion['end_at'];
                }

                $lines[] = $line;
            }
        }

        if ($lensOptions->isNotEmpty()) {
            $lines[] = '';
            $lines[] = '### TÙY CHỌN TRÒNG KÍNH (giá cộng thêm vào gọng)';

            foreach ($lensOptions as $option) {
                $lines[] = sprintf(
                    '- %s: %s%s',
                    $option['name'],
                    $this->money($option['price']),
                    $option['description'] !== '' ? ' — ' . $option['description'] : '',
                );
            }
        }

        return implode("\n", $lines);
    }

    private function money(float $amount): string
    {
        return number_format($amount, 0, ',', '.') . 'đ';
    }
}
