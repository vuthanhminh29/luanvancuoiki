# Giáo Trình Giảng Dạy & Đào Tạo Cấp Tốc (7 Buổi Làm Chủ Source Code Laravel)

Tài liệu này là **giáo trình đứng lớp chuẩn mực** thiết kế cho Người dạy (Tutor / Giảng viên) dùng để đào tạo người học IT (đặc biệt là người đã tạo project 100% bằng AI) nắm vững bản chất hệ thống Website Bán Kính Mắt trong **7 buổi học**.

---

## 📅 Lộ Trình Giảng Dạy 7 Buổi

```
Buổi 1: Kiến trúc Laravel & Request Flow ➡️ Buổi 2: Database 26 Bảng & ORM ➡️ Buổi 3: Storefront & Thử kính AI
   ⬇️
Buổi 4: Giỏ hàng Session & Checkout ➡️ Buổi 5: Tích hợp Thanh toán VNPay ➡️ Buổi 6: Admin, Kho hàng & Đổi trả
   ⬇️
Buổi 7: Bảo mật, Audit Flaws & Phản biện thử nghiệm (Mock Defense)
```

---

## 📖 BÀI GIẢNG CHI TIẾT TỪNG BUỔI

### 📌 BUỔI 1: Tổng Quan Kiến Trúc Laravel MVC & Luồng Request Lifecycle

#### 1. Mục tiêu buổi học
- Học viên hiểu được Laravel hoạt động thế nào khi có một người truy cập vào trang web.
- Phân biệt rõ vai trò của 3 thành phần MVC: Model, View, Controller.
- Biết cách tìm một trang web tương ứng với file code nào trong thư mục `routes/` và `app/Http/Controllers/`.

#### 2. Kiến thức cốt lõi & Minh họa trong Code

##### A. Luồng Request Lifecycle (Ẩn dụ Nhà Hàng)
- **Khách hàng gõ URL:** Giống như khách vào nhà hàng xem Menu.
- **`public/index.php`:** Cửa chính đón khách.
- **`routes/web.php`:** Bồi bàn nhận món (Route trỏ request đến đúng bếp xử lý).
- **`Controller`:** Bếp trưởng (Xử lý logic, lấy nguyên liệu từ Model).
- **`Model`:** Kho nguyên liệu (Kết nối Database lấy dữ liệu sản phẩm/đơn hàng).
- **`View (Blade)`:** Đĩa thức ăn đã trang trí xong (Giao diện HTML/CSS gửi về cho khách xem).

