# Bí Kíp Phản Biện & Giải Mã Mã Nguồn Cấp Tốc (Dành Cho Người Dùng AI Code 100%)

Tài liệu này được thiết kế dành riêng cho bạn — người học IT nhưng đã tạo project bằng AI 100%, chưa hiểu hết bản chất mã nguồn và đang phải chuẩn bị đi **bảo vệ đồ án / phản biện** trước hội đồng.

---

## 🧭 1. Quy Tắc 4 Bước "Đọc Code Không Cần Nhớ" (Quy Tắc 5 Giây)

Khi hội đồng bảo: *"Em hãy chỉ cho thầy/cô xem đoạn code xử lý [Tính năng X] nằm ở đâu và hoạt động ra sao?"*, bạn **không được hoảng loạn**. Hãy làm đúng 4 bước sau:

```
[Màn hình Web / URL] ➡️ [1. File Routes] ➡️ [2. File Controller] ➡️ [3. File Model / Service] ➡️ [4. File View (Blade)]
```

### Chi tiết 4 bước:
1. **Bước 1 — Nhìn đường dẫn (URL) trên trình duyệt:**
   - Ví dụ: `http://localhost:8000/thu-kinh` hoặc `http://localhost:8000/vnpay/return`.
2. **Bước 2 — Mở file Routes tương ứng:**
   - Nếu là trang khách: Mở [`routes/web.php`](file:///c:/source/luanvancuoiki/routes/web.php).
   - Nếu là trang admin: Mở [`routes/admin.php`](file:///c:/source/luanvancuoiki/routes/admin.php).
3. **Bước 3 — Mở Controller & Method tương ứng:**
   - Ví dụ: `ProductController@tryOnModelCheck` tại [`ProductController.php:L214`](file:///c:/source/luanvancuoiki/app/Http/Controllers/ProductController.php#L214).
4. **Bước 4 — Chỉ vào dòng xử lý Logic / Query Database / View:**
   - Đọc các dòng comment tiếng Việt và chỉ cho thầy cô thấy nơi gọi API Jeeliz AI hay truy vấn Model.

---

## 🤖 2. Phân Tích Chuyên Sâu Tính Năng Thử Kính AI (Virtual Try-on)

Nếu Thầy cô hỏi xoáy về tính năng Thử kính AI, hãy tự tin trả lời theo 3 góc độ kỹ thuật này:

| Thắc mắc của Thầy Cô | Giải thích chuẩn Dân IT | Vị trí minh chứng dòng code |
|---|---|---|
| **"Tính năng AI thử kính chạy thế nào?"** | Dùng thư viện Jeeliz WebGL/AR 3D Face Tracker phát hiện các điểm mốc khuôn mặt qua Webcam/Ảnh -> Đè model 3D gọng kính khớp theo tọa độ mắt. | View [`resources/views/tryon-ai.blade.php`](file:///c:/source/luanvancuoiki/resources/views/tryon-ai.blade.php) |
| **"Làm sao biết gọng kính đó có hỗ trợ thử AI không?"** | Controller gửi HTTP Request ngầm đến API `https://glassesdbcached.jeeliz.com/sku/{sku}` kiểm tra xem SKU có dữ liệu 3D Mesh không. | [`ProductController.php:L223`](file:///c:/source/luanvancuoiki/app/Http/Controllers/ProductController.php#L223) |
| **"Ảnh thử kính lưu vào đâu?"** | Client chuyển Canvas thành chuỗi mã hóa Base64 gửi qua AJAX -> Server decode và lưu thành file ảnh trong `public/upload/tryon/` -> Tạo record `TryOnSnapshot`. | [`ProductController.php:L285`](file:///c:/source/luanvancuoiki/app/Http/Controllers/ProductController.php#L285) & Model [`TryOnSnapshot.php`](file:///c:/source/luanvancuoiki/app/Models/TryOnSnapshot.php) |

---

## 🔤 3. Giải Mã Các Thuật Ngữ "AI Sinh Ra" Thường Bị Thầy Cô Hỏi Xoáy

| Thuật ngữ AI code | Giải thích siêu dễ hiểu | Vị trí minh chứng trong dự án |
|---|---|---|
| **Middleware** | Bộ lọc trung gian đứng trước Controller để kiểm tra quyền (vd: đã đăng nhập chưa, có phải Admin không). | [`app/Http/Middleware/EnsureAdmin.php:L16`](file:///c:/source/luanvancuoiki/app/Http/Middleware/EnsureAdmin.php#L16) |
| **Strict Types** | Cấu hình `declare(strict_types=1);` buộc PHP phải truyền đúng kiểu dữ liệu, tránh bug ngầm. | [`routes/web.php:L3`](file:///c:/source/luanvancuoiki/routes/web.php#L3) |
| **Signed Route** | URL được ký bảo mật kèm mã hash chống sửa tham số URL (vd: URL hủy đơn hàng). | [`routes/web.php:L61`](file:///c:/source/luanvancuoiki/routes/web.php#L61) |
| **Rate Limiting (Throttle)** | Giới hạn số lần gửi request/giây để chống spam form hoặc DDoS. | [`routes/web.php:L108`](file:///c:/source/luanvancuoiki/routes/web.php#L108) |
| **Service Layer** | Gom logic phức tạp (mã hóa VNPay SHA512) ra class riêng `VnPayService`. | [`app/Services/VnPayService.php:L12`](file:///c:/source/luanvancuoiki/app/Services/VnPayService.php#L12) |
| **HMAC-SHA512** | Thuật toán mã hóa băm dùng Secret Key tạo chữ ký số `vnp_SecureHash` cho VNPay. | [`app/Services/VnPayService.php:L90`](file:///c:/source/luanvancuoiki/app/Services/VnPayService.php#L90) |
| **StockTransaction** | Bảng ghi vết toàn bộ lịch sử biến động kho (Nhập, Xuất, Đổi trả, Hủy đơn). | [`app/Models/StockTransaction.php`](file:///c:/source/luanvancuoiki/app/Models/StockTransaction.php) |

---

## 🎬 4. Kịch Bản Demo 10 Phút Ấn Tượng Nhất Dành Cho Phản Biện

1. **Phút 1 — Giới thiệu tổng quan:** Web Bán Kính Mắt Laravel 11 tích hợp Thử kính AI Jeeliz 3D, Kho hàng đa biến thể & VNPay SHA512.
2. **Phút 2-4 — Demo Thử kính AI:** Mở `/thu-kinh` -> Chọn kính -> Bật Webcam -> Chụp Snapshot lưu ảnh -> Chỉ code [`ProductController.php:L241`](file:///c:/source/luanvancuoiki/app/Http/Controllers/ProductController.php#L241).
3. **Phút 5-7 — Demo Đặt hàng & VNPay:** Thêm giỏ -> Checkout -> Nhấn VNPay -> Cho xem code hash SHA512 tại [`VnPayService.php:L46`](file:///c:/source/luanvancuoiki/app/Services/VnPayService.php#L46) -> Cho xem luồng IPN.
4. **Phút 8-10 — Demo Admin Kho & Đổi trả:** Đăng nhập `/admin` -> Xem lịch sử kho `StockTransaction` & Duyệt đơn đổi trả `/admin/returns`.
