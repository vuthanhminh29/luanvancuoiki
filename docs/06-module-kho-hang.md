# 06 — Module Kho hàng & Tồn kho

File chính: `app/Http/Controllers/Admin/WarehouseAdminController.php` (**402 dòng — file lớn
nhất dự án**), cùng các model `Warehouse`, `Inventory`, `StockTransaction`, `StockTransactionItem`.

---

> **Đơn giản hóa lớn trong đợt cập nhật `ee3dfa5`:** bỏ hẳn `reserved_quantity`, bỏ loại phiếu
> `TRANSFER`, gộp kho `RETURN` về `NORMAL`, thêm loại phiếu `SALE_OUT` sinh tự động từ đơn hàng.

## 6.1. Mô hình tồn kho (đã cập nhật)

```
Warehouse (kho)
    │  type: NORMAL | WARRANTY | STORE        ← RETURN đã bị gộp về NORMAL
    │  capacity, min_stock_level, địa chỉ
    │
    └──< Inventory >── ProductVariant
           quantity            (tồn thực tế — DUY NHẤT)
           min_stock_level     (ngưỡng cảnh báo riêng của dòng này)
                               ← reserved_quantity ĐÃ BỊ XÓA

StockTransaction (phiếu kho)
    │  type: IMPORT | EXPORT | SALE_OUT | DAMAGE   ← bỏ TRANSFER, thêm SALE_OUT
    │  source_warehouse_id, target_warehouse_id, related_order_id
    │  status, created_by, confirmed_by, confirmed_at
    │
    └──< StockTransactionItem
           variant_id, ordered_quantity, actual_quantity, unit_cost
```

**Tồn khả dụng** nay chỉ đơn giản là `quantity`.
**Cảnh báo sắp hết**: `quantity <= COALESCE(min_stock_level, 10)`.

### Migration `2026_08_04_152000` — xóa `reserved_quantity`

```php
DB::table('inventories')->where('reserved_quantity', '>', 0)
    ->update(['quantity' => DB::raw('quantity + reserved_quantity'), ...]);   // gộp về quantity

Schema::table('inventories', fn ($t) => $t->dropColumn('reserved_quantity'));

DB::statement('ALTER TABLE `inventories` ADD CONSTRAINT `chk_inventories_quantity` CHECK (`quantity` >= 0)');
```

Ba điểm đáng ghi nhận:
- **Cộng dồn trước khi xóa** — không mất số liệu ✅
- **`dropReservedQuantityChecks()`** quét `information_schema.CHECK_CONSTRAINTS` để gỡ mọi CHECK
  cũ tham chiếu tới cột sắp xóa, tránh lỗi ràng buộc ✅
- **Thêm `CHECK (quantity >= 0)`** — lớp bảo vệ cuối ở tầng CSDL, rất hữu ích khi cài logic trừ
  kho (C-02) ✅

> ⚠️ Toàn bộ migration này là **MySQL-only** (`information_schema.CHECK_CONSTRAINTS`,
> `ALTER TABLE ... DROP CHECK`). `down()` chỉ thêm lại cột rỗng, **không tách ngược số liệu** —
> rollback sẽ mất thông tin giữ chỗ (chấp nhận được vì tính năng này chưa từng hoạt động).

Mọi truy vấn `(quantity - reserved_quantity)` trong `WarehouseAdminController`,
`DashboardController`, `ReportAdminController`, `ProductAdminController`, `ProductController`
**đã được đổi đồng bộ** sang `quantity` ✅.

---

## 6.2. 🟠 Vấn đề trung tâm: tồn kho vẫn tách rời khỏi bán hàng

Đây vẫn là lỗi nghiêm trọng nhất của dự án, dù đợt cập nhật đã đi được **nửa đường**.

### Đã cải thiện

