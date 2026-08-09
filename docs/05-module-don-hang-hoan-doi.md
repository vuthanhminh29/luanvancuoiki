# 05 — Module Đơn hàng & Hoàn/Đổi

> **Module được viết lại nhiều nhất trong đợt cập nhật `ee3dfa5`.** `OrderAdminController` tăng
> từ 111 → 363 dòng, `ReturnRequestController` từ 85 → ~185 dòng, thêm mới
> `OrderCancellationService` (295 dòng), `OrderCancellationController`,
> `OrderConfirmationEmailService`, `OrderInvoiceEmailService`.

---

## 5.1. Vòng đời đơn hàng

### State machine (MỚI — `OrderAdminController::STATUS_TRANSITIONS`)

Trước đây không có ràng buộc chuyển tiếp nào. Nay đã có bảng chuyển trạng thái tường minh:

```php
'PENDING'          => ['CONFIRMED', 'CANCELLED'],
'AWAITING_PAYMENT' => ['CONFIRMED', 'CANCELLED'],
'CONFIRMED'        => ['DELIVERING', 'CANCELLED'],
'DELIVERING'       => ['DELIVERED'],
'DELIVERED'        => ['RETURN_PENDING'],
'RETURN_PENDING'   => ['RETURNED', 'EXCHANGED', 'DELIVERED'],
'CANCELLED'        => [],   // trạng thái kết thúc
'RETURNED'         => [],   // trạng thái kết thúc
'EXCHANGED'        => [],   // trạng thái kết thúc
```

Sơ đồ:
```
PENDING ──────┐
              ├──> CONFIRMED ──> DELIVERING ──> DELIVERED ──> RETURN_PENDING ──┬──> RETURNED
AWAITING_PAY ─┘         │                                          ↑          ├──> EXCHANGED
              └─────────┴──> CANCELLED (chỉ từ 3 trạng thái đầu)    └──────────┘ (quay lại DELIVERED)
```

`changeStatus()` bọc toàn bộ trong `DB::transaction` + `lockForUpdate()`, từ chối:
- chuyển sang **chính trạng thái hiện tại** ("Vui lòng chọn trạng thái mới khác trạng thái hiện tại.")
- chuyển sang trạng thái **không nằm trong bảng** ("Không thể chuyển đơn từ trạng thái hiện tại...")

Giao diện chỉ hiển thị các lựa chọn hợp lệ (`availableStatusOptions()` lọc `STATUS_LABELS`
theo `nextStatuses()`) — chặn ở cả tầng UI lẫn tầng logic ✅. **M-13 đã khắc phục.**

`STATUS_LABELS` cũng mới: mỗi trạng thái kèm nhãn tiếng Việt + màu + icon FontAwesome, dùng
chung cho danh sách và trang chi tiết.

> ⚠️ **`LOST_IN_TRANSIT` đã bị loại** khỏi `VALID_STATUSES` và `STATUS_TRANSITIONS`, nhưng
> `ReportAdminController` **vẫn lọc `NOT IN ('CANCELLED', 'LOST_IN_TRANSIT')`** ở mọi truy vấn
> báo cáo. Không gây sai số (điều kiện thừa), nhưng enum chưa được đồng bộ khi refactor.

> ⚠️ **`AWAITING_PAYMENT` là trạng thái không thể tới được.** Nó có nhánh đi ra nhưng không
> trạng thái nào chuyển vào, và không luồng nào (COD, VNPay, draft) đặt đơn ở trạng thái này —
> cả hai đều tạo với `status = 'PENDING'`. Xem [10](10-ket-qua-audit.md) mục **N-08**.

### Enum `orders.payment_status`
`UNPAID` (mặc định khi tạo) → `PAID` (chỉ do VNPay đặt).

> ⚠️ **Không có luồng nào đặt `payment_status = 'PAID'` cho đơn COD.** Admin giao hàng thành
> công, thu tiền mặt, chuyển `status` sang `DELIVERED` — nhưng `payment_status` **vĩnh viễn là
> `UNPAID`**. Hệ quả: mọi đơn COD đã hoàn tất vẫn hiển thị "chưa thanh toán". Xem
> [10](10-ket-qua-audit.md) mục **H-08**.

