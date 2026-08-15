# 140 câu hỏi phản biện dễ học - Laravel bán kính mắt

Cập nhật theo code hiện tại trong workspace `D:\SourceCode` ngày 15/08/2026.

Tài liệu này được mở rộng lên 140 câu hỏi để bao thêm các luồng mới, nhưng không bê nguyên câu trả lời cũ nếu code hiện tại đã thay đổi. Mục tiêu là giúp em hiểu để trả lời phản biện: lấy phần quan trọng của bản cũ, sửa các chỗ đã lỗi thời, và thêm các luồng mới trong code như đặt lịch đo mắt, thử kính, queue email, hủy đơn qua signed URL, xuất kho khi giao hàng và hoàn/đổi.

## 1. Tổng quan dự án

#### Q1: Dự án hiện tại dùng công nghệ gì?
- **Trả lời:** Dự án là website bán kính mắt viết bằng Laravel. Theo `composer.json`, dự án đang dùng PHP `^8.4`, Laravel Framework `^13.8`, Jetstream, Sanctum, Livewire và PHPUnit.
- **Nói khi bảo vệ:** "Dạ dự án của em là Laravel phiên bản mới, không còn là Laravel 10 hay 11 như tài liệu cũ nữa ạ."
- **Minh chứng:** `composer.json`.

#### Q2: Request từ trình duyệt đi qua những file nào?
- **Trả lời:** Request vào `public/index.php`, Laravel khởi tạo app trong `bootstrap/app.php`, sau đó đi qua middleware, route trong `routes/web.php` hoặc `routes/admin.php`, rồi tới controller và view.
- **Minh chứng:** `bootstrap/app.php`, `routes/web.php`, `routes/admin.php`.

#### Q3: Vì sao tách `routes/web.php` và `routes/admin.php`?
- **Trả lời:** Route khách hàng và route admin có mục đích khác nhau. Route khách dùng cho xem sản phẩm, giỏ hàng, thanh toán, tài khoản. Route admin dùng cho quản trị sản phẩm, đơn hàng, kho, báo cáo. Tách riêng giúp dễ quản lý và dễ phân quyền.
- **Minh chứng:** cuối `routes/web.php` có `require __DIR__ . '/admin.php';`.

#### Q4: `bootstrap/app.php` đang cấu hình gì quan trọng?
- **Trả lời:** File này khai báo routing, middleware web, alias middleware `admin`, loại CSRF cho `vnpay/ipn`, route health check `/up`, và xử lý lỗi signed URL hết hạn.
- **Minh chứng:** `bootstrap/app.php`.

#### Q5: Vì sao route `/` gọi `ClientRouteAliasController::home`?
- **Trả lời:** Controller này giúp gom logic trang chủ và xử lý các URL cũ. Khi đổi cấu trúc URL, link cũ vẫn có thể chuyển về trang mới thay vì bị lỗi.
- **Minh chứng:** `routes/web.php`, `app/Http/Controllers/ClientRouteAliasController.php`.

#### Q6: Route alias là gì?
- **Trả lời:** Là cơ chế nhận các đường dẫn cũ như `trang-chu`, `cua-hang`, `chitietsanpham`, `thanh-toan-2` rồi chuyển về route Laravel mới. Nó giúp dự án tương thích với link cũ.
- **Minh chứng:** `routes/web.php`, `routes/admin.php`.

#### Q7: Named route có lợi ích gì?
- **Trả lời:** Named route cho phép gọi `route('products.show', $product)` thay vì hardcode URL. Nếu sau này đổi đường dẫn, code ít bị hỏng hơn.
- **Minh chứng:** `routes/web.php`.

#### Q8: Vì sao có `declare(strict_types=1);`?
- **Trả lời:** Để PHP kiểm tra kiểu dữ liệu nghiêm ngặt hơn, hạn chế lỗi do tự ép kiểu ngầm.
- **Minh chứng:** `routes/web.php`, `bootstrap/app.php`, nhiều controller/service.

## 2. Middleware, bảo mật và phân quyền

#### Q9: Dự án đang dùng những middleware bảo mật nào?
- **Trả lời:** Có `ValidateRequestInput` để chặn request bất thường, `SecurityHeaders` để thêm header bảo mật, middleware `admin` để bảo vệ khu quản trị, và các middleware throttle để giới hạn tần suất request.
- **Minh chứng:** `bootstrap/app.php`, `app/Http/Middleware`.

#### Q10: `ValidateRequestInput` dùng để làm gì?
- **Trả lời:** Middleware này chặn query string quá dài, quá nhiều field, key không hợp lệ, payload quá sâu, chuỗi không đúng UTF-8 hoặc chứa ký tự điều khiển nguy hiểm.
- **Nói khi bảo vệ:** "Dạ middleware này là lớp lọc đầu vào trước khi request đi vào controller."
- **Minh chứng:** `app/Http/Middleware/ValidateRequestInput.php`.

#### Q11: `SecurityHeaders` thêm những header nào?
- **Trả lời:** Thêm `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`. Với trang admin/login/register thì thêm `Cache-Control: no-store, private`.
- **Minh chứng:** `app/Http/Middleware/SecurityHeaders.php`.

#### Q12: Vì sao `Permissions-Policy` cho phép camera?
- **Trả lời:** Vì luồng thử kính online cần camera. Microphone và geolocation không dùng nên bị tắt.
- **Minh chứng:** `SecurityHeaders.php`, route `/thu-kinh`.

#### Q13: Rate limit được cấu hình ở đâu?
- **Trả lời:** Trong `AppServiceProvider`, dự án có các bucket như `web-read`, `auth`, `admin-auth`, `cart`, `checkout`, `user-actions`, `admin`, `uploads`.
- **Minh chứng:** `app/Providers/AppServiceProvider.php`.

#### Q14: Vì sao login cần throttle?
- **Trả lời:** Đăng nhập dễ bị brute-force. `throttle:auth` và `throttle:admin-auth` giới hạn số lần thử theo email và IP.
- **Minh chứng:** `routes/web.php`, `routes/admin.php`, `AppServiceProvider.php`.

#### Q15: Middleware `admin` kiểm tra những gì?
- **Trả lời:** Kiểm tra user đã đăng nhập chưa, tài khoản có `ACTIVE` không, và có role `ADMIN` hoặc `STAFF` không. Một số route còn yêu cầu riêng `admin:ADMIN`.
- **Minh chứng:** `app/Http/Middleware/EnsureAdmin.php`, `routes/admin.php`.

#### Q16: Khác nhau giữa `admin` và `admin:ADMIN` là gì?
- **Trả lời:** `admin` cho phép `ADMIN` và `STAFF`. `admin:ADMIN` chỉ cho tài khoản có role `ADMIN`, dùng cho báo cáo, quản lý thành viên, banner, khuyến mãi và cấu hình nghiệp vụ.
- **Minh chứng:** `routes/admin.php`.

