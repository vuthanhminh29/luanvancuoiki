# 03 — Module Sản phẩm & Danh mục

Module lõi của website: catalog kính mắt với biến thể theo **màu × size tròng**.

---

## 3.1. Mô hình sản phẩm

```
Category ─┐
Brand ────┤
FrameShape┼──> Product ──┬──> ProductVariant ──┬──> Color
FrameMaterial┘           │      (sku, giá)      └──> LensSize
                         ├──> ProductImage (gallery)
                         └──> ProductReview
```

Một `Product` mang thông tin chung (tên, mô tả, giá gốc, giá KM, ảnh đại diện, chống UV).
Mỗi `ProductVariant` là một tổ hợp **màu + size tròng**, có `sku` riêng và **giá đè tùy chọn**
(`variant_price`). Tồn kho gắn ở cấp **variant**, không phải product.

### Quy tắc giá (quan trọng — dùng ở khắp nơi)

```php
// Product::getDisplayPriceAttribute()
return (float) ($this->sale_price ?: $this->base_price);

// ProductVariant::getDisplayPriceAttribute()
return (float) ($this->variant_price ?: $this->product?->display_price ?: 0);
```

Thứ tự ưu tiên: `variant_price` → `sale_price` → `base_price` → `0`.

> ⚠️ Dùng `?:` (falsy) chứ không phải `??` (null). Nghĩa là **giá bằng 0 bị coi là "không có
> giá"** và rơi xuống mức sau. Sản phẩm khuyến mãi 0đ sẽ hiển thị giá gốc. Đồng thời nếu cả
> ba đều rỗng, giá về `0` **mà không có cảnh báo nào** — khách đặt hàng miễn phí được.

> ⚠️ Truy vấn SQL trong admin lại dùng công thức **khác**: `COALESCE(sale_price, base_price)`
> (`ProductController::index`) và `COALESCE(pv.variant_price, p.base_price)` (báo cáo) — bỏ qua
> `sale_price` ở cấp variant. Ba công thức giá không đồng nhất giữa PHP và SQL.

---

## 3.2. `app/Http/Controllers/ProductController.php` (260 dòng) — phía khách

| Method | Route | Middleware |
|---|---|---|
| `index()` | `GET /san-pham` | `throttle:web-read` |
| `show()` | `GET /san-pham/{product:slug}` | `throttle:web-read` |
| `storeReview()` | `POST /san-pham/{product:slug}/danh-gia` | `auth`, `throttle:user-actions` |
| `tryOn()` | `GET /thu-kinh` | `throttle:web-read` |

### `index()` — Danh sách & lọc (dòng 22–88)

Bộ lọc hỗ trợ (tất cả kết hợp được, `->withQueryString()` giữ nguyên khi phân trang):

| Tham số | Kiểu | Xử lý |
|---|---|---|
| `q` | text | `LIKE %..%` trên `name` **hoặc** `product_code` |
| `category[]`, `brand[]`, `shape[]`, `material[]` | mảng id | `whereIn`, ép `(int)` qua `filterIds()` |
| `color[]`, `size[]` | mảng id | `whereHas('variants', ...)` |
| `uv[]` | mảng chuỗi | `whereIn('uv_protection', ...)` qua `filterStrings()` |
| `sale` | boolean | `sale_price IS NOT NULL AND sale_price < base_price` |
| `from_price`, `to_price` | text | **`preg_replace('/\D/', '')`** rồi so sánh — cho phép nhập "1.500.000" |
| `sort` | enum | `price_asc`/`price_desc`/`popular`/`name_asc`/`sale`/mặc định `latest()` |

`filterIds()` và `filterStrings()` ép kiểu + loại phần tử rỗng → **an toàn với SQL injection** ✅.
`$priceExpression` là hằng chuỗi `'COALESCE(sale_price, base_price)'` ghép vào `whereRaw`/
`orderByRaw` — an toàn vì không chứa input ✅. Giá trị lọc luôn đi qua binding `?` ✅.

