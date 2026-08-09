# 01 — Tổng quan & Kiến trúc

## 1.1. Hệ thống này là gì

Website thương mại điện tử bán **kính mắt** (kính mát, kính thời trang, gọng kính), gồm
hai khu vực:

- **Client (tiếng Việt, URL tiếng Việt):** trang chủ, danh sách/chi tiết sản phẩm, thử kính
  AI bằng webcam, giỏ hàng, thanh toán COD/VNPay, tài khoản, đơn hàng, yêu cầu hoàn/đổi, blog.
- **Admin (`/admin`):** dashboard, quản lý sản phẩm/danh mục/đơn hàng/hoàn đổi/kho/bài viết/
  bình luận/banner/bố cục trang chủ/thành viên/thương hiệu/khuyến mãi, và 5 màn hình báo cáo.

Đây là dự án luận văn, được **port từ một hệ thống PHP thuần cũ** sang Laravel — dấu vết rõ
nhất là hai controller `ClientRouteAliasController` và `AdminRouteAliasController` chuyên
redirect ~60 URL kiểu cũ (`?url=chitietsanpham&id_sp=5`) sang route Laravel mới.

## 1.2. Stack kỹ thuật

| Thành phần | Phiên bản / lựa chọn |
|---|---|
| PHP | `^8.4` (khai báo trong `composer.json`) |
| Framework | Laravel `^13.8` (cấu trúc bootstrap mới, không có `app/Http/Kernel.php`) |
| Auth scaffolding | Laravel Jetstream `^5.5` + Fortify + Livewire `^3.6` — **đã bị vô hiệu hóa** (xem §1.6) |
| API token | Laravel Sanctum `^4.0` (chỉ dùng cho route `/api/user`) |
| CSDL | MySQL (bắt buộc — `ext-pdo_mysql` là require, nhiều truy vấn dùng cú pháp MySQL-only) |
| Frontend | Blade + Bootstrap 4 + jQuery (theme cũ trong `public/css`, `public/js`) |
| Build tool | Vite `^8` + TailwindCSS `^3.4` — **chỉ phục vụ các view Jetstream không dùng đến** |
| Thử kính 3D | Jeeliz Glasses VTO Widget (`public/vendor/jeelizGlassesVTOWidget`) |
| Cổng thanh toán | VNPay (sandbox), tự viết trong `app/Services/VnPayService.php` |
| Deploy | Railway (`railway.json` + `railway/init-app.sh`) |

> **Mâu thuẫn frontend:** giao diện thật chạy bằng Bootstrap/jQuery nạp tĩnh từ `public/`,
> còn Vite/Tailwind chỉ build `resources/css/app.css` + `resources/js/app.js` cho các view
> Jetstream (`profile/`, `api/`, `dashboard.blade.php`) mà người dùng không bao giờ truy cập.
> `npm run build` vẫn nằm trong build command của Railway.

## 1.3. Luồng khởi động (`bootstrap/app.php`)

```
Application::configure()
  ├─ withRouting(web: routes/web.php, api: routes/api.php, health: '/up')
  ├─ withMiddleware()
  │    ├─ web(append: [ValidateRequestInput, SecurityHeaders])
  │    ├─ validateCsrfTokens(except: ['vnpay/ipn'])
  │    └─ alias(['admin' => EnsureAdmin])
  └─ withExceptions()
       ├─ shouldRenderJsonWhen('api/*')
       └─ render(InvalidSignatureException)          ← MỚI
            ├─ route 'orders.cancel-confirm.*' → về trang chủ kèm thông báo
            └─ còn lại                        → về trang đăng nhập kèm thông báo
```

Xử lý `InvalidSignatureException` là bổ sung mới: link ký hết hạn (xác nhận hủy đơn, xác thực
email) nay hiển thị thông báo tiếng Việt thay vì trang lỗi 403 trần trụi ✅.

