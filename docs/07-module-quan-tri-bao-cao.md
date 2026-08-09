# 07 — Module Quản trị & Báo cáo

Gồm: `DashboardController`, `ReportAdminController`, `CustomerAdminController`,
`BusinessAdminController`, `AdminRouteAliasController`.

---

## 7.1. `app/Http/Controllers/Admin/DashboardController.php` (112 dòng)

Controller kiểu `__invoke`, nhưng **không được gọi trực tiếp từ route**. Route
`GET /admin` trỏ tới `AdminRouteAliasController::home()`, hàm này gọi
`app(DashboardController::class)()` khi không có tham số `?quanli=`.

### Dữ liệu hiển thị (7 truy vấn độc lập)

| Biến | Nội dung |
|---|---|
| `$orderStats` | 1 query 7 cột: tổng doanh thu (DELIVERED), doanh thu tháng, tổng đơn, đơn hôm nay, đơn chờ, đã xác nhận, đang giao |
| `$returnStats` | 1 query 3 cột: yêu cầu hoàn/đổi đang chờ, tách RETURN / EXCHANGE |
| `$stock` | Tổng tồn + tổng giữ chỗ |
| `$lowStockItems` | 5 biến thể sắp hết (JOIN 4 bảng + GROUP BY + HAVING) |
| `$topCategories` | 5 danh mục bán chạy |
| `$chartRows` | Đơn & doanh thu 7 ngày gần nhất |
| + 6 truy vấn đếm | `activeProducts`, `totalVariants`, `activeCategories`, `activeBrands`, `activeCustomers`, `latestOrders`, `pendingReturns` |

**≈ 14 truy vấn** cho một lần tải dashboard, không cache.

### 🔴 Vấn đề 1 — Cú pháp MySQL-only

```sql
MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())
DATE(created_at) = CURRENT_DATE()
```

`MONTH()`, `YEAR()`, `CURRENT_DATE()` là hàm MySQL. Trong khi `.env.example` mặc định
`DB_CONNECTION=sqlite`. Ai làm theo `.env.example` sẽ gặp lỗi SQL ngay ở trang chủ admin.
Xem [10](10-ket-qua-audit.md) mục M-18.

Ngoài ra `MONTH(created_at) = MONTH(...)` **không dùng được index** trên `created_at` — nên
dùng `created_at >= ? AND created_at < ?`.

### ⚠️ Vấn đề 2 — Số liệu doanh thu không nhất quán

| Nơi | Công thức doanh thu |
|---|---|
| `DashboardController` `total_revenue` | `SUM(orders.total_amount)` **chỉ đơn DELIVERED** |
| `DashboardController` `$chartRows.revenue` | `SUM(orders.total_amount)` — **mọi trạng thái, kể cả CANCELLED** |
| `ReportAdminController` `delivered_revenue` | `SUM(orders.total_amount)` chỉ DELIVERED |
| `ReportAdminController` các bảng SP/danh mục | `SUM(oi.quantity * oi.unit_price)` — **bỏ qua giảm giá** |

Bốn con số "doanh thu" khác nhau trên cùng hệ thống. Biểu đồ 7 ngày của dashboard đặc biệt
sai vì tính cả đơn đã hủy. Xem [10](10-ket-qua-audit.md) mục **H-12**.

### ⚠️ Vấn đề 3 — `lowStockCount` sai

```php
'lowStockCount' => $lowStockItems->count(),     // ← luôn ≤ 5
```
`$lowStockItems` đã bị `->limit(5)`. Nên "số mặt hàng sắp hết" hiển thị trên dashboard
**tối đa là 5**, dù thực tế có 500. Cần một `COUNT(*)` riêng.

### ⚠️ Vấn đề 4 — `$topCategories` đếm sai