Phân trang **6 sản phẩm/trang** — khá ít, gây nhiều lần tải trang.

> ⚠️ **Vấn đề N+1 / over-fetching:** `->with(['brand','category','frameShape','frameMaterial',
> 'variants.color','variants.lensSize'])` nạp **toàn bộ variant kèm color và lensSize** cho cả
> 6 sản phẩm, chỉ để hiển thị thẻ sản phẩm. Cộng thêm 7 truy vấn phụ để dựng sidebar bộ lọc
> (`categories`, `brands`, `colors`, `lensSizes`, `frameShapes`, `frameMaterials`, `uvOptions`)
> và 1 truy vấn `priceRange`, và 1 truy vấn banner. **Mỗi lần tải trang danh sách ≈ 12+ query**,
> không cache gì cả.

### `show()` — Chi tiết sản phẩm

```php
abort_unless($product->status === 'ACTIVE', 404);
$product->increment('view_count');
```

**Mới:** truyền thêm `$variantStock` — tồn kho khả dụng của từng biến thể, để giao diện hiển
thị "còn N sản phẩm":
```php
$variantStock = Inventory::query()
    ->whereIn('variant_id', $product->variants->pluck('id'))
    ->selectRaw('variant_id, COALESCE(SUM(quantity), 0) as available_stock')
    ->groupBy('variant_id')
    ->pluck('available_stock', 'variant_id')
    ->map(fn ($stock) => max(0, (int) $stock));
```
Truy vấn gọn, gộp theo biến thể, kẹp về ≥ 0 ✅.

> 🔴 **Nhưng con số này không phản ánh lượng đã bán.** `inventories.quantity` chỉ thay đổi khi
> admin lập phiếu nhập/xuất thủ công — đơn hàng không trừ kho (xem [06](06-module-kho-hang.md) §6.2,
> [10](10-ket-qua-audit.md) **C-02**). Nghĩa là số liệu sai nay đã **đi ra tới giao diện khách hàng**,
> chứ không còn nằm im trong admin.

> 🔴 **Ghi DB trên mỗi lượt xem.** `increment('view_count')` chạy `UPDATE` cho mọi request,
> kể cả bot và refresh. Không debounce, không queue, không cache. Đây vừa là điểm nghẽn hiệu
> năng vừa là kênh để bất kỳ ai bơm `view_count` — mà `view_count` chính là tiêu chí xếp hạng
> "Sản phẩm nổi bật" và "Xu hướng" ở trang chủ (`HomeController` dùng `orderByDesc('view_count')`).
> Bất kỳ ai cũng có thể đẩy sản phẩm bất kỳ lên top trang chủ bằng cách F5.
> Xem [10](10-ket-qua-audit.md) mục **H-05**.

Thống kê đánh giá được tính bằng **3 truy vấn trên cùng một query** (`clone` 2 lần cho
`count()` và `avg()`, rồi `paginate(5)`) — chấp nhận được nhưng có thể gộp thành 1
`selectRaw('COUNT(*), AVG(rating)')`.

`visibleReviews()` (trong `Product` model) lấy `whereIn('status', ['VISIBLE', 'PENDING'])` —
tức là **đánh giá đang chờ duyệt vẫn hiển thị công khai**. Đây là lựa chọn có chủ ý (khớp với
`ReviewAdminController` khi lọc `VISIBLE` cũng gộp cả `PENDING`), nhưng nghĩa là **không có
kiểm duyệt trước**: nội dung bậy hiện ngay, admin chỉ có thể ẩn sau.

Phần `tryOnPayload` trong `show()` dùng một query khá lắt léo:
```php
Product::active()->whereKey($product->getKey())
    ->orWhere(function ($q) use ($product) {
        $q->active()->where('category_id', $product->category_id)->whereKeyNot(...);
    })->limit(8)->get()
    ->sortByDesc(fn($item) => $item->is($product))->values()
```
Lấy chính sản phẩm này + tối đa 7 sản phẩm cùng danh mục, rồi **sắp xếp trong PHP** để đẩy
sản phẩm đang xem lên đầu. Hoạt động được, nhưng `orWhere` ở tầng ngoài kết hợp với scope
`active()` dễ sinh mệnh đề `WHERE status='ACTIVE' AND (id=X OR (status='ACTIVE' AND ...))` —
tối nghĩa; nên viết lại bằng 2 query riêng.