> 🔴 **Nhưng 2 chuỗi thông báo này bị lỗi mã hóa (mojibake)** — UTF-8 bị mã hóa hai lần:
> `'LiÃªn káº¿t xÃ¡c nháº­n há»§y Ä‘Æ¡n...'`. Người dùng sẽ thấy đúng chuỗi rác này.
> Đây là **file duy nhất** trong dự án bị lỗi này. Xem [10](10-ket-qua-audit.md) mục **N-05**.

`bootstrap/providers.php` đăng ký 3 provider: `AppServiceProvider`, `FortifyServiceProvider`,
`JetstreamServiceProvider`.

**Ghi chú:** `routes/admin.php` không được nạp qua `withRouting` mà được `require` ở **cuối**
`routes/web.php`. Hệ quả: route catch-all của client
(`Route::get('/{oldRoute}')`, dòng 86 của `web.php`) được đăng ký **trước** toàn bộ route admin.
May mắn là ràng buộc `->where('oldRoute', 'trang-chu|cua-hang|...')` không khớp `admin`, nên
hiện tại không xung đột — nhưng đây là thứ tự mong manh, thêm một alias tên `admin` là vỡ.

## 1.4. Bản đồ source → module

| File | Module | Tài liệu |
|---|---|---|
| `HomeController` | Trang chủ | [08](08-module-noi-dung.md) |
| `ProductController` | Sản phẩm + thử kính | [03](03-module-san-pham.md) |
| `BlogController`, `PageController` | Nội dung tĩnh | [08](08-module-noi-dung.md) |
| `AuthController` | Xác thực client | [02](02-module-xac-thuc-phan-quyen.md) |
| `AccountController` | Hồ sơ, địa chỉ, đơn của tôi | [02](02-module-xac-thuc-phan-quyen.md) |
| `CartController`, `CheckoutController` | Giỏ hàng, đặt hàng | [04](04-module-gio-hang-thanh-toan.md) |
| `VnPayController`, `Services/VnPayService` | Thanh toán VNPay | [04](04-module-gio-hang-thanh-toan.md) |
| `ReturnRequestController` | Hoàn/đổi (khách) | [05](05-module-don-hang-hoan-doi.md) |
| `ClientRouteAliasController` | Tương thích URL cũ | mục §1.7 |
| `Admin/AdminAuthController`, `Middleware/EnsureAdmin` | Đăng nhập & phân quyền admin | [02](02-module-xac-thuc-phan-quyen.md) |
| `Admin/DashboardController`, `Admin/ReportAdminController` | Dashboard & báo cáo | [07](07-module-quan-tri-bao-cao.md) |
| `Admin/ProductAdminController`, `Admin/CategoryAdminController` | CRUD sản phẩm/danh mục | [03](03-module-san-pham.md) |
| `Admin/OrderAdminController`, `Admin/ReturnAdminController` | Xử lý đơn & hoàn đổi | [05](05-module-don-hang-hoan-doi.md) |
| `Admin/WarehouseAdminController` | Kho & phiếu nhập/xuất | [06](06-module-kho-hang.md) |
| `Admin/CustomerAdminController`, `Admin/BusinessAdminController` | Thành viên, thương hiệu, KM, kho | [07](07-module-quan-tri-bao-cao.md) |
| `Admin/PostAdminController`, `Admin/BannerAdminController`, `Admin/HomeLayoutAdminController`, `Admin/ReviewAdminController` | Nội dung & marketing | [08](08-module-noi-dung.md) |
| `Admin/AdminRouteAliasController` | Tương thích URL cũ (admin) | mục §1.7 |
| `Actions/Fortify/*`, `Actions/Jetstream/*`, `View/Components/*` | Scaffolding chết | §1.6 |
| **`Services/OrderCancellationService`**, **`OrderCancellationController`** | Hủy đơn 2 bước (mới) | [05](05-module-don-hang-hoan-doi.md) |
| **`Services/OrderConfirmationEmailService`**, **`OrderInvoiceEmailService`** | Email đơn hàng & hóa đơn (mới) | [05](05-module-don-hang-hoan-doi.md) |
| **`Support/QueuedRawMail`**, **`Jobs/SendRawMailJob`** | Hàng đợi gửi mail (mới) | §1.12 |
| **`Admin/PromotionAdminController`** | CRUD khuyến mãi (mới) | [07](07-module-quan-tri-bao-cao.md) |
| **`Admin/TryOnSnapshotAdminController`**, **`Models/TryOnSnapshot`** | Lưu kết quả thử kính (mới) | [08](08-module-noi-dung.md) |