#### Q17: Vì sao role admin được cache?
- **Trả lời:** Để mỗi lần admin chuyển trang không phải query join `user_roles` và `roles`. Cache role trong 5 phút giúp trang admin nhanh hơn.
- **Minh chứng:** `EnsureAdmin::hasAnyRole()`.

#### Q18: CSRF được xử lý ra sao?
- **Trả lời:** Các form POST/PUT/PATCH/DELETE phải có CSRF token. Riêng `vnpay/ipn` được loại khỏi CSRF vì đó là request server-to-server từ VNPay, bảo mật bằng chữ ký HMAC.
- **Minh chứng:** `bootstrap/app.php`, `routes/web.php`, `VnPayService.php`.

## 3. Xác thực tài khoản

#### Q19: Đăng ký tài khoản tạo những dữ liệu nào?
- **Trả lời:** Tạo user, gán role `USER`, tạo địa chỉ mặc định và gửi email xác thực. Nếu gửi email xác thực lỗi, code xóa lại user, role và địa chỉ vừa tạo để tránh dữ liệu rác.
- **Minh chứng:** `app/Http/Controllers/AuthController.php`.

#### Q20: Mật khẩu được lưu như thế nào?
- **Trả lời:** Mật khẩu không lưu dạng text. Dự án dùng `Hash::make()` để hash vào cột `password_hash`, khi đăng nhập dùng `Hash::check()`.
- **Minh chứng:** `AuthController.php`, `AccountController.php`.

#### Q21: Vì sao đăng nhập phải regenerate session?
- **Trả lời:** Sau khi đăng nhập, `$request->session()->regenerate()` tạo session ID mới để giảm nguy cơ session fixation.
- **Minh chứng:** `AuthController::login()`.

#### Q22: Vì sao đăng xuất phải invalidate session và regenerate token?
- **Trả lời:** Để hủy phiên đăng nhập cũ, xóa dữ liệu session và làm CSRF token cũ hết hiệu lực.
- **Minh chứng:** `AuthController::logout()`, `EnsureAdmin::logout()`.

#### Q23: Email verification hoạt động thế nào?
- **Trả lời:** Khi đăng ký, hệ thống tạo temporary signed URL có hạn 60 phút. Link có `user` và hash email. Khi user bấm link, controller kiểm tra hash rồi cập nhật `email_verified_at`.
- **Minh chứng:** `AuthController::sendEmailVerificationLink()`, `AuthController::verifyEmail()`.

#### Q24: Quên mật khẩu có lưu token thật trong database không?
- **Trả lời:** Không. Token thật chỉ gửi qua email. Database chỉ lưu `token_hash = sha256(token)`, có `expires_at` 60 phút và `used_at` để chặn dùng lại.
- **Minh chứng:** `AuthController::sendResetPasswordLink()`, `AuthController::validResetToken()`.

## 4. Sản phẩm và thử kính

#### Q25: Trang danh sách sản phẩm lọc theo gì?
- **Trả lời:** Lọc theo từ khóa, danh mục, thương hiệu, dáng gọng, chất liệu, UV, màu, size, sản phẩm sale, khoảng giá và sắp xếp theo giá, tên, phổ biến hoặc mới nhất.
- **Minh chứng:** `ProductController::index()`.

#### Q26: Giá hiển thị của sản phẩm tính thế nào?
- **Trả lời:** Sản phẩm ưu tiên `sale_price`, nếu không có thì dùng `base_price`. Biến thể ưu tiên giá sale của sản phẩm, rồi `variant_price`, rồi `base_price`.
- **Minh chứng:** `Product.php`, `ProductVariant.php`.

#### Q27: Sản phẩm không active có xem được không?
- **Trả lời:** Không. Trang chi tiết dùng `abort_unless($product->status === 'ACTIVE', 404)`.
- **Minh chứng:** `ProductController::show()`.

#### Q28: Tồn kho hiển thị trên sản phẩm lấy từ đâu?
- **Trả lời:** Lấy từ bảng `inventories`, gom theo `variant_id`, chỉ tính kho active và loại trừ kho cách ly `QUARANTINE`.
- **Minh chứng:** `ProductController::show()`, `CartController::sellableStockForMany()`.

#### Q29: Ai được đánh giá sản phẩm?
- **Trả lời:** Chỉ khách đã mua sản phẩm trong đơn `DELIVERED` và dòng sản phẩm đó chưa có review mới được đánh giá.
- **Minh chứng:** `ProductController::reviewOrderItemFor()`.

#### Q30: Luồng thử kính online hoạt động dựa trên gì?
- **Trả lời:** Trang thử kính lấy các sản phẩm active có `product_code` giống SKU model Jeeliz. Sản phẩm có mã hợp lệ sẽ được đưa vào payload cho giao diện thử kính.
- **Minh chứng:** `ProductController::tryOn()`.

#### Q31: `tryOnModelCheck` dùng để làm gì?
- **Trả lời:** Gọi API Jeeliz theo SKU để kiểm tra sản phẩm có model 3D hợp lệ không. Nếu API lỗi hoặc model không hợp lệ thì trả `supported: false`.
- **Minh chứng:** `ProductController::tryOnModelCheck()`.

#### Q32: Khi lưu kết quả thử kính, hệ thống lưu gì?
- **Trả lời:** Lưu user, product, variant nếu có, tên/email user, tên sản phẩm, model SKU, giá, ảnh kết quả và chế độ thử `camera` hoặc `image`.
- **Minh chứng:** `ProductController::storeTryOnSnapshot()`, `TryOnSnapshot` model.

#### Q33: Vì sao ảnh thử kính phải kiểm tra base64?
- **Trả lời:** Để chắc ảnh đúng định dạng PNG/JPEG, decode được, không quá 5MB và không phải dữ liệu giả.
- **Minh chứng:** `ProductController::storeTryOnImage()`.

#### Q34: Khách xem lại ảnh thử kính ở đâu?
- **Trả lời:** Trang tài khoản lấy `TryOnSnapshot` theo `user_id`, sắp xếp mới nhất và phân trang.
- **Minh chứng:** `AccountController::index()`.

## 5. Luồng mới: đặt lịch đo mắt

#### Q35: Luồng đặt lịch đo mắt gồm những route nào?
- **Trả lời:** Có route tạo lịch `/dat-lich-do-mat`, tra cứu slot không khả dụng, tra cứu lịch hẹn, và đổi lịch bằng route PATCH `/tra-cuu-lich-hen/{appointment}/doi-lich`.
- **Minh chứng:** `routes/web.php`, `AppointmentController.php`.

