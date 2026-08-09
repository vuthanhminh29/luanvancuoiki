# 10 — Kết quả Audit

**Bản gốc:** commit `7967a7b` · **Bản cập nhật:** commit `ee3dfa5` (27 commit mới, 115 file, +8.834/−1.318 dòng)

**Phạm vi:** toàn bộ mã nguồn.
**Phương pháp:** phân tích tĩnh 100% `app/`, `routes/`, `bootstrap/`, `database/`, `config/`,
đối chiếu chéo controller ↔ model ↔ view ↔ migration.
**Giới hạn:** máy audit chỉ có PHP 7.4 và chưa cài `vendor/` (dự án yêu cầu PHP ^8.4), nên
**không chạy được** test/migration/route:list. Các mục đánh dấu *(chưa kiểm chứng runtime)*
cần chạy thử để xác nhận.

---

## Tóm tắt biến động sau lần cập nhật

| | Bản gốc | Bản mới | Thay đổi |
|---|---|---|---|
| 🔴 Nghiêm trọng | 5 | **3** | −3 đã sửa, +1 mới |
| 🟠 Cao | 14 | **9** | −7 đã sửa, +2 mới |
| 🟡 Trung bình | 24 | **19** | −8 đã sửa, +3 mới |
| 🔵 Thấp | 17 | **14** | −5 đã sửa, +2 mới |

**Đã khắc phục 23 phát hiện.** Chất lượng code cải thiện rõ: xuất hiện tầng **Service**
(`OrderCancellationService`, `OrderConfirmationEmailService`, `OrderInvoiceEmailService`),
**state machine** cho đơn hàng, **hàng đợi email**, **9 migration mới** có bảo vệ
`Schema::hasTable()`, và comment tiếng Việt giải thích nghiệp vụ ở hầu hết file được sửa.

**8 phát hiện mới** phát sinh từ chính đợt thay đổi này — xem mục "Phát hiện mới" bên dưới.

---

# ✅ ĐÃ KHẮC PHỤC

| Mã cũ | Nội dung | Cách đã sửa |
|---|---|---|
| **C-05** | Test suite không chạy được | ⚠️ **Chỉ một phần** — xem N-03, `UserFactory` vẫn còn lỗi |
| **H-04** | SKU biến thể sinh lại theo chỉ số dòng | `ProductAdminController::syncVariants()` đã chỉnh lại cách sinh SKU |
| **H-09** | Danh sách đơn hàng không phân trang | `orderList()` → `->paginate(20)->withQueryString()`; 5 truy vấn `summary` gộp còn **1 truy vấn** `selectRaw` |
| **H-10** | Yêu cầu hoàn/đổi thiếu ràng buộc | `ReturnRequestController` viết lại: `ELIGIBLE_ORDER_STATUSES`, `returnableItems()`, `remainingReturnQuantity()` cộng dồn số lượng đã yêu cầu, `reason_id` phải `status = ACTIVE`, form tự lọc sản phẩm còn hoàn được |
| **H-11** | `delivered_at` không bao giờ được lưu | Migration `2026_08_04_140000` thêm cột; `changeStatus()` ghi qua `forceFill()` (bỏ qua mass-assignment) |
| **M-13** | Không có state machine | `OrderAdminController::STATUS_TRANSITIONS` — bảng chuyển tiếp 9 trạng thái, chặn chuyển ngược |
| **M-17** | Trùng `variant_id` trong cùng phiếu kho | Thêm kiểm tra `$items->pluck('variant_id')->duplicates()->isNotEmpty()` |
| **M-21** | Khuyến mãi chỉ tạo được, không sửa được | `PromotionAdminController` mới có `store()` + `update()` + toggle riêng |
| **L-08** | `inventories` có thể sinh dòng trùng | Migration `2026_08_04_130000` thêm index `idx_inventories_variant_warehouse` |
| **L-12** (phần index) | Thiếu chỉ mục CSDL | Migration `2026_08_04_130000` thêm **~40 index** trên 18 bảng — bao trùm gần hết khuyến nghị tại [09](09-mo-hinh-du-lieu.md) §9.6 |
| **M-16** (phần role query) | Truy vấn vai trò lặp mỗi request | `EnsureAdmin` + `AdminAuthController` dùng `Cache::remember("users.{id}.role_codes", 5 phút)` — ⚠️ nhưng sinh ra **N-02** |
| **L-09** (kho RETURN) | Kho `RETURN`/`WARRANTY` không dùng | Migration `2026_08_04_151000` gộp kho RETURN về `NORMAL` — đơn giản hóa mô hình |
| **L-10** | Hai màn hình phiếu kho trùng chức năng | Loại `TRANSFER`; `transactions()` đổi tiêu đề thành "Phiếu nhập/xuất kho" |
| — | `reserved_quantity` là cột chết | Migration `2026_08_04_152000` **cộng dồn vào `quantity` rồi xóa hẳn cột**, thêm CHECK `quantity >= 0`. Toàn bộ truy vấn `(quantity - reserved_quantity)` đã đổi thành `quantity` |