```php
->leftJoin('products', fn($j) => $j->on(...)->where('products.status','=','ACTIVE'))
->leftJoin('order_items', 'order_items.product_id', '=', 'products.id')
->selectRaw('COUNT(DISTINCT products.id) as product_count')
->selectRaw('COALESCE(SUM(order_items.quantity), 0) as sold_quantity')
```
JOIN `order_items` **không lọc trạng thái đơn hàng** → doanh số bao gồm cả đơn `CANCELLED`
(trong khi `ProductAdminController` và `ReportAdminController` đều loại `CANCELLED`,
`LOST_IN_TRANSIT`). Thêm nữa, `SUM` sau nhiều JOIN dễ bị **nhân bản dòng** (fan-out).

---

## 7.2. `app/Http/Controllers/Admin/ReportAdminController.php` (298 dòng)

5 màn hình báo cáo, **chỉ dành cho vai trò `ADMIN`** (`Route::middleware('admin:ADMIN')`).

| Method | Route | Nội dung |
|---|---|---|
| `products()` | `/admin/bao-cao/san-pham` | Thống kê theo danh mục: SL sản phẩm/biến thể, tồn, sắp hết, giá min/max/avg, đã bán, doanh thu |
| `orders()` | `/admin/bao-cao/don-hang` | Tổng quan đơn + phân bố theo trạng thái + bảng chi tiết từng sản phẩm (kèm SL hoàn) |
| `salesChart()` | `/admin/bao-cao/bieu-do-luot-ban` | Doanh số theo danh mục, chọn top 5/10/30 |
| `topSales()` | `/admin/bao-cao/top-luot-ban` | Top sản phẩm bán chạy, top 5/10/15/30/100 |
| `dailySales()` | `/admin/bao-cao/luot-ban-theo-ngay` | Doanh số theo ngày, 7/14/30/90/365 ngày, biểu đồ bar/line |

### Toàn bộ dùng SQL thô (`DB::select` / `DB::selectOne`)

Đây là lựa chọn hợp lý cho báo cáo (Eloquent sẽ rất cồng kềnh với các subquery lồng nhau này).
Các truy vấn được viết khá tốt: dùng subquery đã gộp sẵn thay vì JOIN trực tiếp lên bảng chi
tiết, nên **tránh được lỗi nhân bản dòng (fan-out)** mà `DashboardController` mắc phải.

Ví dụ trong `orders()`, tồn kho được gộp 2 tầng:
```sql
LEFT JOIN (
    SELECT product_id, SUM(available_stock), SUM(CASE WHEN available_stock <= min_stock_level ...)
    FROM ( SELECT pv.product_id, pv.id, SUM(i.quantity - i.reserved_quantity) AS available_stock,
                  MAX(i.min_stock_level) AS min_stock_level
           FROM product_variants pv LEFT JOIN inventories i ON ... GROUP BY pv.product_id, pv.id
    ) variant_stock GROUP BY product_id
) stock ON stock.product_id = p.id
```
Kỹ thuật đúng ✅.

### Nội suy chuỗi vào SQL — **an toàn nhờ whitelist** ✅

```php
LIMIT {$top}
WHERE o.created_at >= DATE_SUB(CURDATE(), INTERVAL " . ($limitDay - 1) . " DAY)
```

Thoạt nhìn giống SQL injection, nhưng cả hai giá trị đều đi qua:
```php
private function resolveTop(int $value, array $allowed): int {
    return in_array($value, $allowed, true) ? $value : $allowed[0];
}
```
với `$request->integer(...)` (đã ép `int`) và một mảng whitelist cứng. **Không khai thác được.**

> ⚠️ Tuy vậy đây là mô hình rủi ro: an toàn phụ thuộc hoàn toàn vào việc lập trình viên tương
> lai nhớ giữ nguyên `resolveTop()`. Nên dùng binding hoặc `(int)` trực tiếp tại điểm nội suy.

### ⚠️ Không phân trang, không giới hạn

`products()` và `orders()` trả về **toàn bộ** danh mục / sản phẩm (chỉ loại `DISCONTINUED`),
không `LIMIT`, không phân trang, đổ hết vào một trang HTML. Với catalog lớn sẽ rất nặng.