#### Q36: Hệ thống có những dịch vụ đo mắt nào?
- **Trả lời:** Có đo thị lực cơ bản `CO_BAN`, đo chuyên sâu `CHUYEN_SAU`, và đo cho trẻ em `TRE_EM`. Mỗi dịch vụ có tên, giá, thời lượng và mô tả.
- **Minh chứng:** `AppointmentController::SERVICES`.

#### Q37: Khung giờ đặt lịch bị giới hạn thế nào?
- **Trả lời:** Chỉ cho đặt các slot cố định như `09:00`, `10:00`, `11:00`, `14:00` đến `18:00`, trong vòng 30 ngày tới. Mỗi slot chỉ nhận 1 lịch active.
- **Minh chứng:** `AppointmentController::TIME_SLOTS`, `SLOT_CAPACITY`, `MAX_ADVANCE_DAYS`.

#### Q38: Làm sao tránh hai khách đặt trùng slot?
- **Trả lời:** Code kiểm tra slot đã đầy trước khi tạo. Ngoài ra bảng `appointments` có `slot_lock_key = date|time` và unique index, nên nếu hai request cùng lúc thì database chặn request đến sau.
- **Minh chứng:** `AppointmentController::store()`, migration `2026_08_14_211000_add_slot_lock_key_to_appointments_table.php`.

#### Q39: Khách tra cứu lịch hẹn bằng gì?
- **Trả lời:** Khách nhập mã lịch hẹn và email hoặc số điện thoại. Controller tìm đúng lịch rồi mới cho xem/đổi.
- **Minh chứng:** `AppointmentController::lookup()`.

#### Q40: Khách được đổi lịch mấy lần?
- **Trả lời:** Tối đa 1 lần, chỉ khi lịch đang `PENDING` hoặc `CONFIRMED`, và còn cách giờ hẹn hơn 24 giờ.
- **Minh chứng:** `Appointment::MAX_RESCHEDULE_COUNT`, `Appointment::canReschedule()`.

#### Q41: Đổi lịch xong trạng thái về đâu?
- **Trả lời:** Status quay về `PENDING`, xóa `confirmed_at`, tăng `reschedule_count`, lưu thời điểm và lý do đổi lịch.
- **Minh chứng:** `AppointmentController::reschedule()`.

#### Q42: Admin quản lý lịch hẹn làm được gì?
- **Trả lời:** Admin có thể xác nhận, hủy, hoàn tất hoặc đánh dấu khách không đến. Các thao tác dùng transaction và `lockForUpdate()` để tránh cập nhật trùng.
- **Minh chứng:** `routes/admin.php`, `AppointmentAdminController.php`.

#### Q43: Nhắc lịch đo mắt được gửi thế nào?
- **Trả lời:** Lệnh `appointments:send-reminders` lấy các lịch `CONFIRMED`, chưa gửi reminder và nằm trong 24 giờ tới, rồi gửi email nhắc lịch.
- **Minh chứng:** `routes/console.php`, `AppointmentNotificationService.php`.

## 6. Giỏ hàng và checkout

#### Q44: Giỏ hàng lưu ở đâu?
- **Trả lời:** Giỏ hàng lưu trong session dạng `variant_id => quantity`, chưa tạo bản ghi database cho đến khi checkout.
- **Minh chứng:** `CartController.php`.

#### Q45: Mỗi đơn được đặt tối đa bao nhiêu sản phẩm?
- **Trả lời:** Tổng số lượng tối đa là 10 sản phẩm. Cả giỏ hàng và checkout đều kiểm tra giới hạn này.
- **Minh chứng:** `CartController::MAX_TOTAL_QUANTITY`, `CheckoutController::MAX_CART_QUANTITY`.

#### Q46: Thêm vào giỏ có kiểm tra tồn kho không?
- **Trả lời:** Có. Code tính tồn kho bán được của biến thể, trừ số lượng đã có trong giỏ, rồi chỉ cho thêm trong phạm vi còn hàng.
- **Minh chứng:** `CartController::store()`.

#### Q47: Nếu cập nhật số lượng về 0 thì sao?
- **Trả lời:** Biến thể đó bị xóa khỏi giỏ hàng session.
- **Minh chứng:** `CartController::update()`.

#### Q48: Checkout kiểm tra thông tin gì?
- **Trả lời:** Tên người nhận, số điện thoại, địa chỉ chi tiết, tỉnh/thành, phương thức thanh toán `COD` hoặc `VNPAY`, ghi chú.
- **Minh chứng:** `CheckoutController::store()`.

#### Q49: Mã giảm giá được kiểm tra như thế nào?
- **Trả lời:** Mã phải tồn tại, scope `ORDER`, status `ACTIVE`, còn thời hạn, chưa hết lượt dùng, chưa vượt lượt theo user, đạt đơn tối thiểu và giảm được số tiền lớn hơn 0.
- **Minh chứng:** `CheckoutController::promotionFromCode()`.

#### Q50: Đơn COD được tạo như thế nào?
- **Trả lời:** Trong transaction, tạo `Order`, tạo `OrderItem`, tăng lượt dùng khuyến mãi nếu có, gửi email xác nhận, sau đó xóa giỏ hàng và mã giảm giá khỏi session.
- **Minh chứng:** `CheckoutController::createOrder()`, `CheckoutController::store()`.

## 7. VNPay

#### Q51: VNPay có tạo đơn thật ngay khi bấm thanh toán không?
- **Trả lời:** Không. Code lưu draft checkout vào session và cache, tạo URL thanh toán. Đơn thật chỉ tạo khi VNPay trả kết quả thành công và chữ ký hợp lệ.
- **Minh chứng:** `CheckoutController::storePendingVnPayCheckout()`, `VnPayController::createPaidOrderFromDraft()`.

#### Q52: Vì sao draft VNPay lưu cả session và cache?
- **Trả lời:** Session phục vụ khách quay lại bằng trình duyệt. Cache phục vụ IPN server-to-server vì IPN không có session của khách.
- **Minh chứng:** `CheckoutController::storePendingVnPayCheckout()`, `VnPayController::pendingDraft()`.

#### Q53: URL VNPay được ký thế nào?
- **Trả lời:** Tạo các tham số `vnp_*`, nhân số tiền với 100, sắp xếp key bằng `ksort()`, URL encode, băm HMAC-SHA512 với secret rồi gắn vào `vnp_SecureHash`.
- **Minh chứng:** `VnPayService.php`.

#### Q54: Return URL và IPN URL khác nhau thế nào?
- **Trả lời:** Return URL là trình duyệt khách quay về website. IPN là VNPay gọi server-to-server để xác nhận giao dịch. IPN đáng tin cậy hơn để chốt trạng thái.
- **Minh chứng:** `VnPayController::return()`, `VnPayController::ipn()`.