##### B. Cấu trúc Routing trong dự án
- Mở file [`routes/web.php`](file:///c:/source/luanvancuoiki/routes/web.php):
  - Nhóm route đọc dữ liệu: dùng middleware `throttle:web-read` (dòng 26 - 35).
  - Nhóm route bắt buộc đăng nhập: dùng `Route::middleware('auth')` (dòng 81).
  - Nhóm route quản trị admin: nạp file `routes/admin.php` tại dòng 125 (`require __DIR__ . '/admin.php';`).

#### 3. Câu hỏi củng cố & Thực hành
- **Câu hỏi:** Khi bấm vào nút "Thêm vào giỏ hàng", Laravel chạy qua các file nào theo thứ tự?
- **Bài tập:** Cho học viên mở file `routes/web.php`, tìm route xem chi tiết bài viết `/bai-viet/{post:slug}` và chỉ ra tên Controller + tên hàm xử lý.

---

### 📌 BUỔI 2: Thiết Kế Cơ Sở Dữ Liệu & Eloquent ORM (26 Bảng)

#### 1. Mục tiêu buổi học
- Nắm vững cấu trúc 26 bảng dữ liệu trong file [`luanvan_ban_mat_kinh.sql`](file:///c:/source/luanvancuoiki/luanvan_ban_mat_kinh (1).sql).
- Hiểu cách Eloquent ORM thiết lập các mối quan hệ `1-1`, `1-N`, `N-N`.
- Biết cách tối ưu truy vấn tránh lỗi N+1 Query.

#### 2. Kiến thức cốt lõi & Minh họa trong Code

##### A. Các nhóm bảng chính
1. **Nhóm Khách hàng & Phân quyền:** `users`, `roles`, `user_roles`, `addresses`.
2. **Nhóm Sản phẩm & Danh mục:** `categories`, `brands`, `products`, `product_variants` (chứa màu sắc, chất liệu gọng/tròng).
3. **Nhóm Bán hàng & Đơn hàng:** `orders`, `order_items`, `promotions`, `payments`.
4. **Nhóm Kho hàng & Giao dịch:** `warehouses`, `inventories`, `stock_transactions`.
5. **Nhóm Đổi trả hàng:** `return_requests`, `return_items`, `return_histories`.

##### B. Quan hệ Eloquent trong Code
- **Quan hệ 1-N (Category - Product):** 1 danh mục có nhiều sản phẩm.
  - Xem Model `Product`: gọi `$product->category()` trỏ về `belongsTo(Category::class)`.
- **Quan hệ N-N (Order - ProductVariant):** Đơn hàng có nhiều sản phẩm qua bảng trung gian `order_items`.
- **Khắc phục lỗi N+1 Query:**
  - Sai: `Product::all()` rồi lặp lấy danh mục làm chạy 100 câu SQL query.
  - Đúng: `Product::with('category', 'brand')->get()` — chỉ chạy đúng 2-3 câu SQL.

---

### 📌 BUỔI 3: Luồng Storefront Khách Hàng & Thử Kính Webcam/AI

#### 1. Mục tiêu buổi học
- Hiểu cách hiển thị danh sách sản phẩm, bộ lọc nâng cao và chi tiết sản phẩm.
- Nắm được cơ chế tính năng **Thử kính AI** (Virtual Try-on) qua Webcam hoặc tải ảnh.

#### 2. Kiến thức cốt lõi & Minh họa trong Code

##### A. Controller Sản phẩm (`ProductController`)
- Mở file [`app/Http/Controllers/ProductController.php`](file:///c:/source/luanvancuoiki/app/Http/Controllers/ProductController.php):
  - Hàm `index()`: Tiếp nhận tham số lọc (`category`, `brand`, `price_range`, `sort`) và thực hiện phân trang `paginate()`.
  - Hàm `show()`: Lấy thông tin kính kèm danh sách biến thể (`variants`), đánh giá (`reviews`) và sản phẩm tương tự.

##### B. Luồng Thử Kính AI
- Dòng 26-27 `routes/web.php`: Route `/thu-kinh` trỏ đến `ProductController@tryOn`.
- Khách upload ảnh khuôn mặt hoặc bật Webcam -> JavaScript xử lý tách gọng kính -> Gửi AJAX lưu snapshot lên server qua route `POST /thu-kinh/luu-ket-qua` (`storeTryOnSnapshot`).

---

### 📌 BUỔI 4: Luồng Giỏ Hàng Session, Checkout & Áp Mã Giảm Giá

#### 1. Mục tiêu buổi học
- Giải thích được tại sao giỏ hàng dùng Session thay vì lưu thẳng DB.
- Nắm rõ logic đặt hàng, trừ số lượng tạm thời và tính mã giảm giá `Promotion`.

#### 2. Kiến thức cốt lõi & Minh họa trong Code

##### A. Giỏ hàng Session (`CartController`)
- Mở [`app/Http/Controllers/CartController.php`](file:///c:/source/luanvancuoiki/app/Http/Controllers/CartController.php):
  - Hàm `store()` (dòng 23): Lấy giỏ hàng từ `session('cart', [])`. Mối sản phẩm lưu dạng `[variant_id => quantity]`.
  - Giới hạn đặt mua tối đa 10 sản phẩm/đơn (`MAX_TOTAL_QUANTITY = 10`) để tránh đầu cơ/spam.

##### B. Màn hình Checkout (`CheckoutController`)
- Mở [`app/Http/Controllers/CheckoutController.php`](file:///c:/source/luanvancuoiki/app/Http/Controllers/CheckoutController.php):
  - Hàm `store()`: Kiểm tra tồn kho thực tế (`ProductVariant::where(...)`), tính tổng tiền, trừ bớt số tiền giảm nếu có mã `Promotion`, tạo record `Order` và các dòng `OrderItem`.

---

### 📌 BUỔI 5: Tích Hợp Cổng Thanh Toán VNPay (HMAC-SHA512 & IPN)

#### 1. Mục tiêu buổi học
- Hiểu trọn vẹn luồng thanh toán online qua cổng VNPay Sandbox.
- Giải thích được thuật toán mã hóa SHA512 tạo chữ ký số `vnp_SecureHash`.
- Phân biệt giữa **Return URL** (trả giao diện cho khách) và **IPN URL** (server-to-server chốt đơn).

#### 2. Kiến thức cốt lõi & Minh họa trong Code

##### A. Service mã hóa thanh toán (`VnPayService`)
- Mở file [`app/Services/VnPayService.php`](file:///c:/source/luanvancuoiki/app/Services/VnPayService.php):
  - Dòng 19 `createPaymentUrl()`: Chuẩn hóa tham số (`vnp_Amount`, `vnp_TxnRef`, `vnp_CreateDate`), sắp xếp theo thứ tự abc (`ksort`), nối chuỗi và băm mã hóa bằng `hash_hmac('sha512', $data, $secret)` tại dòng 92.

##### B. Callback Return vs IPN (`VnPayController`)
- Mở file [`app/Http/Controllers/VnPayController.php`](file:///c:/source/luanvancuoiki/app/Http/Controllers/VnPayController.php):
  - `return()`: Nhận dữ liệu khi khách được chuyển hướng về trang web -> Kiểm tra chữ ký hợp lệ -> Hiển thị màn hình "Thanh toán thành công / Thất bại".
  - `ipn()`: VNPay chủ động gọi ngầm ngầm đến máy chủ -> Cập nhật trạng thái thanh toán đơn hàng sang `PAID` trong Database.

---

### 📌 BUỔI 6: Admin, Quản Lý Tồn Kho & Quy Trình Đổi Trả Hàng

#### 1. Mục tiêu buổi học
- Hiểu cơ chế phân quyền bảo vệ trang Admin qua Middleware.
- Nắm được logic ghi vết lịch sử tồn kho (`StockTransaction`).
- Hiểu quy trình tiếp nhận và xử lý yêu cầu đổi trả từ khách hàng.

#### 2. Kiến thức cốt lõi & Minh họa trong Code

##### A. Phân quyền Admin (`EnsureAdmin`)
- Mở [`app/Http/Middleware/EnsureAdmin.php`](file:///c:/source/luanvancuoiki/app/Http/Middleware/EnsureAdmin.php):
  - Dòng 14: Khai báo danh sách Role được phép vào admin: `['ADMIN', 'STAFF']`.
  - Dòng 52: Dùng `Cache::remember()` để lưu danh sách quyền user trong 5 phút, giảm tải truy vấn DB.

##### B. Quản lý kho (`WarehouseAdminController` & `StockTransaction`)
- Mọi thao tác nhập hàng mới hay xuất kho bán đều tạo 1 record trong bảng `stock_transactions` với kiểu giao dịch `IMPORT`, `EXPORT`, `RETURN`, `ADJUST`.

##### C. Xử lý đổi trả (`ReturnAdminController`)
- Khách tạo yêu cầu đổi trả tại trang cá nhân -> Admin vào `/admin/returns` xem lý do & ảnh minh chứng -> Duyệt yêu cầu -> Hệ thống tự động tạo giao dịch nhập lại kho và hoàn tiền.

---

### 📌 BUỔI 7: Bảo Mật, Audit Flaws & Thi Phản Biện Thử Nghiệm (Mock Defense)

#### 1. Mục tiêu buổi học
- Nắm được các cơ chế bảo mật đã cài đặt trong hệ thống (CSRF, XSS, Throttle, Signed Route).
- Thuộc kịch bản giải trình các lỗi Audit / nhược điểm của dự án khi thầy cô hỏi.
- Thực hành thi phản biện thử nghiệm (Mock Defense): Giả lập không khí buổi bảo vệ thật.

#### 2. Nội dung Mock Defense (Thi thử 30 phút)
- Người dạy đóng vai **Chủ tịch hội đồng phản biện**, đặt ra các câu hỏi dồn dập từ bộ **100 Câu Hỏi Phản Biện**.
- Người học luyện tập thao tác mở file code trong 5 giây theo **Bản Đồ Tra Cứu Code**.
- Đánh giá khả năng bình tĩnh và cách diễn đạt chuẩn dân IT.
