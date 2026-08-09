# Bản Đồ Tra Cứu Code 5 Giây (Mở Đúng Code Ngay Khi Thầy Cô Hỏi)

Tài liệu này là **bảng tra cứu siêu tốc** giúp bạn tìm đúng file và đúng dòng code trong dự án chỉ trong vòng **5 giây**. Khi thầy cô yêu cầu kiểm tra bất kỳ tính năng nào, bạn chỉ cần dò theo bảng dưới đây và bấm trực tiếp vào đường dẫn file.

---

## 🚀 1. Luồng Khách Hàng (Storefront & Mua Hàng)

| Tính năng / Câu hỏi của Thầy Cô | URL Web | File Routes | Controller & Dòng Code | View Blade (Giao diện) |
|---|---|---|---|---|
| **Xem danh sách sản phẩm, lọc theo danh mục / giá** | `/san-pham` | [`routes/web.php:L28`](file:///c:/source/luanvancuoiki/routes/web.php#L28) | [`ProductController@index`](file:///c:/source/luanvancuoiki/app/Http/Controllers/ProductController.php) | `resources/views/products/index.blade.php` |
| **Xem chi tiết 1 kính (Hình ảnh, giá, biến thể màu/gọng)** | `/san-pham/{slug}` | [`routes/web.php:L29`](file:///c:/source/luanvancuoiki/routes/web.php#L29) | [`ProductController@show`](file:///c:/source/luanvancuoiki/app/Http/Controllers/ProductController.php) | `resources/views/products/show.blade.php` |
| **Thử kính AI / Webcam** | `/thu-kinh` | [`routes/web.php:L27`](file:///c:/source/luanvancuoiki/routes/web.php#L27) | [`ProductController@tryOn`](file:///c:/source/luanvancuoiki/app/Http/Controllers/ProductController.php) | `resources/views/products/try-on.blade.php` |
| **Xem Giỏ hàng (Session)** | `/gio-hang` | [`routes/web.php:L55`](file:///c:/source/luanvancuoiki/routes/web.php#L55) | [`CartController@index:L16`](file:///c:/source/luanvancuoiki/app/Http/Controllers/CartController.php#L16) | `resources/views/cart/index.blade.php` |
| **Thêm kính vào Giỏ hàng (Tối đa 10 cái)** | `POST /gio-hang` | [`routes/web.php:L56`](file:///c:/source/luanvancuoiki/routes/web.php#L56) | [`CartController@store:L23`](file:///c:/source/luanvancuoiki/app/Http/Controllers/CartController.php#L23) | N/A (Redirect back) |
| **Màn hình Đặt hàng (Checkout)** | `/thanh-toan` | [`routes/web.php:L107`](file:///c:/source/luanvancuoiki/routes/web.php#L107) | [`CheckoutController@index`](file:///c:/source/luanvancuoiki/app/Http/Controllers/CheckoutController.php) | `resources/views/checkout/index.blade.php` |
| **Xử lý Đặt hàng & Áp Mã giảm giá** | `POST /thanh-toan` | [`routes/web.php:L108`](file:///c:/source/luanvancuoiki/routes/web.php#L108) | [`CheckoutController@store`](file:///c:/source/luanvancuoiki/app/Http/Controllers/CheckoutController.php) | N/A |
| **Tạo URL Thanh toán VNPay (Hash SHA512)** | N/A | Class Service | [`VnPayService@createPaymentUrl:L19`](file:///c:/source/luanvancuoiki/app/Services/VnPayService.php#L19) | N/A |
| **Nhận Callback trả về từ VNPay (Return URL)** | `/vnpay/return` | [`routes/web.php:L46`](file:///c:/source/luanvancuoiki/routes/web.php#L46) | [`VnPayController@return`](file:///c:/source/luanvancuoiki/app/Http/Controllers/VnPayController.php) | `resources/views/checkout/vnpay-return.blade.php` |
| **Nhận IPN từ VNPay (Server-to-Server)** | `/vnpay/ipn` | [`routes/web.php:L47`](file:///c:/source/luanvancuoiki/routes/web.php#L47) | [`VnPayController@ipn`](file:///c:/source/luanvancuoiki/app/Http/Controllers/VnPayController.php) | Reponse JSON |

---

## 🛡️ 2. Luồng Bảo Mật, Phân Quyền & Tài Khoản

| Tính năng / Câu hỏi của Thầy Cô | File Logic / Middleware | Vị trí Dòng Code Minh Chứng |
|---|---|---|
| **Chặn User thường vào Admin** | [`EnsureAdmin.php`](file:///c:/source/luanvancuoiki/app/Http/Middleware/EnsureAdmin.php) | Dòng 16: `handle()` & Dòng 50: `hasAnyRole()` |
| **Đăng ký / Đăng nhập / Logout** | [`AuthController.php`](file:///c:/source/luanvancuoiki/app/Http/Controllers/AuthController.php) | Hỗ trợ mã hóa mật khẩu `Hash::make()` |
| **Mã hóa chữ ký SHA512 VNPay** | [`VnPayService.php`](file:///c:/source/luanvancuoiki/app/Services/VnPayService.php) | Dòng 90: `hash_hmac('sha512', $data, $secret)` |
| **Signed Route chống sửa URL hủy đơn** | [`routes/web.php`](file:///c:/source/luanvancuoiki/routes/web.php) | Dòng 61: `middleware(['signed'])` |
| **Chống Spam Form (Rate Limiting)** | [`routes/web.php`](file:///c:/source/luanvancuoiki/routes/web.php) | Dòng 108: `middleware('throttle:checkout')` |

---

## 🏬 3. Luồng Quản Trị Admin, Tồn Kho & Đổi Trả

| Tính năng / Câu hỏi của Thầy Cô | URL Admin | File Controller | Model liên quan |
|---|---|---|---|
| **Dashboard Tổng quan & Báo cáo** | `/admin` | [`DashboardController.php`](file:///c:/source/luanvancuoiki/app/Http/Controllers/DashboardController.php) | `Order`, `User` |
| **Quản lý Đơn hàng (Duyệt/Giao/Hủy)** | `/admin/orders` | [`OrderAdminController.php`](file:///c:/source/luanvancuoiki/app/Http/Controllers/OrderAdminController.php) | `Order`, `OrderItem` |
| **Quản lý Tồn kho & Giao dịch kho** | `/admin/inventory` | [`WarehouseAdminController.php`](file:///c:/source/luanvancuoiki/app/Http/Controllers/WarehouseAdminController.php) | [`StockTransaction`](file:///c:/source/luanvancuoiki/app/Models/StockTransaction.php) |
| **Xử lý Đổi trả hàng từ khách** | `/admin/returns` | [`ReturnAdminController.php`](file:///c:/source/luanvancuoiki/app/Http/Controllers/ReturnAdminController.php) | [`ReturnRequest`](file:///c:/source/luanvancuoiki/app/Models/ReturnRequest.php) |