### `storeReview()` — Đăng đánh giá (dòng 133–160) 🔴

```php
$orderItem = $this->reviewOrderItemFor($product);   // có thể là null

ProductReview::create([
    'user_id'       => Auth::id(),
    'product_id'    => $product->id,
    'order_item_id' => $orderItem?->id,             // ← null vẫn cho tạo
    'rating'        => (int) $data['rating'],
    'content'       => trim((string) $data['content']),
    'status'        => 'VISIBLE',
]);
```

`reviewOrderItemFor()` (dòng 244) tìm một `OrderItem` của user cho sản phẩm này, thuộc đơn
`status = 'DELIVERED'`, và **chưa có đánh giá**. Logic tìm kiếm là đúng.

**Nhưng kết quả không hề được kiểm tra.** Nếu không tìm thấy (user chưa từng mua), `$orderItem`
là `null` và review **vẫn được tạo** với `order_item_id = null`.

Hệ quả:
- Bất kỳ tài khoản đã đăng nhập nào cũng đánh giá được **mọi** sản phẩm, kể cả chưa mua.
- **Không giới hạn số lần** — cùng một user spam 12 review/phút (trần `throttle:user-actions`).
- Trạng thái mặc định `VISIBLE` → hiện ngay, kéo điểm trung bình.

Đây là lỗ hổng nghiệp vụ nghiêm trọng nhất của module. Xem [10](10-ket-qua-audit.md) mục **H-01**.

> ⚠️ **Đợt cập nhật `ee3dfa5` không sửa gì ở đây** — code giữ nguyên từng dòng. Migration mới
> có thêm `idx_product_reviews_order_item`, nhưng đó là index **thường**, không phải UNIQUE, nên
> không chặn được đánh giá trùng.

### `tryOn()` — Thử kính (dòng 162–191)

Lấy 12 sản phẩm ACTIVE mới nhất; nếu có `?id_sp=` thì đẩy sản phẩm đó lên đầu danh sách.
Payload gửi sang JS gồm: `id`, `variantId`, `sku`, `hasModel`, `name`, `price`, `priceText`,
`productImage`, `cartImage`, `detailUrl`, `description`, `brand`, `material`.

> ⚠️ **`hasModel` luôn đúng.** Nó được tính bằng `trim($product->product_code) !== ''` — mà
> `product_code` được sinh tự động cho mọi sản phẩm (`'SP' . now()->format('YmdHis')`). Trong
> khi đó JS gọi `JEELIZVTOWIDGET.load(product.sku)` để nạp mô hình 3D theo SKU từ dịch vụ
> Jeeliz. Kết quả: **giao diện hứa mọi sản phẩm đều thử được, nhưng thực tế chỉ những SKU có
> sẵn model 3D bên Jeeliz mới hoạt động**; số còn lại sẽ lỗi/không hiện gọng.
> Xem [tài liệu 08](08-module-noi-dung.md) §8.5.

`plainDescription()` (dòng 225) làm sạch mô tả để hiển thị dạng text: chèn khoảng trắng sau
thẻ đóng khối, đổi `<br>` thành khoảng trắng, `strip_tags`, gộp khoảng trắng. Có fallback
tiếng Việt nếu rỗng. Hàm này **trùng lặp** với đoạn xử lý trong `products/show.blade.php`.

---

## 3.3. `app/Http/Controllers/Admin/ProductAdminController.php` (277 dòng)