#### Q55: VNPay verify những điều kiện nào?
- **Trả lời:** Kiểm tra chữ ký, mã phản hồi `00`, transaction status `00`, số tiền khớp, và tồn tại order hoặc draft.
- **Minh chứng:** `VnPayService::verify()`, `VnPayController.php`.

#### Q56: Nếu VNPay trả số tiền không khớp thì sao?
- **Trả lời:** Return URL báo lỗi cho khách. IPN trả `RspCode` `04`. Nếu đã có order thì payment bị đánh dấu thất bại hoặc order bị hủy nếu chưa thanh toán.
- **Minh chứng:** `VnPayController.php`.

#### Q57: Nếu IPN gọi lại nhiều lần thì sao?
- **Trả lời:** Nếu order đã `PAID`, controller trả `RspCode` `02` với message `Order already confirmed`, tránh ghi thanh toán lặp.
- **Minh chứng:** `VnPayController::ipn()`.

## 8. Email, queue và hủy đơn

#### Q58: Email xác nhận đơn hàng có dùng queue không?
- **Trả lời:** Có. `OrderConfirmationEmailService` dùng `QueuedRawMail`, class này dispatch `SendRawMailJob`. Nếu dispatch lỗi thì fallback gửi khi app terminating.
- **Minh chứng:** `OrderConfirmationEmailService.php`, `QueuedRawMail.php`, `SendRawMailJob.php`.

#### Q59: Queue worker chạy ở đâu?
- **Trả lời:** Script `composer dev` có chạy `php artisan queue:listen`, nghĩa là khi dev server chạy thì queue listener cũng chạy.
- **Minh chứng:** `composer.json`.

#### Q60: Hủy đơn trong admin có đổi status ngay không?
- **Trả lời:** Không. Admin chỉ gửi yêu cầu hủy qua email. Đơn chỉ chuyển sang `CANCELLED` khi khách bấm link xác nhận hủy.
- **Minh chứng:** `OrderAdminController::cancel()`, `OrderCancellationService.php`.

#### Q61: Link xác nhận hủy đơn an toàn ở đâu?
- **Trả lời:** Link là temporary signed URL hết hạn sau 3 ngày. Token thật chỉ gửi qua email, database chỉ lưu hash SHA-256. Khi xác nhận dùng `lockForUpdate()` và xóa token sau khi dùng.
- **Minh chứng:** `OrderCancellationService.php`, `OrderCancellationController.php`.

#### Q62: Vì sao dùng `hash_equals()` khi so sánh token?
- **Trả lời:** Để tránh timing attack khi so sánh chuỗi nhạy cảm như token hủy đơn, token email, chữ ký VNPay.
- **Minh chứng:** `OrderCancellationService.php`, `AuthController.php`, `VnPayService.php`.

## 9. Admin, đơn hàng và kho

#### Q63: Quy trình trạng thái đơn hàng là gì?
- **Trả lời:** `PENDING` hoặc `AWAITING_PAYMENT` -> `CONFIRMED` -> `DELIVERING` -> `DELIVERED`. Sau khi giao có thể sang `RETURN_PENDING`, `RETURNED`, `EXCHANGED`.
- **Minh chứng:** `OrderAdminController::STATUS_TRANSITIONS`.

#### Q64: Khi nào hệ thống trừ kho bán hàng?
- **Trả lời:** Khi admin chuyển đơn sang `DELIVERING`, hệ thống tạo giao dịch kho `SALE_OUT` và gọi `InventoryService::issue()` để trừ tồn. Checkout chỉ kiểm tra tồn, chưa trừ kho.
- **Minh chứng:** `OrderAdminController::createSaleOutTransaction()`.

#### Q65: Vì sao trừ kho nằm trong transaction?
- **Trả lời:** Nếu không đủ tồn, service ném lỗi, transaction rollback, đơn giữ trạng thái cũ. Như vậy tránh đơn sang "đang giao" khi kho không đủ hàng.
- **Minh chứng:** `OrderAdminController::changeStatusInTransaction()`, `InventoryService::issue()`.

#### Q66: Làm sao tránh trừ kho hai lần cho cùng một đơn?
- **Trả lời:** Trước khi tạo `SALE_OUT`, code kiểm tra giao dịch đã tồn tại theo `related_order_id` hoặc note xuất kho.
- **Minh chứng:** `OrderAdminController::saleOutTransactionExists()`.

#### Q67: Kho hiện tại là một kho hay nhiều kho?
- **Trả lời:** Code hiện tại đã gom về kho trung tâm ID 1. `InventoryService` vẫn giữ interface như nhiều kho, nhưng các hàm lấy kho bán/kho cách ly hiện đều trả về `1`.
- **Minh chứng:** `InventoryService.php`, migration `2026_08_08_140000_consolidate_to_single_warehouse.php`.

#### Q68: Phiếu kho thủ công gồm loại nào?
- **Trả lời:** Admin tạo được phiếu `IMPORT` và `EXPORT`. `IMPORT` cộng tồn và có thể kích hoạt sản phẩm, `EXPORT` trừ tồn nếu đủ hàng.
- **Minh chứng:** `WarehouseAdminController::storeTransaction()`.

#### Q69: Vì sao `InventoryService::issue()` dùng điều kiện `quantity >= ?`?
- **Trả lời:** Đây là cách trừ kho atomic ở database. Nếu hai request cùng trừ, request nào không còn đủ hàng sẽ update 0 dòng và báo lỗi, tránh tồn âm.
- **Minh chứng:** `InventoryService::issue()`.

#### Q70: Cảnh báo sắp hết hàng tính thế nào?
- **Trả lời:** Nếu `quantity <= min_stock_level` thì xem là sắp hết hàng. Nếu `quantity <= 0` thì hết hàng.
- **Minh chứng:** `WarehouseAdminController::index()`.

## 10. Hoàn/đổi và báo cáo

#### Q71: Khách chỉ được tạo hoàn/đổi khi đơn ở trạng thái nào?
- **Trả lời:** Đơn phải ở `DELIVERED`, `RETURN_PENDING`, `RETURNED` hoặc `EXCHANGED`. Đơn đang chờ xử lý, đang giao, đã hủy hoặc thất lạc không được tạo hoàn/đổi bình thường.
- **Minh chứng:** `ReturnRequestController::ELIGIBLE_ORDER_STATUSES`.

#### Q72: Mỗi dòng sản phẩm được hoàn/đổi mấy lần?
- **Trả lời:** Chỉ một lần. Nếu `order_item_id` đã có trong `ReturnRequestItem` thì số lượng còn được yêu cầu là 0.
- **Minh chứng:** `ReturnRequestController::remainingReturnQuantity()`.