## 1.5. Middleware

### `ValidateRequestInput` (`app/Http/Middleware/ValidateRequestInput.php`, 111 dòng)

Chốt chặn đầu vào toàn cục, chạy trên **mọi** request web. Giới hạn:

| Hằng số | Giá trị | Ý nghĩa |
|---|---|---|
| `MAX_DEPTH` | 6 | Độ sâu mảng lồng nhau |
| `MAX_QUERY_FIELDS` | 50 | Số field trong query string (đếm sau `Arr::dot`) |
| `MAX_BODY_FIELDS` | 300 | Số field trong body |
| `MAX_QUERY_STRING_LENGTH` | 4096 | Độ dài query string thô |
| `MAX_QUERY_VALUE_LENGTH` | 255 | Độ dài mỗi giá trị trong query |
| `MAX_BODY_VALUE_LENGTH` | 65535 | Độ dài mỗi giá trị trong body |

Ngoài ra chặn: tên field chứa ký tự ngoài `[A-Za-z0-9_.:-]`, chuỗi không phải UTF-8 hợp lệ,
và ký tự điều khiển (`\x00-\x08`, `\x0B`, `\x0C`, `\x0E-\x1F`, `\x7F`).

Vi phạm → ném `ValidationException` với message tiếng Việt.

> ⚠️ `MAX_BODY_FIELDS = 300` là trần cứng cho form phiếu kho (`storeTransaction` gửi 3 mảng
> song song `variant_id[]`, `quantity[]`, `unit_cost[]`) → phiếu quá ~95 dòng sẽ bị chặn với
> thông báo "Dữ liệu gửi lên có quá nhiều trường", không phải lỗi nghiệp vụ dễ hiểu.

### `SecurityHeaders` (`app/Http/Middleware/SecurityHeaders.php`, 33 dòng)

Gắn vào mọi response:

- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: SAMEORIGIN`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy: camera=(self), microphone=(), geolocation=()` — cho phép camera
  cùng origin vì chức năng thử kính cần webcam.

Riêng `admin/*`, `/dang-nhap`, `/dang-ky` thêm `Cache-Control: no-store, private`.

> **Thiếu:** không có `Content-Security-Policy` và không có `Strict-Transport-Security`.
> Xem [10 — Audit](10-ket-qua-audit.md) mục M-04.

### `EnsureAdmin` (alias `admin`) — xem [tài liệu 02](02-module-xac-thuc-phan-quyen.md).

## 1.6. Rate limiting (`AppServiceProvider::boot`)

| Limiter | Giới hạn/phút | Khóa theo | Áp dụng cho |
|---|---|---|---|
| `web-read` | 180 | IP | Danh sách/chi tiết SP, blog, hỗ trợ, thử kính |
| `auth` | 5 | `email + IP` | Đăng nhập, đăng ký, quên/đặt lại MK, xác thực email |
| `admin-auth` | 5 | `admin + email + IP` | Đăng nhập admin |
| `cart` | 30 | user id hoặc IP | Thêm/sửa/xóa giỏ hàng |
| `checkout` | 6 | user id hoặc IP | Đặt hàng, áp/gỡ mã giảm giá |
| `user-actions` | 12 | user id hoặc IP | Sửa hồ sơ, địa chỉ, đổi MK, đánh giá, tạo yêu cầu hoàn đổi |
| `admin` | 120 | user id hoặc IP | Toàn bộ khu vực admin sau khi đăng nhập |
| `uploads` | 20 | user id hoặc IP | Upload ảnh từ trình soạn thảo |
| `login`, `two-factor` | 5 | (Fortify) | **Không dùng** — Fortify đã tắt route |

