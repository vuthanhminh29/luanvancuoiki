# 04 — Module Giỏ hàng, Thanh toán & VNPay

Module quan trọng nhất về mặt rủi ro: xử lý tiền. Gồm 4 file:
`CartController`, `CheckoutController`, `VnPayController`, `Services/VnPayService`.

---

## 4.1. `app/Http/Controllers/CartController.php` (161 dòng)

Giỏ hàng lưu **hoàn toàn trong session**, dạng `array<variant_id, quantity>`.
Không có bảng `carts` trong CSDL.

| Method | Route | Throttle |
|---|---|---|
| `index()` | `GET /gio-hang` | — |
| `store()` | `POST /gio-hang` | `cart` (30/phút) |
| `update()` | `PUT /gio-hang` | `cart` |
| `destroy()` | `DELETE /gio-hang/{variant}` | `cart` |

**Giỏ hàng dùng được cho khách chưa đăng nhập** (route không có middleware `auth`), nhưng
`/thanh-toan` thì bắt buộc đăng nhập. Giỏ hàng tồn tại theo session nên **mất khi hết hạn
session (120 phút) hoặc đăng xuất** (`Auth::logout()` + `session()->invalidate()`).

### Ba hàm bảo vệ (dùng lặp ở mọi method)

```php
normalizedCart(array $cart): array   // ép variantId sang int, kẹp quantity, loại rác
limitedCart(array $cart): array      // cắt dần để TỔNG số lượng ≤ MAX_TOTAL_QUANTITY
totalQuantity(array $cart): int
```

**`MAX_TOTAL_QUANTITY` đã đổi từ 20 → 10** (cả `CartController` và `CheckoutController`), là
**tổng số lượng toàn giỏ**, không phải mỗi dòng.

Đợt cập nhật cũng dọn sạch các số 20 hardcode: rule validate nay dùng
`'max:' . self::MAX_TOTAL_QUANTITY`, thông báo lỗi nội suy hằng số, và `normalizedCart()` của
`CheckoutController` dùng `min(self::MAX_CART_QUANTITY, ...)` thay vì `min(20, ...)` ✅.
Thông báo cũng rõ nghĩa hơn: *"Mỗi đơn chỉ đặt tối đa 10 sản phẩm. Vui lòng giảm số lượng trong giỏ."*

> ⚠️ `VnPayController::MAX_CART_QUANTITY` **vẫn là 20**. Ba hằng số cùng ý nghĩa ở ba file, nay
> lệch nhau. Đơn VNPay có 11–20 sản phẩm sẽ bị `CheckoutController` chặn ở bước đặt hàng nhưng
> lọt qua kiểm tra của `createPaidOrderFromDraft()` — hiện không gây lỗi (kiểm tra chặt hơn nằm
> ở trước), nhưng là bẫy khi ai đó đổi giới hạn lần nữa.

`normalizedCart()` dùng `filter_var($variantId, FILTER_VALIDATE_INT)` để loại key rác — cần
thiết vì session có thể bị đầu độc từ code cũ. Đây là phòng thủ hợp lý ✅.

### `store()` (dòng 24–52)

```php
validate(variant_id: exists:product_variants,id | quantity: 1..20)
$variant = ProductVariant::active()->findOrFail($data['variant_id']);   // ← chỉ nhận variant ACTIVE ✅
$remaining = 20 - totalQuantity($cart);
if ($remaining <= 0) → báo lỗi "Giỏ hàng chỉ được tối đa 20 sản phẩm."
$quantity = min($requested, $remaining);   // thêm được bao nhiêu thì thêm
```
Xử lý biên tốt: khi vượt trần, hệ thống thêm phần còn lại và **báo rõ** đã thêm bao nhiêu.

### `update()` (dòng 54–86)

Chỉ cập nhật những `variantId` **đã có trong giỏ** (`array_key_exists`) → không thể chèn
sản phẩm mới qua route update ✅. Số lượng `0` → xóa khỏi giỏ. Nếu tổng vượt 20 → **khôi phục
giỏ cũ nguyên vẹn** và báo lỗi (không cập nhật một phần) ✅.

### `destroy()` (dòng 88–95)

```php
public function destroy(int $variant): RedirectResponse {
    $cart = session('cart', []);
    unset($cart[$variant]);
    ...
}
```
Không kiểm tra variant có tồn tại — nhưng thao tác chỉ là `unset` trên session của chính
user nên vô hại.

### 🔴 Vấn đề chung: giỏ hàng không hề biết đến tồn kho