#### Q73: Return request có những trạng thái nào?
- **Trả lời:** `PENDING`, `APPROVED`, `REJECTED`, `RECEIVED`, `COMPLETED`, `CANCELLED`.
- **Minh chứng:** `ReturnAdminController::update()`.

#### Q74: Khi admin chuyển hoàn/đổi sang `RECEIVED` thì kho thay đổi gì?
- **Trả lời:** Hệ thống tạo phiếu `RETURN_IN`, thêm item và gọi `InventoryService::receive()` để nhập hàng khách trả về.
- **Minh chứng:** `ReturnAdminController::receiveFaultyGoods()`.

#### Q75: Khi hoàn/đổi loại `EXCHANGE` sang `COMPLETED` thì kho thay đổi gì?
- **Trả lời:** Hệ thống tạo phiếu `EXCHANGE_OUT` và gọi `InventoryService::issue()` để xuất hàng mới giao đổi cho khách.
- **Minh chứng:** `ReturnAdminController::issueExchangeGoods()`.

#### Q76: Đánh giá hư hỏng hàng trả về lưu như thế nào?
- **Trả lời:** Admin nhập phần trăm và mô tả theo từng bộ phận như gọng trái, gọng phải, tròng trái, tròng phải, bản lề, đệm mũi. Code quy đổi phần trăm thành mức `NONE`, `LIGHT`, `MEDIUM`, `HEAVY`, `SEVERE`.
- **Minh chứng:** `ReturnAdminController::saveDamageAssessments()`.

#### Q77: Báo cáo doanh thu có tính đơn hủy không?
- **Trả lời:** Không. Các báo cáo loại `CANCELLED`. Riêng tổng doanh thu delivered chỉ tính đơn `DELIVERED`.
- **Minh chứng:** `ReportAdminController.php`.

#### Q78: Báo cáo sản phẩm bán chạy dựa trên bảng nào?
- **Trả lời:** Dựa trên `order_items` join với `orders`, `products`, `categories`, `brands`, loại đơn `CANCELLED`, rồi group theo sản phẩm.
- **Minh chứng:** `ReportAdminController::topSales()`.

#### Q79: Hóa đơn hiện tại là PDF hay view/email?
- **Trả lời:** Code hiện tại hiển thị hóa đơn bằng Blade view và có chức năng gửi hóa đơn qua email raw. `composer.json` không thấy DomPDF, nên không nên nói chắc là server đang sinh PDF bằng DomPDF.
- **Minh chứng:** `AccountController::invoice()`, `OrderInvoiceEmailService.php`, `composer.json`.

#### Q80: Khi chốt phản biện nên nhấn mạnh điều gì?
- **Trả lời:** Nên nhấn mạnh dự án đã có routing rõ ràng, phân quyền admin, security middleware, rate limit, VNPay có IPN/idempotency, queue email, thử kính, đặt lịch đo mắt, xuất kho khi giao hàng và hoàn/đổi có ghi nhận kho.
- **Minh chứng:** `composer.json`, `bootstrap/app.php`, `routes/web.php`, `routes/admin.php`, `VnPayController.php`, `AppointmentController.php`, `OrderAdminController.php`, `ReturnAdminController.php`.

## 11. Câu hỏi bổ sung để đủ 100 câu và tránh trả lời theo luồng cũ

#### Q81: Service Provider trong dự án này dùng để làm gì?
- **Trả lời:** `AppServiceProvider` dùng để bootstrap các cấu hình chạy chung: phân trang Bootstrap 5, ép HTTPS khi production, cache link danh mục trên header, và đăng ký các rate limiter.
- **Minh chứng:** `app/Providers/AppServiceProvider.php`.

#### Q82: Blade Template Engine có ưu điểm gì so với PHP thuần?
- **Trả lời:** Blade giúp tách giao diện rõ hơn, có layout/component/include, escape dữ liệu mặc định bằng `{{ }}` để giảm XSS, và viết điều kiện/vòng lặp gọn hơn PHP thuần.
- **Minh chứng:** thư mục `resources/views`.

#### Q83: Vì sao không nên trả lời rằng hóa đơn đang sinh PDF bằng DomPDF?
- **Trả lời:** Vì code hiện tại hiển thị hóa đơn bằng Blade view và gửi email hóa đơn dạng raw mail. `composer.json` không khai báo DomPDF, nên nói đang dùng DomPDF là sai với code hiện tại.
- **Minh chứng:** `AccountController::invoice()`, `OrderInvoiceEmailService.php`, `composer.json`.

#### Q84: Route xem hóa đơn cần bảo vệ thế nào?
- **Trả lời:** Chỉ chủ đơn hàng mới được xem hóa đơn. Controller kiểm tra `order.user_id === Auth::id()`, nếu không đúng thì trả 403 để tránh lỗi IDOR.
- **Minh chứng:** `AccountController::invoice()`, `AccountController::emailInvoice()`.

#### Q85: IDOR là gì và dự án chống ở đâu?
- **Trả lời:** IDOR là lỗi người dùng sửa ID trên URL để xem/sửa dữ liệu của người khác. Dự án chống bằng cách kiểm tra chủ sở hữu ở đơn hàng, hóa đơn, địa chỉ và yêu cầu hoàn/đổi.
- **Minh chứng:** `AccountController`, `ReturnRequestController`.

#### Q86: Vì sao cập nhật địa chỉ phải kiểm tra chủ sở hữu?
- **Trả lời:** Nếu không kiểm tra, khách có thể sửa URL `address id` để chỉnh địa chỉ của người khác. Code dùng `ensureOwnAddress()` để chặn.
- **Minh chứng:** `AccountController::ensureOwnAddress()`.

#### Q87: Người dùng được lưu tối đa bao nhiêu địa chỉ?
- **Trả lời:** Code hiện tại giới hạn mỗi user tối đa 2 địa chỉ. Khi thêm địa chỉ mới, nếu đã đủ 2 địa chỉ thì redirect báo lỗi.
- **Minh chứng:** `AccountController::createAddress()`, `AccountController::storeAddress()`.

#### Q88: Địa chỉ mặc định được xử lý thế nào?
- **Trả lời:** Khi user chọn địa chỉ mặc định, code chạy transaction để bỏ mặc định các địa chỉ khác, rồi đặt địa chỉ hiện tại làm mặc định. Nếu xóa địa chỉ mặc định, hệ thống chọn địa chỉ còn lại mới nhất làm mặc định.
- **Minh chứng:** `AccountController::storeAddress()`, `updateAddress()`, `destroyAddress()`.

#### Q89: Vì sao `AppServiceProvider` dùng `Paginator::useBootstrapFive()`?
- **Trả lời:** Dự án dùng giao diện Bootstrap. Nếu để paginator mặc định Tailwind, phần phân trang có thể hiển thị sai class và layout.
- **Minh chứng:** `AppServiceProvider::boot()`.