### Ai đặt trạng thái nào

| Trạng thái | Được đặt bởi |
|---|---|
| `PENDING` | `CheckoutController::createOrder()`, `VnPayController::createPaidOrderFromDraft()`, `markPaid()` |
| `CANCELLED` | **`OrderCancellationService::confirmCancellation()`** (sau khi khách xác nhận qua email), `VnPayController::cancelOrderPayment()` |
| Còn lại | **Chỉ** `OrderAdminController::changeStatus()` — thủ công, theo state machine |

**Thay đổi quan trọng:** admin **không còn hủy đơn trực tiếp**. Bấm "Hủy" nay chỉ gửi email yêu
cầu khách xác nhận — xem §5.2b.

---

## 5.2. `app/Http/Controllers/Admin/OrderAdminController.php` (363 dòng — viết lại)

Controller nay **inject `OrderCancellationService`** qua constructor.

| Method | Route |
|---|---|
| `index()` | `GET /admin/don-hang` |
| `unconfirmed()` | `GET /admin/don-hang/cho-xac-nhan` (mặc định lọc `status=PENDING`) |
| `show()` | `GET /admin/don-hang/{order}` — nay truyền thêm `statusLabels`, `statusOptions`, `canCancelOrder` |
| `updateStatus()` | `PUT /admin/don-hang/{order}/trang-thai` |
| `cancel()` | `PATCH /admin/don-hang/{order}/huy` — nay **chỉ gửi email yêu cầu xác nhận** |

### `orderList()` — ✅ đã sửa H-09

```php
'orders' => $ordersQuery->paginate(20)->withQueryString(),
```
Đã phân trang. Ngoài ra **5 truy vấn `summary` được gộp thành 1** bằng `selectRaw` với các
biểu thức `SUM(CASE WHEN ...)`:
```php
$summaryRow = Order::query()
    ->selectRaw('COUNT(*) as total')
    ->selectRaw("SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) as pending")
    ...->first();
```
Từ 6 truy vấn + nạp toàn bảng → còn 2 truy vấn có giới hạn ✅.

### `updateStatus()` / `changeStatus()` — ✅ đã sửa M-13, H-11

```php
public function updateStatus(Request $request, Order $order): RedirectResponse {
    $data = $request->validate([
        'status' => ['required', Rule::in(self::VALID_STATUSES)],
        'cancel_reason' => ['nullable', 'string', 'max:500'],
    ], [...thông báo tiếng Việt...]);

    if ($data['status'] === 'CANCELLED') {
        return $this->cancel($request, $order);       // ← chuyển sang luồng xác nhận email
    }
    $result = $this->changeStatus($order, $data['status'], ...);
}
```

`changeStatus()` (dòng 128–165):
```php
DB::transaction(function () {
    $lockedOrder = Order::query()->lockForUpdate()->find($order->id);   // ✅ khóa dòng
    ... kiểm tra state machine ...
    $lockedOrder->forceFill([
        'status' => $newStatus,
        'delivered_at' => $newStatus === 'DELIVERED'
            ? ($lockedOrder->delivered_at ?: now())      // ✅ chỉ đặt lần đầu
            : $lockedOrder->delivered_at,
        'note' => ...,
    ])->save();

    if ($newStatus === 'DELIVERING') {
        $this->createSaleOutTransaction($lockedOrder);
    }
});
```

**`delivered_at` nay được lưu thật** — migration `2026_08_04_140000` thêm cột, và `forceFill()`
bỏ qua `$fillable` (cột này vẫn không có trong `$fillable`, nhưng `forceFill` không quan tâm).
Giá trị chỉ đặt **lần đầu** vào `DELIVERED`, quay lại từ `RETURN_PENDING` không ghi đè ✅.
**H-11 đã khắc phục.**

### `createSaleOutTransaction()` — 🟠 chứng từ có, trừ kho chưa có

Khi đơn chuyển `DELIVERING`, hệ thống sinh một `StockTransaction` loại `SALE_OUT`:

```php
$payload = [
    'transaction_code' => $this->nextSaleOutTransactionCode(),   // có do..while chống trùng ✅
    'type' => 'SALE_OUT',
    'source_warehouse_id' => $this->saleOutSourceWarehouseId($order),
    'status' => 'COMPLETED',
    'created_by' => Auth::id(), 'confirmed_by' => Auth::id(), 'confirmed_at' => now(),
];
if ($this->stockTransactionsHaveRelatedOrderId()) {
    $payload['related_order_id'] = $order->id;      // ✅ cột này cuối cùng đã được dùng
}
```

Các điểm làm tốt:
- **Chống tạo trùng** (`saleOutTransactionExists()`): tra theo `related_order_id`, và fallback
  tra theo `note` nếu cột đó chưa tồn tại.
- **`stockTransactionsHaveRelatedOrderId()`** dùng `Schema::hasColumn` cache tĩnh — code chạy
  được trên cả CSDL cũ lẫn mới ✅.
- **`saleOutSourceWarehouseId()`** chọn kho thông minh: ưu tiên kho ACTIVE có tồn cao nhất cho
  các biến thể trong đơn → fallback kho bất kỳ có tồn → fallback kho ACTIVE đầu tiên → kho đầu tiên.

> 🔴 **Nhưng hàm này không hề trừ tồn kho.** Không có `Inventory::decrement()` nào; `Inventory`
> chỉ được **đọc** trong `saleOutSourceWarehouseId()`. So sánh với
> `WarehouseAdminController::storeTransaction()` — nơi phiếu EXPORT gọi `subtractVariantInventory()`
> rõ ràng.
>
> Hệ quả: hệ thống nay sinh chứng từ ghi "đã xuất N sản phẩm" nhưng `inventories.quantity`
> không giảm → **sổ sách và tồn kho mâu thuẫn ngay bên trong hệ thống**. Xem
> [10](10-ket-qua-audit.md) mục **C-02**.

### Vẫn còn thiếu

- 🔴 Hủy đơn (dù nay qua luồng 2 bước) vẫn **không hoàn `used_count`** mã giảm giá, **không
  hoàn tiền** VNPay, **không hoàn tồn kho**.
- ⚠️ **Vẫn không có nhật ký thay đổi trạng thái** (ai, khi nào). Không có `order_status_logs`.
- ✅ **Đã có thông báo cho khách**: `OrderConfirmationEmailService` gửi email khi đặt hàng, và
  email xác nhận hủy khi admin yêu cầu hủy.

---

## 5.2b. Luồng hủy đơn 2 bước (MỚI) — `OrderCancellationService` (295 dòng)

Đây là **đoạn code bảo mật tốt nhất được thêm mới** trong đợt cập nhật.

### Nghiệp vụ

```
Admin bấm "Hủy đơn" + nhập lý do
   │
   ├─ requestCancellation()
   │     ├─ sinh token = Str::random(72)
   │     ├─ lưu hash('sha256', $token) vào orders.cancel_confirmation_token_hash
   │     ├─ lưu cancel_reason + cancel_requested_at
   │     ├─ status VẪN GIỮ NGUYÊN  ← đơn chưa bị hủy
   │     └─ gửi email chứa URL::temporarySignedRoute(hạn 3 ngày)
   │
   └─ Khách mở email, bấm link
         ├─ GET  /don-hang/{order}/xac-nhan-huy/{token}   (middleware: signed)
         └─ POST cùng URL → confirmCancellation()
               └─ status = 'CANCELLED', xóa token, ghi cancel_confirmed_at
```

### Các biện pháp bảo vệ

| Biện pháp | Chi tiết |
|---|---|
| Token không lưu thô | Chỉ lưu `hash('sha256', $token)`; token thật chỉ có trong email ✅ |
| So sánh an toàn | `hash_equals()` chống timing attack ✅ |
| Khóa dòng | `lockForUpdate()` ở **cả** `requestCancellation()` và `confirmCancellation()` ✅ |
| Signed URL | `URL::temporarySignedRoute` + middleware `signed` — không sửa được `order`/`token` trong URL ✅ |
| Hạn kép | Signed URL 3 ngày **và** kiểm tra `cancel_requested_at->lt(now()->subDays(3))` ✅ |
| Dùng một lần | Xóa `cancel_confirmation_token_hash` sau khi xác nhận ✅ |
| Rollback khi SMTP lỗi | Xóa token vừa lưu để admin gửi lại được, kèm `Log::error` ✅ |
| Chỉ trạng thái hợp lệ | `CANCELLABLE_STATUSES = ['PENDING','AWAITING_PAYMENT','CONFIRMED']` ✅ |
| Throttle | Route có `throttle:user-actions` ✅ |

