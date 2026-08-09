# 12 — Plan xử lý góp ý của thầy

Gồm **6 mục thầy yêu cầu** + **2 lỗi bạn báo** (luồng hoàn trả không hoàn kho, thử kính lỗi).

Mỗi mục có: nguyên nhân đã xác minh trong code → cách sửa → ước tính công → **câu trả lời để nói với thầy**.

---

## Bảng tổng hợp

| # | Việc | Mức độ | Công | Ưu tiên |
|---|---|---|---|---|
| 3 + A | Hoàn/đổi + kho (sản phẩm lỗi vẫn bán được) | 🔴 Nghiệp vụ lõi | 2–3 ngày | **1** |
| 2 | Chậm 10 giây khi thêm SP / xác nhận thanh toán | 🔴 Trải nghiệm | 0.5–1 ngày | **2** |
| 5 | Menu "Đơn hàng" thiếu mục | 🟢 Dễ | 1 giờ | **3** |
| 1 | Tự tính giá bán niêm yết từ giá nhập | 🟡 Dễ, có điểm | 3 giờ | **4** |
| 6 | Sửa xuất Excel sản phẩm | 🟡 Trung bình | 0.5 ngày | **5** |
| B | Thử kính lỗi | ❓ Cần bạn mô tả rõ | ? | **6** |
| 4 | Thiết kế đỡ "bị AI" | 🟡 Cảm quan | 1–2 ngày | **7** |

**Tổng ước tính: 5–7 ngày công.**

Thứ tự ưu tiên đặt theo *khả năng bị thầy hỏi lại*: mục 3 là mục thầy đã hỏi mà bạn chưa trả lời được → làm trước và chuẩn bị kỹ phần giải thích.

---

## Mục 3 + A — Hoàn/đổi và kho: "sản phẩm lỗi bị đổi nó vẫn bán được"

> Đây là mục quan trọng nhất. Gộp luôn việc bạn báo: *"luồng hoàn trả không backup lại được"*.

### Nguyên nhân (đã xác minh trong code)

**1. Duyệt hoàn/đổi không làm gì với kho.** `ReturnAdminController::update()` chỉ đổi `status`,
ghi `admin_note`, `reviewed_at`, rồi lưu đánh giá hư hỏng. **Không có một dòng nào chạm tới
`inventories`** — kể cả khi chuyển sang `COMPLETED`.

**2. Không có kho riêng cho hàng lỗi.** Migration `2026_08_04_151000` vừa **gộp kho `RETURN` về
`NORMAL`** — tức là vô tình xóa mất đúng cơ chế cần cho nghiệp vụ này.