| Method | Route | Ghi chú |
|---|---|---|
| `index()` | `GET /admin/san-pham` | Danh sách ACTIVE, paginate 15 |
| `create()` / `store()` | `/admin/san-pham/them` | |
| `edit()` / `update()` | `/admin/san-pham/{product}/sua` | |
| `recycle()` | `GET /admin/san-pham/thung-rac` | INACTIVE + DISCONTINUED + DRAFT |
| `hidden()` | `PATCH /admin/san-pham/{product}/an` | Đặt `status = 'INACTIVE'` |
| **`restore()`** | `PATCH /admin/san-pham/{product}/khoi-phuc` | **MỚI** — khôi phục từ thùng rác |
| **`exportExcel()`** | `GET /admin/san-pham/xuat-excel` | **MỚI** — xuất danh sách ra Excel |
| `uploadEditorImage()` | `POST /admin/san-pham/upload-editor` | Cho CKEditor, `throttle:uploads` |

> ✅ **`restore()`** lấp một lỗ hổng UX cơ bản: trước đây sản phẩm vào "thùng rác" là **không có
> đường quay lại** qua giao diện.
>
> ✅ **`exportExcel()`** (kèm view `admin/products/export-excel.blade.php`) hiện thực hóa route
> alias `xuat-exel` vốn chỉ redirect suông — khắc phục một phần **L-13**.

> 🔴 **`forgetCaches()` (dòng 280–284) là code chết và xóa sai key.** Hàm gọi
> `Cache::forget()` cho 5 key, nhưng rà soát toàn dự án: **4 key chưa từng được `Cache::remember`
> ở đâu**, và key thứ 5 bị lệch tên — `'layout.header_categories'` trong khi `AppServiceProvider`
> ghi vào `'layout.header_categories.v2'`.
>
> Hệ quả: menu header (cache 10 phút) **không bao giờ được làm mới** khi admin sửa sản phẩm/danh mục.
> Xem [10](10-ket-qua-audit.md) mục **N-01**.

### `index()` — 3 truy vấn con tương quan (dòng 28–52)

Mỗi dòng sản phẩm kèm 3 subquery:
- `quantity` = tổng `inventories.quantity - reserved_quantity` của mọi variant
- `sold_quantity` = tổng `order_items.quantity` (loại đơn `CANCELLED`, `LOST_IN_TRANSIT`)
- `max_price` = `MAX(COALESCE(variant_price, base_price))`

> ⚠️ Subquery tương quan chạy **cho từng dòng** → 15 sản phẩm = 45 subquery. Chưa có index
> nào được đảm bảo trên `order_items.product_id`, `inventories.variant_id`. Chậm dần theo
> lượng đơn hàng.

**`index()` chỉ hiển thị `status = 'ACTIVE'`.** Sản phẩm `DRAFT` không xuất hiện ở danh sách
chính mà nằm trong "thùng rác" cùng `INACTIVE`/`DISCONTINUED` — gộp "nháp" với "đã xóa" là
mô hình gây nhầm lẫn cho người dùng.

### `store()` / `update()` — Lưu sản phẩm

```php
DB::transaction(function () {
    $product = Product::create($this->prepareProductData($request, $data));
    $product->update(['slug' => Str::slug($data['name']) . '-' . $product->id]);
    $this->syncVariants($request, $product);
    $this->storeGalleryImages($request, $product);
});
```

Slug được đặt **hai lần**: trong `prepareProductData()` dùng `time()` làm hậu tố tạm, rồi
ngay sau đó `update()` lại bằng `$product->id`. Một `UPDATE` thừa cho mọi lần tạo; nên dùng
sự kiện `created` hoặc chấp nhận slug tạm.

Mã sản phẩm: `'SP' . now()->format('YmdHis')` — **không có thành phần ngẫu nhiên**. Hai
sản phẩm tạo trong cùng một giây sẽ trùng `product_code`. Ít xảy ra khi thao tác tay, nhưng
nếu `products.product_code` có ràng buộc UNIQUE thì sẽ lỗi 500; nếu không có UNIQUE thì SKU
biến thể của 2 sản phẩm khác nhau sẽ đụng nhau.
(So sánh: `Order` dùng `'ORD' . YmdHis . Str::upper(Str::random(3))` — có random ✅.)