### Cải tiến khác đáng ghi nhận (không nằm trong audit cũ)

| Hạng mục | Chi tiết |
|---|---|
| **Luồng hủy đơn 2 bước** | `OrderCancellationService` (295 dòng): admin bấm hủy → sinh token `Str::random(72)` lưu **SHA-256** → gửi email có `URL::temporarySignedRoute` hạn 3 ngày → **khách xác nhận** thì đơn mới thật sự `CANCELLED`. Có `lockForUpdate()`, xóa token sau khi dùng, rollback token nếu gửi mail lỗi. Thiết kế rất tốt ✅ |
| **Email đưa vào hàng đợi** | `App\Support\QueuedRawMail` + `App\Jobs\SendRawMailJob` — email không còn chặn request; có fallback gửi trực tiếp nếu dispatch lỗi |
| **Email xác nhận đơn + hóa đơn** | `OrderConfirmationEmailService`, `OrderInvoiceEmailService`, view `account/orders/invoice.blade.php`, route gửi hóa đơn qua email |
| **Hiển thị tồn kho cho khách** | `ProductController::show()` truyền `$variantStock` — trang chi tiết sản phẩm nay biết còn bao nhiêu hàng |
| **Phiếu xuất bán tự động** | `createSaleOutTransaction()` sinh phiếu `SALE_OUT` khi đơn chuyển `DELIVERING`, có chống trùng (`saleOutTransactionExists`), ghi `related_order_id` — ⚠️ nhưng **chưa trừ kho thật**, xem C-02 |
| **Đăng ký thu địa chỉ** | `AuthController::register()` nay bắt buộc `province_name` (whitelist 63 tỉnh) + `address_detail`, tự tạo `UserAddress` mặc định |
| **Xử lý link hết hạn** | `bootstrap/app.php` bắt `InvalidSignatureException` → redirect kèm thông báo thay vì trang lỗi 403 |
| **Thử kính: lưu kết quả** | `TryOnSnapshot` model + bảng + `storeTryOnSnapshot()` (validate data-URI, giới hạn 5 MB) + màn hình admin `TryOnSnapshotAdminController` |
| **Xuất Excel sản phẩm** | `ProductAdminController::exportExcel()` + view — lấp chỗ trống của route alias `xuat-exel` (L-13 cũ) |
| **Khôi phục sản phẩm** | Route `products.restore` — trước đây chỉ ẩn được, không khôi phục được |

---

# 🔴 NGHIÊM TRỌNG (còn lại)

## C-01 — Không thể dựng CSDL từ mã nguồn; deploy vẫn sẽ thất bại ⚠️ **CHƯA SỬA**

**Vị trí:** `database/migrations/2026_07_10_060000_keep_only_cod_and_vnpay_payment_methods.php`

9 migration mới **đều có bảo vệ** đúng cách:
```php
if (! Schema::hasTable('orders') || Schema::hasColumn('orders', 'delivered_at')) return;
```
Đây là cải thiện thật ✅. **Nhưng migration cũ vẫn nguyên xi, không có bảo vệ:**
```php
public function up(): void {
    DB::statement("ALTER TABLE payments MODIFY method ENUM('COD','VNPAY') NOT NULL");
    DB::statement("ALTER TABLE orders MODIFY payment_method ENUM('COD','VNPAY') NOT NULL DEFAULT 'COD'");
}
public function down(): void { /* giống hệt up() — không rollback gì */ }
```

Và **~20 bảng nghiệp vụ vẫn không có migration nào tạo ra chúng**. Trên CSDL trống,
`php artisan migrate --force` trong `railway/init-app.sh` (chạy với `set -e`) vẫn dừng ở đây.

**Khắc phục:**
1. Bọc `up()` bằng `if (! Schema::hasTable('payments')) return;` — giống 9 migration mới.
2. `mysqldump --no-data ten_db > database/schema.sql`, commit, và ghi rõ bước import trong `README.md`.
3. Sửa `down()` cho đúng.

## C-02 — Phiếu SALE_OUT chỉ là chứng từ giấy: tồn kho vẫn không bị trừ 🟠 **SỬA MỘT PHẦN**

**Vị trí:** `OrderAdminController::createSaleOutTransaction()` (dòng 167–207)

Đợt cập nhật đã bổ sung luồng sinh phiếu xuất bán khi đơn chuyển `DELIVERING`:
```php
$transaction = StockTransaction::create([... 'type' => 'SALE_OUT' ...]);
foreach ($items as $item) {
    $transaction->items()->create([
        'variant_id' => (int) $item->variant_id,
        'ordered_quantity' => (int) $item->quantity,
        'actual_quantity' => (int) $item->quantity,
        ...
    ]);
}
```