- ✅ `reserved_quantity` — cột chết — **đã bị xóa hẳn** thay vì để đó gây hiểu nhầm.
- ✅ `stock_transactions.related_order_id` — cột chết — **nay đã được dùng thật**.
- ✅ Có thêm loại phiếu **`SALE_OUT`** sinh **tự động** khi đơn chuyển `DELIVERING`
  (`OrderAdminController::createSaleOutTransaction()`, xem [05](05-module-don-hang-hoan-doi.md) §5.2).
- ✅ CSDL có `CHECK (quantity >= 0)` — sẵn sàng cho logic trừ kho.

### Vẫn chưa giải quyết

**Bảng `inventories` vẫn chỉ được GHI ở đúng 2 hàm**, cả hai trong `WarehouseAdminController`:

| Hàm | Thao tác |
|---|---|
| `addVariantInventory()` | `increment('quantity')` hoặc `Inventory::create(...)` |
| `subtractVariantInventory()` | `decrement('quantity')` |

Cả hai **chỉ được gọi từ `storeTransaction()`** — chỉ khi admin lập phiếu nhập/xuất thủ công.

`createSaleOutTransaction()` **không gọi hàm nào trong hai hàm này**, cũng không có
`Inventory::decrement()` riêng. Nó chỉ tạo bản ghi `StockTransaction` + `StockTransactionItem`.

| Sự kiện | Có trừ tồn kho không? |
|---|---|
| Khách thêm vào giỏ hàng | ❌ Không |
| Khách đặt hàng COD | ❌ **Không** |
| Khách thanh toán VNPay thành công | ❌ **Không** |
| Admin chuyển đơn sang `DELIVERING` | ❌ **Không** — chỉ sinh chứng từ `SALE_OUT` |
| Admin hủy đơn / duyệt hoàn hàng | ❌ Không |
| Admin lập phiếu nhập/xuất thủ công | ✅ Có |

### Hệ quả (nay còn tệ hơn về mặt đối soát)

1. **Bán vượt kho không giới hạn** — nguyên vẹn như trước.
2. **Sổ sách mâu thuẫn ngay bên trong hệ thống**: bảng `stock_transactions` ghi "đã xuất bán N
   sản phẩm cho đơn #X", nhưng `inventories.quantity` không hề giảm. Trước đây ít nhất hệ thống
   *im lặng*; nay nó **tự mâu thuẫn với chính mình**.
3. `ProductController::show()` nay hiển thị `$variantStock` **cho khách hàng** — nghĩa là con số
   sai này đã đi ra tới giao diện người dùng cuối.
4. `ProductVariant::status = 'OUT_OF_STOCK'` vẫn phải đặt hoàn toàn bằng tay.

Xem [10 — Audit](10-ket-qua-audit.md) mục **C-02** để biết hướng khắc phục cụ thể.

---

## 6.3. `index()` — Màn hình kho hàng (dòng 20–127)

Một màn hình 3 tab (`stock` / `warehouses` / `transactions`) với bộ lọc riêng cho từng tab.

### Bộ lọc tồn kho (tiền tố `inventory_`)

| Tham số | Xử lý |
|---|---|
| `inventory_warehouse_id` | `where('warehouse_id', ...)` |
| `inventory_category_id` | `whereHas('variant.product', ...)` |
| `inventory_keyword` | LIKE trên SKU, tên SP, mã SP, tên màu, tên size |
| `inventory_stock_state` | `OUT` / `LOW` / `OK` — dùng `whereRaw` với công thức tồn khả dụng |
| `inventory_limit` | Kẹp trong `[25, 500]`, mặc định 200 |

### Bộ lọc phiếu kho (tiền tố `stock_`)
`stock_type`, `stock_status`, `stock_warehouse_id` (khớp nguồn **hoặc** đích),
`stock_date_from`, `stock_date_to`, `stock_keyword` (mã phiếu / ghi chú),
`stock_limit` kẹp `[25, 300]`, mặc định 100.

### Chọn tab đang hiển thị (dòng 100–106)