Không có một dòng nào trong `CartController` kiểm tra `inventories`. Khách thêm được 20 cái
kính đang tồn kho 0. `ProductVariant::active()` chỉ kiểm cờ `status` thủ công.
Xem [tài liệu 06](06-module-kho-hang.md) và [10](10-ket-qua-audit.md) mục **C-02**.

---

## 4.2. `app/Http/Controllers/CheckoutController.php` (361 dòng)

Toàn bộ route đều nằm trong nhóm `auth` + `throttle:checkout` (6/phút).

| Method | Route |
|---|---|
| `index()` | `GET /thanh-toan` |
| `store()` | `POST /thanh-toan` |
| `applyPromotion()` | `POST /thanh-toan/ma-giam-gia` |
| `removePromotion()` | `POST /thanh-toan/ma-giam-gia/xoa` |

### Mã giảm giá (`promotionFromCode`, dòng 315–360)

Chuỗi kiểm tra rất đầy đủ, mỗi bước có thông báo tiếng Việt riêng:

| # | Điều kiện | Thông báo khi sai |
|---|---|---|
| 1 | Mã tồn tại (`UPPER(promotion_code) = ?`) | "Mã giảm giá không tồn tại." |
| 2 | `scope === 'ORDER'` | "…chưa áp dụng cho toàn đơn hàng." |
| 3 | `status === 'ACTIVE'` | "…chưa được bật hoặc đã hết hiệu lực." |
| 4 | `start_at` không ở tương lai | "…chưa đến thời gian sử dụng." |
| 5 | `end_at` chưa qua | "…đã hết hạn." |
| 6 | `used_count < usage_limit` | "…đã hết lượt sử dụng." |
| 7 | `subtotal >= min_order_amount` | "Đơn hàng chưa đạt giá trị tối thiểu…" |
| 8 | `discountFor($subtotal) > 0` | "…chưa có giá trị giảm hợp lệ." |

Mã áp dụng được lưu trong session (`checkout_promotion_code`) và **được xác thực lại ở mọi
lần render và ở bước đặt hàng** (`appliedPromotion()`), tự động gỡ khi hết hiệu lực ✅.
Thiết kế đúng — không tin session.

`Promotion::discountFor()` (trong model):
```php
$discount = $type === 'PERCENT' ? $subtotal * ($value/100) : $value;
if ($max_discount_amount !== null) $discount = min($discount, $max);
return round(min($discount, $subtotal), 0);   // không vượt quá subtotal, làm tròn về đồng
```
Chặn giảm giá âm tiền ✅.

> 🔴 **`usage_per_user` không bao giờ được kiểm tra.** Cột này có trong `$fillable` của
> `Promotion` và trong CSDL, nhưng **không có một dòng code nào đọc nó**. Một khách có thể
> dùng cùng một mã cho vô số đơn hàng, miễn `usage_limit` tổng chưa hết.

> 🔴 **`used_count` tăng ngoài kiểm soát cạnh tranh.** Kiểm tra `used_count >= usage_limit`
> (bước 6) xảy ra ở `promotionFromCode()`, còn `Promotion::whereKey(...)->increment('used_count')`
> xảy ra sau đó trong `createOrder()`. Giữa hai thời điểm không có khóa. 10 request đồng thời
> với mã chỉ còn 1 lượt → **cả 10 đều qua và `used_count` vượt `usage_limit`**.
> Xem [10](10-ket-qua-audit.md) mục **H-02**.

> ⚠️ Ngoài ra: mã giảm giá dùng cho **đơn VNPay bị hủy giữa chừng** vẫn có thể đã tăng
> `used_count` — không, thực ra nhánh VNPay tăng `used_count` trong `createPaidOrderFromDraft()`
> (chỉ khi thanh toán thành công) ✅. Nhánh COD tăng ngay trong `createOrder()` ✅. Nhất quán.

### `store()` — Đặt hàng (dòng 103–177)

Validate:
```php
recipient_name   → required|string|max:100
recipient_phone  → required|regex:/^(03|05|07|08|09)\d{8}$/
address_detail   → required|string|max:200
city             → required|string|max:100          // ← KHÔNG whitelist
payment_method   → required|in:COD,VNPAY
note             → nullable|string|max:1000
```

> ⚠️ `city` ở đây **không được whitelist** theo danh sách 63 tỉnh, khác với
> `AccountController::validateAddress()` (có `in:` + `cities()`). Khách có thể gửi tỉnh tùy ý.
> Không phải lỗ hổng bảo mật (đã escape khi render) nhưng làm bẩn dữ liệu.