Đây là bước tiến thật: có `related_order_id`, có chống tạo trùng, có chọn kho nguồn thông minh
(`saleOutSourceWarehouseId()` ưu tiên kho ACTIVE còn nhiều hàng nhất).

**Nhưng hàm này không hề gọi `Inventory::decrement()`.** Rà soát toàn bộ
`OrderAdminController`: không có một lệnh ghi nào vào bảng `inventories` — `Inventory` chỉ
được dùng để **đọc** trong `saleOutSourceWarehouseId()`. So sánh với
`WarehouseAdminController::storeTransaction()`, nơi phiếu EXPORT gọi `subtractVariantInventory()`
một cách rõ ràng.

**Hệ quả:** `inventories.quantity` vẫn **chỉ thay đổi khi admin lập phiếu nhập/xuất thủ công**.
- Bán vượt kho không giới hạn vẫn còn nguyên.
- Nay còn tệ hơn về mặt đối soát: hệ thống sinh ra chứng từ `SALE_OUT` ghi "đã xuất N sản phẩm",
  nhưng số tồn không giảm → **sổ sách và tồn thực tế mâu thuẫn nhau ngay trong hệ thống**.
- `$variantStock` mới hiển thị cho khách ở trang sản phẩm là con số **không phản ánh lượng đã bán**.

**Khắc phục:** trong `createSaleOutTransaction()`, sau khi tạo item, trừ kho có kiểm tra:
```php
foreach ($items as $item) {
    $affected = DB::table('inventories')
        ->where('warehouse_id', $sourceWarehouseId)
        ->where('variant_id', $item->variant_id)
        ->where('quantity', '>=', $item->quantity)
        ->decrement('quantity', $item->quantity);

    if ($affected === 0) {
        throw new RuntimeException("Không đủ tồn kho cho {$item->product_name}.");
    }
}
```
Lý tưởng hơn: trừ kho ngay khi **đặt hàng** (giữ chỗ) thay vì đợi tới `DELIVERING`, và hoàn kho
khi đơn `CANCELLED` / hoàn hàng `COMPLETED`.

> Lưu ý tích cực: migration `2026_08_04_152000` đã thêm `CHECK (quantity >= 0)` — nên khi cài
> logic trừ kho, CSDL sẽ chặn được tồn âm ở tầng cuối cùng.

## C-04 — Stored XSS ở trang blog ⚠️ **CHƯA SỬA**

**Vị trí:** `resources/views/blog/show.blade.php:57`, `PostAdminController::validatePost()`

Không thay đổi so với bản gốc:
```blade
{!! $post->content ?: '<p>' . e($post->summary) . '</p>' !!}
```
```php
'content' => ['nullable', 'string'],     // không sanitize
```

Route `posts.*` vẫn nằm **ngoài** nhóm `admin:ADMIN` → mọi `STAFF` đều đăng bài được.
Vẫn không có `Content-Security-Policy`.

Đây hiện là **lỗ hổng bảo mật duy nhất khai thác trực tiếp được** trong hệ thống, và giờ càng
đáng ưu tiên vì cache vai trò 5 phút (N-02) làm việc thu hồi quyền `STAFF` bị chậm.

**Khắc phục:** xem hướng dẫn chi tiết ở [08](08-module-noi-dung.md) §8.3 — sanitize ở tầng lưu
bằng HTMLPurifier, hoặc tái sử dụng đúng logic allowlist đã có ở `products/show.blade.php:33-36`.

---

# 🟠 CAO (còn lại)

## H-01 — Đánh giá sản phẩm không cần mua hàng ⚠️ **CHƯA SỬA**
**`ProductController::storeReview()`** — code không đổi một dòng nào:
```php
$orderItem = $this->reviewOrderItemFor($product);   // có thể null
ProductReview::create([... 'order_item_id' => $orderItem?->id, 'status' => 'VISIBLE']);
```
Mọi tài khoản đăng nhập vẫn đánh giá được mọi sản phẩm, không giới hạn số lần.
Migration mới **đã thêm** `idx_product_reviews_order_item` nhưng đó là index thường, **không
phải UNIQUE** → không chặn được trùng.

**Sửa:** chặn khi `$orderItem === null`, và đổi index thành UNIQUE trên `order_item_id`.

## H-02 — Mã giảm giá vượt `usage_limit` khi truy cập đồng thời ⚠️ **CHƯA SỬA**
Kiểm tra `used_count >= usage_limit` và `increment('used_count')` vẫn tách rời, không khóa.
Xem hướng khắc phục ở [04](04-module-gio-hang-thanh-toan.md) §4.2.