Email dùng dữ liệu **snapshot trong `order_items`** (không phụ thuộc giá/tên sản phẩm hiện tại),
liệt kê đầy đủ sản phẩm, SKU, phân loại, số lượng, đơn giá, thành tiền, và tổng thanh toán ✅.

### Điểm cần lưu ý

> ⚠️ **Không hủy được đơn nếu khách không có email.** `customerEmail()` lấy từ `$order->user?->email`;
> nếu rỗng → trả lỗi "Đơn hàng này chưa có email khách hàng để gửi xác nhận hủy." Admin **không
> có đường vòng nào** để hủy đơn rác/đơn thử nghiệm.

> ⚠️ **Khách không có cách nào từ chối.** Chỉ có nút xác nhận hủy; không có link "tôi không đồng
> ý". Đơn sẽ treo ở trạng thái "đã yêu cầu hủy" cho tới khi token hết hạn 3 ngày.

> ⚠️ **Không có route nào cho khách tự hủy đơn.** Luồng này chỉ khởi động từ phía admin.

> ⚠️ `cancelNote()` nối lý do hủy vào `orders.note` — trường vốn dành cho ghi chú của **khách**,
> nay đã chứa cả mã giao dịch VNPay (xem [04](04-module-gio-hang-thanh-toan.md)) và lý do hủy.
> Ba loại dữ liệu khác nhau trong một cột tự do.

`OrderCancellationController` (75 dòng) chỉ làm nhiệm vụ mỏng: gọi `pendingCancellationError()`
cho GET, `confirmCancellation()` cho POST, render `orders/cancel-confirmation.blade.php`.

---

## 5.3. `app/Http/Controllers/ReturnRequestController.php` (85 dòng) — phía khách

| Method | Route |
|---|---|
| `index()` | `GET /hoan-doi` — danh sách yêu cầu của tôi, paginate 10 |
| `create()` | `GET /hoan-doi/don-hang/{order}` |
| `store()` | `POST /hoan-doi/don-hang/{order}` |
| `show()` | `GET /hoan-doi/{return}` |

**Kiểm tra sở hữu đầy đủ** ở cả 3 method có tham số:
```php
abort_unless($order->user_id === Auth::id(), 403);      // create, store
abort_unless($return->user_id === Auth::id(), 403);     // show
```
`index()` lọc theo `where('user_id', Auth::id())` ✅. Không có IDOR.

### `store()` — ✅ H-10 đã khắc phục phần lớn

Controller được viết lại với comment nghiệp vụ tiếng Việt chi tiết cho từng bước.

**Kiểm tra trạng thái đơn (MỚI):**
```php
private const ELIGIBLE_ORDER_STATUSES = ['DELIVERED', 'RETURN_PENDING', 'RETURNED', 'EXCHANGED'];

if (! $this->isOrderEligible($order)) {
    return redirect()->route('account.orders.show', $order)
        ->with('error', $this->returnIneligibleMessage($order));
}
```
Cho phép cả `RETURNED`/`EXCHANGED` vì đơn nhiều sản phẩm có thể còn dòng khác chưa hoàn — lý
do được ghi rõ trong comment ✅. `Order::hasReturnableStatus()` cũng được thêm vào model.

**Cộng dồn số lượng đã yêu cầu (MỚI):**
```php
$remainingQuantity = $this->remainingReturnQuantity($order, $item);

if ($remainingQuantity < 1) {
    return back()->withErrors(['order_item_id' => 'Sản phẩm này đã được yêu cầu hoàn/đổi đủ số lượng.']);
}
if ((int) $data['quantity'] > $remainingQuantity) {
    return back()->withErrors(['quantity' => 'Số lượng yêu cầu không được vượt quá ... ' . $remainingQuantity . '.']);
}
```
Giải quyết cả điểm 3 và 4 của bản audit cũ ✅.

