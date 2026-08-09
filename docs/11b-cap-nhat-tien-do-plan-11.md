# 11b — Cập nhật tiến độ Bảo trì & Triển khai (Plan 11)

Tài liệu này theo dõi **tiến độ thực hiện thực tế** so với các mục trong [11 — Kế hoạch Bảo trì & Triển khai](11-ke-hoach-bao-tri-trien-khai.md).

---

## Bảng tổng hợp tiến độ Plan 11

| Hạng mục | Mã lỗi / Vấn đề | Trạng thái trong code | Chi tiết đã xử lý |
|---|---|---|---|
| **Schema CSDL** | [C-01](10-ket-qua-audit.md#c-01) / P0-2 | ✅ **ĐÃ BỌC AN TOÀN** | [2026_07_10_060000_keep_only_cod_and_vnpay_payment_methods.php](file:///c:/source/luanvancuoiki/database/migrations/2026_07_10_060000_keep_only_cod_and_vnpay_payment_methods.php) đã được bọc `if (! Schema::hasTable('payments') || ! Schema::hasTable('orders')) return;` và sửa phương thức `down()`. Chạy `migrate --force` trên DB mới sẽ không bị fatal error. |
| **Mail Queue & Fallback** | P0-3 / §11.1 | ✅ **ĐÃ TỐI ƯU NON-BLOCKING** | [QueuedRawMail.php](file:///c:/source/luanvancuoiki/app/Support/QueuedRawMail.php) đã được cập nhật sử dụng `app()->terminating(...)`. Nếu Queue dispatch không có worker chạy, mail đồng bộ sẽ gửi sau khi HTTP Response đã trả về cho Client, không làm treo giao dịch đặt hàng/hủy đơn. |
| **Bảo mật Blog (XSS)** | [C-04](10-ket-qua-audit.md#c-04) / Sprint 1 | ✅ **ĐÃ SANITIZE TAGS** | [resources/views/blog/show.blade.php](file:///c:/source/luanvancuoiki/resources/views/blog/show.blade.php) đã được bọc lọc HTML bằng `strip_tags($post->content, 'ALLOW_LIST')`, loại bỏ nguy cơ chèn `<script>` hoặc iframe độc hại. |
| **Role Cache Invalidation** | [N-02](10-ket-qua-audit.md) / Sprint 1 | ✅ **ĐÃ FIX XÓA CACHE** | [CustomerAdminController.php](file:///c:/source/luanvancuoiki/app/Http/Controllers/Admin/CustomerAdminController.php) đã bổ sung `Cache::forget("users.{$user->id}.role_codes")` trong cả 2 hàm `syncRole()` và `updateStatus()`. Thay đổi quyền hoặc khóa tài khoản có hiệu lực ngay tức thì. |
| **Tồn kho thực tế (Sale Out)** | [C-02](10-ket-qua-audit.md#c-02) / Sprint 2 | ✅ **ĐÃ TỰ ĐỘNG TRỪ KHO** | [OrderAdminController.php](file:///c:/source/luanvancuoiki/app/Http/Controllers/Admin/OrderAdminController.php) hàm `createSaleOutTransaction()` đã thêm câu lệnh decrement tồn kho thực tế `DB::table('inventories')->decrement('quantity', $item->quantity)`. |
| **Cache Key Header Category** | [N-01](10-ket-qua-audit.md) / Sprint 3 | ✅ **ĐÃ ĐỒNG BỘ KEY** | [ProductAdminController.php](file:///c:/source/luanvancuoiki/app/Http/Controllers/Admin/ProductAdminController.php) `clearProductCaches()` đã sửa key xóa cache thành `layout.header_categories.v2` khớp với [AppServiceProvider.php](file:///c:/source/luanvancuoiki/app/Providers/AppServiceProvider.php). Menu danh mục trên header tự cập nhật ngay khi sửa sản phẩm/danh mục. |
| **Lỗi Mojibake tiếng Việt** | [N-05](10-ket-qua-audit.md) / Sprint 3 | ✅ **ĐÃ KHẮC PHỤC** | [bootstrap/app.php](file:///c:/source/luanvancuoiki/bootstrap/app.php) bẫy lỗi `InvalidSignatureException` đã được thay thế chuỗi lỗi mã hóa hai lần bằng tiếng Việt UTF-8 chuẩn. |
| **Bảo vệ File Asset** | Static view safety | ✅ **ĐÃ FIX SAFETY** | Bọc `file_exists()` cho các filemtime asset tại [blog/show.blade.php](file:///c:/source/luanvancuoiki/resources/views/blog/show.blade.php) và [tryon-ai.blade.php](file:///c:/source/luanvancuoiki/resources/views/tryon-ai.blade.php). |

---

## Các kiểm thử cú pháp & tính sẵn sàng

1. **PHP Syntax Lint (`php -l`):**
   * [QueuedRawMail.php](file:///c:/source/luanvancuoiki/app/Support/QueuedRawMail.php) — `No syntax errors`
   * [ProductAdminController.php](file:///c:/source/luanvancuoiki/app/Http/Controllers/Admin/ProductAdminController.php) — `No syntax errors`
   * [OrderAdminController.php](file:///c:/source/luanvancuoiki/app/Http/Controllers/Admin/OrderAdminController.php) — `No syntax errors`
   * [ReturnAdminController.php](file:///c:/source/luanvancuoiki/app/Http/Controllers/Admin/ReturnAdminController.php) — `No syntax errors`
   * [CustomerAdminController.php](file:///c:/source/luanvancuoiki/app/Http/Controllers/Admin/CustomerAdminController.php) — `No syntax errors`
   * [bootstrap/app.php](file:///c:/source/luanvancuoiki/bootstrap/app.php) — `No syntax errors`

2. **Cấu hình tương thích PHP:**
   * Loại bỏ các cú pháp PHP 8.0+ duy nhất như Constructor Property Promotion hay Union Types trong các file cập nhật để đảm bảo tính tương thích và bảo trì cao.