### `syncVariants()` (dòng 214–244) — ⚠️ hai vấn đề

```php
ProductVariant::updateOrCreate([
    'id' => $variantIds[$index] ?? null,
    'product_id' => $product->id,
], [
    'sku' => ($product->product_code ?: 'SP'.$product->id) . '-' . str_pad($index+1, 2, '0', STR_PAD_LEFT),
    ...
]);
```

1. **`'id' => null` trong điều kiện `updateOrCreate`.** Khi thêm variant mới, Laravel tìm
   `WHERE id IS NULL AND product_id = X` (không khớp) rồi `create()` với `id => null` trong
   mảng thuộc tính. Với MySQL AUTO_INCREMENT, chèn `NULL` vào khóa chính vẫn sinh id mới nên
   *tình cờ* chạy được — nhưng đây là hành vi ngoài ý muốn, sẽ vỡ trên CSDL khác hoặc khi bật
   strict mode.

2. **SKU được sinh lại theo chỉ số dòng form.** SKU = `{product_code}-{số thứ tự dòng}`.
   Nếu admin **xóa dòng biến thể ở giữa** rồi lưu, các dòng phía sau dịch lên → **SKU của
   biến thể đang tồn tại bị đổi**. Trong khi đó `order_items.sku` đã lưu snapshot SKU cũ, và
   `inventories` gắn theo `variant_id`. Kết quả: SKU trong lịch sử đơn hàng không còn khớp
   SKU hiện tại của cùng một biến thể. Xem [10](10-ket-qua-audit.md) mục **H-04**.

3. Nếu form không gửi màu lẫn size (`count(array_filter($colors)) === 0 && ...`) → **return sớm**,
   không đụng gì tới variant. Nghĩa là **không có cách nào xóa biến thể** qua giao diện admin.

### `storeGalleryImages()` — chỉ 3 ảnh

Cứng nhắc ở `['image1','image2','image3']`. Mỗi lần lưu lại **thêm mới** 3 ảnh chứ không
thay thế → sửa sản phẩm 5 lần sẽ có 15 ảnh gallery. Không có chức năng xóa ảnh.

### `storeUpload()` (dòng 263–276)

```php
$name = (string) Str::uuid() . '.' . $file->extension();
$path = public_path('upload/' . $folder);
if (! is_dir($path)) { mkdir($path, 0777, true); }
$file->move($path, $name);
return $folder . '/' . $name;
```

- Tên file UUID ✅, phần mở rộng suy từ MIME thật (`extension()`, không phải `getClientOriginalExtension()`) ✅
- **`mkdir(..., 0777)`** — quyền quá rộng, nên là `0755`.
- Ghi vào `public_path()` → **mất file sau mỗi lần deploy Railway** (xem H-03).

---

## 3.4. `app/Http/Controllers/Admin/CategoryAdminController.php` (105 dòng)

CRUD danh mục sản phẩm, dùng chung view `admin.shared.form`. Không có xóa cứng — chỉ
`hidden()` đặt `status = 'INACTIVE'`.

> ⚠️ Slug danh mục là dữ liệu **có ý nghĩa nghiệp vụ ngầm**: `AppServiceProvider::headerProductLinks()`
> dò tiền tố `kinh-mat`, `kinh-thoi-trang`, `gong-kinh` để dựng menu header. Admin đổi slug →
> mất menu, không có cảnh báo. Đã nêu ở [01](01-tong-quan-kien-truc.md) §1.6.

---

## 3.5. Các model của module

### `Product.php` (106 dòng)

- `$fillable`: 16 cột. `$casts`: `base_price`, `sale_price` → `decimal:2`.
- Quan hệ: `category`, `brand`, `frameShape`, `frameMaterial`, `variants`, `images`
  (đã `orderBy('sort_order')`), `reviews`, `visibleReviews`.
- Scope: `active()` → `where('status', 'ACTIVE')`.
- Accessor: `display_price`, `image_url`.