> ⚠️ **Địa chỉ đặt hàng không liên kết với sổ địa chỉ.** `Order` có cột `address_id` trong
> `$fillable`, nhưng `createOrder()` **không bao giờ set nó**. Địa chỉ chỉ được ghép chuỗi
> `address_detail + ', ' + city` vào `shipping_address`. Quan hệ với `UserAddress` là code chết.

Kiểm tra tính hợp lệ của giỏ:
```php
if ($variants->isEmpty() || $variants->count() !== count($cart))
    → "Sản phẩm trong giỏ hàng không còn hợp lệ."
```
Bắt được trường hợp variant bị xóa khỏi CSDL sau khi thêm vào giỏ ✅.

> ⚠️ Truy vấn ở đây dùng `ProductVariant::query()` **không có `->active()`** — khác với
> `CartController::store()`. Nghĩa là variant bị admin chuyển sang `DISCONTINUED` sau khi
> khách đã bỏ vào giỏ **vẫn đặt hàng được**.

### `createOrder()` (dòng 179–228)

```php
DB::transaction(function () {
    $subtotal = Σ (variant->display_price × quantity);     // ← tính LẠI từ DB, không tin client ✅
    $shippingFee = 0;                                       // ← luôn bằng 0
    $discountAmount = min($discountAmount, $subtotal);
    Order::create([... 'payment_status' => 'UNPAID', 'status' => 'PENDING',
                   'total_amount' => max(0, $subtotal - $discount) + $shippingFee]);
    if ($promotion) Promotion::increment('used_count');
    foreach ($variants as $variant) OrderItem::create([...snapshot...]);
});
```

Điểm tốt:
- **Giá được tính lại từ CSDL** ở thời điểm đặt hàng, client không thể can thiệp ✅
- `OrderItem` lưu **snapshot đầy đủ**: `product_name`, `sku`, `color_name`, `lens_size_name`,
  `unit_price` — đơn hàng cũ giữ nguyên thông tin dù sản phẩm đổi sau này ✅
- Toàn bộ nằm trong `DB::transaction` ✅
- Mã đơn `'ORD' . YmdHis . Str::upper(Str::random(3))` — có phần ngẫu nhiên ✅

Điểm thiếu:
- 🔴 **Vẫn không trừ tồn kho khi đặt hàng.** Đợt cập nhật chuyển việc này sang lúc đơn đổi trạng
  thái `DELIVERING` (phiếu `SALE_OUT`) — nhưng phiếu đó cũng **không trừ kho thật**.
  Xem [10](10-ket-qua-audit.md) mục **C-02** và [06](06-module-kho-hang.md) §6.2.
- `shipping_fee` hardcode `0` — không có logic phí vận chuyển nào trong hệ thống.
- `order_items.discount_amount` luôn ghi `0`; giảm giá chỉ tồn tại ở cấp đơn hàng.

**Mới:** sau khi tạo đơn COD thành công, `store()` gọi
`$orderConfirmationEmail->send($order)` — email xác nhận đặt hàng gửi qua hàng đợi ✅
(xem [05](05-module-don-hang-hoan-doi.md) §5.6).

> ⚠️ Chỉ **nhánh COD** gửi email này. Đơn VNPay tạo trong
> `VnPayController::createPaidOrderFromDraft()` **không gửi email xác nhận nào**.

---

## 4.3. Luồng VNPay

### Kiến trúc "đơn hàng nháp" (draft)

Đây là điểm thiết kế đặc trưng nhất của hệ thống: **với VNPay, đơn hàng KHÔNG được tạo trước
khi thanh toán.** Thay vào đó:

```
Khách bấm "Thanh toán VNPay"
   │
   ├─ storePendingVnPayCheckout()
   │     ├─ sinh order_code
   │     ├─ ghi draft vào session:  pending_vnpay_checkouts.{order_code}
   │     └─ ghi draft vào cache:    pending_vnpay_checkout:{order_code}   (TTL = expire_time)
   │
   └─ redirect()->away( VnPayService::createPaymentUrl() )
              │
        [ Cổng VNPay ]
              │
    ┌─────────┴──────────┐
    │                    │
 Return URL          IPN (server→server)
 (trình duyệt)       (không có session!)
    │                    │
    └────────┬───────────┘
             │
   createPaidOrderFromDraft()  ← tạo Order + OrderItem + Payment ở đây
```