**Validate chặt hơn:**
```php
'reason_id' => ['required', Rule::exists('return_reasons', 'id')->where('status', 'ACTIVE')],
```
Trước đây chỉ `exists:return_reasons,id` — khách gửi được lý do đã ngừng dùng. Nay bắt buộc ACTIVE ✅.

**`create()` cũng thông minh hơn:** gọi `returnableItems()` để chỉ hiện sản phẩm còn hoàn được;
nếu hết → chuyển hướng kèm thông báo. Truyền `remainingQuantities` sang view để hiển thị giới
hạn cho từng dòng ✅.

### Còn thiếu

1. ⚠️ **Vẫn không có thời hạn đổi trả.** Không có giới hạn "trong vòng N ngày kể từ `delivered_at`" —
   dù `delivered_at` nay đã được lưu thật (§5.2). Đơn giao 3 năm trước vẫn tạo yêu cầu được.
   Đây là mảnh còn lại của H-10.

2. ⚠️ **Đơn hàng vẫn không tự chuyển sang `RETURN_PENDING`** khi khách tạo yêu cầu. Admin phải
   nhớ đổi tay. Trớ trêu là `ELIGIBLE_ORDER_STATUSES` lại chấp nhận `RETURN_PENDING` như thể
   trạng thái đó sẽ được đặt tự động.

3. ⚠️ `ReturnRequestItem::create()` vẫn giữ `min((int) $data['quantity'], (int) $item->quantity)` —
   thừa, vì đã kiểm tra `$remainingQuantity` ở trên rồi.

> ⚠️ Bảng `return_request_images` và model `ReturnRequestImage` tồn tại, và
> `ReturnAdminController::show()` có load `images` để hiển thị — nhưng **không có bất kỳ code
> nào ghi vào bảng này**. Khách không upload được ảnh minh chứng. Chức năng dở dang.

> ⚠️ `ReturnRequestItem` có cột `exchange_variant_id` (biến thể muốn đổi sang) trong
> `$fillable` — nhưng `store()` **không bao giờ set nó**. Với `type = 'EXCHANGE'`, hệ thống
> không biết khách muốn đổi sang mẫu nào. Chức năng đổi hàng về cơ bản chưa hoàn chỉnh.

---

## 5.4. `app/Http/Controllers/Admin/ReturnAdminController.php` (128 dòng)

| Method | Route |
|---|---|
| `index()` | `GET /admin/hoan-doi` — lọc status/type/keyword, paginate 15 |
| `show()` | `GET /admin/hoan-doi/{return}` |
| `update()` | `PUT /admin/hoan-doi/{return}` |

### `update()` — dòng 44–64

```php
validate([
    'status' => 'required|in:PENDING,APPROVED,REJECTED,RECEIVED,COMPLETED,CANCELLED',
    'admin_note' => 'nullable|string|max:1000',
    'damage' => 'nullable|array',
    'damage.*.percent' => 'nullable|integer|min:0|max:100',
    'damage.*.description' => 'nullable|string|max:1000',
]);

$return->update([
    'status' => $data['status'],
    'admin_note' => $data['admin_note'] ?? null,
    'reviewed_at' => now(),
    'completed_at' => $data['status'] === 'COMPLETED' ? now() : $return->completed_at,
]);
$this->saveDamageAssessments($return, $data['damage'] ?? []);
```

Vấn đề:
- ⚠️ **`reviewed_by` không bao giờ được ghi** dù có trong `$fillable`. `reviewed_at` được ghi
  nhưng không biết ai duyệt.
- ⚠️ **Không có state machine.** Chuyển được `COMPLETED → PENDING`, `REJECTED → APPROVED`…
- 🔴 **Duyệt hoàn hàng KHÔNG nhập lại kho.** `status = 'COMPLETED'` cho một `RETURN` không
  tạo phiếu nhập kho, không tăng `inventories.quantity`. Hàng trả về biến mất khỏi hệ thống.
  (Bảng `warehouses` có `type = 'RETURN'` dành riêng cho việc này — nhưng không code nào dùng.)
