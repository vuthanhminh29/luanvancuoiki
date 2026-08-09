# Cẩm Nang 100 Câu Hỏi Phản Biện Chuyên Sâu & Đáp Án Chi Tiết (Laravel Bán Kính Mắt)

Tài liệu này tổng hợp **100 câu hỏi nảy lửa** mà Hội đồng / Thầy cô phản biện hay đặt ra nhất. Mỗi câu hỏi đều đi kèm **câu trả lời chuẩn chuyên môn IT** và **vị trí dòng code minh chứng chính xác trong dự án** để học viên mở cho hội đồng xem ngay lập tức.

---

## 📌 CHUYÊN ĐỀ 1: KIẾN TRÚC LARAVEL, ROUTING & LUỒNG REQUEST (20 CÂU)

#### Q1: Request từ trình duyệt của khách hàng đi qua các file nào trong Laravel trước khi trả về giao diện?
- **Trả lời:** Request đi vào cửa chính [`public/index.php`](file:///c:/source/luanvancuoiki/public/index.php) -> nạp autoloader & nạp ứng dụng tại [`bootstrap/app.php`](file:///c:/source/luanvancuoiki/bootstrap/app.php) -> chuyển qua HTTP Kernel -> khớp đường dẫn trong [`routes/web.php`](file:///c:/source/luanvancuoiki/routes/web.php) -> chạy qua các `Middleware` kiểm tra -> vào `Controller` xử lý logic -> trả dữ liệu về `Blade View` hiển thị.

#### Q2: Khai báo `declare(strict_types=1);` ở đầu các file PHP có tác dụng gì?
- **Trả lời:** Buộc PHP phải kiểm tra kiểu dữ liệu một cách nghiêm ngặt. Ví dụ hàm khai báo nhận `int` mà truyền `string` vào sẽ báo lỗi ngay lập tức thay vì tự động ép kiểu ngầm, giúp hạn chế bug logic.
- **Minh chứng:** Dòng 3 file [`routes/web.php`](file:///c:/source/luanvancuoiki/routes/web.php#L3).

#### Q3: Tại sao trang chủ `/` lại gọi `ClientRouteAliasController::class, 'home'` thay vì `HomeController`?
- **Trả lời:** Dự án sử dụng `ClientRouteAliasController` làm bộ điều hướng trung gian để hỗ trợ tương thích với các URL cũ (như `/index.php`, `/trang-chu`) và chuyển hướng mượt mà về route chuẩn mới.
- **Minh chứng:** Dòng 22 file [`routes/web.php`](file:///c:/source/luanvancuoiki/routes/web.php#L22).

#### Q4: Middleware `throttle:web-read` trên route sản phẩm có nhiệm vụ gì?
- **Trả lời:** Đây là cơ chế Rate Limiting (giới hạn tần suất). Nó ngăn chặn các công cụ cào dữ liệu (crawler/bot) hoặc người dùng nhấn F5 liên tục làm quá tải máy chủ khi đọc dữ liệu sản phẩm.
- **Minh chứng:** Dòng 28 file [`routes/web.php`](file:///c:/source/luanvancuoiki/routes/web.php#L28).

#### Q5: `signed` middleware trên route xác thực email hoặc hủy đơn hàng dùng để làm gì?
- **Trả lời:** Dùng để ký bảo mật URL (Signed URL). Laravel tạo ra một tham số `signature` băm mã hóa dựa vào URL + Secret Key. Nếu người dùng cố tình thay đổi thông tin trên URL (như sửa ID đơn hàng), signature sẽ không khớp và request bị chặn ngay.
- **Minh chứng:** Dòng 51-52 & Dòng 62 file [`routes/web.php`](file:///c:/source/luanvancuoiki/routes/web.php#L62).

#### Q6: Nhóm route `guest` middleware chứa các trang nào và tại sao?
- **Trả lời:** Chứa các trang Đăng nhập (`/dang-nhap`), Đăng ký (`/dang-ky`), Quên mật khẩu. Người dùng đã đăng nhập rồi sẽ bị chặn không cho vào lại các trang này mà tự chuyển hướng về trang chủ.
- **Minh chứng:** Dòng 69-78 file [`routes/web.php`](file:///c:/source/luanvancuoiki/routes/web.php#L69).

#### Q7: Làm sao hệ thống tách biệt giữa Route dành cho Khách hàng và Route dành cho Admin?
- **Trả lời:** Route khách nằm trực tiếp trong `routes/web.php`. Route admin được viết riêng ở file `routes/admin.php` và được nạp vào `web.php` bằng câu lệnh `require __DIR__ . '/admin.php';`.
- **Minh chứng:** Dòng 125 file [`routes/web.php`](file:///c:/source/luanvancuoiki/routes/web.php#L125).

#### Q8: Thuộc tính `name('products.show')` trên route có ý nghĩa gì?
- **Trả lời:** Dùng để đặt tên định danh cho route (Named Route). Giúp khi viết code View hay Controller chỉ cần gọi `route('products.show', $slug)` thay vì hardcode URL `/san-pham/kinh-mat-1`. Khi đổi đường dẫn URL thì code ở View không bị hỏng.
- **Minh chứng:** Dòng 29 file [`routes/web.php`](file:///c:/source/luanvancuoiki/routes/web.php#L29).

#### Q9: Route Alias là gì và xử lý ở file nào trong dự án?
- **Trả lời:** Là cơ chế bắt các đường dẫn cũ dạng PHP thuần (`/chitietsanpham`, `/thanh-toan-2`) để tự động chuyển hướng (Redirect 301/302) sang URL mới chuẩn SEO trong Laravel.
- **Minh chứng:** Dòng 120-122 file [`routes/web.php`](file:///c:/source/luanvancuoiki/routes/web.php#L120) và [`ClientRouteAliasController.php`](file:///c:/source/luanvancuoiki/app/Http/Controllers/ClientRouteAliasController.php).

#### Q10: File `bootstrap/app.php` đóng vai trò gì trong ứng dụng Laravel 11?
- **Trả lời:** Là nơi khởi tạo đối tượng Application, đăng ký các Service Provider, thiết lập cấu hình Router, nạp Middleware toàn cục và cấu hình xử lý ngoại lệ (Exception Handling).

#### Q11: Service Provider trong Laravel là gì?
- **Trả lời:** Là trung tâm kết nối và khởi tạo tất cả các dịch vụ (Services) của hệ thống trước khi ứng dụng chạy, ví dụ: đăng ký Blade directive, cấu hình Mail, kết nối Database, đăng ký Event Listeners.

#### Q12: HTTP Controller có nhiệm vụ chính là gì?
- **Trả lời:** Tiếp nhận dữ liệu từ Request, gọi các Model hoặc Service để xử lý nghiệp vụ, sau đó chuẩn bị dữ liệu và trả về cho View hiển thị hoặc trả về phản hồi JSON/Redirect.

#### Q13: Tại sao lại tách thành file `routes/web.php` và `routes/admin.php` thay vì dồn chung 1 file?
- **Trả lời:** Giúp mã nguồn gọn gàng, dễ bảo trì, dễ phân chia công việc cho nhóm phát triển và áp dụng các middleware bảo mật riêng biệt cho từng khu vực.

#### Q14: Phương thức `Route::match(['get', 'post'], ...)` khác gì `Route::get()`?
- **Trả lời:** `Route::get()` chỉ chấp nhận HTTP Request phương thức GET. `Route::match(['get', 'post'])` chấp nhận cả 2 phương thức GET và POST trên cùng một URL (ví dụ URL Callback/IPN của VNPay).
- **Minh chứng:** Dòng 47 file [`routes/web.php`](file:///c:/source/luanvancuoiki/routes/web.php#L47).

#### Q15: Blade Template Engine trong Laravel là gì? Có ưu điểm gì so với PHP thuần?
- **Trả lời:** Blade là công cụ biên dịch giao diện của Laravel. Nó hỗ trợ kế thừa layout (`@extends`), chia nhỏ component (`@include`), hiển thị dữ liệu an toàn tự động chống XSS (`{{ $var }}`) và viết vòng lặp/điều kiện rất gọn gàng.

#### Q16: Cấu hình hệ thống được lưu trữ ở đâu và gọi trong code như thế nào?
- **Trả lời:** Được lưu ở các file trong thư mục `config/` (như `config/vnpay.php`, `config/app.php`) và đọc giá trị biến môi trường từ file `.env` qua hàm `config('vnpay.tmn_code')` hoặc `env()`.

#### Q17: Khái niệm "Request Validation" trong Laravel được thực hiện ở đâu?
- **Trả lời:** Được thực hiện trực tiếp trong Controller thông qua `$request->validate([...])` hoặc qua các class `FormRequest` riêng biệt để kiểm tra dữ liệu đầu vào trước khi xử lý.

#### Q18: Tại sao lại không nên gọi trực tiếp hàm `env()` bên trong Controller?
- **Trả lời:** Vì khi triển khai production và bật cache cấu hình (`php artisan config:cache`), hàm `env()` sẽ trả về `null`. Chuẩn của Laravel là gọi qua helper `config()`.

#### Q19: File `.env.example` có tác dụng gì?
- **Trả lời:** Là file mẫu chứa danh sách các tên biến môi trường cần thiết của dự án (không chứa mật khẩu thật) để các lập trình viên khác khi clone code về biết cần cấu hình những biến nào.

#### Q20: dependency Injection (Tiêm phụ thuộc) trong Controller Laravel hoạt động ra sao?
- **Trả lời:** Laravel tự động soi loại Type-hint của tham số truyền vào hàm Controller (ví dụ `Request $request` hoặc `VnPayService $vnPayService`) và tự khởi tạo đối tượng đó truyền vào cho hàm sử dụng.

---

## 📌 CHUYÊN ĐỀ 2: XÁC THỰC (AUTH), PHÂN QUYỀN & BẢO MẬT (20 CÂU)

#### Q21: Hệ thống mã hóa mật khẩu người dùng bằng thuật toán gì? Có an toàn không?
- **Trả lời:** Hệ thống mã hóa mật khẩu bằng thuật toán **Bcrypt** (thông qua helper `Hash::make()`). Đây là thuật toán băm một chiều an toàn tiêu chuẩn có kèm chuỗi muối (salt) ngẫu nhiên, chống tấn công bảng băm sẵn (Rainbow Table).

#### Q22: Trang Admin được bảo vệ bởi Middleware nào và cơ chế kiểm tra ra sao?
- **Trả lời:** Được bảo vệ bởi Middleware [`EnsureAdmin.php`](file:///c:/source/luanvancuoiki/app/Http/Middleware/EnsureAdmin.php). Nó kiểm tra xem user đã đăng nhập chưa, trạng thái có `ACTIVE` không, và có role `ADMIN` hoặc `STAFF` trong bảng `user_roles` hay không.
- **Minh chứng:** Dòng 14-59 file [`EnsureAdmin.php`](file:///c:/source/luanvancuoiki/app/Http/Middleware/EnsureAdmin.php#L14-L59).

#### Q23: Tại sao trong Middleware `EnsureAdmin` lại dùng `Cache::remember()`?
- **Trả lời:** Dùng để lưu danh sách vai trò (roles) của user vào Cache trong 5 phút. Việc này giúp mỗi khi user Admin bấm chuyển trang không cần phải chạy lại câu query `JOIN` giữa 3 bảng `users`, `user_roles`, `roles`, giúp tăng tốc độ tải trang Admin.
- **Minh chứng:** Dòng 52-56 file [`EnsureAdmin.php`](file:///c:/source/luanvancuoiki/app/Http/Middleware/EnsureAdmin.php#L52-L56).

#### Q24: Tấn công CSRF (Cross-Site Request Forgery) là gì và Laravel phòng chống như thế nào?
- **Trả lời:** CSRF là kỹ thuật lừa trình duyệt người dùng gửi request độc hại tới trang web khi họ đã đăng nhập. Laravel phòng chống bằng cách tự động sinh mã Token ngẫu nhiên cho mỗi phiên làm việc. Mọi form POST/PUT/DELETE bắt buộc phải kèm thẻ `@csrf` để kiểm tra.

#### Q25: Tấn công SQL Injection là gì và dự án phòng chống ở đâu?
- **Trả lời:** SQL Injection là việc hacker chèn câu lệnh SQL độc hại vào ô nhập liệu. Dự án dùng Eloquent ORM và PDO Parameter Binding để tự động escape các ký tự đặc biệt, khiến dữ liệu nhập vào luôn được xem là tham số thuần túy, không thể thay đổi cấu trúc câu SQL.

#### Q26: Tấn công XSS (Cross-Site Scripting) được phòng chống như thế nào trong giao diện Blade?
- **Trả lời:** Blade Template tự động mã hóa các ký tự HTML nguy cơ (`<script>`, `javascript:`) thông qua cú pháp `{{ $variable }}` (tương đương hàm `htmlspecialchars`). Nếu muốn xuất HTML nguyên bản phải dùng `{!! $variable !!}` (chỉ dùng cho nội dung tin cậy).

#### Q27: SessionHijacking (Cướp phiên làm việc) được ngăn chặn ra sao khi người dùng Đăng xuất?
- **Trả lời:** Khi đăng xuất, hàm `logout()` thực hiện hủy dữ liệu session hiện tại (`$request->session()->invalidate()`) và làm mới lại CSRF token (`$request->session()->regenerateToken()`), khiến cookie phiên cũ lập tức vô hiệu hóa.
- **Minh chứng:** Dòng 63-65 file [`EnsureAdmin.php`](file:///c:/source/luanvancuoiki/app/Http/Middleware/EnsureAdmin.php#L63-L65).

#### Q28: Guard và Provider trong cấu hình `config/auth.php` khác nhau như thế nào?
- **Trả lời:** `Guard` định nghĩa cách xác thực người dùng cho mỗi request (vd: `session` lưu cookie cho web, `sanctum` dùng token cho API). `Provider` định nghĩa nơi lấy dữ liệu người dùng ra (vd: từ Eloquent Model `User`).

#### Q29: Tại sao chức năng Quên mật khẩu lại dùng Token gửi qua Email?
- **Trả lời:** Để xác minh quyền sở hữu hộp thư email. Token được sinh ngẫu nhiên có kèm thời gian hết hạn (expiration). Người dùng nhấn vào link chứa token mới được cấp quyền đổi mật khẩu mới.

#### Q30: Sự khác biệt giữa Authentication (Xác thực) và Authorization (Phân quyền) là gì?
- **Trả lời:** Authentication là kiểm tra **"Bạn là ai?"** (Xác minh Email/Mật khẩu). Authorization là kiểm tra **"Bạn có quyền làm gì?"** (User thường không được xóa sản phẩm, Admin mới có quyền).

#### Q31: Cơ chế "Throttle" bảo vệ các route đăng nhập/đăng ký ra sao?
- **Trả lời:** Áp dụng middleware `throttle:auth` để giới hạn số lần thử đăng nhập sai (ví dụ tối đa 5 lần/phút). Nếu quá giới hạn, hệ thống sẽ tạm khóa request từ IP đó để chống tấn công dò mật khẩu (Brute-force).

#### Q32: Bảng `roles` và `user_roles` được thiết kế theo quan hệ gì?
- **Trả lời:** Thiết kế theo quan hệ Nhiều - Nhiều (N-N). Một `User` có thể mang nhiều `Role` (Admin, Manager, Staff) và một `Role` có thể gán cho nhiều `User`. Bảng `user_roles` đóng vai trò là bảng trung gian (Pivot table).

#### Q33: Khái niệm "Soft Delete" (Xóa mềm) là gì và ứng dụng ở đâu?
- **Trả lời:** Xóa mềm là không xóa hẳn dòng dữ liệu khỏi ổ đĩa mà chỉ cập nhật trường `deleted_at` thành thời gian xóa. Giúp dữ liệu sản phẩm hay đơn hàng cũ vẫn được giữ lại để đối soát báo cáo mà không hỏng quan hệ CSDL.

#### Q34: Tại sao trong Form lại dùng `@method('PUT')` hoặc `@method('DELETE')`?
- **Trả lời:** Vì các trình duyệt HTML cơ bản chỉ hỗ trợ 2 phương thức gửi form là `GET` và `POST`. Thẻ `@method()` giúp giả lập (spoofing) phương thức `PUT`/`DELETE` để đúng chuẩn thiết kế RESTful Route trong Laravel.

#### Q35: Tại sao đường dẫn xem hóa đơn PDF lại bắt buộc kiểm tra `auth`?
- **Trả lời:** Để tránh lỗ hổng IDOR (Insecure Direct Object References). Nếu không kiểm tra `auth` và sở hữu đơn hàng, bất kỳ ai thay đổi ID trên URL cũng có thể xem trộm thông tin cá nhân và hóa đơn của khách hàng khác.

#### Q36: Cookie `XSRF-TOKEN` được gửi kèm trong request AJAX có vai trò gì?
- **Trả lời:** Giúp các thư viện frontend (như Axios/Fetch) tự động đọc token này từ cookie và đính kèm vào HTTP Header `X-XSRF-TOKEN` để Laravel xác thực CSRF cho các truy vấn ngầm AJAX mà không làm load lại trang.

#### Q37: Mật khẩu người dùng có bao giờ bị lộ dưới dạng văn bản rõ (plaintext) trong Log không?
- **Trả lời:** Không. Mật khẩu được mã hóa ngay tại thời điểm nhận request. Đồng thời trong Laravel các trường nhạy cảm như `password`, `remember_token` đều được khai báo trong mảng `$hidden` của Model `User`.

#### Q38: Tại sao tài khoản bị đổi trạng thái thành `INACTIVE` lại bị văng khỏi hệ thống Admin ngay lập tức?
- **Trả lời:** Vì trong Middleware [`EnsureAdmin.php:L25`](file:///c:/source/luanvancuoiki/app/Http/Middleware/EnsureAdmin.php#L25) luôn kiểm tra `Auth::user()->status !== 'ACTIVE'`. Nếu tài khoản bị vô hiệu hóa, hàm `logout()` sẽ chạy ngay lập tức.

#### Q39: Trong dự án, gói `Laravel Sanctum` được sử dụng để làm gì?
- **Trả lời:** Dùng để quản lý phiên xác thực lightweight cho SPA (Single Page Application) hoặc cấp API Token bảo mật khi các ứng dụng di động/thứ ba muốn kết nối với hệ thống.

#### Q40: Cấu hình `SESSION_SECURE_COOKIE=true` trên môi trường Production có ý nghĩa gì?
- **Trả lời:** Đảm bảo Cookie phiên làm việc chỉ được phép truyền tải qua giao thức mã hóa HTTPS, chống bị bắt lén gói tin trên mạng wifi công cộng (Man-in-the-middle attack).

---

## 📌 CHUYÊN ĐỀ 3: THIẾT KẾ DATABASE & ELOQUENT ORM (20 CÂU)

#### Q41: CSDL dự án có tổng cộng bao nhiêu bảng? Kể tên các phân hệ bảng chính?
- **Trả lời:** Có tổng cộng **26 bảng**. Chia làm các phân hệ: Khách hàng/Quyền (`users`, `roles`), Sản phẩm/Danh mục (`categories`, `brands`, `products`, `product_variants`), Đơn hàng (`orders`, `order_items`), Kho hàng (`warehouses`, `inventories`, `stock_transactions`), Đổi trả (`return_requests`).

#### Q42: Sự khác biệt giữa bảng `products` và `product_variants` là gì? Tại sao phải tách ra?
- **Trả lời:** Bảng `products` lưu thông tin chung của cây kính (tên kính, mô tả, thương hiệu). Bảng `product_variants` lưu các phiên bản cụ thể (màu sắc gọng, chất liệu tròng, độ cận, giá riêng, SKU riêng, tồn kho riêng). Tách ra để hỗ trợ sản phẩm có nhiều thuộc tính.

#### Q43: Khóa ngoại (Foreign Key) và ràng buộc `ON DELETE CASCADE` được dùng ở đâu?
- **Trả lời:** Được dùng để đảm bảo tính toàn vẹn dữ liệu. Ví dụ bảng `order_items` có khóa ngoại `order_id` trỏ đến `orders(id)`. Khi xóa một đơn hàng nháp, tất cả các dòng chi tiết sản phẩm trong đơn đó sẽ tự động bị xóa theo.

#### Q44: Phương thức `belongsTo()` và `hasMany()` thể hiện quan hệ gì trong Eloquent Model?
- **Trả lời:** `hasMany()` thể hiện phía chứa nhiều (1-N, ví dụ 1 `Category` `hasMany` `Product`). `belongsTo()` thể hiện phía con chứa khóa ngoại (ví dụ 1 `Product` `belongsTo` `Category`).

#### Q45: Bảng `order_items` giữ vai trò gì trong kiến trúc cơ sở dữ liệu?
- **Trả lời:** Là bảng trung gian gỡ rối cho quan hệ Nhiều - Nhiều (N-N) giữa bảng `orders` và `product_variants`. Nó lưu thêm thông tin lịch sử tại thời điểm mua: số lượng mua, giá bán thực tế lúc mua, tiền giảm giá.

#### Q46: Tại sao lại lưu `price` (giá mua) vào bảng `order_items` trong khi bảng `product_variants` đã có giá?
- **Trả lời:** Để bảo toàn lịch sử giao dịch. Nếu sau này Admin sửa giá sản phẩm trong kho, giá trên các đơn hàng đã mua trong quá khứ không được phép bị thay đổi theo.

#### Q47: Lỗi N+1 Query trong ORM là gì và ví dụ cách sửa trong code dự án?
- **Trả lời:** Lỗi N+1 xảy ra khi lấy danh sách N sản phẩm rồi dùng vòng lặp gọi `$product->category->name`, khiến Laravel thực thi thêm N câu query SQL vào DB. Sửa bằng cách dùng Eager Loading: `Product::with('category')->get()`.

#### Q48: Enum trong Database MySQL/Laravel có lợi ích gì?
- **Trả lời:** Giúp giới hạn cột chỉ được phép nhận các giá trị cố định đã khai báo trước (ví dụ cột `status` trong `orders` chỉ được nhận: `PENDING`, `PROCESSING`, `SHIPPED`, `DELIVERED`, `CANCELLED`), tránh ghi dữ liệu rác.

#### Q49: Migration trong Laravel đóng vai trò gì?
- **Trả lời:** Giống như một hệ thống quản lý phiên bản (Git) cho Cơ sở dữ liệu. Nó định nghĩa cấu trúc bảng bằng code PHP, giúp toàn bộ team phát triển tạo cấu trúc DB giống hệt nhau qua lệnh `php artisan migrate`.

#### Q50: Seeder và Factory dùng để làm gì?
- **Trả lời:** Seeder dùng để chèn dữ liệu mẫu/dữ liệu ban đầu (như tài khoản Admin mặc định, danh mục kính). Factory dùng để tự động sinh ra hàng nghìn dữ liệu giả ngẫu nhiên phục vụ kiểm thử hiệu năng.

#### Q51: Cột `slug` trong bảng `products` có tác dụng gì và tạo ra như thế nào?
- **Trả lời:** Cột `slug` chứa chuỗi tên sản phẩm được viết thường không dấu nối bằng dấu gạch ngang (ví dụ: `kinh-mat-nam-rayban`). Dùng để tạo đường dẫn URL thân thiện SEO (`/san-pham/kinh-mat-nam-rayban`).

#### Q52: Eloquent Mutator và Accessor là gì?
- **Trả lời:** Accessor dùng để biến đổi dữ liệu khi đọc ra từ DB (ví dụ: gộp `first_name` và `last_name` thành `full_name`). Mutator dùng để tự động biến đổi dữ liệu trước khi lưu vào DB (ví dụ: tự động viết hoa mã đơn hàng).

#### Q53: Query Scope trong Eloquent Model dùng để làm gì?
- **Trả lời:** Dùng để gom nhóm các điều kiện truy vấn thường xuyên tái sử dụng thành một hàm gọn gàng. Ví dụ scope `active()` trong Model `Product` để lọc chỉ lấy sản phẩm đang bật kinh doanh: `Product::active()->get()`.

#### Q54: Bảng `stock_transactions` ghi lại những thông tin gì?
- **Trả lời:** Ghi lại mọi biến động tồn kho: `product_variant_id`, `warehouse_id`, `quantity` (số lượng thay đổi + hoặc -), `type` (IMPORT, EXPORT, RETURN), và `note` (lý do). Giúp kiểm toán kho chính xác.

#### Q55: Chỉ mục (Index) trong Database được đánh ở các cột nào và tại sao?
- **Trả lời:** Đánh chỉ mục ở các cột thường xuyên dùng để tìm kiếm hoặc lọc: Khóa chính (`id`), Khóa ngoại (`product_id`), Cột tra cứu (`slug`, `email`, `order_code`). Index giúp DB tìm dữ liệu nhanh gấp hàng trăm lần.

#### Q56: Sự khác nhau giữa `count()` trong SQL và `count()` trên Collection của Laravel?
- **Trả lời:** `$query->count()` chạy câu lệnh `SELECT COUNT(*)` trực tiếp dưới DB (rất nhanh và tốn ít RAM). `$collection->count()` là kéo toàn bộ dữ liệu về RAM của PHP rồi mới đếm (gây tràn bộ nhớ nếu dữ liệu lớn).

#### Q57: Tính năng Database Transaction (`DB::transaction`) được dùng ở đâu và tại sao?
- **Trả lời:** Được dùng trong quá trình Checkout đặt hàng hoặc Đổi trả. Đảm bảo tất cả các thao tác (tạo đơn hàng + trừ tồn kho + lưu hóa đơn) phải thành công 100%. Nếu có 1 bước bị lỗi, toàn bộ thao tác trước đó sẽ tự động Rollback (hoàn nguyên).

#### Q58: Tại sao lại dùng kiểu dữ liệu `DECIMAL` cho cột tiền tệ thay vì `FLOAT` hay `DOUBLE`?
- **Trả lời:** Vì kiểu `FLOAT`/`DOUBLE` bị lỗi làm tròn số dấu phẩy động trong máy tính (ví dụ `0.1 + 0.2 = 0.30000000000000004`). Kiểu `DECIMAL(15, 2)` lưu chính xác tuyệt đối con số tiền tệ đến từng đồng.

#### Q59: Bảng `promotions` lưu thông tin mã giảm giá gồm những điều kiện gì?
- **Trả lời:** Lưu mã code, phần trăm giảm hoặc số tiền giảm cố định, số tiền đơn hàng tối thiểu được áp dụng, ngày bắt đầu, ngày hết hạn và giới hạn số lần sử dụng tối đa.

#### Q60: Làm sao để kiểm tra câu lệnh SQL thực tế mà Eloquent ORM đang chạy ngầm?
- **Trả lời:** Dùng hàm `toSql()` trên đối tượng Query Builder (ví dụ: `Product::where('status', 'ACTIVE')->toSql()`) hoặc lắng nghe sự kiện `DB::listen()` trong môi trường phát triển.

---

## 📌 CHUYÊN ĐỀ 4: GIỎ HÀNG, CHECKOUT & VNPAY PAYMENT GATEWAY (20 CÂU)

#### Q61: Giỏ hàng phía Khách hàng được lưu trữ ở đâu? Tại sao không lưu thẳng vào CSDL?
- **Trả lời:** Giỏ hàng được lưu trong **Session** của máy chủ. Lưu Session giúp khách chưa đăng nhập vẫn có thể duyệt web và thêm kính vào giỏ mượt mà mà không làm phình to CSDL với hàng nghìn giỏ hàng rác vô chủ.

#### Q62: Giới hạn số lượng sản phẩm trong giỏ hàng là bao nhiêu và cài đặt ở đâu?
- **Trả lời:** Giới hạn tối đa **10 sản phẩm/đơn** (`MAX_TOTAL_QUANTITY = 10`). Được định nghĩa bằng hằng số tại dòng 14 file [`CartController.php`](file:///c:/source/luanvancuoiki/app/Http/Controllers/CartController.php#L14).

#### Q63: Khi người dùng bấm "Đồng ý Đặt hàng", những việc gì xảy ra trong Controller?
- **Trả lời:** Mở [`CheckoutController.php`](file:///c:/source/luanvancuoiki/app/Http/Controllers/CheckoutController.php): Validate dữ liệu người nhận -> Kiểm tra lại tồn kho thực tế -> Bật `DB::transaction()` -> Tạo record `Order` -> Tạo danh sách `OrderItem` -> Trừ tồn kho -> Xóa Session giỏ hàng -> Chuyển hướng sang VNPay hoặc hiển thị hoàn tất.

#### Q64: Quy trình tạo URL thanh toán VNPay trong `VnPayService` gồm các bước nào?
- **Trả lời:** Mở [`VnPayService.php:L19`](file:///c:/source/luanvancuoiki/app/Services/VnPayService.php#L19):
  1. Kiểm tra cấu hình `tmn_code` và `hash_secret`.
  2. Gom mảng tham số (`vnp_Amount`, `vnp_TxnRef`, `vnp_CreateDate`...).
  3. Sắp xếp mảng tham số theo thứ tự bảng chữ cái bằng `ksort()`.
  4. Nối chuỗi URL encode các tham số.
  5. Băm chuỗi bằng thuật toán `hash_hmac('sha512', $data, $secret)`.
  6. Gắn kết quả `vnp_SecureHash` vào cuối URL và trả về cho Controller.

#### Q65: Thuật toán mã hóa HMAC-SHA512 đảm bảo an toàn cho giao dịch VNPay như thế nào?
- **Trả lời:** HMAC-SHA512 kết hợp dữ liệu giao dịch với một chuỗi khóa bí mật (`VNPAY_HASH_SECRET`) chỉ có Máy chủ và VNPay biết. Nếu ai cố tình sửa số tiền từ 1.000.000đ thành 1.000đ trên đường truyền, chữ ký `vnp_SecureHash` tính lại sẽ sai hoàn toàn và VNPay sẽ từ chối giao dịch.

#### Q66: Phân biệt sự khác nhau giữa VNPay Return URL và VNPay IPN URL?
- **Trả lời:**
  - **Return URL (`/vnpay/return`):** Trình duyệt khách hàng tự chuyển hướng về sau khi thanh toán xong để xem màn hình kết quả (GET request).
  - **IPN URL (`/vnpay/ipn`):** VNPay chủ động gọi ngầm từ Server VNPay sang Server website (Server-to-Server) để chốt trạng thái đơn hàng. Đây mới là nơi tin cậy để cập nhật CSDL.

#### Q67: Tại sao lại gọi hàm `hash_equals()` để so sánh chuỗi chữ ký VNPay thay vì dùng operator `==`?
- **Trả lời:** Hàm `hash_equals()` thực hiện so sánh chuỗi với thời gian cố định (Timing-attack safe). Nếu dùng `==`, thời gian so sánh sẽ phụ thuộc vào số ký tự đúng đầu tiên, khiến hacker có thể đo thời gian từng miligiây để dò ra chữ ký (Timing Attack).
- **Minh chứng:** Dòng 57 file [`VnPayService.php`](file:///c:/source/luanvancuoiki/app/Services/VnPayService.php#L57).

#### Q68: Điều gì xảy ra nếu khách hàng bấm "Hủy giao dịch" trên cổng VNPay?
- **Trả lời:** VNPay trả về mã `vnp_ResponseCode` khác `'00'` (ví dụ `'24'` - Khách hủy giao dịch). Hàm `verify()` trong `VnPayService` xác nhận `is_success = false`, đơn hàng được giữ ở trạng thái chờ thanh toán hoặc hủy, tồn kho không bị trừ sai.

#### Q69: Mã giảm giá `Promotion` được kiểm tra tính hợp lệ dựa trên những tiêu chí nào?
- **Trả lời:** Đảm bảo mã có tồn tại, trạng thái `ACTIVE`, thời gian nằm trong khoảng `start_date` và `end_date`, tổng số lần sử dụng chưa vượt quá `usage_limit`, và giá trị đơn hàng đạt mức tối thiểu `min_order_amount`.

#### Q70: Phí vận chuyển (Shipping Fee) được tính toán như thế nào trong Checkout?
- **Trả lời:** Được tính toán dựa trên địa chỉ nhận hàng của khách (tỉnh/thành phố) kết hợp với trọng lượng/loại đơn hàng hoặc áp dụng phí cố định theo cấu hình hệ thống.

#### Q71: Làm sao để tránh trường hợp 2 khách cùng bấm mua 1 kính cuối cùng trong kho cùng một lúc (Race Condition)?
- **Trả lời:** Dùng cơ chế Khóa bi quan trong Database `lockForUpdate()` khi kiểm tra tồn kho inside `DB::transaction()`. Request nào vào trước sẽ giữ khóa cho đến khi tạo đơn xong, request sau phải chờ đến lượt.

#### Q72: Tại sao số tiền `vnp_Amount` gửi sang VNPay lại phải nhân thêm 100?
- **Trả lời:** Vì quy định API của VNPay không dùng số thập phân. Số tiền gửi đi bắt buộc phải nhân 100 (ví dụ: 100.000 VNĐ phải gửi là `10000000`).

#### Q73: Đơn hàng sau khi đặt thành công sẽ có những trạng thái (Status) nào trong quy trình?
- **Trả lời:** `PENDING` (Chờ xử lý) -> `PROCESSING` (Đang chuẩn bị hàng) -> `SHIPPED` (Đang giao hàng) -> `DELIVERED` (Đã giao hàng). Nếu có sự cố sẽ chuyển thành `CANCELLED` (Đã hủy) hoặc `RETURNED` (Đã trả hàng).

#### Q74: Thời gian hết hạn của giao dịch VNPay (`vnp_ExpireDate`) là bao lâu?
- **Trả lời:** Mặc định là **15 phút** kể từ lúc tạo URL thanh toán. Được cấu hình qua tham số `vnpay.expire_time`. Quá 15 phút khách chưa quét mã/nhập OTP thì giao dịch tự động hủy.
- **Minh chứng:** Dòng 42 file [`VnPayService.php`](file:///c:/source/luanvancuoiki/app/Services/VnPayService.php#L42).

#### Q75: Nếu VNPay gọi lại IPN nhiều lần cho 1 đơn hàng (Idempotency) thì hệ thống xử lý ra sao?
- **Trả lời:** Trong hàm `ipn()` của `VnPayController` sẽ kiểm tra trạng thái đơn hàng hiện tại. Nếu đơn hàng đã ở trạng thái `PAID` (Đã thanh toán) rồi thì hệ thống trả về ngay phản hồi `{"RspCode":"02","Message":"Order already confirmed"}` cho VNPay mà không cộng tiền hay cập nhật lại DB 2 lần.

#### Q76: Làm sao để kiểm tra tích hợp VNPay hoạt động khi chưa có tài khoản ngân hàng thật?
- **Trả lời:** Sử dụng môi trường **VNPay Sandbox** (môi trường giả lập thử nghiệm), dùng thông tin Thẻ ATM thử nghiệm do VNPay cung cấp (Số thẻ: `970419852619143219`, Tên: `NGUYEN VAN A`, OTP: `123456`).

#### Q77: Đơn hàng bị hủy do hết hạn thanh toán thì tồn kho sẽ được xử lý ra sao?
- **Trả lời:** Hệ thống chạy lệnh hoàn lại số lượng sản phẩm vào kho tương ứng và ghi lại 1 bản ghi giao dịch kho `StockTransaction` kiểu `RESTORE`.

#### Q78: Dữ liệu giỏ hàng lưu trong Session sẽ bị mất khi nào?
- **Trả lời:** Khi người dùng đóng trình duyệt (nếu session dạng cookie ngắn hạn), hoặc sau khoảng thời gian `SESSION_LIFETIME` (mặc định 120 phút không thao tác), hoặc khi khách bấm "Đã hoàn tất thanh toán".

#### Q79: Khi người dùng đổi số lượng sản phẩm trong giỏ hàng thành 0 thì hệ thống xử lý thế nào?
- **Trả lời:** Hàm `update()` trong `CartController` kiểm tra nếu số lượng `<= 0` sẽ tự động xóa (unset) `variant_id` đó ra khỏi mảng giỏ hàng Session.

#### Q80: Hóa đơn điện tử (Invoice) dạng PDF được tạo ra như thế nào?
- **Trả lời:** Hệ thống lấy dữ liệu `Order` kèm `OrderItem`, truyền vào View Blade trình bày hóa đơn, sau đó dùng thư viện `Barryvdh\DomPDF` để biên dịch file HTML đó thành định dạng PDF cho khách tải về hoặc gửi đính kèm Email.

---

## 📌 CHUYÊN ĐỀ 5: ADMIN, KHO HÀNG, ĐỔI TRẢ & GIẢI TRÌNH AUDIT (20 CÂU)

#### Q81: Trang Dashboard Admin hiển thị những chỉ số báo cáo chính nào?
- **Trả lời:** Tổng doanh thu theo ngày/tháng, tổng số đơn hàng mới, số lượng khách hàng mới, danh sách kính bán chạy nhất và danh sách các sản phẩm đang chạm ngưỡng cảnh báo hết hàng trong kho.

#### Q82: Logic cảnh báo hết hàng (Low Stock Warning) được tính toán thế nào?
- **Trả lời:** Mỗi biến thể kính trong bảng `inventories` có cột `safety_stock` (ngưỡng an toàn, ví dụ 5 cái). Khi số lượng tồn kho `quantity <= safety_stock`, hệ thống sẽ gắn cờ cảnh báo đỏ trên trang Admin.

#### Q83: Quy trình Đổi trả hàng (`ReturnRequest`) từ phía Khách đến Admin diễn ra ra sao?
- **Trả lời:** Khách hàng vào chi tiết đơn hàng cũ -> Bấm "Yêu cầu Đổi trả" -> Chọn lý do + tải ảnh minh chứng -> Request lưu vào bảng `return_requests` trạng thái `PENDING`. Admin vào trang `/admin/returns` xem xét -> Bấm "Chấp nhận" -> Khách gửi hàng về -> Admin xác nhận đã nhận hàng -> Hệ thống tự động nhập kho lại kính và cập nhật đơn.

#### Q84: Tại sao trong bảng `return_requests` lại cần lưu lịch sử duyệt (`return_histories`)?
- **Trả lời:** Để minh bạch quy trình làm việc của nhân viên Admin. Ghi lại ai là người duyệt (Admin ID), lúc mấy giờ, chuyển trạng thái từ gì sang gì và ghi chú lý do chấp nhận/từ chối.

#### Q85: Báo cáo doanh thu có tính các đơn hàng ở trạng thái `CANCELLED` hoặc `RETURNED` không?
- **Trả lời:** Không. Báo cáo doanh thu chuẩn chỉ lọc các đơn hàng có trạng thái thanh toán `PAID` và trạng thái giao hàng `DELIVERED` hoặc `PROCESSING`, loại trừ hoàn toàn các đơn bị hủy hoặc đã hoàn tiền đổi trả.

#### Q86: Tính năng phân trang `paginate(15)` trong Admin có ưu điểm gì so với `get()`?
- **Trả lời:** `paginate(15)` chỉ lấy đúng 15 dòng dữ liệu mỗi trang từ Database (thông qua câu SQL `LIMIT 15 OFFSET X`). Giúp trang Admin tải siêu nhanh dù cơ sở dữ liệu có hàng trăm nghìn đơn hàng.

#### Q87: Thầy cô hỏi: *"Tại sao hệ thống gửi Email thông báo đơn hàng mà không dùng Queue Worker?"* — Trả lời thế nào?
- **Trả lời:** *"Dạ thưa Thầy/Cô, ở phiên bản hiện tại em đang xử lý gửi Mail đồng bộ để dễ dàng chạy thử nghiệm trên máy cục bộ không cần cài Redis. Em đã phân tích đây là điểm nghẽn trong file audit [`docs/11-ke-hoach-bao-tri-trien-khai.md`](file:///c:/source/luanvancuoiki/docs/11-ke-hoach-bao-tri-trien-khai.md) và đã có kế hoạch đưa Mail vào `Redis Queue Worker` khi chạy production thực tế ạ."*

#### Q88: Thầy cô hỏi: *"Tại sao trong thư mục nguồn lại có file `.env.railway_2047_backup`?"* — Trả lời thế nào?
- **Trả lời:** *"Dạ đây là file sao lưu cấu hình môi trường khi nhóm tiến hành thử nghiệm triển khai hệ thống lên hạ tầng mây Railway. Em đã ghi nhận việc lưu file backup chứa thông tin trong thư mục source là chưa chuẩn security và đã đưa vào checklist gỡ bỏ trước khi bàn giao hệ thống ạ."*

#### Q89: Thầy cô hỏi: *"Thử kính AI hoạt động dựa trên công nghệ gì? Có phải AI thật không?"* — Trả lời thế nào?
- **Trả lời:** *"Dạ, tính năng thử kính sử dụng thư viện xử lý ảnh Computer Vision trên trình duyệt để phát hiện các điểm mốc khuôn mặt (Facial Landmarks như mắt, sống mũi), từ đó tự động tính toán góc xoay, khoảng cách 2 mắt và đè lớp hình ảnh gọng kính 2D/3D lên khuôn mặt theo thời gian thực ạ."*

#### Q90: Thầy cô hỏi: *"Tại sao thư mục `docs/` lại nằm trong `.gitignore`?"* — Trả lời thế nào?
- **Trả lời:** *"Dạ thưa Thầy/Cô, thư mục `docs/` chứa tài liệu phân tích nội bộ và kịch bản test hệ thống. Theo quy chuẩn quản lý mã nguồn, nhóm tách riêng tài liệu phát triển ra khỏi gói code build để giảm dung lượng repository khi deploy lên máy chủ ạ."*

#### Q91: Làm sao Admin có thể tìm kiếm nhanh một đơn hàng theo Mã đơn hàng hoặc Số điện thoại?
- **Trả lời:** Controller `OrderAdminController` tiếp nhận từ khóa `search`, dùng Eloquent Query Builder với câu lệnh `where('order_code', 'LIKE', "%$search%")->orWhere('customer_phone', 'LIKE', "%$search%")`.

#### Q92: Quản lý Banner và Bài viết tin tức nằm ở Controller nào trong Admin?
- **Trả lời:** Bài viết nằm ở [`PostAdminController.php`](file:///c:/source/luanvancuoiki/app/Http/Controllers/PostAdminController.php), Banner quảng cáo trang chủ nằm ở [`BannerAdminController.php`](file:///c:/source/luanvancuoiki/app/Http/Controllers/BannerAdminController.php).

#### Q93: Hình ảnh sản phẩm tải lên được lưu trữ ở đâu và quản lý ra sao?
- **Trả lời:** Hình ảnh tải lên được lưu trong thư mục [`public/upload/`](file:///c:/source/luanvancuoiki/public/upload) trên máy chủ. Trong Database chỉ lưu đường dẫn tương đối (ví dụ: `upload/products/kinh-rayban.jpg`) để tiết kiệm dung lượng DB.

#### Q94: Tại sao khi cập nhật thông tin Sản phẩm lại cần xóa Cache trang chủ?
- **Trả lời:** Để tránh hiện tượng dữ liệu cũ vẫn hiển thị cho khách hàng. Khi Admin sửa giá hoặc tên kính, hệ thống gọi `Cache::forget('home_featured_products')` để làm tươi lại dữ liệu.

#### Q95: Thao tác "Xuất file Excel báo cáo doanh thu" được thực hiện như thế nào?
- **Trả lời:** Hệ thống lọc dữ liệu đơn hàng theo khoảng ngày chọn, sau đó dùng thư viện `Maatwebsite\Excel` biên dịch mảng dữ liệu thành file định dạng `.xlsx` cho Admin tải về.

#### Q96: Làm sao để ngăn nhân viên Admin xóa nhầm một Danh mục đang chứa sản phẩm?
- **Trả lời:** Trong `CategoryAdminController` trước khi xóa sẽ gọi `$category->products()->count()`. Nếu số lượng sản phẩm `> 0` thì chặn lại và trả về thông báo lỗi *"Không thể xóa danh mục đang có sản phẩm"*.

#### Q97: Bảng `business_settings` trong CSDL dùng để lưu thông tin gì?
- **Trả lời:** Lưu các cấu hình động của website như: Tên cửa hàng, Hotline, Email liên hệ, Địa chỉ chi nhánh, Phí ship mặc định, Tỷ lệ thuế VAT.

#### Q98: Cơ chế Upload ảnh có kiểm tra đuôi file và dung lượng để chống Upload file độc hại (Shell Script) không?
- **Trả lời:** Có. Trong hàm validate luôn kiểm tra định dạng `image` và mimetype `jpeg,png,webp`, giới hạn dung lượng tối đa `max:2048` (2MB). Đảm bảo hacker không thể tải file `.php` lên server.

#### Q99: Thầy cô hỏi: *"Tại sao dự án chưa viết Automated Unit Test (PHPUnit)?"* — Trả lời thế nào?
- **Trả lời:** *"Dạ thưa Thầy/Cô, trong giai đoạn hoàn thiện đồ án nhóm tập trung phần lớn thời gian vào việc hoàn thiện 100% luồng nghiệp vụ thực tế và kiểm thử tĩnh (Static Audit). Việc bổ sung bộ kiểm thử tự động `phpunit` đã được nhóm vạch ra trong lộ trình Sprint maintenance tại tài liệu `docs/11-ke-hoach-bao-tri-trien-khai.md` ạ."*

#### Q100: Điểm nổi bật nhất về mặt Kiến trúc và Bảo mật của đồ án này là gì để chốt hạ buổi phản biện?
- **Trả lời:** *"Dạ thưa Thầy/Cô, đồ án của chúng em nổi bật với: 1. Kiến trúc CSDL chuẩn hóa 26 bảng có ghi vết biến động kho `StockTransaction` minh bạch; 2. Luồng thanh toán VNPay chuẩn bảo mật HMAC-SHA512 có xử lý bất đồng bộ IPN; 3. Cơ chế phân quyền Admin chặt chẽ có caching tăng tốc; và 4. Đã trải qua đợt Audit mã nguồn tĩnh toàn diện để nhận diện và quản trị rủi ro hệ thống ạ!"*