## H-03 — Ảnh upload mất sau mỗi lần deploy ⚠️ **TỆ HƠN TRƯỚC**
Vẫn ghi vào `public_path()`. Đợt này **thêm một nguồn ghi mới**:
```php
// ProductController::storeTryOnImage()
$directory = 'upload/tryons/' . now()->format('Y/m');
File::ensureDirectoryExists(public_path($directory));
File::put(public_path($path), $binary);
```
Ảnh thử kính (tối đa 5 MB/ảnh) cũng vào `public/`. Repo đã có 9 ảnh try-on được **commit thẳng
vào Git** (`public/upload/tryons/2026/08/*.jpg`) — dấu hiệu cho thấy đang phải commit ảnh
runtime để chúng sống sót qua deploy. Đây là cách chữa cháy, không phải giải pháp.

## H-05 — `view_count` ghi DB mỗi lượt xem, thao túng được trang chủ ⚠️ **CHƯA SỬA**
`$product->increment('view_count')` vẫn chạy mọi request. Migration mới thêm
`idx_products_status_views` → truy vấn xếp hạng nhanh hơn, nhưng **không giải quyết** việc ghi
DB mỗi pageview và việc bơm chỉ số bằng F5.

## H-06 — Bí mật VNPay có giá trị mặc định hardcode ⚠️ **CHƯA SỬA**
`config/vnpay.php` và `railway/init-app.sh` vẫn giữ `TYIMV67T` / `LNBQQ3N8MYP26ECD7DW47JM60474RKUD`.

> ⚠️ **Phát sinh thêm:** đợt pull commit file **`.env.railway_2047_backup`** (77 dòng) vào repo.
> `.gitignore` có `.env` và `.env.*` — nhưng tên này **không khớp** pattern `.env.*` theo cách
> Git hiểu? Thực tế nó đã được commit thành công, nên pattern không chặn được.
> **Cần kiểm tra ngay file này có chứa credential thật hay không và gỡ khỏi lịch sử Git nếu có.**
> Xem N-04.

## H-07 — Draft VNPay phụ thuộc cache store ⚠️ **CHƯA SỬA**

## H-08 — Đơn COD không bao giờ chuyển `payment_status = 'PAID'` ⚠️ **CHƯA SỬA**
`changeStatus()` mới có xử lý `delivered_at` khi `DELIVERED`, nhưng **không đụng tới
`payment_status`**. Đơn COD giao thành công vẫn hiển thị "chưa thanh toán", vẫn không có bản
ghi `payments` nào.

## H-12 — Nhiều công thức doanh thu khác nhau ⚠️ **CHƯA SỬA**
`DashboardController` biểu đồ 7 ngày vẫn không lọc `status` → tính cả đơn `CANCELLED`.
Báo cáo vẫn dùng `SUM(oi.quantity * oi.unit_price)`, bỏ qua `discount_amount`.

> ⚠️ **Phát sinh thêm:** `LOST_IN_TRANSIT` đã bị **loại khỏi** `OrderAdminController::VALID_STATUSES`
> và `STATUS_TRANSITIONS`, nhưng `ReportAdminController::$excludedOrderStatuses` và mọi truy vấn
> báo cáo **vẫn lọc `NOT IN ('CANCELLED', 'LOST_IN_TRANSIT')`**. Không gây sai số (chỉ là điều
> kiện thừa), nhưng là dấu hiệu enum không được đồng bộ khi refactor.

## H-13 — Tài khoản admin tạo không có `email_verified_at` ⚠️ **CHƯA SỬA**

## H-14 — ADMIN có thể tự khóa mình / hạ cấp admin cuối cùng ⚠️ **CHƯA SỬA — và nay nguy hiểm hơn**
Kết hợp với cache vai trò 5 phút (N-02): sau khi hạ cấp nhầm, hệ thống còn **mất thêm 5 phút**
mới phản ánh thay đổi, khiến việc chẩn đoán càng rối.

---

# 🆕 PHÁT HIỆN MỚI (phát sinh từ đợt cập nhật)

## N-01 🟡 — Khối xóa cache là code chết và **không xóa đúng key thật**

**Vị trí:** `ProductAdminController.php:280-284` vs `AppServiceProvider.php:64`

```php
// ProductAdminController::forgetCaches()
Cache::forget('admin.product.form_lookups');
Cache::forget('products.index.price_range');
Cache::forget('products.index.filter_lookups');
Cache::forget('layout.header_categories');      // ← key SAI
Cache::forget('home.payload');
```
```php
// AppServiceProvider — key thật đang được ghi
Cache::remember('layout.header_categories.v2', now()->addMinutes(10), ...)
```

Rà soát toàn bộ `app/`: **4/5 key trên không được `Cache::remember` ở bất kỳ đâu** — chúng
không tồn tại. Key thứ 5 bị lệch tên (`layout.header_categories` vs `...v2`).

**Hệ quả:** menu header (được cache 10 phút) **không bao giờ bị làm mới** khi admin thêm/sửa
sản phẩm hay danh mục. Admin sẽ thấy thay đổi "không có tác dụng" trong tối đa 10 phút.

**Sửa:** đổi thành `Cache::forget('layout.header_categories.v2')` và xóa 4 dòng còn lại (hoặc
bổ sung phần `Cache::remember` tương ứng nếu ý định ban đầu là cache thật).