#### Q90: Header danh mục sản phẩm được cache thế nào?
- **Trả lời:** `AppServiceProvider` dùng view composer cho `layouts.app`, lấy danh mục active có sản phẩm active và cache key `layout.header_categories.v2` trong 10 phút.
- **Minh chứng:** `AppServiceProvider::headerProductLinks()`.

#### Q91: Vì sao ảnh sản phẩm cần chuẩn hóa đường dẫn?
- **Trả lời:** Dữ liệu ảnh có thể là URL đầy đủ, đường dẫn bắt đầu bằng `upload/`, `anh_san_pham/`, hoặc chỉ là tên file. Accessor `getImageUrlAttribute()` chuẩn hóa để view luôn nhận URL đúng.
- **Minh chứng:** `app/Models/Product.php`.

#### Q92: Sản phẩm có thể được tự kích hoạt khi nhập kho không?
- **Trả lời:** Có. Khi admin tạo phiếu `IMPORT`, code cộng tồn kho và gọi `activateVariantProduct()` để bật variant, đồng thời bật sản phẩm nếu đang `DRAFT` hoặc `INACTIVE`.
- **Minh chứng:** `WarehouseAdminController::storeTransaction()`, `activateVariantProduct()`.

#### Q93: Vì sao checkout chỉ kiểm tra tồn kho mà chưa trừ kho?
- **Trả lời:** Vì hệ thống đang thiết kế trừ kho khi đơn chuyển sang `DELIVERING`. Checkout chỉ đảm bảo lúc đặt đơn còn đủ hàng; trừ kho thật diễn ra ở bước xử lý giao hàng để đồng bộ nghiệp vụ kho.
- **Minh chứng:** `CheckoutController::store()`, `OrderAdminController::createSaleOutTransaction()`.

#### Q94: Nếu thầy cô hỏi "hai khách cùng mua sản phẩm cuối cùng thì sao" trả lời thế nào?
- **Trả lời:** Ở checkout hệ thống có kiểm tra tồn trước khi tạo đơn. Khi xuất kho thật, `InventoryService::issue()` dùng update có điều kiện `quantity >= số lượng`, nên nếu không đủ tồn thì rollback và không cho đơn chuyển sang `DELIVERING`.
- **Minh chứng:** `CheckoutController::store()`, `InventoryService::issue()`, `OrderAdminController::changeStatusInTransaction()`.

#### Q95: Vì sao không nên nói giỏ hàng lưu database?
- **Trả lời:** Code hiện tại lưu giỏ hàng trong session, không có bảng cart riêng. Database chỉ có đơn hàng và dòng đơn hàng sau khi checkout thành công.
- **Minh chứng:** `CartController.php`, `CheckoutController.php`.

#### Q96: Tại sao ảnh thử kính lưu trong `public/upload/tryons/YYYY/MM`?
- **Trả lời:** Cách chia thư mục theo năm/tháng giúp tránh một thư mục chứa quá nhiều file, dễ quản lý và dễ dọn dẹp hơn.
- **Minh chứng:** `ProductController::storeTryOnImage()`.

#### Q97: Luồng đặt lịch có gửi email ở những sự kiện nào?
- **Trả lời:** Có email khi tiếp nhận lịch, xác nhận lịch, hủy lịch, đổi lịch và nhắc lịch. Các nội dung này nằm trong `AppointmentNotificationService`.
- **Minh chứng:** `app/Services/AppointmentNotificationService.php`.

#### Q98: Lệnh nhắc lịch có tự chạy nếu không cấu hình scheduler không?
- **Trả lời:** Code đã có command `appointments:send-reminders`, nhưng để tự chạy định kỳ trên production cần cấu hình scheduler/cron gọi Artisan. Nếu chỉ có command mà không cấu hình cron thì phải chạy thủ công.
- **Minh chứng:** `routes/console.php`.

#### Q99: Vì sao email xác nhận hủy đơn phải có khách bấm xác nhận?
- **Trả lời:** Vì hủy đơn là thao tác nhạy cảm. Admin chỉ gửi yêu cầu, khách xác nhận qua signed URL thì đơn mới chuyển `CANCELLED`. Cách này tránh admin hủy nhầm mà khách không biết.
- **Minh chứng:** `OrderCancellationService.php`, `OrderCancellationController.php`.

#### Q100: Khi tài liệu cũ nói sai luồng hiện tại thì em nên trả lời thế nào?
- **Trả lời:** Nên trả lời theo code hiện tại, không trả lời theo tài liệu cũ. Ví dụ: dự án hiện dùng Laravel `^13.8`, hóa đơn không dùng DomPDF, giỏ hàng lưu session, VNPay tạo draft trước rồi mới tạo đơn sau khi thanh toán thành công, và kho trừ khi đơn sang `DELIVERING`.
- **Minh chứng:** `composer.json`, `CartController.php`, `VnPayController.php`, `OrderAdminController.php`, `OrderInvoiceEmailService.php`.

## 12. Tài khoản khách hàng và dữ liệu cá nhân

#### Q101: Vì sao khi quên mật khẩu, hệ thống vẫn trả thông báo chung nếu email không tồn tại?
- **Trả lời:** Để tránh lộ email nào đang tồn tại trong hệ thống. Nếu trả "email không tồn tại", attacker có thể dò danh sách tài khoản thật.
- **Minh chứng:** `AuthController::sendResetPasswordLink()`.

#### Q102: Vì sao token reset mật khẩu cũ bị đánh dấu `used_at` trước khi tạo token mới?
- **Trả lời:** Để chỉ link reset mới nhất còn hiệu lực. Nếu user yêu cầu nhiều lần, link cũ không dùng được nữa, giảm rủi ro bị dùng nhầm hoặc bị lộ.
- **Minh chứng:** `AuthController::sendResetPasswordLink()`.

#### Q103: Vì sao đăng nhập có `Hash::needsRehash()`?
- **Trả lời:** Nếu cấu hình thuật toán hash thay đổi, mật khẩu sẽ được rehash lại sau lần đăng nhập hợp lệ. Điều này giúp nâng cấp bảo mật dần mà không bắt user đổi mật khẩu ngay.
- **Minh chứng:** `AuthController::login()`.

#### Q104: Vì sao user chưa xác thực email bị chặn đăng nhập?
- **Trả lời:** Vì hệ thống cần chắc email là thật trước khi cho dùng tài khoản. Điều này cũng hỗ trợ các luồng gửi hóa đơn, reset mật khẩu, xác nhận hủy đơn.
- **Minh chứng:** `AuthController::login()`.