### 🔴 Cú pháp MySQL-only

`DATE_SUB(CURDATE(), INTERVAL n DAY)` (dòng 263) — không chạy trên SQLite/PostgreSQL.

### ⚠️ Doanh thu bỏ qua giảm giá

Mọi bảng sản phẩm/danh mục dùng `SUM(oi.quantity * oi.unit_price)`. Trong khi `order_items`
**có cột `discount_amount`** (luôn bằng 0) và `orders` có `discount_amount` thật (từ mã giảm
giá). Nên **doanh thu báo cáo cao hơn doanh thu thực tế** đúng bằng tổng giảm giá đã áp dụng.

### ⚠️ Route `xuat-exel` là lời hứa suông

`AdminRouteAliasController` ánh xạ `xuat-exel` → `admin.reports.orders`. **Không có chức năng
xuất Excel nào** trong dự án. URL cũ được giữ lại nhưng chỉ redirect về trang báo cáo HTML.

---

## 7.3. `app/Http/Controllers/Admin/CustomerAdminController.php` (247 dòng)

Quản lý tài khoản — **chỉ `ADMIN`**.

| Method | Route |
|---|---|
| `index()` | `GET /admin/thanh-vien` — lọc keyword/role/status, paginate 20 |
| `create()` / `store()` | Thêm tài khoản |
| `edit()` / `update()` | Sửa tài khoản |
| `updateStatus()` | `PATCH /admin/thanh-vien/{user}/trang-thai` — khóa/mở khóa |

Dùng chung view `admin.shared.form` với mảng `fields` khai báo động (`customerFields()`).

### Validate (`validateCustomer`, dòng 150–162)

```php
'email' => ['required','email','max:255', 'regex:/@gmail\.com$/', Rule::unique('users','email')->ignore($user?->id)],
'phone' => ['nullable','string','max:20','regex:/^0[0-9]{9}$/'],
'password' => [$user ? 'nullable' : 'required', 'string', 'min:6', 'max:100'],
'role_code' => ['required','exists:roles,code'],
```

> ⚠️ **`regex:/@gmail\.com$/` — chỉ chấp nhận email Gmail.** Không thể tạo tài khoản
> `admin@congty.vn`. Ràng buộc này không có ở form đăng ký công khai (`AuthController::register`
> chỉ yêu cầu `email`). Bất đối xứng khó hiểu, có lẽ do phụ thuộc SMTP Gmail.

> ⚠️ **`min:6` ở admin vs `min:8` ở đăng ký công khai.** Tài khoản do admin tạo — kể cả tài
> khoản `ADMIN` — được phép đặt mật khẩu **yếu hơn** tài khoản khách hàng tự đăng ký.
> Ngược hoàn toàn với logic bảo mật.

> ⚠️ **`phone` regex khác nhau:** ở đây là `/^0[0-9]{9}$/` (mọi đầu số bắt đầu bằng 0), còn ở
> `AccountController` và `CheckoutController` là `/^(03|05|07|08|09)\d{8}$/` (đầu số di động).
> Ba nơi, hai quy tắc.

### 🔴 Thiếu sót nghiêm trọng — tài khoản admin tạo không dùng được ở client

`store()` (dòng 93–106) tạo user với `$data` từ validate — **không có `email_verified_at`**.
Trong khi `AuthController::login()` bắt buộc:
```php
if (! $user->email_verified_at) {
    return back()->withErrors(['email' => 'Vui lòng kiểm tra Gmail và bấm link xác thực...']);
}
```

Kết quả: mọi tài khoản do admin tạo **không thể đăng nhập ở phía khách hàng**, và cũng không
có cách nào gửi lại mail xác thực. Với tài khoản `ADMIN`/`STAFF` thì vẫn vào được `/admin`
(vì `AdminAuthController` không kiểm `email_verified_at`), nhưng với tài khoản `USER` do admin
tạo hộ khách thì **hoàn toàn vô dụng**. Xem [10](10-ket-qua-audit.md) mục **H-13**.