## N-02 🟠 — Cache vai trò 5 phút không bao giờ bị vô hiệu hóa

**Vị trí:** `EnsureAdmin.php:52`, `AdminAuthController.php:66`

```php
Cache::remember("users.{$userId}.role_codes", now()->addMinutes(5), fn () => ...);
```

Đây là bản sửa đúng hướng cho vấn đề truy vấn vai trò lặp lại. **Nhưng không có một
`Cache::forget("users.{$userId}.role_codes")` nào trong toàn dự án.**

`CustomerAdminController::syncRole()` và `updateStatus()` đổi vai trò/trạng thái trong CSDL
nhưng không đụng tới cache.

**Hệ quả bảo mật:**
- Hạ cấp một `ADMIN` xuống `USER` → người đó **vẫn giữ toàn quyền admin trong tối đa 5 phút**.
- Thu hồi quyền của nhân viên vừa nghỉ việc **không có hiệu lực ngay**.
- Kết hợp với C-04 (STAFF chèn được JS): cửa sổ 5 phút này là thời gian tấn công.

Lưu ý: kiểm tra `status !== 'ACTIVE'` trong `EnsureAdmin` đọc từ `Auth::user()` (không cache)
nên **khóa tài khoản vẫn có hiệu lực ngay** ✅ — chỉ vai trò bị chậm.

**Sửa:**
```php
// Trong CustomerAdminController::syncRole() và updateStatus(), sau khi ghi DB:
Cache::forget("users.{$user->id}.role_codes");
```

## N-03 🟠 — `UserFactory` vẫn hỏng; test suite vẫn không chạy được

Bản gốc là **C-05**. Đợt cập nhật **không sửa** `database/factories/UserFactory.php`:
```php
use App\Models\Team;                       // class không tồn tại
'name' => fake()->name(),                  // users không có cột 'name'
'password' => Hash::make(...),             // users dùng 'password_hash'
'profile_photo_path' => null,              // cột không tồn tại
'current_team_id' => null,                 // cột không tồn tại
```
`DatabaseSeeder` cũng giữ nguyên `User::factory()->create(['name' => 'Test User', ...])`.

Hạ mức từ 🔴 xuống 🟠 vì không chặn triển khai, nhưng **hệ quả nghiêm trọng hơn trước**: đợt
này thêm ~1.500 dòng logic nghiệp vụ mới (state machine, luồng hủy 2 bước, phiếu SALE_OUT,
snapshot thử kính) mà **không có một test nào** kiểm chứng. *(chưa kiểm chứng runtime)*

## N-04 🟠 — File `.env.railway_2047_backup` bị commit vào repo

**Vị trí:** thư mục gốc, 77 dòng, thêm ở commit `75de001`

`.gitignore` khai báo `.env` và `.env.*`, nhưng file này vẫn vào được repo — tên
`.env.railway_2047_backup` không khớp cách Git áp dụng pattern đó.

**Cần làm ngay:**
1. Mở file, kiểm tra có `APP_KEY`, `DB_PASSWORD`, `MAIL_PASSWORD`, `VNPAY_HASH_SECRET` thật hay không.
2. Nếu có: **coi như đã lộ** — xoay vòng toàn bộ credential, rồi gỡ khỏi lịch sử Git
   (`git filter-repo` hoặc BFG), không chỉ `git rm`.
3. Bổ sung `.gitignore`: `.env*` (thay vì `.env.*`).

> Cùng đợt này còn có 7 file `RECOVERY_*.csv` (783 + 201 + 104 + … dòng) và `RECOVERY_NOTES.md`
> — sản phẩm phụ của một lần khôi phục dữ liệu. Chúng không thuộc mã nguồn ứng dụng và nên
> chuyển ra ngoài repo.

## N-05 🟡 — Lỗi mã hóa tiếng Việt (mojibake) trong `bootstrap/app.php`

**Vị trí:** `bootstrap/app.php:41,46`

```php
->with('error', 'LiÃªn káº¿t xÃ¡c nháº­n há»§y Ä‘Æ¡n khÃ´ng há»£p lá»‡ hoáº·c Ä‘Ã£ háº¿t háº¡n...')
->withErrors(['email' => 'LiÃªn káº¿t xÃ¡c thá»±c khÃ´ng há»£p lá»‡ hoáº·c Ä‘Ã£ háº¿t háº¡n...'])
```

Chuỗi UTF-8 bị mã hóa hai lần. Người dùng sẽ thấy đúng chuỗi rác này khi bấm vào link xác nhận
hủy đơn đã hết hạn.

Rà soát toàn bộ `app/`, `resources/views/`, `bootstrap/`, `routes/`: **đây là file duy nhất**
bị lỗi ✅ (repo có kèm `RECOVERY_EXISTING_FILES_WITH_MOJIBAKE_*.csv`, cho thấy vấn đề đã được
biết và xử lý phần lớn).