#### Q105: Vì sao đăng ký dùng transaction?
- **Trả lời:** Vì đăng ký tạo nhiều dữ liệu cùng lúc: user, role, địa chỉ. Transaction giúp các bước này nhất quán; nếu lỗi thì rollback hoặc cleanup để tránh dữ liệu thiếu.
- **Minh chứng:** `AuthController::register()`.

#### Q106: Hồ sơ cá nhân kiểm tra ngày sinh như thế nào?
- **Trả lời:** Ngày sinh phải là ngày hợp lệ, không lớn hơn hôm nay và không nhỏ hơn năm 1900. Điều này tránh dữ liệu phi thực tế.
- **Minh chứng:** `AccountController::updateProfile()`.

#### Q107: Vì sao số điện thoại được validate bằng regex đầu số Việt Nam?
- **Trả lời:** Để hạn chế nhập sai định dạng, đặc biệt vì số điện thoại dùng cho giao hàng, địa chỉ và đặt lịch đo mắt.
- **Minh chứng:** `AuthController`, `AccountController`, `CheckoutController`.

#### Q108: Vì sao danh sách tỉnh/thành được whitelist?
- **Trả lời:** Trường tỉnh/thành không cho nhập tùy ý mà phải thuộc danh sách có sẵn. Điều này giúp dữ liệu địa chỉ đồng nhất hơn.
- **Minh chứng:** `AuthController::cities()`, `AccountController::cities()`.

#### Q109: Vì sao đổi mật khẩu yêu cầu mật khẩu hiện tại?
- **Trả lời:** Để đảm bảo người đang thao tác thật sự biết mật khẩu cũ. Nếu ai đó mượn máy đang đăng nhập thì cũng không đổi mật khẩu được nếu không biết mật khẩu hiện tại.
- **Minh chứng:** `AccountController::updatePassword()`.

#### Q110: Vì sao trang tài khoản hiển thị cả đơn hàng và snapshot thử kính?
- **Trả lời:** Vì trang tài khoản là nơi gom thông tin cá nhân của khách: địa chỉ, đơn gần đây, và lịch sử ảnh thử kính đã lưu.
- **Minh chứng:** `AccountController::index()`.

## 13. Quản trị sản phẩm, nội dung và giao diện bán hàng

#### Q111: Vì sao danh sách sản phẩm dùng `paginate(6)`?
- **Trả lời:** Để không tải tất cả sản phẩm một lúc trên trang khách hàng. Phân trang giúp giảm tải database và giao diện dễ xem hơn.
- **Minh chứng:** `ProductController::index()`.

#### Q112: Vì sao trang chi tiết sản phẩm load nhiều quan hệ như brand, category, images, variants?
- **Trả lời:** Vì view chi tiết cần hiển thị đầy đủ thương hiệu, danh mục, hình ảnh, màu, size và tồn kho theo biến thể. Eager loading giúp hạn chế query lặp.
- **Minh chứng:** `ProductController::show()`.

#### Q113: Vì sao sản phẩm liên quan lấy theo cùng danh mục?
- **Trả lời:** Sản phẩm cùng danh mục thường có nhu cầu gần nhau, ví dụ cùng loại kính. Điều này giúp gợi ý sản phẩm hợp lý hơn cho khách.
- **Minh chứng:** `ProductController::show()`.

#### Q114: Vì sao review hiển thị cả `VISIBLE` và `PENDING` trong product model?
- **Trả lời:** Code hiện tại xem cả review `VISIBLE` và `PENDING` là review có thể hiện ở luồng sản phẩm. Khi bảo vệ nên nói theo code hiện tại, không tự khẳng định chỉ review đã duyệt mới hiện nếu chưa kiểm tra lại chính sách view.
- **Minh chứng:** `Product::visibleReviews()`.

#### Q115: Vì sao khi upload ảnh thử kính phải giới hạn chuỗi input tối đa?
- **Trả lời:** Field `image` là base64 nên có thể rất lớn. Validate `max:7000000` và kiểm tra binary tối đa 5MB giúp tránh request quá nặng.
- **Minh chứng:** `ProductController::storeTryOnSnapshot()`, `storeTryOnImage()`.

#### Q116: Vì sao model thử kính chỉ lấy product code có dấu `_`?
- **Trả lời:** Code đang dùng quy ước SKU model Jeeliz có dấu gạch dưới, ví dụ dạng `rayban_aviator_or_vert`. Mã sản phẩm tự sinh `SP20...` bị loại để tránh gọi model không tồn tại.
- **Minh chứng:** `ProductController::tryOn()`.

#### Q117: Vì sao `tryOnModelCheck` dùng timeout 5 giây?
- **Trả lời:** Vì đây là API bên ngoài. Timeout giúp giao diện không bị treo quá lâu nếu Jeeliz chậm hoặc không phản hồi.
- **Minh chứng:** `ProductController::tryOnModelCheck()`.

#### Q118: Vì sao `plainDescription()` strip HTML mô tả sản phẩm?
- **Trả lời:** Payload thử kính chỉ cần mô tả dạng text ngắn, không cần HTML. Strip tag giúp tránh đưa HTML dài hoặc không cần thiết vào dữ liệu frontend.
- **Minh chứng:** `ProductController::plainDescription()`.

#### Q119: Vì sao ảnh sản phẩm có fallback `upload/no-image.jpg`?
- **Trả lời:** Nếu sản phẩm chưa có ảnh, giao diện vẫn có ảnh mặc định thay vì bị vỡ layout hoặc hiện icon ảnh lỗi.
- **Minh chứng:** `Product::getImageUrlAttribute()`.

#### Q120: Vì sao trang admin sản phẩm có route export Excel?
- **Trả lời:** Admin cần xuất danh sách sản phẩm để báo cáo hoặc kiểm kê. Route này nằm ở khu admin sản phẩm.
- **Minh chứng:** `routes/admin.php`, `ProductAdminController`.

## 14. Đặt lịch đo mắt chuyên sâu

#### Q121: Trạng thái lịch hẹn gồm những gì?
- **Trả lời:** Lịch hẹn có `PENDING`, `CONFIRMED`, `COMPLETED`, `CANCELLED`, `NO_SHOW`. Mỗi trạng thái tương ứng với chờ xác nhận, đã xác nhận, hoàn tất, đã hủy, khách không đến.
- **Minh chứng:** `app/Models/Appointment.php`.

#### Q122: Trạng thái nào được tính là đang chiếm slot?
- **Trả lời:** Chỉ `PENDING` và `CONFIRMED` được xem là active slot. Lịch đã hủy, hoàn tất hoặc no-show không còn chặn slot theo logic active.
- **Minh chứng:** `Appointment::ACTIVE_SLOT_STATUSES`.