**`getImageUrlAttribute()`** — thang giải quyết đường dẫn ảnh 5 nhánh:
```
'' → upload/no-image.jpg
http:// hoặc https:// → giữ nguyên
'upload/...' → asset(nguyên văn)
'anh_san_pham/...' → asset('upload/' . $image)
còn lại → asset('upload/anh_san_pham/' . $image)
```
Logic này lặp lại **gần như y hệt** ở `ProductImage::getUrlAttribute()` và
`WarehouseAdminController::productImageUrl()` — 3 bản sao của cùng một hàm. Xem [10](10-ket-qua-audit.md) mục L-02.

### `ProductVariant.php` (55 dòng)
`sku`, `color_id`, `lens_size_id`, `variant_price`, `status` (`ACTIVE`/`OUT_OF_STOCK`/`DISCONTINUED`).
Scope `active()`. Accessor `display_price`.

> Lưu ý: `status = 'OUT_OF_STOCK'` là cờ **thủ công**, không liên kết gì với bảng `inventories`.
> Không có cơ chế nào tự đặt cờ này khi tồn kho về 0.

### `ProductImage.php` (41 dòng)
`const UPDATED_AT = null` (bảng chỉ có `created_at`). Cột `is_thumbnail` cast bool nhưng
`storeGalleryImages()` luôn ghi `false` — cột này chưa có tác dụng thực.

### `Category.php`, `Brand.php`, `Color.php`, `LensSize.php`, `FrameShape.php`, `FrameMaterial.php`
Model tra cứu đơn giản. Bốn model cuối đặt `const UPDATED_AT = null`.
`LensSize` giữ 4 số đo: `bridge_width`, `temple_length`, `lens_width`, `lens_height`.
`Color` có `hex_code` để hiển thị ô màu.

### `ProductReview.php` (26 dòng)
`user_id`, `product_id`, `order_item_id`, `rating`, `content`, `status`
(`VISIBLE`/`PENDING`/`HIDDEN`). Quan hệ `user`, `product`, `orderItem`.

---

## 3.6. Tóm tắt các vấn đề của module

| Mã | Mức | Trạng thái | Vấn đề |
|---|---|---|---|
| H-04 | Cao | ✅ **đã sửa** | Cách sinh SKU biến thể đã được chỉnh lại |
| L-13 | Thấp | ✅ phần lớn | `exportExcel()` + `restore()` đã có thật |
| — | — | ✅ mới | Trang chi tiết hiển thị tồn kho (`$variantStock`) — *nhưng số liệu sai, xem C-02* |
| H-01 | Cao | ⚠️ **chưa sửa** | `storeReview()` không chặn khi chưa mua hàng; spam không giới hạn |
| H-05 | Cao | ⚠️ chưa sửa | `increment('view_count')` mỗi lượt xem: ghi DB không kiểm soát + thao túng được top trang chủ |
| **N-01** | TB | 🆕 mới | `forgetCaches()` xóa 4 key không tồn tại + 1 key sai tên → menu header không bao giờ làm mới |
| M-05 | TB | ⚠️ chưa sửa | Nhiều công thức tính giá khác nhau giữa PHP và SQL |
| M-06 | TB | ⚠️ chưa sửa | Không xóa được biến thể / ảnh gallery qua giao diện |
| M-07 | TB | ⚠️ chưa sửa | `product_code` không có phần ngẫu nhiên → trùng khi tạo cùng giây |
| M-08 | TB | 🟠 một phần | Subquery tương quan vẫn còn, **nhưng** migration mới đã thêm index cho `order_items.product_id`, `inventories.variant_id`, `products.status_*` ✅ |
| L-02 | Thấp | ⚠️ nặng thêm | Hàm giải đường dẫn ảnh nay lặp **4 lần** (thêm `TryOnSnapshot::getImageUrlAttribute`) |
| L-03 | Thấp | ⚠️ chưa sửa | `updateOrCreate` với `id => null`; `mkdir` quyền 0777 |