**Sửa:** gõ lại 2 chuỗi bằng UTF-8 đúng, lưu file với encoding UTF-8 không BOM.

## N-06 🟡 — `AccountController::editProfile()` / `updateProfile()` thành code chết, trỏ tới view đã xóa

**Vị trí:** `routes/web.php:74`, `AccountController.php:80-127`

```php
Route::redirect('/tai-khoan/ho-so', '/tai-khoan')->name('account.profile.edit');
```
Route cũ bị thay bằng redirect, và `resources/views/account/profile.blade.php` **đã bị xóa**.
Nhưng `editProfile()` (trả `view('account.profile')`) và `updateProfile()` (74 dòng, xử lý
upload avatar) **vẫn còn trong controller**, không còn route nào gọi tới.

Không gây lỗi runtime (không ai gọi được), nhưng:
- `editProfile()` sẽ ném `ViewNotFoundException` nếu có ai khôi phục route.
- Chức năng **sửa hồ sơ và đổi avatar của khách hàng đã biến mất khỏi sản phẩm** — cần xác nhận
  đây là quyết định có chủ ý, vì đăng ký nay có thu địa chỉ nhưng khách không sửa lại được.

## N-07 🔵 — `tryOnModelCheck` đã có nhưng JavaScript chưa dùng

**Vị trí:** `ProductController::tryOnModelCheck()`, `resources/views/tryon-ai.blade.php:18`,
`public/js/tryon-ai.js`

Endpoint mới kiểm tra SKU có model 3D thật hay không bằng cách gọi API Jeeliz:
```php
Http::withoutVerifying()->timeout(5)->get('https://glassesdbcached.jeeliz.com/sku/' . rawurlencode($sku));
$isSupported = $response->ok() && is_array($data) && isset($data['intrinsic']) && empty($data['error']);
```
View đã truyền URL qua `data-jeeliz-model-check-url="{{ route('tryon.model-check') }}"`.

**Nhưng `public/js/tryon-ai.js` không hề đọc thuộc tính này** — grep `modelCheck` trong toàn bộ
`public/js/` không có kết quả. JS vẫn dùng `product.hasModel` từ PHP, mà giá trị đó vẫn là
`trim($product->product_code) !== ''` → **luôn `true`**.

Vậy M-23 mới sửa được **một nửa**: hạ tầng kiểm tra đã có, phần tiêu thụ chưa nối.

> ⚠️ Đồng thời: **`Http::withoutVerifying()` tắt xác minh chứng chỉ TLS**. Với một API công khai
> chỉ trả về true/false thì tác động thấp, nhưng đây là thói quen nguy hiểm — nên bỏ và xử lý
> đúng vấn đề CA gốc nếu gặp lỗi SSL.

## N-08 🔵 — `AWAITING_PAYMENT` là trạng thái không thể tới được

**Vị trí:** `OrderAdminController::STATUS_TRANSITIONS` (dòng 49–59)

```php
'PENDING'          => ['CONFIRMED', 'CANCELLED'],
'AWAITING_PAYMENT' => ['CONFIRMED', 'CANCELLED'],
...
```
`AWAITING_PAYMENT` có nhánh **đi ra**, nhưng **không trạng thái nào chuyển vào nó**, và
không luồng nào (checkout COD, VNPay, draft) đặt đơn ở trạng thái này — cả hai đều tạo đơn
với `status = 'PENDING'`.

Trạng thái này vẫn xuất hiện trong `VALID_STATUSES`, `STATUS_LABELS`, `CANCELLABLE_STATUSES`,
bộ lọc dashboard (`pending_orders`) và báo cáo — nhưng **không bao giờ có đơn nào mang nó**.

Đây chính là trạng thái lẽ ra dùng cho đơn VNPay chờ thanh toán — bằng chứng nữa cho thấy mô
hình "đơn nháp" (C-03 cũ, nay H-07) đã thay thế một thiết kế ban đầu khác.

---

# 🟡 TRUNG BÌNH (còn lại)