**3. Bán hàng cũng chưa trừ kho** (mục [C-02](10-ket-qua-audit.md#c-02)). `createSaleOutTransaction()`
sinh chứng từ `SALE_OUT` nhưng không gọi `Inventory::decrement()`.

→ Kết quả: hàng đổi về không vào đâu cả, hàng giao đi không trừ đi. Tồn kho **không phản ánh gì**.

### Thiết kế đề xuất

Ý tưởng cốt lõi: **tách "hàng bán được" và "hàng lỗi" thành hai kho khác nhau.**
Tồn hiển thị cho khách chỉ đếm kho bán được.

```
        KHO BÁN (NORMAL)                    KHO LỖI (QUARANTINE)
        ─ tính vào tồn bán ─                ─ KHÔNG tính vào tồn bán ─

Nhập hàng NCC ──> [KHO BÁN]
                     │
                     │ (1) Khách đặt & giao hàng
                     │     phiếu SALE_OUT: trừ 1
                     ▼
                  Khách hàng
                     │
                     │ (2) Hàng lỗi, khách yêu cầu ĐỔI
                     │     phiếu RETURN_IN: +1 vào KHO LỖI
                     ▼
              [KHO LỖI] ─────────────────────────┐
                     │                            │
                     │ (4a) sửa được              │ (4b) không sửa được
                     │ phiếu TRANSFER             │ phiếu DAMAGE_OUT
                     ▼                            ▼
                [KHO BÁN]                      Ghi giảm/hủy
                (lúc này mới bán lại được)

                     │ (3) Xuất 1 cái MỚI cho khách
        [KHO BÁN] ───┘  phiếu EXCHANGE_OUT: trừ 1
```

### Việc cần làm

| Bước | Nội dung |
|---|---|
| 3.1 | Thêm lại loại kho `QUARANTINE` (hoặc khôi phục `RETURN`); tạo 1 kho lỗi mặc định trong seed |
| 3.2 | Sửa **mọi** truy vấn tồn kho: chỉ cộng kho có `type = 'NORMAL'` (`ProductController::show`, `WarehouseAdminController`, `DashboardController`, `ReportAdminController`, `ProductAdminController`) |
| 3.3 | Bổ sung 3 loại phiếu: `RETURN_IN`, `EXCHANGE_OUT`, `DAMAGE_OUT` vào enum `stock_transactions.type` |
| 3.4 | `ReturnAdminController::update()`: khi `status = 'RECEIVED'` → sinh `RETURN_IN` (+1 kho lỗi). Khi `COMPLETED` + `type = 'EXCHANGE'` → sinh `EXCHANGE_OUT` (−1 kho bán) |
| 3.5 | Thêm nút "Chuyển về kho bán" (TRANSFER) và "Hủy hàng lỗi" (DAMAGE_OUT) ở màn hình chi tiết hoàn/đổi — nối với kết quả đánh giá hư hỏng 8 bộ phận đã có |
| 3.6 | Làm luôn [C-02](10-ket-qua-audit.md#c-02): cho `createSaleOutTransaction()` trừ kho thật — **nếu không làm, toàn bộ mục 3 vẫn sai số** |
| 3.7 | Ghi `reviewed_by`, bọc `saveDamageAssessments()` trong transaction ([M-12, L-06](10-ket-qua-audit.md)) |

> Bước **3.6 là bắt buộc**. Không thể vá riêng luồng hoàn trả khi luồng bán ra vẫn chưa trừ kho —
> nói với thầy là "đã xử lý hoàn trả" mà tồn vẫn sai thì sẽ bị hỏi tiếp.

### 📣 Cách trả lời thầy

> "Dạ, vấn đề là khi khách đổi một sản phẩm bị lỗi, có **hai** món hàng cùng di chuyển chứ không
> phải một: món lỗi đi về kho, và món mới đi ra cho khách. Nếu em cho món lỗi quay thẳng lại kho
> bán thì hệ thống sẽ tưởng còn hàng tốt và bán lại đúng cái kính hỏng đó cho người khác.
>
> Nên em tách làm hai kho: **kho bán** và **kho lỗi**. Hàng khách đổi về sẽ nhập vào kho lỗi —
> tồn kho hiển thị cho khách không tính kho này, nên không ai mua trúng hàng lỗi. Đồng thời em
> xuất một cái mới từ kho bán giao cho khách, nên tồn bán giảm đúng 1.
>
> Sau đó nhân viên đánh giá mức hư hỏng — em đã có sẵn bảng đánh giá 8 bộ phận của gọng kính.
> Nếu sửa được thì lập phiếu chuyển từ kho lỗi sang kho bán, **lúc đó món đó mới bán lại được**.
> Nếu hỏng nặng thì lập phiếu hủy, ghi giảm tài sản.
>
> Nhờ vậy sản phẩm vẫn bán được bình thường — chỉ riêng **cái đơn vị hàng bị lỗi** là tạm khóa
> lại chờ xử lý, chứ không khóa cả mã sản phẩm."

Nếu thầy hỏi thêm *"sao không cho quay lại kho bán luôn cho nhanh?"*:

> "Vì như vậy là bán hàng lỗi cho khách tiếp theo ạ. Ngoài ra kế toán cũng cần biết bao nhiêu
> hàng đang hỏng chờ xử lý để tính chi phí bảo hành — nếu trộn chung vào kho bán thì mất con số đó."

---

## Mục 2 — Chậm 10 giây khi thêm sản phẩm / xác nhận thanh toán

### ⚠️ Phải đo trước, đừng đoán

10 giây là con số rất đặc trưng — thường là **timeout mạng**, không phải truy vấn chậm.
Đo trước rồi sửa, nếu không sẽ tối ưu nhầm chỗ.

```bash
composer require --dev barryvdh/laravel-debugbar
```
Debugbar hiện thời gian từng truy vấn + tổng thời gian request. Hoặc đo thô:

```php
// Tạm thêm vào routes/web.php để xác định request nào chậm
DB::listen(fn ($q) => $q->time > 100 && logger()->warning("SLOW SQL {$q->time}ms: {$q->sql}"));
```

### Giả thuyết xếp theo khả năng

**H1 — Gửi email SMTP đồng bộ (khả năng cao nhất, giải thích được đúng ~10 giây)**

`CheckoutController::store()` gọi `$orderConfirmationEmail->send($order)`. Bên trong dùng
`QueuedRawMail`, mà class này có fallback:

```php
try {
    SendRawMailJob::dispatch(...);      // nếu dispatch NÉM LỖI...
    return;
} catch (\Throwable $e) { Log::warning(...); }

LaravelMail::raw(...);                  // ...thì gửi thẳng qua SMTP — CHẶN REQUEST
```

`.env` của bạn đang là `DB_CONNECTION=sqlite` + `QUEUE_CONNECTION=database`. Nếu bảng `jobs`
chưa tồn tại trong file SQLite → `dispatch()` ném lỗi → rơi xuống fallback → **kết nối
smtp.gmail.com mất 5–15 giây**. Khớp chính xác triệu chứng.

Kiểm tra:
```bash
php artisan tinker --execute="echo Schema::hasTable('jobs') ? 'CO' : 'KHONG CO';"
tail -50 storage/logs/laravel.log | grep "Queued raw mail dispatch failed"
```
Nếu log có dòng đó → đúng H1.

**Cách sửa (chọn 1):**
- **Nhanh nhất:** `php artisan migrate` để tạo bảng `jobs`, rồi chạy `php artisan queue:work`
  ở một cửa sổ terminal riêng khi demo.
- **Chắc chắn nhất — gửi email sau khi đã trả response:**
  ```php
  // Trong QueuedRawMail::raw(), thay nhánh fallback:
  app()->terminating(fn () => LaravelMail::raw($body, ...));
  ```
  Cách này request trả về ngay, email gửi sau — **không cần queue worker**, rất hợp cho demo.

> 🔴 Lưu ý quan trọng khi lên production: nếu `dispatch()` **thành công** mà **không có worker
> chạy** thì email nằm mãi trong bảng `jobs` và **không bao giờ được gửi** — kể cả email xác
> nhận hủy đơn. Chi tiết ở [11 §11.1](11-ke-hoach-bao-tri-trien-khai.md).

**H2 — "Thêm sản phẩm" chậm vì trang danh sách sau khi redirect**

`store()` xong sẽ `redirect()->route('admin.products.index')`. Trang đó chạy **3 truy vấn con
tương quan cho từng dòng** (tồn kho, đã bán, giá cao nhất) × 15 sản phẩm = **45 subquery**,
trên SQLite không có index → chậm. Đây có thể là phần lớn thời gian bạn thấy.

Sửa: đổi 3 subquery tương quan thành 3 `leftJoin` với bảng đã gộp sẵn (giống cách
`ReportAdminController` đang làm), hoặc bỏ cột `sold_quantity` khỏi danh sách.

**H3 — Đang chạy SQLite**

`.env` ghi `DB_CONNECTION=sqlite`, trong khi dự án yêu cầu MySQL (`composer.json` require
`ext-pdo_mysql`) và **nhiều truy vấn là MySQL-only** (`MONTH()`, `CURRENT_DATE()`,
`DATE_SUB()`, UPDATE-JOIN). SQLite còn khóa toàn file khi ghi → mọi thao tác ghi bị nối đuôi nhau.

**Nên chuyển hẳn sang MySQL cho môi trường chạy demo** — vừa nhanh hơn, vừa tránh các trang
báo cáo bị lỗi SQL.

**H4 — Upload ảnh**

`storeUpload()` gọi `mkdir()` + `$file->move()` vào `public/`. Trên Windows + phần mềm diệt
virus quét file mới, thao tác này có thể mất 1–3 giây/ảnh × 4 ảnh (thumbnail + 3 gallery).
Ít khả năng là nguyên nhân chính nhưng cộng dồn.

### Thứ tự làm
1. Bật Debugbar, đo lại — ghi lại con số trước/sau để đưa vào báo cáo
2. Xử lý H1 (`terminating()`)
3. Chuyển sang MySQL (H3)
4. Nếu vẫn chậm → tối ưu truy vấn danh sách sản phẩm (H2)

### 📣 Cách trả lời thầy

> "Dạ em đo lại bằng Debugbar thì thấy phần lớn thời gian không nằm ở truy vấn CSDL mà ở bước
> **gửi email xác nhận đơn hàng** — hệ thống đang chờ kết nối tới máy chủ Gmail xong mới trả kết
> quả cho người dùng. Em đã chuyển việc gửi mail sang chạy **sau khi đã trả trang cho khách**,
> nên thời gian phản hồi giảm từ ~10 giây xuống còn dưới 1 giây. Riêng trang danh sách sản phẩm
> em gộp lại các truy vấn con thành phép nối bảng nên cũng nhanh hơn."

---

## Mục 5 — Menu "Đơn hàng" thiếu mục

### Nguyên nhân (đã xác minh)

`resources/views/admin/layouts/app.blade.php:173` — "Đơn hàng" đang là **link phẳng**, chỉ trỏ
tới `admin.orders.index`:

```blade
<a href="{{ route('admin.orders.index') }}" class="nav-item nav-link ...">Đơn hàng</a>
```

Trong khi các route sau **đã tồn tại nhưng không có mục menu nào dẫn tới**:
- `admin.orders.unconfirmed` — `/admin/don-hang/cho-xac-nhan`
- `admin.returns.index` — `/admin/hoan-doi`

### Cách sửa (~15 phút)

Đổi thành dropdown 3 mục, theo đúng mẫu của "Sản phẩm" và "Danh mục" ngay bên trên:

```blade
<div class="nav-item dropdown">
    <a href="#" class="nav-link dropdown-toggle {{ $isRoute('admin.orders.*', 'admin.returns.*') ? 'active' : '' }}"
       data-bs-toggle="dropdown"><i class="fas fa-shopping-basket me-2"></i>Đơn hàng</a>
    <div class="dropdown-menu bg-transparent border-0">
        <a href="{{ route('admin.orders.index') }}"       class="dropdown-item {{ $isRoute('admin.orders.index', 'admin.orders.show') ? 'active' : '' }}">Tất cả đơn hàng</a>
        <a href="{{ route('admin.orders.unconfirmed') }}" class="dropdown-item {{ $isRoute('admin.orders.unconfirmed') ? 'active' : '' }}">Đơn chờ xác nhận</a>
        <a href="{{ route('admin.returns.index') }}"      class="dropdown-item {{ $isRoute('admin.returns.*') ? 'active' : '' }}">Yêu cầu hoàn/đổi</a>
    </div>
</div>
```

### Tiện thể: còn 4 nhóm route khác cũng không có mục menu

| Route | Chức năng | Có trong sidebar? |
|---|---|---|
| `admin.posts.*` | Bài viết + chuyên mục | ❌ **Không** |
| `admin.banners.*` | Banner | ❌ **Không** |
| `admin.home-layout.*` | Bố cục trang chủ | ❌ **Không** |
| `admin.business.*` | Nghiệp vụ (thương hiệu, kho, cửa hàng) | ❌ **Không** |

Bốn chức năng này **chỉ vào được bằng cách gõ URL tay**. Nếu thầy bấm quanh admin mà không thấy
thì sẽ tưởng chưa làm. **Nên thêm hết vào sidebar** — công 30 phút, nhưng làm lộ ra được thêm
4 chức năng đã code sẵn.

> ❓ **Cần bạn xác nhận:** thầy ghi *"nó hiện ra 3 cái là trang sản phẩm tổng, trang danh sách đơn
> hàng đang chờ, trang danh sách đơn hàng hoàn đổi"*. Mình hiểu "trang sản phẩm tổng" là **"trang
> tổng đơn hàng"** (tất cả đơn). Nếu ý thầy là mục khác thì báo lại.

---

## Mục 1 — Tự tính giá bán niêm yết từ giá nhập

### Hiện trạng

`ProductAdminController::validateProduct()` nhận `import_price` và `base_price` **hoàn toàn độc
lập**, không có ràng buộc nào giữa hai giá. `prepareProductData()` chỉ đặt
`$data['import_price'] = $data['import_price'] ?? 0`.

### Cách làm

**Bước 1 — Đưa hệ số vào file cấu hình** (để bảo vệ được, và đổi được không cần sửa code):

```php
// config/pricing.php  (tạo mới)
return [
    // Hệ số giá bán niêm yết = giá nhập × markup
    'markup' => (float) env('PRICING_MARKUP', 1.45),
    // Làm tròn lên tới bội số này cho đẹp số (1.000đ)
    'round_to' => (int) env('PRICING_ROUND_TO', 1000),
];
```

**Bước 2 — Tự điền phía client** (`admin/products/form.blade.php`), vẫn cho sửa tay:

```js
const markup  = @json(config('pricing.markup'));
const roundTo = @json(config('pricing.round_to'));
const importEl = document.querySelector('[name="import_price"]');
const baseEl   = document.querySelector('[name="base_price"]');

importEl?.addEventListener('input', () => {
    if (baseEl.dataset.touched === 'true') return;      // admin đã sửa tay thì không ghi đè
    const v = parseFloat(importEl.value) || 0;
    baseEl.value = v > 0 ? Math.ceil(v * markup / roundTo) * roundTo : '';
});
baseEl?.addEventListener('input', () => { baseEl.dataset.touched = 'true'; });
```

**Bước 3 — Chốt phía server** (không tin client):

```php
// prepareProductData()
if (blank($data['base_price'] ?? null) && filled($data['import_price'] ?? null)) {
    $roundTo = (int) config('pricing.round_to');
    $data['base_price'] = (int) (ceil($data['import_price'] * config('pricing.markup') / $roundTo) * $roundTo);
}
```

**Bước 4 — Cảnh báo khi bán dưới giá vốn** (thầy rất dễ hỏi tới):

```php
'base_price' => ['required', 'numeric', 'min:0', 'gte:import_price'],
'sale_price' => ['nullable', 'numeric', 'min:0', 'lte:base_price'],
```
Kèm thông báo: *"Giá bán niêm yết không được thấp hơn giá nhập."*

**Bước 5 — Hiện tỉ suất lợi nhuận ngay trên form:**
```
Giá nhập 200.000đ → Niêm yết 290.000đ → Lợi nhuận gộp 90.000đ (31,0%)
```
Chi tiết nhỏ này gây ấn tượng tốt và thể hiện bạn hiểu con số mình đang làm.

### 📣 Giải thích hệ số cho thầy

Chọn **1.45 (tức markup 45%)**, lập luận:

> "Dạ em chọn hệ số 1,45 tức là cộng thêm 45% trên giá nhập, tương đương **tỉ suất lợi nhuận gộp
> khoảng 31%** trên giá bán. Con số này để bù các chi phí mà giá nhập chưa gồm:
> chi phí mặt bằng và nhân viên tư vấn, khấu hao máy đo mắt và máy mài tròng, chi phí bảo hành –
> đổi trả (ngành kính tỉ lệ đổi trả cao vì phụ thuộc form mặt), chi phí đóng gói – vận chuyển,
> và chi phí marketing.
>
> Em để hệ số này trong file cấu hình chứ không viết cứng trong code, nên khi giá vốn hay chính
> sách thay đổi thì chỉ cần sửa một chỗ. Người quản trị vẫn sửa tay được giá cho từng sản phẩm —
> hệ thống chỉ **gợi ý** giá mặc định, không ép."

Công thức quy đổi để nếu thầy hỏi ngược:
`Lợi nhuận gộp % = (markup − 1) / markup` → 1,4 → 28,6% · **1,45 → 31,0%** · 1,5 → 33,3%

---

## Mục 6 — Sửa xuất Excel sản phẩm

### Vấn đề hiện tại (`ProductAdminController::exportExcel()`)

| # | Vấn đề | Hậu quả |
|---|---|---|
| 1 | **Không phải file Excel thật** — đang xuất HTML `<table>` đặt tên `.xls` | Excel hiện cảnh báo *"định dạng không khớp phần mở rộng"* mỗi lần mở. **Rất dễ bị thầy soi.** |
| 2 | **Bỏ qua bộ lọc đang áp dụng** — không nhận `$request`, luôn xuất toàn bộ | Admin lọc theo danh mục rồi bấm xuất → ra hết tất cả |
| 3 | Không lọc `status` | Xuất cả sản phẩm trong thùng rác |
| 4 | Số bị Excel hiểu thành chữ | Không cộng/lọc/sắp xếp được trong Excel |
| 5 | `LEFT JOIN product_variants` làm **lặp dòng sản phẩm** theo số biến thể | Cộng cột "đã bán" trong Excel sẽ ra số sai |
| 6 | Subquery tương quan `sold_quantity` chạy cho **từng dòng** | Chậm khi nhiều sản phẩm |
| 7 | Xuất cả cột `description` chứa HTML từ CKEditor | Ô Excel đầy thẻ `<p>`, `<img>` |

### Cách sửa

**Bước 1 — Dùng thư viện Excel thật:**
```bash
composer require maatwebsite/excel
```
```php
// app/Exports/ProductsExport.php
class ProductsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
```
Route đổi đuôi file thành `.xlsx`:
```php
return Excel::download(new ProductsExport($request), 'danh-sach-san-pham-' . now()->format('Ymd-His') . '.xlsx');
```

**Bước 2 — Nhận và áp dụng bộ lọc y hệt `index()`:**
```php
public function exportExcel(Request $request): BinaryFileResponse
```
Dùng chung một hàm dựng query giữa `index()` và `exportExcel()` để hai bên không lệch nhau.

**Bước 3 — Bỏ `description`**, thay bằng các cột có ích cho quản lý:
`Mã SP · Tên · Danh mục · Thương hiệu · Kiểu gọng · Chất liệu · Chống UV · Giá nhập ·
Giá niêm yết · Giá KM · Lợi nhuận gộp · Tồn kho · Đã bán · Trạng thái · Ngày tạo`

**Bước 4 — Quyết định mức chi tiết dòng.** Chọn 1 trong 2, đừng trộn:
- **Theo sản phẩm** (bỏ JOIN biến thể) — cộng số liệu chuẩn, hợp để báo cáo
- **Theo biến thể** (giữ JOIN) — chi tiết hơn, nhưng phải ghi rõ tiêu đề *"mỗi dòng là 1 biến thể"*

Khuyến nghị: làm **2 sheet** trong cùng file — sheet "Tổng hợp" theo sản phẩm, sheet "Chi tiết"
theo biến thể. `maatwebsite/excel` hỗ trợ sẵn qua `WithMultipleSheets`.

**Bước 5 — Định dạng:** cột tiền dùng `#,##0 "đ"`, cột ngày dùng `dd/mm/yyyy`, in đậm dòng tiêu đề,
cố định dòng đầu (`freeze pane`).

---

## Mục B — Thử kính bị lỗi

### ❓ Cần bạn mô tả rõ hơn

Mình chưa sửa được nếu chưa biết lỗi cụ thể. Cho mình biết bạn gặp trường hợp nào:

- **(a)** Mở `/thu-kinh` ra trang lỗi 500?
- **(b)** Trang mở được nhưng camera không bật / trình duyệt không xin quyền?
- **(c)** Camera bật, thấy mặt mình, nhưng **không thấy gọng kính nào**?
- **(d)** Bấm "Lưu kết quả" thì báo lỗi?
- **(e)** Lỗi khác — chụp màn hình + mở **F12 → Console** chụp giúp mình phần chữ đỏ.

### Chẩn đoán sẵn theo từng trường hợp

**Nếu là (c) — nhiều khả năng nhất, và đây là vấn đề đã biết:**

`ProductController::tryOnPayload()` gán:
```php
'hasModel' => trim((string) $product->product_code) !== '',
```
`product_code` được sinh tự động cho **mọi** sản phẩm (`'SP' . YmdHis`) → `hasModel` **luôn `true`**.
Nhưng JS gọi `JEELIZVTOWIDGET.load(product.sku)` — Jeeliz nạp model 3D **theo SKU từ thư viện
của họ**. Một mã như `SP20260715103042` không tồn tại bên Jeeliz → **không có gọng kính nào hiện ra**.

Hệ thống **đã có sẵn** endpoint kiểm tra `tryOnModelCheck` (`/thu-kinh/model-check?sku=...`), view
cũng đã truyền URL qua `data-jeeliz-model-check-url` — nhưng **`public/js/tryon-ai.js` chưa hề gọi nó**.

**Hai cách xử lý:**

| Cách | Nội dung | Công |
|---|---|---|
| **Nhanh — để demo được** | Thêm cột `model_sku` vào bảng `products`, admin nhập tay SKU demo có thật của Jeeliz cho vài sản phẩm. `tryOnPayload` trả `model_sku` thay vì `product_code`, `hasModel = model_sku !== ''` | 3 giờ |
| **Đúng** | Nối `tryOnModelCheck` vào JS: khi chọn sản phẩm thì gọi kiểm tra trước, không có model thì hiện "Sản phẩm này chưa hỗ trợ thử kính" và tắt nút | 4 giờ |

Nên làm **cả hai**: cách nhanh để có sản phẩm thử được khi demo, cách đúng để không hứa suông với
những sản phẩm còn lại.

**Nếu là (a) 500:** kiểm tra `filemtime()` trong `tryon-ai.blade.php` dòng 6, 110, 111 — hàm này
**ném lỗi nếu file không tồn tại**, và ở đây không có `file_exists()` bảo vệ (trong khi
`layouts/app.blade.php:94` thì có). Ba file cần tồn tại:
`public/css/views/tryon-ai.css`, `public/vendor/jeelizGlassesVTOWidget/dist/JeelizVTOWidget.js`,
`public/js/tryon-ai.js`.

**Nếu là (b):** webcam yêu cầu **HTTPS** (hoặc `localhost`). Mở qua IP LAN kiểu `192.168.x.x`
trình duyệt sẽ chặn camera. Header `Permissions-Policy: camera=(self)` đã đúng rồi.

**Nếu là (d):** ảnh chụp từ canvas WebGL thường ra **ảnh đen** nếu widget không bật
`preserveDrawingBuffer`. Code đã ưu tiên `JEELIZVTOWIDGET.capture_image()` (đúng cách) và chỉ
fallback về `canvas.toDataURL()` — nếu ảnh lưu bị đen thì là do rơi vào nhánh fallback. Ngoài ra
`storeTryOnSnapshot()` từ chối nếu ảnh giải mã ra **nhỏ hơn 1 KB**.

---

## Mục 4 — Thiết kế "đỡ bị AI"

### Vì sao giao diện bị nhận ra là AI làm

Các dấu hiệu điển hình — đối chiếu với `public/css/ui-human.css` và `admin-human.css`
(tên file đã cho thấy có người từng cố xử lý việc này):

| Dấu hiệu | Cách chữa |
|---|---|
| Gradient tím–xanh dương | Dùng **một** màu thương hiệu + màu trung tính. Đã có `#1b4ea0` và `#c41e3a` trong `layouts/app.blade.php` — bám theo đó |
| Bo góc lớn đều tăm tắp ở mọi thứ | Phân cấp: thẻ 8px, nút 4px, ảnh 0px |
| Emoji làm icon | Đã dùng FontAwesome rồi — bỏ hết emoji còn sót |
| Đổ bóng đều mọi phần tử | Chỉ đổ bóng thứ nổi lên trên (dropdown, modal); thẻ tĩnh dùng viền 1px |
| Chữ "Lorem ipsum" / ảnh stock chung chung | **Ảnh kính thật, tên sản phẩm thật, giá thật** |
| Khoảng cách tùy hứng | Chọn thang 4/8/16/24/32px và bám chặt |
| Mọi phần đều căn giữa | Nội dung dài căn trái — dễ đọc hơn và trông "người" hơn |

### Ba việc cho hiệu quả cao nhất

1. **Thay toàn bộ ảnh mẫu bằng ảnh kính thật** — đây là thứ tạo khác biệt lớn nhất, hơn mọi
   chỉnh sửa CSS. Repo đang có ảnh trong `public/upload/anh_san_pham/`.
2. **Thống nhất bảng màu.** Hiện `layouts/app.blade.php` có ~40 dòng CSS inline đè lên
   `style.css` — gom hết vào một file, khai báo biến CSS:
   ```css
   :root { --brand: #1b4ea0; --sale: #c41e3a; --ink: #1f2937; --line: #e5e7eb; }
   ```
3. **Thêm chi tiết đặc thù ngành kính** mà AI không tự nghĩ ra: bảng thông số tròng
   (đã có `lens_sizes` với `bridge_width`, `temple_length`, `lens_width`, `lens_height`),
   hướng dẫn đo PD, gợi ý gọng theo dáng mặt. **Nội dung chuyên ngành làm giao diện "có nghề"
   hơn bất kỳ hiệu ứng CSS nào.**

> Gợi ý: đổi tên `ui-human.css` → `theme.css`, `admin-human.css` → `admin.css`.
> Tên file hiện tại vô tình tự tố cáo.

---

## Thứ tự thực hiện đề xuất

```
Tuần 1
├─ Ngày 1     Mục 5 (menu) + Mục 1 (giá bán)          ← nhanh, thấy kết quả ngay
├─ Ngày 2     Mục 2 (hiệu suất) — đo rồi sửa
└─ Ngày 3-5   Mục 3 + A (hoàn/đổi + kho)              ← quan trọng nhất, làm khi còn nhiều thời gian

Tuần 2
├─ Ngày 1     Mục 6 (Excel)
├─ Ngày 2     Mục B (thử kính) — sau khi bạn xác nhận lỗi cụ thể
└─ Ngày 3-4   Mục 4 (thiết kế)
```

Lý do xếp mục 5 và 1 lên đầu: làm nhanh, dễ thấy, và **có cái để báo cáo tiến độ ngay** nếu thầy
hỏi lại sớm. Mục 3 cần nhiều thời gian nhất nên phải bắt đầu khi vẫn còn dư địa.

---

## Việc cần bạn xác nhận trước khi mình code

1. **Thử kính lỗi gì?** — chọn (a)–(e) ở Mục B, kèm ảnh chụp Console nếu có.
2. **Mục 5** — "trang sản phẩm tổng" có phải ý là **trang tổng đơn hàng** không?
3. **Hệ số giá** — thầy muốn 1.4, 1.5, hay để mình chọn 1.45 và giải thích như trên?
4. **Excel** — muốn 1 sheet theo sản phẩm, hay 2 sheet (tổng hợp + chi tiết biến thể)?
5. **Đang chạy SQLite hay MySQL?** `.env` ghi `sqlite`, nhưng nhiều truy vấn trong dự án là
   MySQL-only → các trang **Báo cáo** và **Tổng quan** nhiều khả năng đang lỗi. Cần thống nhất
   trước khi tối ưu hiệu suất.