### 🔴 Không có bảo vệ chống tự khóa / mất admin cuối cùng

- `updateStatus()` cho phép ADMIN **tự khóa chính mình** (`status = 'LOCKED'`) → lần request
  admin tiếp theo, `EnsureAdmin` phát hiện `status !== 'ACTIVE'` → **logout + không vào lại được**.
- `syncRole()` cho phép **hạ cấp admin cuối cùng** xuống `USER` → **không còn ai vào được
  khu vực quản trị**. Chỉ sửa được bằng cách can thiệp trực tiếp CSDL.
- Không có xác nhận nào cho các thao tác này.

Xem [10](10-ket-qua-audit.md) mục **H-14**.

### `syncRole()` — mô hình 1 vai trò

```php
DB::table('user_roles')->where('user_id', $user->id)->delete();
DB::table('user_roles')->insert(['user_id' => ..., 'role_id' => ...]);
```

Dù CSDL thiết kế quan hệ **nhiều-nhiều** (`user_roles`), giao diện chỉ cho phép **một vai trò
duy nhất** — xóa hết rồi chèn một dòng. `currentRoleCode()` cũng chỉ lấy `orderBy('roles.id')->value()`
(vai trò đầu tiên). Mô hình n-n bị dùng như 1-1.

> ⚠️ Không có transaction bao quanh `delete()` + `insert()`. Lỗi giữa chừng → **user mất sạch
> vai trò**.

> ⚠️ `$user->update($data)` trong `update()` nhận cả `email` và `status` — không có kiểm tra
> nào ngăn ADMIN đổi email của ADMIN khác thành email mình kiểm soát rồi dùng chức năng quên
> mật khẩu để chiếm tài khoản. Trong mô hình 1 cấp ADMIN thì đây là rủi ro chấp nhận được,
> nhưng nên ghi log.

### `index()` — hiệu năng

`withCount('orders')` + `withSum(['orders as delivered_total' => ...], 'total_amount')` là
subquery, tốt hơn N+1 ✅. Bộ lọc dùng `whereExists` cho vai trò ✅.
Tuy nhiên có **5 truy vấn `$summary`** riêng (`total`, `active`, `customers`, `staff`, `locked`)
chạy trên toàn bảng mỗi lần tải trang.

`->orWhere('id', (int) $keyword)` trong bộ lọc keyword: khi tìm chuỗi không phải số,
`(int) 'abc'` = `0` → điều kiện `id = 0` (vô hại nhưng thừa).

---

## 7.4. `app/Http/Controllers/Admin/BusinessAdminController.php` (185 dòng)

Màn hình "Nghiệp vụ" — **chỉ `ADMIN`**. Gộp 5 tab: `brands`, `promotions`, `warehouses`,
`stores`, `stock`.

### Mô hình action động

```php
public function store(Request $request): RedirectResponse {
    $action = (string) $request->input('_business_action');
    return match ($action) {
        'save_brand'        => $this->saveBrand($request),
        'save_promotion'    => $this->savePromotion($request),
        'save_warehouse'    => $this->saveWarehouse($request),
        'toggle_brand'      => $this->toggleTableStatus($request, 'brands', 'brands'),
        'toggle_promotion'  => $this->toggleTableStatus($request, 'promotions', 'promotions'),
        'toggle_warehouse'  => $this->toggleTableStatus($request, 'warehouses', 'warehouses'),
        default             => back()->with('success', 'Chưa chọn nghiệp vụ cần xử lý.'),
    };
}
```

Một route `POST /admin/nghiep-vu` phục vụ 6 hành động qua trường ẩn `_business_action`.
`match` với whitelist cứng → **an toàn** ✅ (không phải dynamic method dispatch).