| Mã | Trạng thái | Vấn đề |
|---|---|---|
| **M-01** | chưa sửa | `failed_login_count`, `locked_until`, `last_failed_login_at`, `last_login_at` không bao giờ được ghi |
| **M-02** | chưa sửa | `Log::error` ghi `reset_url` chứa token thô khi gửi mail lỗi |
| **M-03** | chưa sửa | `/vnpay/ipn` và `/vnpay/return` không throttle, không log |
| **M-04** | chưa sửa | Thiếu `Content-Security-Policy` và `Strict-Transport-Security` |
| **M-05** | chưa sửa | Nhiều công thức tính giá khác nhau giữa PHP và SQL |
| **M-06** | chưa sửa | Không xóa được biến thể / ảnh gallery qua giao diện |
| **M-07** | chưa sửa | `product_code = 'SP'.YmdHis` không có phần ngẫu nhiên |
| **M-08** | chưa sửa | Subquery tương quan trong danh sách admin; trang danh sách client nhiều truy vấn |
| **M-09** | chưa sửa | `promotions.usage_per_user` không bao giờ được đặt/kiểm tra |
| **M-10** | chưa sửa | `city` ở checkout không whitelist (dù `register` nay **đã** whitelist); `orders.address_id` vẫn không được ghi |
| **M-11** | chưa sửa | Hủy đơn không hoàn `used_count`, không hoàn tiền, không nhập lại kho |
| **M-12** | chưa sửa | Duyệt hoàn hàng `COMPLETED` không nhập kho, không hoàn tiền, không ghi `reviewed_by` |
| **M-14** | một phần | Vẫn không có nhật ký thay đổi trạng thái. **Nhưng** nay đã có email xác nhận đơn hàng và email hủy đơn gửi cho khách ✅ |
| **M-15** | chưa sửa | `activateVariantProduct()` ép `ACTIVE` cho biến thể/sản phẩm đã bị ẩn có chủ ý |
| **M-18** | chưa sửa | Cú pháp MySQL-only (`MONTH()`, `CURRENT_DATE()`, `DATE_SUB`, UPDATE-JOIN, `information_schema.CHECK_CONSTRAINTS`, `ALTER TABLE ... DROP CHECK` trong migration mới) |
| **M-19** | chưa sửa | Báo cáo bỏ qua `discount_amount` |
| **M-20** | chưa sửa | Mật khẩu admin `min:6` yếu hơn đăng ký `min:8`; email bắt buộc Gmail; 2 regex SĐT khác nhau |
| **M-22** | chưa sửa | Nhiều chiến lược lưu ảnh mâu thuẫn; nay thêm nhánh thứ tư cho ảnh thử kính |
| **M-24** | chưa sửa | Slug bài viết không `unique` và đổi sau lần sửa đầu tiên |
| **N-01** | mới | Khối xóa cache là code chết, không xóa đúng key thật |
| **N-05** | mới | Mojibake trong `bootstrap/app.php` |
| **N-06** | mới | `editProfile`/`updateProfile` thành code chết, view đã bị xóa |

---

# 🔵 THẤP (còn lại)

| Mã | Trạng thái | Vấn đề |
|---|---|---|
| **L-01** | chưa sửa | Jetstream/Fortify/Livewire là code chết; `sepay/sepay-pg` không dùng ở đâu |
| **L-02** | chưa sửa | Hàm giải đường dẫn ảnh lặp nhiều lần (nay thêm `TryOnSnapshot::getImageUrlAttribute`) |
| **L-03** | chưa sửa | `mkdir(..., 0777)`; avatar dùng `getClientOriginalExtension()` (nay là code chết, xem N-06) |
| **L-04** | chưa sửa | `shipping_fee` hardcode 0; mã GD VNPay ghi vào `orders.note` — nay `orders.note` còn nhận thêm lý do hủy đơn, càng quá tải |
| **L-05** | chưa sửa | `return_request_images` không có code nào ghi vào; `exchange_variant_id` không bao giờ được set |
| **L-06** | chưa sửa | `saveDamageAssessments` không có transaction; `assessed_by ?? 1` |
| **L-07** | đã sửa một phần | `nextSaleOutTransactionCode()` mới **có** vòng `do...while` kiểm tra trùng ✅; `nextTransactionCode()` cũ trong `WarehouseAdminController` vẫn chưa có |
| **L-09** | một phần | Kho `RETURN` đã được gộp bỏ ✅; `warehouses.capacity` vẫn không bao giờ được kiểm tra |
| **L-11** | chưa sửa | `lowStockCount` luôn ≤ 5; `topCategories` không lọc đơn hủy; `syncRole()` không có transaction |
| **L-13** | đã sửa một phần | Xuất Excel **đã có thật** (`products.export-excel`) ✅; bảng `stores` vẫn chỉ-đọc |
| **L-14** | chưa sửa | 3/5 vị trí banner không được hiển thị; `banners.platform` không có tác dụng |
| **L-15** | chưa sửa | Trạng thái `PENDING` của đánh giá vô nghĩa |
| **L-16** | chưa sửa | `featuredProducts`/`trendProducts` trùng dữ liệu; `/lien-he` không có form hoạt động |
| **L-17** | chưa sửa | `home_layouts` không có model/migration; `categories.parent_id` không có quan hệ cha-con |
| **N-07** | mới | `tryOnModelCheck` chưa được JS gọi; `Http::withoutVerifying()` tắt kiểm tra TLS |
| **N-08** | mới | `AWAITING_PAYMENT` là trạng thái không thể tới được |

---

## Những điểm đã làm tốt ✅ (cập nhật)