#### Q123: Khi nào admin được đánh dấu khách không đến?
- **Trả lời:** Chỉ khi lịch đã `CONFIRMED` và thời gian hẹn đã qua. Điều này tránh đánh dấu no-show trước giờ hẹn.
- **Minh chứng:** `Appointment::canMarkNoShow()`.

#### Q124: Vì sao đổi lịch phải trước 24 giờ?
- **Trả lời:** Để cửa hàng còn thời gian sắp xếp nhân sự và slot. Code dùng `RESCHEDULE_NOTICE_HOURS = 24`.
- **Minh chứng:** `Appointment::RESCHEDULE_NOTICE_HOURS`, `canReschedule()`.

#### Q125: Vì sao mã lịch hẹn có dạng `AO-YYYYMMDD-XXXX`?
- **Trả lời:** Mã này dễ đọc, có ngày tạo lịch và chuỗi ngẫu nhiên để tránh trùng. Controller lặp cho đến khi tạo được mã chưa tồn tại.
- **Minh chứng:** `AppointmentController::generateCode()`.

#### Q126: Vì sao `unavailableSlots` trả JSON?
- **Trả lời:** Route này phục vụ frontend kiểm tra slot theo ngày mà không cần reload trang. JSON trả từng slot có `available`, `reason`, `label`.
- **Minh chứng:** `AppointmentController::unavailableSlots()`.

#### Q127: Vì sao đặt lịch mở cho cả khách chưa đăng nhập?
- **Trả lời:** Dịch vụ đo mắt là luồng thu hút khách mới, nên route đặt lịch không nằm trong group `auth`. Khách vẫn phải nhập tên, phone, email để cửa hàng liên hệ.
- **Minh chứng:** `routes/web.php`, `AppointmentController::store()`.

#### Q128: Vì sao lịch hẹn có `user_id` nullable?
- **Trả lời:** Khách đã đăng nhập thì gắn `user_id`, khách vãng lai vẫn đặt được bằng thông tin liên hệ. Điều này phù hợp với dịch vụ tại cửa hàng.
- **Minh chứng:** `AppointmentController::store()`, migration appointments.

#### Q129: Email nhắc lịch có đánh dấu đã gửi không?
- **Trả lời:** Có. Nếu gửi reminder thành công, service cập nhật `reminder_email_sent_at` để không gửi lại nhiều lần.
- **Minh chứng:** `AppointmentNotificationService::reminder()`.

#### Q130: Nếu gửi email lịch hẹn thất bại thì sao?
- **Trả lời:** Service ghi log lỗi và trả `false`, không làm ứng dụng crash. Với reminder, chỉ khi gửi thành công mới đánh dấu đã gửi.
- **Minh chứng:** `AppointmentNotificationService::send()`.

## 15. VNPay, email và vận hành chuyên sâu

#### Q131: Vì sao `VnPayService::isConfigured()` cần tồn tại?
- **Trả lời:** Để checkout kiểm tra merchant code và hash secret trước khi tạo URL thanh toán. Nếu thiếu cấu hình, hệ thống báo lỗi rõ thay vì redirect sang URL sai.
- **Minh chứng:** `VnPayService::isConfigured()`, `CheckoutController::store()`.

#### Q132: Vì sao `vnp_OrderInfo` dùng `Str::ascii()`?
- **Trả lời:** Để nội dung gửi sang VNPay an toàn hơn với ký tự tiếng Việt/dấu, giảm nguy cơ lỗi encoding ở cổng thanh toán.
- **Minh chứng:** `VnPayService::createPaymentUrl()`.

#### Q133: Vì sao `vnp_ExpireDate` mặc định 15 phút?
- **Trả lời:** Giao dịch thanh toán nên có thời hạn ngắn. Code lấy `vnpay.expire_time`, mặc định 15 phút.
- **Minh chứng:** `config/vnpay.php`, `VnPayService::createPaymentUrl()`.

#### Q134: Khi VNPay thất bại, hệ thống lưu payment thế nào?
- **Trả lời:** Nếu đã có order, hệ thống dùng `Payment::updateOrCreate()` để lưu method `VNPAY`, amount, status `FAILED`, transaction number, bank code, response code và message.
- **Minh chứng:** `VnPayController::markFailed()`.

#### Q135: Vì sao `Payment::updateOrCreate()` dùng `payment_code = order_code`?
- **Trả lời:** Để cùng một mã đơn không tạo nhiều payment trùng khi VNPay return/IPN gọi lại. Bản ghi payment được cập nhật theo mã đơn.
- **Minh chứng:** `VnPayController::saveSuccessfulPayment()`, `markFailed()`.

#### Q136: Vì sao gửi email raw có fallback khi queue dispatch lỗi?
- **Trả lời:** Để nếu queue tạm lỗi, hệ thống vẫn cố gửi email sau khi request kết thúc. Cách này giảm khả năng mất email quan trọng.
- **Minh chứng:** `app/Support/QueuedRawMail.php`.

#### Q137: `SendRawMailJob` có retry không?
- **Trả lời:** Có. Job khai báo `$tries = 3` và `$timeout = 30`, nếu thất bại thì ghi log trong `failed()`.
- **Minh chứng:** `app/Jobs/SendRawMailJob.php`.

#### Q138: Vì sao email hóa đơn hiện chưa đi qua `QueuedRawMail`?
- **Trả lời:** `OrderInvoiceEmailService` hiện dùng `Mail::raw()` trực tiếp. Khi bảo vệ nên nói đúng hiện trạng: email xác nhận đơn/hủy đơn/lịch hẹn đã qua queue wrapper, nhưng email hóa đơn thì đang gửi trực tiếp.
- **Minh chứng:** `OrderInvoiceEmailService.php`, `QueuedRawMail.php`.

#### Q139: Production cần lưu ý gì với queue email?
- **Trả lời:** Cần chạy queue worker ổn định, ví dụ Supervisor/systemd hoặc dịch vụ tương đương. Nếu không có worker, job queue sẽ không được xử lý đúng như mong muốn.
- **Minh chứng:** `composer.json`, `SendRawMailJob.php`.

#### Q140: Nếu thầy cô hỏi "luồng mới quan trọng nhất sau bản cũ là gì" thì trả lời sao?
- **Trả lời:** Nên trả lời 6 luồng: đặt lịch đo mắt có khóa slot và reminder; thử kính có lưu snapshot; VNPay tạo draft rồi chốt bằng return/IPN; email quan trọng đi qua queue job; hủy đơn cần khách xác nhận qua signed URL; kho chỉ trừ khi đơn sang `DELIVERING` và hoàn/đổi có ghi phiếu kho.
- **Minh chứng:** `AppointmentController.php`, `ProductController.php`, `VnPayController.php`, `QueuedRawMail.php`, `OrderCancellationService.php`, `OrderAdminController.php`, `ReturnAdminController.php`.