- 🔴 **Không có hoàn tiền.** Không tạo bản ghi `Payment` kiểu REFUND, không gọi API hoàn tiền
  VNPay. Với đơn đã `PAID`, việc "COMPLETED" một yêu cầu hoàn hàng **không trả tiền cho khách**.
- ⚠️ **`admin_note` bị ghi đè bằng `null`** nếu form không gửi trường này — mất ghi chú cũ.

### `saveDamageAssessments()` — dòng 80–106

Đánh giá hư hỏng theo 8 bộ phận cố định của gọng kính:

| `part_code` | Tên |
|---|---|
| `FRAME_LEFT` / `FRAME_RIGHT` | Gọng trái / phải |
| `LENS_LEFT` / `LENS_RIGHT` | Tròng trái / phải |
| `HINGE` | Bản lề / ốc vít |
| `NOSE_PAD` | Đệm mũi |
| `ACCESSORY` | Phụ kiện / hộp kính |
| `OTHER` | Khác |

Quy đổi `damage_percent` → `damage_level`:

| % | Mức |
|---|---|
| 0 | `NONE` |
| 1–20 | `LIGHT` |
| 21–50 | `MEDIUM` |
| 51–80 | `HEAVY` |
| 81–100 | `SEVERE` |

Chiến lược lưu: **xóa sạch rồi ghi lại** (`delete()` toàn bộ theo `return_request_id`, sau đó
`create()` từng dòng có dữ liệu). Đơn giản nhưng:
- ⚠️ **Không nằm trong transaction** — nếu lỗi giữa chừng, mất toàn bộ đánh giá cũ mà chưa ghi
  được đánh giá mới.
- ⚠️ **`delete()` + `create()` lặp** → mỗi lần lưu sinh tối đa 1 DELETE + 8 INSERT.
- ⚠️ `'assessed_by' => Auth::id() ?? 1` — fallback về **user id 1** khi không có phiên đăng
  nhập. Ghi nhận sai người đánh giá. Trong luồng admin thì `Auth::id()` luôn có, nên nhánh
  fallback này là code phòng thủ vô nghĩa và gây hiểu nhầm dữ liệu.

Đây là phần **đặc thù nghiệp vụ kính mắt** rõ nét nhất trong dự án — điểm sáng về mặt thiết
kế miền nghiệp vụ, đáng nhấn mạnh trong báo cáo luận văn.

---

## 5.5. Các model của module

### `Order.php` (79 dòng — cập nhật)
- `$fillable` **thêm 4 cột** phục vụ luồng hủy 2 bước: `cancel_confirmation_token_hash`,
  `cancel_reason`, `cancel_requested_at`, `cancel_confirmed_at`
  (migration `2026_08_04_110000`).
- `$casts` thêm `cancel_requested_at`, `cancel_confirmed_at` → `datetime`.
- **Phương thức mới:** `hasReturnableStatus()` — dùng chung cho view và `ReturnRequestController`.
- ⚠️ **`delivered_at` vẫn được cast nhưng vẫn KHÔNG có trong `$fillable`.** Nay không còn gây
  lỗi vì `OrderAdminController::changeStatus()` ghi qua **`forceFill()`** (bỏ qua mass-assignment).
  Hoạt động đúng ✅, nhưng là giải pháp vòng — thêm `'delivered_at'` vào `$fillable` sẽ sạch hơn
  và tránh bẫy cho người sửa sau.
- Quan hệ: `user`, `items`, `returnRequests`, `payments`, `promotion`.

### `OrderItem.php` (57 dòng)
`public $timestamps = false`. Lưu snapshot: `product_name`, `sku`, `color_name`,
`lens_size_name`, `quantity`, `unit_price`, `discount_amount`, `total_price`.
Quan hệ `review(): HasOne` — dùng trong `ProductController::reviewOrderItemFor()`
(`whereDoesntHave('review')`).