`AppServiceProvider` còn làm 2 việc nữa:

1. **Ép HTTPS ở production:** nếu `app.env === production` và `config('app.url')` bắt đầu bằng
   `https://` thì gọi `URL::forceRootUrl()` + `URL::forceScheme('https')`. Đây là fix cho
   Railway (commit `d8c3a34`).
2. **View composer cho `layouts.app`:** bơm `$headerProductLinks` — 3 link menu ("Kính mát",
   "Kính thời trang", "Gọng kính") được suy ra bằng cách **so khớp tiền tố slug danh mục**
   (`kinh-mat`, `kinh-thoi-trang`, `gong-kinh`) và chỉ hiện khi danh mục có ≥1 sản phẩm ACTIVE.
   Toàn bộ khối này bọc trong `try/catch (Throwable) → return []` để trang không vỡ khi DB chưa sẵn sàng.
   Nay được **cache 10 phút** dưới key `layout.header_categories.v2`, và chỉ `get(['id','slug'])`
   rồi map sang mảng thuần thay vì giữ cả Eloquent collection ✅.

   > Quy ước ngầm nguy hiểm: đổi slug danh mục trong admin sẽ **âm thầm làm mất menu header**,
   > không có cảnh báo nào.
   >
   > 🔴 **Cache này không bao giờ bị làm mới.** `ProductAdminController::forgetCaches()` gọi
   > `Cache::forget('layout.header_categories')` — **thiếu hậu tố `.v2`**, nên xóa nhầm một key
   > không tồn tại. 4 key còn lại trong hàm đó cũng chưa từng được ghi ở đâu.
   > Xem [10](10-ket-qua-audit.md) mục **N-01**.

## 1.7. Scaffolding chết (Jetstream / Fortify)

`FortifyServiceProvider::register()` gọi `Fortify::ignoreRoutes()` → **toàn bộ route của
Fortify bị tắt**. Hệ thống dùng `AuthController` tự viết thay thế. Hệ quả: các file sau tồn
tại nhưng **không bao giờ được thực thi**:

- `app/Actions/Fortify/CreateNewUser.php`, `ResetUserPassword.php`, `UpdateUserPassword.php`,
  `UpdateUserProfileInformation.php`, `PasswordValidationRules.php`
- `app/Actions/Jetstream/DeleteUser.php`
- `app/View/Components/AppLayout.php`, `GuestLayout.php`
- `resources/views/profile/*`, `api/*`, `dashboard.blade.php`, `navigation-menu.blade.php`,
  `emails/team-invitation.blade.php`, `components/switchable-team.blade.php`, và phần lớn
  `resources/views/components/*`
- 15 test trong `tests/Feature/` (đều là test Jetstream mặc định)

Route `/dashboard` (cuối `routes/web.php`) yêu cầu middleware `verified` — nhưng `User` model
**không implement `MustVerifyEmail`**, nên middleware này hành xử khác mong đợi.

`database/factories/UserFactory.php` `use App\Models\Team;` — **class này không tồn tại** — và
sinh các cột `name`, `password`, `profile_photo_path`, `current_team_id` **không có trong bảng
`users`** (bảng dùng `full_name`, `password_hash`). Xem [10 — Audit](10-ket-qua-audit.md) mục C-05.

`composer.json` khai báo `sepay/sepay-pg: ^1.0` nhưng **không có một dòng code nào dùng đến** —
dependency chết, có thể do thử nghiệm cổng thanh toán khác rồi bỏ.

## 1.8. Cấu hình & bí mật