Giữ nguyên toàn bộ danh sách của bản gốc (xác minh chữ ký VNPay, chống giả mạo giá,
idempotency thanh toán, token reset password, di trú hash mật khẩu, kiểm tra sở hữu,
phân quyền 2 cấp, snapshot đơn hàng, validate mã giảm giá, rate limiting,
`ValidateRequestInput`, an toàn SQL, xử lý tiếng Việt, đánh giá hư hỏng kính, rollback đăng ký,
quản lý địa chỉ mặc định), **cộng thêm**:

| Hạng mục mới | Chi tiết |
|---|---|
| **Luồng hủy đơn 2 bước** | Token 72 ký tự lưu SHA-256, signed URL hạn 3 ngày, `lockForUpdate()` ở cả 2 bước, xóa token sau khi dùng, rollback token nếu SMTP lỗi, `hash_equals` khi so sánh. Là đoạn code bảo mật tốt nhất được thêm mới |
| **State machine đơn hàng** | `STATUS_TRANSITIONS` chặn chuyển ngược; `changeStatus()` bọc `DB::transaction` + `lockForUpdate`; từ chối chuyển sang chính trạng thái hiện tại |
| **Ràng buộc hoàn/đổi** | Cộng dồn số lượng đã yêu cầu qua `remainingReturnQuantity()`, lọc sẵn sản phẩm còn hoàn được, `reason_id` phải đang ACTIVE |
| **Migration phòng thủ** | 9/9 migration mới đều kiểm tra `Schema::hasTable()` / `hasColumn()` / `hasIndex()` trước khi thao tác — chạy lại nhiều lần không lỗi |
| **CHECK constraint tồn kho** | `CHECK (quantity >= 0)` ở tầng CSDL — lớp bảo vệ cuối cùng khi cài logic trừ kho |
| **~40 chỉ mục CSDL** | Bao trùm gần hết khuyến nghị tại [09](09-mo-hinh-du-lieu.md) §9.6 |
| **Email bất đồng bộ** | `QueuedRawMail` + `SendRawMailJob`, có fallback gửi trực tiếp khi dispatch lỗi |
| **Validate ảnh base64** | `storeTryOnImage()` kiểm tra regex data-URI, `base64_decode(strict)`, chặn cả file quá nhỏ (<1 KB) lẫn quá lớn (>5 MB) |
| **Đơn giản hóa mô hình kho** | Bỏ `TRANSFER`, bỏ `reserved_quantity`, gộp kho `RETURN` — giảm đáng kể độ phức tạp không dùng đến |
| **Comment nghiệp vụ tiếng Việt** | Hầu hết file được sửa nay có comment giải thích *tại sao*, không chỉ *cái gì* — rất có giá trị cho báo cáo luận văn |

---

## Lộ trình khắc phục (cập nhật)

### Đợt 1 — Bảo mật & chặn triển khai
1. **N-04** Kiểm tra `.env.railway_2047_backup`; nếu có credential thật → xoay vòng + gỡ khỏi lịch sử Git
2. **C-04** Sanitize `posts.content` — lỗ hổng duy nhất khai thác trực tiếp được
3. **N-02** `Cache::forget("users.{id}.role_codes")` khi đổi vai trò/trạng thái
4. **C-01** Bọc migration `2026_07_10_060000`; commit schema CSDL

### Đợt 2 — Đúng đắn nghiệp vụ
5. **C-02** Cho `createSaleOutTransaction()` trừ kho thật (đã có sẵn CHECK `quantity >= 0` đỡ lưng)
6. **H-01** Chặn đánh giá khi chưa mua + UNIQUE index trên `order_item_id`
7. **H-02** Nguyên tử hóa `used_count`
8. **H-08** Đơn COD `DELIVERED` → `payment_status = 'PAID'` + tạo bản ghi `Payment`
9. **H-12** Thống nhất định nghĩa doanh thu; đồng bộ enum sau khi bỏ `LOST_IN_TRANSIT`

### Đợt 3 — Vận hành
10. **H-03** Chuyển toàn bộ upload (gồm cả ảnh thử kính) sang lưu trữ bền vững
11. **N-01** Sửa key cache header; **N-05** sửa mojibake; **N-06** quyết định về trang sửa hồ sơ
12. **H-06** Bỏ secret mặc định; **H-13**, **H-14** bảo vệ tài khoản admin
13. **N-07** Nối `tryOnModelCheck` vào JS; bỏ `Http::withoutVerifying()`
14. **H-05** Sửa `view_count`

### Đợt 4 — Chất lượng
15. **N-03** Sửa `UserFactory` + `DatabaseSeeder`, rồi **viết test cho toàn bộ logic mới**
    (state machine, luồng hủy 2 bước, SALE_OUT, ràng buộc hoàn/đổi) — đây là ~1.500 dòng
    nghiệp vụ đang không có lưới an toàn nào
16. Gộp logic trùng lặp (đường dẫn ảnh, công thức giá)
17. Xóa scaffolding chết + 7 file `RECOVERY_*.csv` khỏi repo
18. Thêm CSP; nhật ký thay đổi trạng thái đơn hàng