> ⚠️ Nhánh `default` trả về `with('success', ...)` cho một **lỗi** — thông báo thành công màu
> xanh cho tình huống "chưa chọn gì". Sai ngữ nghĩa UX.

### `toggleTableStatus()` — dòng 154–168

```php
private function toggleTableStatus(Request $request, string $table, string $tab) {
    $data = $request->validate(['id' => ['required','integer',"exists:{$table},id"]]);
    $row = DB::table($table)->where('id', $data['id'])->first(['status']);
    $nextStatus = ($row?->status === 'ACTIVE') ? 'INACTIVE' : 'ACTIVE';
    DB::table($table)->where('id', $data['id'])->update(['status' => $nextStatus, 'updated_at' => now()]);
}
```

`$table` được nội suy vào rule `exists:{$table},id` và vào `DB::table($table)`. **An toàn** vì
`$table` chỉ đến từ 3 hằng chuỗi trong `match` ở trên, không bao giờ từ request ✅. Nhưng đây
là mô hình cần cẩn trọng nếu ai đó mở rộng sau này.

> ⚠️ Bật/tắt một khuyến mãi đang có hiệu lực chuyển giữa `ACTIVE`/`INACTIVE`, nhưng enum thật
> của `promotions.status` là `SCHEDULED|ACTIVE|INACTIVE|EXPIRED` (theo rule của `savePromotion`).
> Toggle từ `EXPIRED` sẽ nhảy thẳng sang `ACTIVE` — **hồi sinh mã đã hết hạn**.

> ✅ **Khuyến mãi đã tách ra controller riêng.** Đợt cập nhật thêm
> `Admin/PromotionAdminController` (141 dòng) với `index()`, `store()`, **`update()`** và toggle
> trạng thái riêng, cùng view `admin/promotions/index.blade.php`. **M-21 khắc phục cho khuyến mãi** —
> nay sửa được mã đã tạo thay vì phải tạo mã mới.
>
> Thương hiệu và kho **vẫn** chỉ tạo được, chưa sửa được.

### `savePromotion()` — dòng 100–133 (bản cũ trong `BusinessAdminController`)

Dùng `DB::table('promotions')->insert($data)` thay vì model `Promotion` — phải tự set
`created_at`, `updated_at`, `scope`, `used_count`, `stackable`.

Mặc định cứng: `'scope' => 'ORDER'` (khớp với `CheckoutController` chỉ chấp nhận scope ORDER ✅).

> ⚠️ **Chỉ tạo được, không sửa được.** Không có `updatePromotion` — muốn đổi giá trị giảm giá
> phải tạo mã mới. Tương tự với `saveBrand` và `saveWarehouse`: cả 3 đều chỉ INSERT.
> `unique:brands,name` / `unique:warehouses,name` khiến không thể lưu đè.

> ⚠️ `usage_per_user` **không có trong form** — cột này không bao giờ được đặt giá trị, và
> cũng không bao giờ được kiểm tra (xem [04](04-module-gio-hang-thanh-toan.md) §4.2).

`nextCode()` (dòng 170–177) dùng vòng lặp `do...while` kiểm tra trùng ✅ — tốt hơn
`WarehouseAdminController::nextTransactionCode()`.

### Bảng `stores` không có model

```php
$stores = DB::table('stores')->leftJoin('warehouses', ...)->limit(40)->get();
```
Bảng `stores` chỉ được đọc để hiển thị, **không có model, không có chức năng thêm/sửa**.
Tab "stores" là màn hình chỉ-đọc cho dữ liệu không có cách nào tạo qua giao diện.

### Hiệu năng
5 truy vấn danh sách (mỗi cái `limit(40)`) + 5 truy vấn `COUNT(*)` = 10 query cho một lần
tải trang, **kể cả khi chỉ xem 1 tab**. Nên tải theo tab.

---

## 7.5. `app/Http/Controllers/Admin/AdminRouteAliasController.php` (124 dòng)

Đã mô tả ở [01](01-tong-quan-kien-truc.md) §1.10. Bổ sung chi tiết:

- `home()` kiêm 2 vai trò: **dashboard** (khi không có `?quanli=`) và **redirector** (khi có).
  Việc `app(DashboardController::class)()` bên trong một alias controller làm mờ trách nhiệm —
  route `admin.dashboard` thực chất render dashboard nhưng tên controller lại là "RouteAlias".
- `$map` (27 mục) cho trang danh sách, `$editMap` (9 mục) cho trang chi tiết có id.
- Route catch-all được **tách làm 2 nhóm quyền**: nhóm `admin:ADMIN` (14 alias: báo cáo, thành
  viên, banner, bố cục, nghiệp vụ) và nhóm chung (21 alias). Phân quyền được giữ nhất quán với
  route thật ✅ — chi tiết dễ bỏ sót nhưng đã làm đúng.
- Mọi trường hợp không khớp → `redirect()->route('admin.dashboard')`, không bao giờ 404.

> ⚠️ `'kho-hang' => ['admin.warehouses.index']` có trong `$map` nhưng **`kho-hang` không nằm
> trong biểu thức `->where('oldRoute', '...')` của route catch-all** — nên nhánh này không bao
> giờ được kích hoạt qua đường `/admin/kho-hang` (đó đã là route thật). Chỉ dùng được qua
> `/admin?quanli=kho-hang`. Code thừa nhưng vô hại.

---

## 7.6. Tổng kết module

| Mã | Mức | Trạng thái | Vấn đề |
|---|---|---|---|
| M-21 | TB | ✅ phần lớn | `PromotionAdminController` mới có `update()`; thương hiệu/kho **vẫn** chỉ tạo được |
| L-12 (index) | Thấp | ✅ **đã sửa** | Migration `2026_08_04_130000` thêm ~40 chỉ mục trên 18 bảng |
| L-13 | Thấp | ✅ phần lớn | Xuất Excel đã có thật (`products.export-excel`); bảng `stores` vẫn chỉ-đọc |
| **N-02** | **Cao** | 🆕 mới | Cache vai trò 5 phút **không bao giờ bị xóa** — `syncRole()`/`updateStatus()` không gọi `Cache::forget()` → hạ cấp admin mất tới 5 phút mới có hiệu lực |
| H-12 | Cao | ⚠️ chưa sửa | Nhiều công thức doanh thu khác nhau; biểu đồ dashboard tính cả đơn đã hủy. **Thêm:** `LOST_IN_TRANSIT` đã bị gỡ khỏi enum đơn hàng nhưng báo cáo vẫn lọc theo nó |
| H-13 | Cao | ⚠️ chưa sửa | Tài khoản admin tạo không có `email_verified_at` → không đăng nhập được ở client |
| H-14 | Cao | ⚠️ **nguy hiểm hơn** | ADMIN có thể tự khóa mình / hạ cấp admin cuối cùng — nay cộng thêm độ trễ cache 5 phút khiến khó chẩn đoán |
| M-18 | TB | ⚠️ nặng thêm | MySQL-only; migration mới còn dùng `information_schema.CHECK_CONSTRAINTS` và `ALTER TABLE ... DROP CHECK` |
| M-19 | TB | ⚠️ chưa sửa | Báo cáo bỏ qua `discount_amount` → doanh thu cao hơn thực tế |
| M-20 | TB | ⚠️ chưa sửa | Mật khẩu admin `min:6` yếu hơn đăng ký công khai `min:8`; email bắt buộc Gmail; 2 regex SĐT khác nhau |
| L-11 | Thấp | ⚠️ chưa sửa | `lowStockCount` luôn ≤ 5; `topCategories` không lọc đơn hủy; `syncRole` không có transaction |
| L-12 (cache) | Thấp | 🟠 một phần | Menu header nay có cache 10 phút ✅ nhưng **không bao giờ được làm mới** (N-01); dashboard/"Nghiệp vụ" vẫn không cache |