**Vì sao ghi draft vào cả session lẫn cache:** IPN là callback server-to-server từ VNPay,
**không mang cookie session**. Nếu chỉ lưu session thì IPN không tìm thấy draft.
`pendingDraft()` (`VnPayController` dòng 133) đọc session trước, không có thì đọc cache:
```php
return session("pending_vnpay_checkouts.{$txnRef}") ?: Cache::get("pending_vnpay_checkout:{$txnRef}");
```
Thiết kế này đúng ✅ — nhưng **phụ thuộc hoàn toàn vào cache store**. `.env.example` đặt
`CACHE_STORE=database` (bền vững, OK). Nếu ai đó đổi sang `array` hoặc `file` trên nhiều
instance → **mọi đơn VNPay sẽ mất khi IPN về**. Xem [10](10-ket-qua-audit.md) mục H-07.

Ưu điểm của mô hình draft: không sinh rác đơn `AWAITING_PAYMENT` cho các giao dịch bị bỏ dở.
Nhược điểm: **không có bất kỳ dấu vết nào** về các lần thanh toán thất bại/bị hủy — draft chỉ
bị `forget`, không log, không lưu DB. Không thể phân tích tỉ lệ rớt thanh toán.

### `app/Services/VnPayService.php` (94 dòng)

```php
isConfigured(): bool                      // tmn_code và hash_secret khác rỗng
createPaymentUrl(Order $order, Request): string
verify(array $params): array
private buildQueryString(array): string   // ksort + urlencode, bỏ giá trị null/''
private secureHash(string): string        // hash_hmac('sha512', $data, hash_secret)
```

**Tạo URL thanh toán** — tham số đúng chuẩn VNPay v2.1.0:
`vnp_Version`, `vnp_Command=pay`, `vnp_TmnCode`, `vnp_Amount` (**×100**, ép `(int) round()` ✅),
`vnp_CurrCode=VND`, `vnp_TxnRef` (= `order_code`), `vnp_OrderInfo` (`Str::ascii` + giới hạn 255 ✅),
`vnp_OrderType=billpayment`, `vnp_Locale`, `vnp_ReturnUrl`, `vnp_IpAddr`, `vnp_CreateDate`,
`vnp_ExpireDate`.

**Xác minh chữ ký** (`verify`, dòng 51–72):
```php
$receivedHash = $params['vnp_SecureHash'] ?? '';
unset($params['vnp_SecureHash'], $params['vnp_SecureHashType']);
$isValid = hash_equals($this->secureHash($this->buildQueryString($params)), $receivedHash);
$isSuccess = $isValid && $responseCode === '00' && $transactionStatus === '00';
```

- Dùng **`hash_equals`** → chống timing attack ✅
- Loại bỏ `vnp_SecureHash` và `vnp_SecureHashType` trước khi băm ✅
- `ksort()` trước khi ghép ✅
- Yêu cầu **cả** `vnp_ResponseCode === '00'` **và** `vnp_TransactionStatus === '00'` ✅
  (nhiều triển khai chỉ kiểm ResponseCode — đây là điểm cộng)
- `is_success` được gate bởi `is_valid` → không thể "thành công" mà chữ ký sai ✅

> ⚠️ **`buildQueryString()` bỏ qua các tham số có giá trị rỗng** (`$value === null || $value === ''`).
> Nếu VNPay gửi về một tham số hợp lệ với giá trị rỗng, nó bị loại khỏi chuỗi băm phía ta
> nhưng VNPay có thể đã tính vào chuỗi băm phía họ → chữ ký lệch, giao dịch hợp lệ bị từ chối.
> Hành vi này khớp với đa số SDK VNPay hiện hành nên rủi ro thấp, nhưng cần lưu ý khi debug.

> ⚠️ **`urlencode()` với VNPay:** chuẩn VNPay yêu cầu encode kiểu RFC3986 ở một số phiên bản.
> `urlencode()` của PHP encode khoảng trắng thành `+`, còn `rawurlencode()` thành `%20`.
> Nếu gặp lỗi "sai chữ ký" khi lên production thì đây là chỗ đầu tiên cần kiểm tra.
> *(chưa kiểm chứng runtime)*

### `app/Http/Controllers/VnPayController.php` (302 dòng)

Hai điểm vào: `return()` (trình duyệt) và `ipn()` (server-to-server, **đã miễn CSRF** trong
`bootstrap/app.php` ✅ — bắt buộc, vì VNPay không có token).

#### `return()` — dòng 26–80