```php
$activeTab = $request->input('warehouse_tab', 'stock');
if (collect($request->query())->keys()->contains(fn($k) => str_starts_with((string) $k, 'stock_')))
    $activeTab = 'transactions';
if (! in_array($activeTab, ['stock','warehouses','transactions'], true))
    $activeTab = 'stock';
```
Tự chuyển sang tab "phiếu kho" khi phát hiện bất kỳ tham số `stock_*` nào — UX thông minh ✅.
Whitelist giá trị tab ✅.

### Đánh giá

✅ Toàn bộ giá trị lọc đi qua query builder với binding — **không có SQL injection**.
Các `whereRaw` chỉ chứa tên cột và hằng số, không nội suy input.
✅ Giới hạn kết quả có kẹp trên/dưới — không thể yêu cầu 1 triệu dòng.

⚠️ **Dùng `limit()` thay vì `paginate()`** cho cả 3 danh sách. Với 200 dòng tồn kho ×
eager-load `warehouse`, `variant.product.category`, `variant.color`, `variant.lensSize` —
mỗi lần mở màn hình kho là một truy vấn nặng, và **không có cách nào xem quá 500 dòng**.

⚠️ Có tới **4 truy vấn tổng hợp riêng biệt** trên cùng bảng (`$summary` với 6 hàm SUM, cộng
`transactionItemTotals` group-by toàn bảng `stock_transaction_items`, không lọc gì).
`transactionItemTotals` **quét toàn bộ bảng** kể cả khi chỉ hiển thị 100 phiếu.

---

## 6.4. `createTransaction()` — Form lập phiếu (dòng 149–204)

Dựng danh sách **toàn bộ** biến thể (trừ sản phẩm `DISCONTINUED`) qua một query builder thô
với 4 JOIN + GROUP BY 12 cột, kèm tồn khả dụng.

> ⚠️ **Không phân trang, không lọc.** Toàn bộ catalog được nạp vào một mảng PHP rồi đổ vào
> view (dưới dạng JSON cho JS). Với 5.000 biến thể, đây là vài MB HTML mỗi lần mở form.

> ⚠️ `GROUP BY` liệt kê 12 cột — nếu MySQL bật `ONLY_FULL_GROUP_BY` (mặc định từ 5.7) thì
> phải khớp chính xác; hiện tại có vẻ đủ, nhưng thêm cột SELECT nào là vỡ ngay.

Giá đề xuất được suy theo thang:
```php
'price'     => import_price ?: variant_price ?: sale_price ?: base_price ?: 0
'salePrice' => sale_price ?: variant_price ?: base_price ?: 0
```
Lại thêm **2 công thức giá nữa** khác với `display_price` của model — tổng cộng dự án có
**5 cách tính giá khác nhau**. Xem [03](03-module-san-pham.md) §3.1.

---

## 6.5. `storeTransaction()` — Lập phiếu kho (dòng 206–308)

Đây là hàm nghiệp vụ được viết **cẩn thận nhất** trong dự án.

### Validate

```php
transaction_code  → nullable|string|max:50|unique:stock_transactions,transaction_code
type              → required|in:IMPORT,EXPORT,TRANSFER
source_warehouse_id / target_warehouse_id → nullable|exists:warehouses,id
expected_date     → nullable|date
note              → nullable|string|max:1000
variant_id        → required|array|min:1
variant_id.*      → required|integer|exists:product_variants,id
quantity          → required|array|min:1
quantity.*        → required|integer|min:1
unit_cost.*       → nullable|numeric|min:0
```

### Quy tắc kho theo loại phiếu (đã đơn giản hóa)

| Loại | Kho nguồn | Kho đích |
|---|---|---|
| `IMPORT` | **ép về `null`** | **bắt buộc**, phải ACTIVE |
| `EXPORT` | **bắt buộc**, phải ACTIVE | **ép về `null`** |
| ~~`TRANSFER`~~ | **đã bị loại bỏ** | |

