# 11 — Kế hoạch Bảo trì & Triển khai

Tài liệu này là **kế hoạch hành động** tiếp nối [10 — Kết quả Audit](10-ket-qua-audit.md):
đưa hệ thống từ trạng thái hiện tại lên môi trường chạy thật một cách an toàn, rồi duy trì nó.

Đối tượng đọc: người trực tiếp code và deploy dự án.

---

## 11.0. Kết luận sẵn sàng triển khai: 🔴 **CHƯA SẴN SÀNG**

Có **4 vấn đề chặn cứng**. Deploy lên môi trường mới ngay bây giờ sẽ hỏng theo đúng thứ tự sau:

| # | Vấn đề | Hậu quả tức thì |
|---|---|---|
| **B-1** | `migrate --force` lỗi trên CSDL trống ([C-01](10-ket-qua-audit.md#c-01)) | Deploy dừng ở `preDeployCommand`, container không lên |
| **B-2** | **Không có queue worker** (mới phát hiện, §11.1) | 3 loại email đơn hàng **không bao giờ được gửi**; luồng hủy đơn kẹt vĩnh viễn |
| **B-3** | Ảnh upload nằm trên đĩa ephemeral ([H-03](10-ket-qua-audit.md)) | Mất toàn bộ ảnh sản phẩm/bài viết/thử kính sau mỗi lần deploy |
| **B-4** | `.env.railway_2047_backup` trong repo ([N-04](10-ket-qua-audit.md)) | Rò rỉ bí mật nếu file chứa credential thật |

§11.2 là checklist gỡ từng cái. Ước tính **2–3 ngày công** cho toàn bộ P0.

---

## 11.1. 🔴 PHÁT HIỆN MỚI: Queue worker không tồn tại

> Đây là phát hiện của lần rà soát này, chưa có trong tài liệu 10. Mức độ: **chặn triển khai**.

### Vấn đề

Đợt cập nhật `ee3dfa5` chuyển email đơn hàng sang hàng đợi:

```php
class SendRawMailJob implements ShouldQueue   // ← job bất đồng bộ
```
```
QUEUE_CONNECTION=database                     // ← job ghi vào bảng `jobs`
```

Nhưng rà soát toàn bộ `railway.json`, `railway/init-app.sh`, `Procfile`, `Dockerfile`,
`nixpacks.toml`: **không có tiến trình nào chạy `php artisan queue:work`.**
Lệnh `queue:listen` chỉ xuất hiện đúng một lần — trong script `composer dev` dành cho máy local.

### Vì sao nó không tự lộ ra

`QueuedRawMail::raw()` có cơ chế fallback:
```php
try {
    SendRawMailJob::dispatch($message->to, $message->subject, $body);
    return;
} catch (\Throwable $exception) {
    Log::warning(...);          // ← chỉ chạy khi dispatch NÉM LỖI
}
LaravelMail::raw(...);          // ← gửi trực tiếp
```

Trên Railway, `dispatch()` **thành công** — nó chỉ ghi một dòng vào bảng `jobs` rồi trả về.
Không có exception → **fallback không bao giờ kích hoạt** → job nằm trong bảng `jobs` vĩnh viễn.

### Hậu quả

| Email | Cách gửi | Trạng thái trên production |
|---|---|---|
| Xác thực đăng ký (`AuthController`) | `Mail::raw()` đồng bộ | ✅ **Vẫn gửi được** |
| Đặt lại mật khẩu (`AuthController`) | `Mail::raw()` đồng bộ | ✅ **Vẫn gửi được** |
| Xác nhận đặt hàng (`OrderConfirmationEmailService`) | `QueuedRawMail` | 🔴 **Không bao giờ gửi** |
| **Xác nhận hủy đơn** (`OrderCancellationService`) | `QueuedRawMail` | 🔴 **Không bao giờ gửi** |
| Hóa đơn (`OrderInvoiceEmailService`) | `QueuedRawMail` | 🔴 **Không bao giờ gửi** |

Nghiêm trọng nhất là **luồng hủy đơn 2 bước**: `requestCancellation()` trả về `true` sau khi
dispatch thành công, admin thấy thông báo *"Đã gửi email xác nhận hủy đơn cho khách hàng"* —
nhưng khách không bao giờ nhận được email. Đơn **kẹt vĩnh viễn** ở trạng thái đã lưu token mà
chưa hủy, và **không có đường nào hủy được nữa** (admin bấm lại chỉ sinh token mới).

Chức năng hủy đơn — vừa được xây rất công phu — **hoàn toàn không hoạt động trên production**.

### Hai cách khắc phục

**Cách A — Thêm worker service (khuyến nghị, đúng kiến trúc):**

Trên Railway, tạo **service thứ hai** cùng repo, cùng biến môi trường, đổi Start Command:
```bash
php artisan queue:work database --tries=3 --timeout=60 --sleep=3 --max-time=3600
```
`--max-time=3600` để worker tự khởi động lại mỗi giờ, tránh rò rỉ bộ nhớ. Service này **không
cần healthcheck** và **không chạy** `preDeployCommand`.

**Cách B — Chuyển về đồng bộ (giải pháp tạm, 1 dòng):**

Nếu chưa muốn thêm service, đặt biến môi trường:
```
QUEUE_CONNECTION=sync
```
Job sẽ chạy ngay trong request. Email gửi được, nhưng request đặt hàng phải chờ SMTP Gmail
(~1–3 giây) — mất đúng lợi ích mà đợt cập nhật hướng tới.

> **Bất kể chọn cách nào**, nên sửa `QueuedRawMail` để fallback đáng tin hơn: kiểm tra
> `config('queue.default') === 'sync'` hoặc bắt cả trường hợp không có worker, thay vì chỉ dựa
> vào exception của `dispatch()`.

### Kiểm chứng sau khi sửa

```bash
php artisan tinker --execute="App\Support\QueuedRawMail::raw('test', fn(\$m) => \$m->to('ban@gmail.com')->subject('test'));"
php artisan queue:monitor database        # số job đang chờ, phải về 0
# hoặc:
php artisan tinker --execute="echo DB::table('jobs')->count();"   # phải là 0 sau vài giây
```

---

## 11.2. P0 — Checklist gỡ chặn triển khai (2–3 ngày)

Làm **đúng thứ tự**, mỗi mục có tiêu chí nghiệm thu rõ ràng.

### ☐ P0-1 · Kiểm tra & xử lý `.env.railway_2047_backup` (30 phút)

```bash
cat .env.railway_2047_backup          # đọc trước khi làm gì
```

Nếu chứa `APP_KEY`, `DB_PASSWORD`, `MAIL_PASSWORD`, hoặc `VNPAY_HASH_SECRET` **thật**:

```bash
# 1. Coi như đã lộ — xoay vòng TOÀN BỘ:
php artisan key:generate            # APP_KEY mới (lưu ý: làm mất session + dữ liệu đã mã hóa)
#    - đổi mật khẩu CSDL trên Railway
#    - thu hồi Gmail App Password cũ, tạo cái mới
#    - đổi VNPAY_HASH_SECRET trên cổng VNPay (nếu là merchant thật)

# 2. Gỡ khỏi lịch sử Git (git rm KHÔNG đủ — file vẫn nằm trong lịch sử):
pip install git-filter-repo
git filter-repo --path .env.railway_2047_backup --invert-paths --force
git push --force-with-lease origin main
```

Nếu chỉ chứa giá trị mẫu: `git rm .env.railway_2047_backup && git commit`.

Sửa `.gitignore` trong cả hai trường hợp:
```diff
-.env.backup
-.env.production
+.env*
+!.env.example
```

Dọn luôn 8 file không thuộc mã nguồn:
```bash
git rm RECOVERY_*.csv RECOVERY_NOTES.md
```

**Nghiệm thu:** `git log --all --full-history -- .env.railway_2047_backup` không ra kết quả.

---

### ☐ P0-2 · Đưa schema CSDL vào repo (nửa ngày)

Đây là gốc rễ của B-1 và là điều kiện cần cho mọi việc còn lại (test, môi trường staging).

```bash
# Từ CSDL đang chạy đúng:
mysqldump --no-data --skip-comments --single-transaction \
          -u USER -p TEN_DB > database/schema.sql

# Dữ liệu nền bắt buộc (roles, return_reasons, home_layouts, colors, lens_sizes,
# frame_shapes, frame_materials) — hệ thống không chạy được nếu thiếu:
mysqldump --no-create-info --single-transaction -u USER -p TEN_DB \
          roles return_reasons home_layouts colors lens_sizes frame_shapes frame_materials \
          > database/seed-data.sql
```

Commit cả hai, rồi **bọc migration cũ đang gây lỗi**:

```php
// database/migrations/2026_07_10_060000_keep_only_cod_and_vnpay_payment_methods.php
public function up(): void
{
    if (! Schema::hasTable('payments') || ! Schema::hasTable('orders')) {
        return;                                  // ← giống 9 migration mới
    }
    DB::statement("ALTER TABLE payments MODIFY method ENUM('COD','VNPAY') NOT NULL");
    DB::statement("ALTER TABLE orders MODIFY payment_method ENUM('COD','VNPAY') NOT NULL DEFAULT 'COD'");
}

public function down(): void
{
    // Khong the khoi phuc danh sach phuong thuc thanh toan cu.
}
```
(`down()` hiện đang **giống hệt `up()`** — không rollback gì cả.)

Cập nhật `README.md` mục cài đặt:
```bash
mysql -u USER -p TEN_DB < database/schema.sql
mysql -u USER -p TEN_DB < database/seed-data.sql
php artisan migrate --force
```

**Nghiệm thu:** trên CSDL trống hoàn toàn, chạy đúng 3 lệnh trên → không lỗi → `/` và
`/admin/dang-nhap` mở được.

> **Dài hạn:** chuyển `schema.sql` thành migration thật. Nhưng đừng để việc đó chặn P0 —
> có `schema.sql` trong repo đã giải quyết 90% vấn đề tái lập môi trường.

---

### ☐ P0-3 · Queue worker (1 giờ)

Theo §11.1. Nếu chọn Cách A, thêm vào `railway/init-app.sh` một dòng cảnh báo phòng khi quên:

```sh
if [ "${QUEUE_CONNECTION:-database}" != "sync" ]; then
    echo "LUU Y: QUEUE_CONNECTION=${QUEUE_CONNECTION}. Phai co service chay 'php artisan queue:work'."
fi
```

**Nghiệm thu:** đặt một đơn COD thật → email xác nhận về hộp thư trong vòng 30 giây →
`DB::table('jobs')->count()` trở về 0.

---

### ☐ P0-4 · Lưu trữ ảnh bền vững (nửa ngày)

Hiện có **4 nơi ghi ảnh khác nhau**, 3 trong số đó ghi thẳng vào `public/`:

| File | Hiện tại |
|---|---|
| `ProductAdminController::storeUpload()` | `$file->move(public_path('upload/anh_san_pham'))` |
| `PostAdminController::storeUpload()` | `$file->move(public_path('upload/BaiViet'))` |
| `ProductController::storeTryOnImage()` | `File::put(public_path('upload/tryons/...'))` |
| `BannerAdminController::storeUpload()` | `Storage::disk('public')` ✅ đúng API |

**Giải pháp nhanh (Railway Volume, ~1 giờ):**
1. Railway → service → Volumes → mount vào `/app/storage/app/public`
2. Sửa 3 hàm trên dùng chung một helper:
```php
// Gợi ý: app/Support/ImageStorage.php
public static function put(UploadedFile $file, string $folder): string
{
    return $file->store('upload/' . $folder, 'public');   // trả 'upload/{folder}/{hash}.ext'
}
```
3. `php artisan storage:link` đã có sẵn trong `init-app.sh` ✅
4. Copy ảnh cũ từ `public/upload/` vào volume một lần

**Giải pháp đúng (S3/Cloudflare R2, ~nửa ngày):** đổi `FILESYSTEM_DISK=s3`, dùng
`Storage::disk('s3')->url()` — chịu được nhiều instance, có CDN.

Đồng thời **đơn giản hóa `Banner::getImageSrcAttribute()`**: 50/74 dòng của model đó chỉ để
đoán đường dẫn bằng `file_exists()`. Khi mọi ảnh dùng chung một chiến lược, hàm này rút còn ~5 dòng.

**Nghiệm thu:** upload 1 ảnh sản phẩm → deploy lại → ảnh vẫn hiển thị.

---

## 11.3. Quy trình triển khai chuẩn (runbook)

### Trước khi deploy

```bash
# 1. Sao lưu CSDL — LUÔN LUÔN, không có ngoại lệ
mysqldump --single-transaction --routines -u USER -p TEN_DB \
  | gzip > backup-$(date +%Y%m%d-%H%M%S).sql.gz

# 2. Chạy migration ở chế độ thử trên bản sao CSDL production
php artisan migrate --pretend

# 3. Kiểm tra cấu hình
php artisan about
```

### Biến môi trường bắt buộc trên Railway

| Biến | Giá trị | Ghi chú |
|---|---|---|
| `APP_ENV` | `production` | Bật `URL::forceScheme('https')` |
| `APP_DEBUG` | `false` | 🔴 **Bắt buộc** — `true` sẽ lộ stack trace + biến môi trường |
| `APP_KEY` | `base64:...` | Sinh 1 lần, **không đổi** (đổi = mất session, mất dữ liệu mã hóa) |
| `APP_URL` | `https://...` | Nếu bỏ trống, `init-app.sh` tự suy từ `RAILWAY_PUBLIC_DOMAIN` |
| `DB_*` | | MySQL — **không dùng SQLite**, nhiều truy vấn là MySQL-only |
| `QUEUE_CONNECTION` | `database` + worker, **hoặc** `sync` | Xem §11.1 |
| `CACHE_STORE` | `database` | 🔴 **Không được để `array`** — draft VNPay phụ thuộc cache ([H-07](10-ket-qua-audit.md)) |
| `SESSION_DRIVER` | `database` | |
| `MAIL_*` | Gmail App Password | Không dùng mật khẩu Gmail thường |
| `VNPAY_TMN_CODE` / `VNPAY_HASH_SECRET` | **giá trị thật** | Nếu quên, hệ thống **im lặng** chạy sandbox ([H-06](10-ket-qua-audit.md)) |
| `VNPAY_ENVIRONMENT` | `sandbox` \| `live` | |

### Sau khi deploy — smoke test 10 phút

| # | Kịch bản | Kỳ vọng |
|---|---|---|
| 1 | Mở `/` | Trang chủ có banner, sản phẩm; menu header có 3 mục kính |
| 2 | Mở `/up` | `200 OK` (healthcheck của Railway) |
| 3 | Đăng ký tài khoản mới | **Nhận được email xác thực** |
| 4 | `/san-pham` → lọc theo danh mục + khoảng giá | Kết quả đúng, phân trang giữ bộ lọc |
| 5 | Thêm giỏ → `/thanh-toan` → đặt COD | Đơn tạo thành công + **nhận email xác nhận** ← kiểm tra §11.1 |
| 6 | Đặt đơn VNPay → thanh toán sandbox | Quay về đúng đơn, `payment_status = PAID` |
| 7 | `/admin/dang-nhap` với tài khoản ADMIN | Vào được dashboard, số liệu hiển thị |
| 8 | Admin: đơn `PENDING` → `CONFIRMED` → `DELIVERING` | Chỉ hiện lựa chọn hợp lệ; sinh phiếu `SALE_OUT` |
| 9 | Admin: bấm hủy một đơn `PENDING` | **Khách nhận được email** → bấm link → đơn `CANCELLED` |
| 10 | Admin: lập phiếu nhập kho | Tồn kho tăng đúng |
| 11 | `/thu-kinh` | Camera bật được (cần HTTPS) |

Mục **5** và **9** là hai mục dễ hỏng nhất — chúng phụ thuộc trực tiếp vào §11.1.

### Rollback

```bash
# Railway giữ lịch sử deploy: Deployments → chọn bản trước → Redeploy
# Nếu migration đã đổi schema, phải phục hồi CSDL:
gunzip < backup-YYYYMMDD-HHMMSS.sql.gz | mysql -u USER -p TEN_DB
```

> ⚠️ Migration `2026_08_04_153000` (gộp loại phiếu kho) và `2026_08_04_152000` (xóa
> `reserved_quantity`) **không rollback được bằng `migrate:rollback`** — `down()` không phục hồi
> dữ liệu. Bắt buộc phải có bản sao lưu.

---

## 11.4. Lộ trình sửa lỗi

### Sprint 1 — Bảo mật (1 tuần)

| Việc | Mã | Ước tính |
|---|---|---|
| Sanitize `posts.content` (HTMLPurifier hoặc allowlist có sẵn) | [C-04](10-ket-qua-audit.md#c-04) | 3h |
| `Cache::forget("users.{id}.role_codes")` khi đổi vai trò/trạng thái | [N-02](10-ket-qua-audit.md) | 1h |
| Chặn ADMIN tự khóa mình / hạ cấp admin cuối cùng | [H-14](10-ket-qua-audit.md) | 2h |
| Bỏ giá trị mặc định VNPay trong `config/vnpay.php` + `init-app.sh` | [H-06](10-ket-qua-audit.md) | 30ph |
| Bỏ `Http::withoutVerifying()` | [N-07](10-ket-qua-audit.md) | 15ph |
| Thêm `Content-Security-Policy` vào `SecurityHeaders` | [M-04](10-ket-qua-audit.md) | 2h |
| Bỏ `reset_url` khỏi `Log::error` | [M-02](10-ket-qua-audit.md) | 15ph |
| Throttle + log cho `/vnpay/ipn`, `/vnpay/return` | [M-03](10-ket-qua-audit.md) | 1h |

> CSP nên bắt đầu ở chế độ `Content-Security-Policy-Report-Only` khoảng 1 tuần — giao diện
> hiện dùng nhiều inline script/style, bật thẳng sẽ vỡ.

### Sprint 2 — Đúng đắn nghiệp vụ (1–2 tuần)

| Việc | Mã | Ước tính |
|---|---|---|
| **Trừ tồn kho thật** trong `createSaleOutTransaction()` + hoàn kho khi hủy/hoàn hàng | [C-02](10-ket-qua-audit.md#c-02) | 2 ngày |
| Log + ghi nhận khi VNPay thu tiền nhưng tạo đơn thất bại | [C-03](10-ket-qua-audit.md#c-03) | 4h |
| Chặn đánh giá khi chưa mua + UNIQUE index `order_item_id` | [H-01](10-ket-qua-audit.md) | 2h |
| Nguyên tử hóa `used_count` của khuyến mãi | [H-02](10-ket-qua-audit.md) | 2h |
| Đơn COD `DELIVERED` → `payment_status = PAID` + tạo `Payment` | [H-08](10-ket-qua-audit.md) | 2h |
| Thống nhất định nghĩa doanh thu ở 4 nơi | [H-12](10-ket-qua-audit.md) | 4h |
| Thời hạn đổi trả N ngày kể từ `delivered_at` | [H-10](10-ket-qua-audit.md) | 2h |
| Bổ sung 6 ràng buộc UNIQUE ([09](09-mo-hinh-du-lieu.md) §9.6) | | 2h |

**C-02 là việc lớn nhất còn lại** — nên làm trước tiên trong sprint này, vì mọi số liệu tồn kho
trên dashboard, báo cáo và trang sản phẩm đều phụ thuộc nó.

### Sprint 3 — Vận hành & chất lượng (1–2 tuần)

| Việc | Mã |
|---|---|
| Sửa key cache header (`.v2`) | [N-01](10-ket-qua-audit.md) |
| Sửa mojibake `bootstrap/app.php` | [N-05](10-ket-qua-audit.md) |
| Quyết định về trang sửa hồ sơ (khôi phục hay xóa hẳn code chết) | [N-06](10-ket-qua-audit.md) |
| Nối `tryOnModelCheck` vào `tryon-ai.js` | [N-07](10-ket-qua-audit.md) |
| `email_verified_at` cho tài khoản admin tạo | [H-13](10-ket-qua-audit.md) |
| Sửa `view_count` (session + `afterResponse`) | [H-05](10-ket-qua-audit.md) |
| Đồng bộ hằng `MAX_CART_QUANTITY` (10 vs 20 ở `VnPayController`) | — |
| Email xác nhận đơn cho nhánh VNPay | — |
| Gom `cities()` (đang có 2 bản sao) vào `config/` | — |
| Sửa `UserFactory` + `DatabaseSeeder`, viết test | [N-03](10-ket-qua-audit.md) |

### Bộ test tối thiểu cần có

Hiện có **0 test** cho nghiệp vụ. Ưu tiên theo mức rủi ro:

```
tests/Feature/
├── CheckoutTest.php          # đặt COD, giới hạn 10 sản phẩm, giá tính lại từ DB, mã giảm giá
├── VnPayTest.php             # chữ ký hợp lệ/sai, số tiền lệch, IPN gọi 2 lần (idempotency)
├── InventoryTest.php         # trừ kho khi DELIVERING, hoàn kho khi hủy, chặn bán vượt kho
├── OrderStatusTest.php       # state machine: chuyển hợp lệ / bị chặn
├── OrderCancellationTest.php # token sai, token hết hạn, dùng lại token, đúng luồng
├── ReturnRequestTest.php     # cộng dồn số lượng, trạng thái đơn không hợp lệ
└── AdminAccessTest.php       # STAFF bị chặn ở route ADMIN, cache vai trò bị xóa đúng lúc
```

Chạy trên MySQL riêng (không phải SQLite — code dùng cú pháp MySQL-only):
```xml
<!-- phpunit.xml -->
<env name="DB_CONNECTION" value="mysql"/>
<env name="DB_DATABASE" value="luanvan_test"/>
<env name="QUEUE_CONNECTION" value="sync"/>
```

---

## 11.5. Bảo trì định kỳ

### Hằng ngày (5 phút)

```bash
php artisan queue:monitor database          # job tồn đọng — phải gần 0
php artisan tinker --execute="echo DB::table('failed_jobs')->count();"
tail -100 storage/logs/laravel.log | grep -i "error\|critical"
```

Kiểm tra nhanh trên admin: có đơn `PENDING` quá 24h không.

### Hằng tuần (30 phút)

- [ ] **Kiểm tra bản sao lưu CSDL khôi phục được** — sao lưu chưa test không phải là sao lưu
- [ ] Đối soát: tổng `SUM(quantity)` trong `inventories` so với tồn thực tế
- [ ] Dọn job hỏng: `php artisan queue:prune-failed --hours=168`
- [ ] Dọn ảnh mồ côi trong `upload/tryons/` (chưa có chức năng tự dọn)
- [ ] Xem log VNPay: có giao dịch nào chữ ký sai / số tiền lệch không

### Hằng tháng (2 giờ)

- [ ] `composer audit` + `npm audit` — kiểm tra lỗ hổng thư viện
- [ ] `composer outdated --direct`
- [ ] Xoay vòng Gmail App Password
- [ ] Rà bảng `sessions` và `cache` — xóa bản ghi hết hạn
- [ ] Xem lại [10 — Audit](10-ket-qua-audit.md): mục nào đã đóng, mục nào phát sinh mới
- [ ] Kiểm tra dung lượng đĩa `public/upload/` và volume

### Trước mỗi kỳ cao điểm (khuyến mãi, bảo vệ luận văn)

- [ ] Sao lưu CSDL + ảnh
- [ ] Chạy đủ 11 mục smoke test (§11.3)
- [ ] Kiểm tra `APP_DEBUG=false`
- [ ] Xác nhận worker đang chạy
- [ ] Chuẩn bị sẵn lệnh rollback

---

## 11.6. Giám sát & cảnh báo

Hiện tại **không có giám sát nào** ngoài healthcheck `/up` của Railway.

### Mức tối thiểu (miễn phí, ~1 giờ cài)

| Cần theo dõi | Cách làm |
|---|---|
| Ứng dụng sống | Railway healthcheck `/up` (đã có ✅) |
| Lỗi ứng dụng | Sentry gói free — `composer require sentry/sentry-laravel` |
| Job tồn đọng | Cron gọi `queue:monitor database --max=100` → cảnh báo |
| Uptime từ ngoài | UptimeRobot (miễn phí) ping `/up` mỗi 5 phút |
| Sao lưu CSDL | `spatie/laravel-backup` + cron hằng ngày |

### Những chỗ nên bổ sung log (hiện đang im lặng)

```php
Log::critical(...)   // VNPay thu tiền nhưng không tạo được đơn        ← C-03
Log::warning(...)    // Chữ ký VNPay sai / số tiền lệch                ← M-03
Log::info(...)       // Đổi trạng thái đơn: ai, từ gì sang gì, lúc nào ← M-14
Log::warning(...)    // Đổi vai trò / khóa tài khoản                   ← H-14
```

Nhật ký thay đổi trạng thái đơn hàng ([M-14](10-ket-qua-audit.md)) nên là bảng riêng
`order_status_logs (order_id, from_status, to_status, changed_by, note, created_at)` —
với hệ thống thương mại, mất khả năng truy vết là rủi ro thật khi có tranh chấp với khách.

---

## 11.7. Runbook xử lý sự cố

### "Khách không nhận được email xác nhận đơn hàng"

```bash
DB::table('jobs')->count()          # > 0 và tăng dần → worker chết. Xem §11.1
DB::table('failed_jobs')->latest()->first()   # có bản ghi → xem cột exception
```
Nguyên nhân thường gặp: worker không chạy (§11.1) → sai Gmail App Password →
Gmail chặn vì quá hạn mức gửi.

### "Đơn hàng kẹt ở trạng thái chờ xác nhận hủy"

Do email hủy không tới (§11.1). Xử lý thủ công:
```php
// Lấy lại link xác nhận cho một đơn cụ thể:
$order = Order::find($id);
// Token thật KHÔNG lấy lại được (chỉ lưu hash). Phải sinh yêu cầu mới:
app(App\Services\OrderCancellationService::class)->requestCancellation($order, 'Lý do');
// Sau khi đã sửa xong §11.1 thì email mới đến được.
```

### "Thanh toán VNPay thành công nhưng không có đơn hàng"

Đây là [C-03](10-ket-qua-audit.md#c-03) — hiện **không có log nào**. Cách tra:
```sql
SELECT * FROM payments WHERE transaction_no = 'MA_GD_VNPAY';   -- thường trống
SELECT * FROM orders WHERE order_code = 'ORD...';              -- thường trống
```
Nếu cả hai trống mà khách có biên lai VNPay → phải tạo đơn thủ công và đối soát với cổng VNPay.
**Sửa C-03 trước khi lên VNPay live** để tình huống này có dấu vết.

### "Menu header không cập nhật sau khi sửa danh mục"

[N-01](10-ket-qua-audit.md) — key cache sai. Tạm thời:
```bash
php artisan cache:clear
```

### "Admin bị hạ cấp vẫn vào được khu quản trị"

[N-02](10-ket-qua-audit.md) — cache vai trò 5 phút. Tạm thời:
```bash
php artisan cache:forget "users.{ID}.role_codes"
```
Cần khóa gấp thì đặt `users.status = 'LOCKED'` — kiểm tra này **không qua cache**, có hiệu lực ngay.

### "Ảnh sản phẩm biến mất sau khi deploy"

[H-03](10-ket-qua-audit.md) / P0-4. Không khôi phục được nếu chưa có volume/S3 —
đây là lý do P0-4 phải làm **trước** khi có dữ liệu thật.

---

## 11.8. Chuẩn bị bảo vệ luận văn

### Nên trình bày (điểm mạnh thật của hệ thống)

- **Tích hợp VNPay** — xác minh HMAC-SHA512 + `hash_equals`, so khớp số tiền ở 3 lớp,
  idempotency bằng `lockForUpdate` chống race giữa Return và IPN
- **Luồng hủy đơn 2 bước** — token băm SHA-256, signed URL, khóa dòng, dùng một lần
- **Đánh giá hư hỏng kính 8 bộ phận × 5 mức** — mô hình hóa nghiệp vụ đặc thù ngành kính mắt
- **State machine đơn hàng** — chặn ở cả tầng UI lẫn tầng logic
- **Thử kính AI** bằng Jeeliz VTO + lưu kết quả cho admin xem lại
- **Phòng thủ nhiều lớp:** `ValidateRequestInput`, `SecurityHeaders`, 8 rate limiter phân tầng,
  whitelist chặt, không có SQL injection trong toàn bộ mã nguồn

### Nên chủ động nêu là hạn chế (thay vì để hội đồng phát hiện)

- Tồn kho chưa liên thông đầy đủ với bán hàng — đã có chứng từ `SALE_OUT`, còn thiếu bước trừ kho
- Thử kính 3D phụ thuộc thư viện model của Jeeliz, chưa gắn được model riêng cho sản phẩm
- Chưa có bộ kiểm thử tự động
- Chưa có chức năng hoàn tiền (chỉ ghi nhận yêu cầu hoàn hàng)

Nêu trước một hạn chế kèm hướng khắc phục luôn tốt hơn là bị hỏi mà chưa chuẩn bị.

### Checklist trước buổi bảo vệ

- [ ] Xong toàn bộ P0 (§11.2)
- [ ] 11 mục smoke test (§11.3) đều xanh
- [ ] Dữ liệu mẫu đủ đẹp: ≥20 sản phẩm có ảnh, ≥10 đơn ở đủ các trạng thái, vài yêu cầu hoàn/đổi
- [ ] Chuẩn bị sẵn tài khoản demo (1 ADMIN, 1 STAFF, 1 khách) — mật khẩu ghi ra giấy
- [ ] Sao lưu CSDL ngay trước buổi bảo vệ
- [ ] Có phương án dự phòng: video quay màn hình các luồng chính, phòng khi mạng hỏng
- [ ] Ít nhất một SKU thử kính **thật sự chạy được** (dùng SKU demo của Jeeliz)

---

## 11.9. Bảng tổng hợp ưu tiên

| Ưu tiên | Việc | Chặn cái gì | Công |
|---|---|---|---|
| 🔴 P0 | Kiểm tra `.env.railway_2047_backup` | Bảo mật | 30ph |
| 🔴 P0 | Schema CSDL vào repo + bọc migration cũ | Deploy | 4h |
| 🔴 P0 | Queue worker | **Toàn bộ email đơn hàng** | 1h |
| 🔴 P0 | Lưu trữ ảnh bền vững | Mất dữ liệu | 4h |
| 🟠 P1 | Sanitize `posts.content` | Bảo mật (XSS) | 3h |
| 🟠 P1 | Xóa cache vai trò khi đổi quyền | Bảo mật | 1h |
| 🟠 P1 | Trừ tồn kho thật | Đúng đắn nghiệp vụ | 2 ngày |
| 🟠 P1 | Log khi VNPay thu tiền mà không tạo được đơn | Tiền bạc | 4h |
| 🟡 P2 | H-01, H-02, H-08, H-12, H-13, H-14 | Đúng đắn dữ liệu | ~2 ngày |
| 🟡 P2 | Sentry + sao lưu tự động | Vận hành | 4h |
| 🔵 P3 | Bộ test tối thiểu | Chất lượng dài hạn | 3–5 ngày |
| 🔵 P3 | Dọn code chết, gom logic trùng lặp | Bảo trì | 2 ngày |

**Tổng P0: ~2 ngày công** → hệ thống deploy được an toàn.
**Tổng P0 + P1: ~5 ngày công** → hệ thống chạy thật được với dữ liệu khách hàng.