`config/vnpay.php` chứa **giá trị mặc định hardcode**:

```php
'tmn_code'    => env('VNPAY_TMN_CODE', 'TYIMV67T'),
'hash_secret' => env('VNPAY_HASH_SECRET', 'LNBQQ3N8MYP26ECD7DW47JM60474RKUD'),
```

Cùng bộ giá trị này lặp lại ở `railway/init-app.sh` và `.env.example`. Đây là credential
sandbox nên rủi ro tiền bạc bằng 0, nhưng mô hình "secret có default trong code" là sai
nguyên tắc: khi lên production mà quên set biến môi trường, hệ thống sẽ **im lặng chạy bằng
merchant sandbox** thay vì báo lỗi. Xem [10 — Audit](10-ket-qua-audit.md) mục H-06.

`.env` đã nằm trong `.gitignore` ✅ và `.htaccess` chặn truy cập trực tiếp `.env`,
`composer.json/lock`, `package*.json`, `phpunit.xml`, `auth.json` ✅.

`.env.example` mặc định `DB_CONNECTION=sqlite` — **sai** với dự án này (bắt buộc MySQL).

> 🔴 **Đợt cập nhật mới commit file `.env.railway_2047_backup` (77 dòng) vào repo.**
> `.gitignore` khai báo `.env` và `.env.*` nhưng file này vẫn lọt qua. **Cần kiểm tra ngay** có
> chứa credential thật hay không; nếu có thì phải xoay vòng toàn bộ và gỡ khỏi lịch sử Git
> (không chỉ `git rm`). Đổi pattern thành `.env*`.
> Xem [10](10-ket-qua-audit.md) mục **N-04**.
>
> Cùng đợt còn có 7 file `RECOVERY_*.csv` + `RECOVERY_NOTES.md` — sản phẩm phụ của một lần khôi
> phục dữ liệu, không thuộc mã nguồn ứng dụng, nên chuyển ra ngoài repo.

## 1.9. Deploy (Railway)

`railway.json`:
```json
{ "build":  { "builder": "RAILPACK", "buildCommand": "npm run build" },
  "deploy": { "preDeployCommand": ["... sh ./railway/init-app.sh"],
              "healthcheckPath": "/up", "healthcheckTimeout": 300,
              "restartPolicyType": "ON_FAILURE", "restartPolicyMaxRetries": 3 } }
```

`railway/init-app.sh` (chạy với `set -e`):
1. Suy ra `APP_URL` từ `RAILWAY_PUBLIC_DOMAIN` / `RAILWAY_STATIC_URL` nếu chưa có.
2. Đặt mặc định các biến `VNPAY_*`.
3. `config:clear` → `route:clear` → `view:clear`
4. **`php artisan migrate --force`**
5. `storage:link || true`
6. `config:cache` → `route:cache` → `view:cache`

> 🔴 **Bước 4 vẫn sẽ thất bại trên CSDL trống.** Repo nay có 15 migration, nhưng chúng chỉ tạo
> `users`, `password_reset_tokens`, `sessions`, `cache`, `jobs`, `personal_access_tokens`,
> `try_on_snapshots` — cộng các thao tác ALTER lên bảng có sẵn.
> **Vẫn không có migration nào tạo `products`, `orders`, `payments`, `inventories`… (~20 bảng).**
>
> 9 migration mới (`2026_08_04_*`) **đều có bảo vệ** `if (! Schema::hasTable(...)) return;` ✅ —
> cải thiện thật. Nhưng migration cũ `2026_07_10_060000_keep_only_cod_and_vnpay_payment_methods.php`
> **vẫn chạy `ALTER TABLE payments MODIFY ...` không bảo vệ** trên bảng chưa tồn tại → SQL error
> → `set -e` dừng script → deploy fail. Xem [10 — Audit](10-ket-qua-audit.md) mục **C-01**.

## 1.10. Tương thích URL cũ