`TRANSFER` bị gỡ khỏi rule validate (`in:IMPORT,EXPORT`), và migration
`2026_08_04_153000` **chuyển mọi phiếu `TRANSFER` cũ thành `IMPORT`**:
```php
DB::table('stock_transactions')
    ->whereNotIn('type', ['IMPORT', 'EXPORT', 'SALE_OUT', 'DAMAGE'])
    ->update(['type' => 'IMPORT']);
```
> ⚠️ Migration này **không thể rollback** (`down()` để trống, có ghi chú rõ). Phiếu chuyển kho
> lịch sử sẽ vĩnh viễn hiển thị sai loại là "nhập kho". Chấp nhận được nếu dữ liệu thật chưa có
> phiếu TRANSFER nào, nhưng cần xác nhận trước khi chạy trên production.
>
> ⚠️ Loại `DAMAGE` xuất hiện trong danh sách whitelist của migration nhưng **không có code nào
> tạo ra phiếu loại này** — chuẩn bị trước cho tương lai.

Việc **ép `null`** thay vì báo lỗi khi người dùng chọn nhầm kho là thay đổi hành vi: phiếu nhập
có chọn kho nguồn sẽ âm thầm bỏ qua lựa chọn đó thay vì cảnh báo.

`assertActiveWarehouse()` kiểm tra kho tồn tại **và** đang `ACTIVE`, ném `ValidationException`
với thông báo tiếng Việt gắn đúng field ✅.

Migration `2026_08_04_154000` còn đổi tên mã phiếu cũ sang tiền tố `PN` cho nhất quán.

### Ghép mảng song song (dòng 246–255)

```php
$items = collect($data['variant_id'])->map(fn($variantId, $index) => [
    'variant_id' => (int) $variantId,
    'quantity'   => (int) ($data['quantity'][$index] ?? 0),
    'unit_cost'  => filled($data['unit_cost'][$index] ?? null) ? (float) ... : null,
])->filter(fn($i) => $i['variant_id'] > 0 && $i['quantity'] > 0)->values();

if ($items->isEmpty()) throw ValidationException::withMessages([...]);
```

> ⚠️ **Ghép 3 mảng song song theo chỉ số** vẫn là mô hình dễ vỡ. Nếu trình duyệt gửi thiếu một
> phần tử `quantity[]` (ví dụ input bị disable), các dòng sau **lệch chỉ số** và ghép nhầm
> số lượng cho biến thể khác — âm thầm, không có lỗi nào. Nên gửi dạng `items[0][variant_id]`.
>
> Đợt cập nhật có làm nhẹ vấn đề: bộ lọc đổi từ `variant_id > 0 && quantity > 0` thành chỉ
> `variant_id > 0`, rồi **kiểm tra riêng** `quantity < 1` và báo lỗi rõ ràng — trước đây dòng
> thiếu số lượng bị **âm thầm loại bỏ**, nay được báo:
> ```php
> if ($items->contains(fn ($item) => $item['quantity'] < 1)) {
>     throw ValidationException::withMessages(['quantity' => 'Số lượng nhập hoặc xuất kho phải tối thiểu là 1.']);
> }
> ```

✅ **Đã chặn trùng `variant_id` trong cùng phiếu (M-17 khắc phục):**
```php
if ($items->pluck('variant_id')->duplicates()->isNotEmpty()) {
    throw ValidationException::withMessages([
        'variant_id' => 'Mỗi biến thể sản phẩm chỉ được chọn một lần trong cùng phiếu kho.',
    ]);
}
```

### Transaction (dòng 263–303)