Thứ tự kiểm tra:
1. `verify()` → nếu **chữ ký sai** → xóa draft, báo "Chữ ký thanh toán VNPay không hợp lệ."
2. Không tìm thấy `Order` **và** không có draft → "Không tìm thấy giao dịch…"
3. **So khớp số tiền**: `abs($expected - $result['amount']) > 0.01` → hủy đơn / xóa draft ✅
4. `is_success` false → ghi `Payment` FAILED, hủy đơn nếu chưa PAID
5. Thành công → `markPaid()` hoặc `createPaidOrderFromDraft()`
6. Xóa draft + `session()->forget('cart')`
7. Nếu `Auth::id() === $order->user_id` → về trang chi tiết đơn; ngược lại → về trang chủ

Bước 3 (kiểm tiền) là **phòng thủ then chốt** và được làm đúng ✅.
Bước 7 xử lý được tình huống session đã đổi trong lúc thanh toán ✅.

> ⚠️ **Thứ tự thực thi có vấn đề nhỏ:** dòng 30–31 gọi `findOrder()` và `pendingDraft()`
> **trước** khi kiểm tra `is_valid` (dòng 33). Nghĩa là với chữ ký sai, hệ thống vẫn thực hiện
> 1 truy vấn DB + 1 lần đọc cache dựa trên `txn_ref` do kẻ tấn công cung cấp. Không rò rỉ dữ
> liệu (kết quả không được dùng) nhưng là bề mặt tấn công DoS nhẹ. Route `/vnpay/return`
> **không có throttle**.

#### `ipn()` — dòng 82–126

Trả về mã theo chuẩn VNPay:

| RspCode | Trường hợp |
|---|---|
| `97` | Chữ ký không hợp lệ |
| `01` | Không tìm thấy đơn / draft |
| `04` | Số tiền không khớp |
| `02` | Đơn đã được xác nhận PAID (idempotent) ✅ |
| `99` | Không tạo được đơn (`RuntimeException`) |
| `00` | Xác nhận thành công |

Kiểm tra `$order->payment_status === 'PAID'` → trả `02` là xử lý **idempotency** đúng chuẩn ✅.

> ⚠️ **Route IPN không có throttle và không log.** `Route::match(['get','post'], '/vnpay/ipn')`
> — không middleware nào ngoài web. Endpoint công khai, miễn CSRF, gọi được không giới hạn.
> Chữ ký sai bị chặn nên không mất tiền, nhưng **không có bản ghi nào** về các lần gọi thất
> bại → không phát hiện được khi bị dò. Xem [10](10-ket-qua-audit.md) mục M-03.

#### `createPaidOrderFromDraft()` — dòng 152–231 (hàm quan trọng nhất)

```php
DB::transaction(function () {
    $existing = Order::where('order_code', $draft['order_code'])->lockForUpdate()->first();
    if ($existing) return $this->markPaid($existing, $result);   // ← chống race return-vs-IPN ✅

    ... dựng lại giỏ, nạp lại variant ...
    if (count($variants) !== count($cart)) throw new RuntimeException(...);

    $subtotal = Σ (display_price × quantity);                     // ← tính lại từ DB ✅
    $totalAmount = max(0, $subtotal - $discount) + $shippingFee;

    if (abs($totalAmount - $result['amount']) > 0.01)
        throw new RuntimeException('Số tiền thanh toán không khớp với giỏ hàng hiện tại.');  // ✅

    Order::create([... 'payment_status' => 'PAID', 'status' => 'PENDING' ...]);
    if ($promotion_id) Promotion::increment('used_count');
    foreach ($variants as $v) OrderItem::create([...]);
    $this->saveSuccessfulPayment($order, $result);
});
```

Rất nhiều thứ được làm đúng ở đây:
- `lockForUpdate()` trên `order_code` → **`return` và `ipn` chạy đồng thời không tạo 2 đơn** ✅
- **Tính lại tổng tiền từ CSDL và so với số tiền VNPay báo** — chặn được kịch bản giá sản
  phẩm bị đổi giữa lúc khách đang ở cổng thanh toán ✅
- `Payment::updateOrCreate(['payment_code' => $order->order_code])` → idempotent ✅