Hai controller alias chuyển hướng URL của hệ thống PHP thuần cũ:

**`ClientRouteAliasController`** — bắt `/` (khi có `?url=...`), `/index.php`, và catch-all
`/{oldRoute}` với whitelist 30 giá trị. Ánh xạ đặc biệt:
- `chitietsanpham` + `?id_sp=` → tra `Product` → `/san-pham/{slug}`
- `chi-tiet-bai-viet` + `?id_bv=` → tra `Post` → `/bai-viet/{slug}`
- `edit-address` + `?id=` → `/tai-khoan/dia-chi/{id}/sua`
- 3 URL `thanh-toan-momo*` → `/thanh-toan` (dấu vết cổng MoMo đã bỏ)

**`AdminRouteAliasController`** — bắt `/admin` (khi có `?quanli=...`), `/admin/index.php`, và
2 catch-all riêng: một nhóm chỉ cho `ADMIN` (báo cáo, thành viên, banner, bố cục), một nhóm
cho cả `ADMIN` và `STAFF`. `oldProjectId()` dò lần lượt 10 tên tham số id cũ
(`id`, `id_sp`, `id_dm`, `id_dh`, `id_bv`, `id_bl`, `id_banner`, `id_user`, `id_kh`, `return_id`).

Cả hai `redirectPath()` dùng `redirect()->away($request->getSchemeAndHttpHost() . $path)` —
tự ghép host từ request nên **an toàn** (không phải open redirect), nhưng dùng `away()` cho
URL nội bộ là thừa và bỏ qua `URL::forceScheme('https')` đã cấu hình ở §1.6.

## 1.11. Hàng đợi email (mới)

`app/Support/QueuedRawMail.php` + `app/Jobs/SendRawMailJob.php` thay thế `Mail::raw()` đồng bộ:

```php
QueuedRawMail::raw($body, fn ($m) => $m->to($email)->subject($subject));
```

Bên trong dùng một lớp thu thập nhỏ (`QueuedRawMailMessage`) chỉ hỗ trợ `to()` và `subject()`,
rồi `SendRawMailJob::dispatch(...)`. Nếu dispatch ném exception (queue chưa cấu hình, Redis
chết…) thì **fallback gửi trực tiếp** bằng `Mail::raw()` và ghi `Log::warning` ✅.

Đây là bản vá đúng cho vấn đề "request phải chờ SMTP Gmail" nêu ở [02](02-module-xac-thuc-phan-quyen.md) §2.1.

> ⚠️ Áp dụng **chưa đồng đều**: `OrderCancellationService` và các service email đơn hàng dùng
> `QueuedRawMail`, nhưng `AuthController` (email xác thực đăng ký, email đặt lại mật khẩu) **vẫn
> dùng `Mail::raw()` đồng bộ**. Hai luồng nhạy cảm nhất về trải nghiệm vẫn chặn request.

## 1.12. Kiểm thử

`tests/` chỉ có **test mặc định của Jetstream** (15 file Feature + 1 Unit + `ExampleTest`).
**Không có một test nào** cho: giỏ hàng, đặt hàng, VNPay, tồn kho, hoàn/đổi, báo cáo, phân quyền.
Thêm nữa các test Jetstream này gần như chắc chắn **đang fail** vì `UserFactory` tham chiếu
class `Team` không tồn tại và các cột sai (§1.7) — *(chưa kiểm chứng runtime: máy audit không
có PHP 8.4 + vendor)*.

> 🔴 **Đợt cập nhật không sửa gì ở đây**, trong khi thêm ~1.500 dòng logic nghiệp vụ mới
> (state machine đơn hàng, luồng hủy 2 bước, phiếu SALE_OUT, ràng buộc hoàn/đổi, snapshot thử
> kính). Toàn bộ phần mới này **không có lưới an toàn nào**. Xem [10](10-ket-qua-audit.md) mục **N-03**.