```php
DB::transaction(function () {
    if (type in [EXPORT, TRANSFER])  foreach items → subtractVariantInventory(source, ...)
    if (type in [IMPORT, TRANSFER])  foreach items → addVariantInventory(target, ...)
                                                   + activateVariantProduct(...)
    $transaction = StockTransaction::create([... 'status' => 'COMPLETED',
                                              'created_by' => Auth::id(),
                                              'confirmed_by' => Auth::id(),
                                              'confirmed_at' => now() ]);
    foreach items → $transaction->items()->create([...]);
});
```

Thứ tự đúng: **trừ kho nguồn trước** (có thể ném lỗi → rollback), sau đó cộng kho đích ✅.

> ⚠️ **Phiếu luôn được tạo với `status = 'COMPLETED'` và tự duyệt bởi chính người lập**
> (`created_by === confirmed_by`). Không có luồng "chờ duyệt". `expected_date` được lưu nhưng
> vô nghĩa vì phiếu đã hoàn tất ngay. Cột `ordered_quantity` và `actual_quantity` **luôn bằng
> nhau** — mô hình CSDL cho phép nhập thiếu/thừa so với đặt hàng, nhưng giao diện không hỗ trợ.

---

## 6.6. `subtractVariantInventory()` / `addVariantInventory()` — dòng 317–356

```php
private function subtractVariantInventory(int $warehouseId, int $variantId, int $quantity): void
{
    $inventory = Inventory::query()
        ->where('warehouse_id', $warehouseId)->where('variant_id', $variantId)
        ->lockForUpdate()->first();                              // ← khóa hàng ✅

    $available = $inventory ? max(0, $inventory->quantity - $inventory->reserved_quantity) : 0;

    if ($available < $quantity)
        throw ValidationException::withMessages(['quantity' => 'Số lượng xuất vượt quá tồn kho khả dụng.']);

    $inventory->decrement('quantity', $quantity);
}
```

✅ **`lockForUpdate()` bên trong `DB::transaction`** — chống race condition đúng cách. Đây là
nơi duy nhất trong dự án dùng khóa bi quan cho tồn kho.
✅ Kiểm tra tồn khả dụng trước khi trừ, không cho âm kho.
✅ `addVariantInventory` tự tạo dòng `Inventory` mới nếu chưa có, lấy `min_stock_level` mặc
định từ kho (fallback 10).

> ⚠️ **`lockForUpdate()->first()` trả `null` thì không khóa được gì.** Trong
> `addVariantInventory`, nếu 2 phiếu nhập đồng thời cho cùng cặp (kho, biến thể) chưa có dòng
> `Inventory`, cả hai đều thấy `null` và cùng gọi `Inventory::create()` → **2 dòng trùng lặp**
> cho cùng cặp khóa. Trừ khi CSDL có UNIQUE INDEX trên `(warehouse_id, variant_id)` — điều này
> **không thể xác nhận** vì bảng `inventories` không có migration trong repo. Các truy vấn báo
> cáo dùng `SUM()` nên vẫn ra số đúng, nhưng `subtractVariantInventory` chỉ lấy `first()` →
> sẽ trừ nhầm dòng.

> ⚠️ Với trường hợp **trùng `variant_id` trong cùng phiếu** (§6.5): vòng lặp gọi
> `subtractVariantInventory` 2 lần liên tiếp. Lần 1 trừ và commit vào transaction hiện tại,
> lần 2 đọc lại giá trị đã trừ → thực ra **vẫn đúng**, vì cùng transaction. Rủi ro thấp hơn dự đoán.

---

## 6.7. `activateVariantProduct()` — dòng 358–367

```php
ProductVariant::whereKey($variantId)->update(['status' => 'ACTIVE']);

DB::table('products')
    ->join('product_variants', 'product_variants.product_id', '=', 'products.id')
    ->where('product_variants.id', $variantId)
    ->whereIn('products.status', ['DRAFT', 'INACTIVE'])
    ->update(['products.status' => 'ACTIVE']);
```