> 🔴 **Nhưng: đây là lỗi tiền thật.** Nếu bất kỳ kiểm tra nào ở trên **thất bại sau khi khách
> đã trả tiền**, hàm ném `RuntimeException`, transaction rollback, `return()` chỉ hiển thị
> thông báo lỗi và **không có gì được ghi lại**:
> - Không tạo `Order`
> - Không tạo bản ghi `Payment` nào (kể cả FAILED — vì `markFailed()` cần `$order`)
> - Không `Log::error`
>
> Kịch bản thực tế: admin đổi giá sản phẩm trong lúc khách đang ở cổng VNPay → khách trả tiền
> thành công → hệ thống từ chối tạo đơn → **tiền đã vào tài khoản merchant nhưng không tồn tại
> bất kỳ dấu vết nào trong hệ thống.** Khiếu nại của khách không thể tra được.
> Xem [10](10-ket-qua-audit.md) mục **C-03**.
>
> Tương tự, nhánh `catch (RuntimeException)` của `ipn()` trả `RspCode 99` mà không log gì.

#### `markPaid()` / `markFailed()` / `saveSuccessfulPayment()`

`Payment` được ghi bằng `updateOrCreate` khóa theo `payment_code = order_code`. Lưu:
`method`, `amount`, `status` (SUCCESS/FAILED), `paid_at`, `transaction_no`, `bank_code`,
`response_code`, `response_message` (cắt 255 ký tự).

`appendPaymentNote()` chèn `"VNPay transaction: {mã GD}"` vào `orders.note`, có kiểm tra
`str_contains` để **không nhân đôi khi cả return lẫn IPN cùng chạy** ✅.

> ⚠️ Ghi mã giao dịch vào `note` — cột dành cho **ghi chú của khách hàng**. Trộn dữ liệu hệ
> thống vào trường tự do của người dùng. Bảng `payments` đã có cột `transaction_no` rồi.

`cancelOrderPayment()` chỉ hủy đơn nếu `payment_status !== 'PAID'` — tránh hủy nhầm đơn đã
thanh toán ✅.

---

## 4.4. Bảng tổng kết rủi ro module thanh toán

### Đã làm đúng ✅

| Hạng mục | Chi tiết |
|---|---|
| Xác minh chữ ký | HMAC-SHA512 + `hash_equals`, kiểm cả ResponseCode và TransactionStatus |
| Chống giả mạo giá | Tổng tiền tính lại từ DB ở cả COD lẫn VNPay |
| So khớp số tiền | `abs(expected - actual) > 0.01` ở return, IPN, và lúc tạo đơn |
| Idempotency | `lockForUpdate` trên order_code; `Payment::updateOrCreate`; IPN trả `02` khi đã PAID |
| CSRF | Bật toàn cục, miễn đúng một route `vnpay/ipn` |
| Snapshot đơn hàng | `OrderItem` lưu tên/SKU/màu/size/giá tại thời điểm mua |
| Mã giảm giá | 8 lớp kiểm tra, xác thực lại ở mọi bước, không tin session |

### Cần sửa 🔴

| Mã | Mức | Trạng thái | Vấn đề |
|---|---|---|---|
| C-02 | Nghiêm trọng | 🟠 một phần | Đặt hàng vẫn không trừ/giữ tồn kho; `SALE_OUT` chỉ là chứng từ |
| C-03 | Nghiêm trọng | ⚠️ chưa sửa | Thanh toán thành công nhưng tạo đơn thất bại → **mất dấu tiền hoàn toàn**, không log |
| H-02 | Cao | ⚠️ chưa sửa | `used_count` tăng không có khóa → mã giảm giá vượt `usage_limit` |
| H-07 | Cao | ⚠️ chưa sửa | Draft VNPay phụ thuộc cache; đổi `CACHE_STORE` sai là mất đơn |
| M-03 | TB | ⚠️ chưa sửa | Route `/vnpay/ipn` và `/vnpay/return` không throttle, không log |
| M-09 | TB | ⚠️ chưa sửa | `usage_per_user` của khuyến mãi không bao giờ được kiểm tra |
| M-10 | TB | ⚠️ chưa sửa | `city` ở checkout **vẫn** không whitelist (dù `register` nay đã whitelist 63 tỉnh); `address_id` không bao giờ được ghi |
| L-04 | Thấp | ⚠️ nặng thêm | `shipping_fee` hardcode 0; `orders.note` nay chứa **3 loại dữ liệu**: ghi chú khách + mã GD VNPay + lý do hủy đơn |
| — | — | mới | `MAX_CART_QUANTITY` lệch nhau: 10 ở Cart/Checkout, **20** ở `VnPayController` |
| — | — | mới | Email xác nhận đơn hàng chỉ gửi cho **nhánh COD**, không gửi cho đơn VNPay |