### `Payment.php` (35 dòng)
`payment_code` (= `order_code`), `method` (COD/VNPAY), `amount`, `status`, `paid_at`,
`expired_at`, `transaction_no`, `bank_code`, `response_code`, `response_message`.
> ⚠️ Chỉ VNPay ghi vào bảng này. **Đơn COD không bao giờ có bản ghi `Payment`.**

### `ReturnRequest.php` (63 dòng)
```php
const CREATED_AT = 'requested_at';
const UPDATED_AT = null;
```
Quan hệ: `order`, `user`, `reason`, `items`, `images`, `damageAssessments`.

### `ReturnRequestItem.php`, `ReturnRequestImage.php`, `ReturnDamageAssessment.php`, `ReturnReason.php`
Model đơn giản, `$timestamps = false` hoặc `UPDATED_AT = null`.
`ReturnReason` có `type` để phân loại lý do theo RETURN/EXCHANGE và scope `active()`.

---

## 5.6. Email & hóa đơn (MỚI)

| Service | Dùng ở | Chức năng |
|---|---|---|
| `OrderConfirmationEmailService` (177 dòng) | `CheckoutController::store()` sau khi tạo đơn COD | Email xác nhận đặt hàng; cột `order_confirmation_email_sent_at` (migration `2026_08_04_113000`) chống gửi trùng |
| `OrderInvoiceEmailService` (140 dòng) | `AccountController::emailInvoice()` | Khách tự gửi hóa đơn về email mình |
| — | `AccountController::invoice()` → `account/orders/invoice.blade.php` (157 dòng) | Trang hóa đơn in được |

Route mới:
```php
Route::get ('/tai-khoan/don-hang/{order}/hoa-don',           [AccountController::class, 'invoice']);
Route::post('/tai-khoan/don-hang/{order}/hoa-don/gui-email', [AccountController::class, 'emailInvoice'])
     ->middleware('throttle:user-actions');
```

Tất cả đều gửi qua `QueuedRawMail` (hàng đợi, không chặn request — xem [01](01-tong-quan-kien-truc.md) §1.11) ✅.

> ⚠️ Email VNPay: `CheckoutController::store()` chỉ gọi `$orderConfirmationEmail->send($order)`
> ở **nhánh COD**. Đơn VNPay tạo trong `VnPayController::createPaidOrderFromDraft()`
> **không gửi email xác nhận nào**.

---

## 5.7. Tổng kết module

| Mã | Mức | Trạng thái | Vấn đề |
|---|---|---|---|
| H-09 | Cao | ✅ **đã sửa** | Danh sách đơn nay `paginate(20)`; summary gộp còn 1 truy vấn |
| H-10 | Cao | ✅ **phần lớn** | Đã kiểm trạng thái đơn + cộng dồn số lượng; **còn thiếu thời hạn đổi trả** |
| H-11 | Cao | ✅ **đã sửa** | Migration thêm cột; ghi qua `forceFill()` |
| M-13 | TB | ✅ **đã sửa** | `STATUS_TRANSITIONS` — state machine 9 trạng thái |
| **C-02** | **Nghiêm trọng** | 🟠 một phần | Phiếu `SALE_OUT` được tạo nhưng **không trừ tồn kho** |
| H-08 | Cao | ⚠️ chưa sửa | Đơn COD không bao giờ chuyển `payment_status` sang `PAID` |
| M-11 | TB | ⚠️ chưa sửa | Hủy đơn không hoàn `used_count`, không hoàn tiền, không nhập lại kho |
| M-12 | TB | ⚠️ chưa sửa | Duyệt hoàn hàng COMPLETED không nhập kho, không hoàn tiền, không ghi `reviewed_by` |
| M-14 | TB | 🟠 một phần | Vẫn không có nhật ký trạng thái; **nhưng đã có email cho khách** ✅ |
| N-08 | Thấp | mới | `AWAITING_PAYMENT` là trạng thái không thể tới được |
| L-05 | Thấp | ⚠️ chưa sửa | `exchange_variant_id`, `return_request_images` là chức năng dở dang |
| L-06 | Thấp | ⚠️ chưa sửa | `saveDamageAssessments` không có transaction; `assessed_by ?? 1` |
| — | — | mới | Không hủy được đơn nếu khách không có email; khách không có cách từ chối hủy |