Khi nhập hàng, **tự động kích hoạt** biến thể và (nếu sản phẩm đang `DRAFT`/`INACTIVE`) kích
hoạt luôn sản phẩm. Ý đồ hợp lý: "có hàng thì cho bán".

> 🔴 **Nhưng đây là hành vi phụ ẩn nguy hiểm.** Admin cố ý ẩn một sản phẩm (`INACTIVE`, ví dụ
> vì sai mô tả hoặc đang tranh chấp), rồi nhập thêm hàng → **sản phẩm tự động hiện lại trên
> website** mà không có bất kỳ cảnh báo nào. Trạng thái `DISCONTINUED` được loại trừ ✅ nhưng
> `INACTIVE` thì không nên bị ghi đè.
>
> Tương tự, biến thể bị đặt `OUT_OF_STOCK` hay `DISCONTINUED` **đều bị ép về `ACTIVE`** không
> điều kiện (dòng 360 không có `whereIn` lọc trạng thái như dòng 365).
> Xem [10](10-ket-qua-audit.md) mục **M-15**.

> ⚠️ `DB::table('products')->join(...)->update(['products.status' => ...])` — cú pháp
> UPDATE-JOIN chỉ hoạt động trên MySQL. Sẽ lỗi trên PostgreSQL/SQLite.

---

## 6.8. `transactions()` — dòng 129–147

Màn hình danh sách phiếu kho đơn giản, tái sử dụng view chung `admin.shared.table`:

```php
'rows' => StockTransaction::with([...])->latest()->paginate(20)
    ->through(fn($t) => [$t->transaction_code, $t->type, $t->sourceWarehouse->name ?? '-', ...]),
```

Đây là màn hình **duy nhất trong module có `paginate()`** ✅. Tuy nhiên nó bị **trùng chức
năng** với tab "transactions" của `index()` (giàu tính năng hơn nhiều) — hai màn hình cho
cùng một việc. Route cũ `kho-hang2` trỏ về đây, còn `kho-hang` trỏ về `index()`.

`type` được hiển thị nguyên văn `IMPORT`/`EXPORT`/`TRANSFER` chứ không dịch sang tiếng Việt,
khác với phần còn lại của giao diện.

---

## 6.9. `nextTransactionCode()` — dòng 369–378

```php
$prefix = ['IMPORT' => 'PN', 'EXPORT' => 'PX', 'TRANSFER' => 'DC'][$type] ?? 'STK';
return $prefix . now()->format('YmdHis') . random_int(10, 99);
```

Mã phiếu tiếng Việt: **PN** (phiếu nhập), **PX** (phiếu xuất), **DC** (điều chuyển) ✅.

> ⚠️ Chỉ có 90 giá trị ngẫu nhiên trong cùng một giây → xác suất trùng ~1/90 nếu 2 phiếu tạo
> đồng thời. Khác với `BusinessAdminController::nextCode()` (có vòng lặp `do...while` kiểm tra
> tồn tại), hàm này **không kiểm tra trùng**. Vì `transaction_code` có rule `unique` trong
> validate (chỉ khi người dùng tự nhập) nhưng mã tự sinh thì không qua validate → sẽ ném
> lỗi SQL 500 nếu CSDL có UNIQUE constraint.

---

## 6.10. Các model

### `Warehouse.php` (33 dòng)
`warehouse_code`, `name`, `type` (`NORMAL`/`RETURN`/`WARRANTY`/`STORE`), `capacity`,
địa chỉ (`province_name`, `district_name`, `ward_name`, `address_detail`),
`min_stock_level`, `status`. Scope `active()`. Quan hệ `inventories()`.

> ⚠️ `capacity` được validate bắt buộc khi tạo kho (`BusinessAdminController::saveWarehouse`)
> nhưng **không bao giờ được kiểm tra khi nhập hàng**. Kho sức chứa 100 nhận được 10.000 sản phẩm.

> ⚠️ `type = 'RETURN'` và `'WARRANTY'` được định nghĩa nhưng **không có luồng nghiệp vụ nào
> sử dụng** — hàng hoàn không tự vào kho RETURN (xem [05](05-module-don-hang-hoan-doi.md) §5.4).

### `Inventory.php` (26 dòng)
```php
public const CREATED_AT = null;    // bảng chỉ có updated_at
protected $fillable = ['warehouse_id','variant_id','quantity','reserved_quantity','min_stock_level'];
```
Quan hệ `warehouse()`, `variant()`.

### `StockTransaction.php` (46 dòng)
`const UPDATED_AT = null`. `$casts`: `expected_date` → `date`, `confirmed_at` → `datetime`.
Quan hệ: `sourceWarehouse`, `targetWarehouse`, `items`.
> Cột `related_order_id` có trong `$fillable` — **không bao giờ được ghi**. Đây chính là cột
> lẽ ra dùng để liên kết phiếu xuất kho với đơn hàng, xác nhận rằng luồng tự động trừ kho theo
> đơn **đã được thiết kế nhưng chưa cài đặt**.

### `StockTransactionItem.php` (33 dòng)
`$timestamps = false`. `ordered_quantity`, `actual_quantity`, `unit_cost` (`decimal:2`).
Quan hệ `transaction()` (khóa ngoại `stock_transaction_id`), `variant()`.

---

## 6.11. Tổng kết module

### Điểm tốt ✅
- `storeTransaction()` là hàm nghiệp vụ được viết chỉn chu nhất dự án: validate đầy đủ,
  ràng buộc kho theo loại phiếu rõ ràng, transaction bao trọn, `lockForUpdate()` khi trừ kho,
  không cho tồn âm, thông báo lỗi tiếng Việt gắn đúng field.
- Bộ lọc tồn kho/phiếu kho phong phú, an toàn SQL, có kẹp giới hạn.
- Mô hình dữ liệu (kho nhiều loại, giữ chỗ, ngưỡng cảnh báo, phiếu 3 loại) được thiết kế đúng
  chuẩn ERP thu nhỏ.

### Cần sửa

| Mã | Mức | Trạng thái | Vấn đề |
|---|---|---|---|
| M-17 | TB | ✅ **đã sửa** | Chặn trùng `variant_id`; báo lỗi rõ khi thiếu số lượng |
| L-08 | Thấp | ✅ **đã sửa** | Migration thêm `idx_inventories_variant_warehouse` |
| L-09 | Thấp | ✅ phần lớn | Kho `RETURN` đã gộp về `NORMAL`; `capacity` **vẫn** không được kiểm tra |
| L-10 | Thấp | ✅ phần lớn | Bỏ `TRANSFER`; hai màn hình vẫn còn nhưng đã phân vai rõ hơn |
| — | — | ✅ **đã sửa** | `reserved_quantity` bị xóa hẳn; `related_order_id` nay được dùng |
| **C-02** | **Nghiêm trọng** | 🟠 **một phần** | `SALE_OUT` sinh chứng từ nhưng **không trừ tồn kho** → sổ sách mâu thuẫn với tồn |
| M-15 | TB | ⚠️ chưa sửa | `activateVariantProduct()` ép `ACTIVE` cho biến thể/sản phẩm đã bị ẩn có chủ ý |
| M-16 | TB | ⚠️ chưa sửa | `index()` và `createTransaction()` không phân trang; `transactionItemTotals` quét toàn bảng |
| M-18 | TB | ⚠️ nặng thêm | Migration mới dùng `information_schema.CHECK_CONSTRAINTS` + `DROP CHECK` — MySQL-only |
| L-07 | Thấp | 🟠 một phần | `nextSaleOutTransactionCode()` mới **có** `do...while` ✅; `nextTransactionCode()` cũ vẫn chưa có |
| — | — | mới | Migration `2026_08_04_153000` **không rollback được**; loại `DAMAGE` khai báo nhưng chưa dùng |
