# 02 — Module Xác thực & Phân quyền

Module này gồm 3 phần độc lập: xác thực khách hàng (client), xác thực quản trị (admin),
và quản lý tài khoản cá nhân.

---

## 2.1. `app/Http/Controllers/AuthController.php` (304 dòng)

Xác thực phía khách hàng. Viết tay hoàn toàn, **không dùng Fortify**.

### Bảng phương thức

| Method | Route | Mô tả |
|---|---|---|
| `showLogin()` | `GET /dang-nhap` | View `auth.login` |
| `login()` | `POST /dang-nhap` | Đăng nhập (throttle:auth) |
| `showRegister()` | `GET /dang-ky` | View `auth.register` |
| `register()` | `POST /dang-ky` | Đăng ký + gửi mail xác thực |
| `verifyEmail()` | `GET /xac-thuc-email/{user}/{hash}` | Kích hoạt tài khoản (middleware `signed`) |
| `showForgotPassword()` | `GET /quen-mat-khau` | View |
| `sendResetPasswordLink()` | `POST /quen-mat-khau` | Gửi link đặt lại MK |
| `showResetPassword()` | `GET /khoi-phuc-mat-khau/{token}` | View (đã kiểm token) |
| `resetPassword()` | `POST /khoi-phuc-mat-khau` | Đặt MK mới |
| `logout()` | `GET|POST /dang-xuat` | Đăng xuất |

### Luồng đăng nhập (`login`, dòng 26–57)

```
validate(email, password)
  → tìm User theo email
  → passwordMatches()?           → không: "Email hoặc mật khẩu không đúng."
  → status === 'ACTIVE'?         → không: "Tài khoản đã bị khóa."
  → email_verified_at != null?   → không: "Vui lòng kiểm tra Gmail..."
  → Hash::needsRehash()? → rehash và lưu lại (di trú hash cũ)
  → Auth::login() + session()->regenerate()
  → redirect()->intended(route('account.index'))
```

`passwordMatches()` (dòng 256) có fallback thú vị:
```php
try { return Hash::check($password, $hash); }
catch (\RuntimeException) { return password_verify($password, $hash); }
```
Đây là để đọc được hash từ hệ thống PHP cũ mà driver hash của Laravel không nhận diện.
Kết hợp với `needsRehash` ở trên, hệ thống **tự động nâng cấp hash cũ sang bcrypt** khi user
đăng nhập lần đầu. Thiết kế tốt.

> ⚠️ **`login()` không phân biệt "sai mật khẩu" và "email không tồn tại"** — đúng chuẩn bảo
> mật ✅. Nhưng **thông báo "Tài khoản đã bị khóa" và "chưa xác thực email" lại xuất hiện SAU
> khi mật khẩu đúng**, nên không rò rỉ thông tin. Cũng ổn.

> 🔴 **Cột khóa tài khoản là code chết.** Bảng `users` có `failed_login_count`,
> `last_failed_login_at`, `locked_until` (khai báo đủ trong migration và `$fillable`), nhưng
> **`login()` không bao giờ tăng bộ đếm, không bao giờ kiểm tra `locked_until`**. Nơi duy nhất
> chạm tới chúng là `resetPassword()` (reset về 0/null). Tương tự `last_login_at` **không bao
> giờ được ghi**. Cơ chế chống brute-force thực tế chỉ còn `throttle:auth` (5 lần/phút theo
> email+IP). Xem [10](10-ket-qua-audit.md) mục M-01.

### Luồng đăng ký (`register`) — đã mở rộng

Điểm mạnh: **transaction + rollback thủ công khi gửi mail thất bại**.

```php
$user = DB::transaction(fn() => {
    tạo User (email_verified_at = null, provider = 'LOCAL', status = 'ACTIVE')
    gán role 'USER' qua DB::table('user_roles')->insert()
    tạo UserAddress mặc định (MỚI)
});
$this->sendEmailVerificationLink($user);   // ← ngoài transaction
```
Nếu bước gửi mail ném exception, `catch` sẽ **xóa user, user_roles và UserAddress vừa tạo**,
log lỗi, và báo "Chưa gửi được email xác thực...". Tránh được tài khoản mồ côi không kích hoạt được.

**Thay đổi trong đợt cập nhật `ee3dfa5`:** form đăng ký nay thu thêm địa chỉ:
```php
'phone'          => ['nullable', 'regex:/^(03|05|07|08|09)\d{8}$/'],   // ← trước chỉ 'string|max:20'
'province_name'  => ['required', 'string', 'max:100', Rule::in($this->cities())],
'address_detail' => ['required', 'string', 'max:255'],
```
Kèm **bộ thông báo lỗi tiếng Việt đầy đủ** cho mọi rule ✅, và tự tạo `UserAddress` với
`is_default = true`.

> ⚠️ **`cities()` bị sao chép nguyên văn** (63 tỉnh) từ `AccountController` sang `AuthController`.
> Nay tồn tại **2 bản sao** của cùng một danh sách; sửa một nơi quên nơi kia là sinh lỗi validate
> khó truy. Nên đưa vào `config/` hoặc một class dùng chung.

> ⚠️ `'phone' => $data['phone'] ?? ''` khi tạo `UserAddress` — nếu khách bỏ trống SĐT thì địa chỉ
> mặc định có `phone` rỗng, trong khi `AccountController::validateAddress()` bắt buộc `phone`.
> Khách sửa lại địa chỉ đó sẽ bị chặn cho tới khi nhập SĐT.

> ⚠️ **Khách nay không sửa được hồ sơ.** Route `/tai-khoan/ho-so` đã đổi thành
> `Route::redirect('/tai-khoan/ho-so', '/tai-khoan')` và view `account/profile.blade.php` **đã bị
> xóa**. Nghịch lý: đăng ký thu địa chỉ + SĐT nhưng khách không có nơi sửa lại.
> Xem [10](10-ket-qua-audit.md) mục **N-06**.

> ⚠️ Việc xóa nằm trong `catch` không có transaction bao ngoài. Nếu `sendEmailVerificationLink`
> thành công nhưng có lỗi khác sau đó, điều kiện `if ($user && ! $user->email_verified_at)`
> vẫn đúng → **xóa nhầm user đã gửi mail thành công**. Xác suất thấp nhưng logic chưa chặt.

Quy tắc validate mật khẩu đăng ký: `min:8`, `confirmed`, `max:255`. **Không yêu cầu** chữ hoa/
số/ký tự đặc biệt, không kiểm tra danh sách mật khẩu rò rỉ (`Password::defaults()`).

### Xác thực email (`verifyEmail`, dòng 118–132)

Route có middleware `signed` (chữ ký Laravel, hết hạn 60 phút) **cộng thêm** kiểm tra thủ công
`hash_equals($hash, sha1($user->email))`. Hai lớp, an toàn. Sau khi xác thực → `Auth::login()`
+ `session()->regenerate()` → vào thẳng trang tài khoản.

### Đặt lại mật khẩu (dòng 139–245)

**Không dùng** bảng `password_reset_tokens` mặc định của Laravel mà dùng schema riêng:
`id, user_id, token_hash, expires_at, used_at, created_at`.

Luồng đúng chuẩn:
- Token `Str::random(72)`, **lưu dưới dạng `hash('sha256', $token)`** — DB bị lộ vẫn không dùng được token ✅
- Vô hiệu hóa mọi token cũ chưa dùng của user (`update(['used_at' => now()])`) trước khi tạo mới ✅
- Hạn 60 phút ✅
- **Không tiết lộ email có tồn tại hay không** — luôn trả cùng một thông báo ✅
- Khi đổi MK thành công: transaction đổi `password_hash` + đánh dấu token `used_at` ✅

> ⚠️ Ngoại lệ rò rỉ: nếu gửi mail **thất bại**, hàm trả về `withErrors(['email' => 'Chưa gửi
> được email...'])` — **chỉ xảy ra khi email tồn tại**. Kẻ tấn công gây lỗi SMTP có thể phân
> biệt email tồn tại/không. Rủi ro thấp.
>
> ⚠️ Khi gửi mail lỗi, `Log::error` ghi **cả `reset_url` chứa token thô** vào log file. Ai đọc
> được log có thể chiếm tài khoản. Xem [10](10-ket-qua-audit.md) mục M-02.

> ⚠️ `resetPassword()` **không hủy các session đang hoạt động** của user. Kẻ đã chiếm session
> vẫn giữ quyền sau khi nạn nhân đổi mật khẩu.

### Email

Cả 2 mail dùng `Mail::raw()` — **plain text, không template, không queue**. Gửi đồng bộ ngay
trong request → request đăng ký/quên MK phải chờ SMTP Gmail phản hồi. `QUEUE_CONNECTION=database`
đã cấu hình nhưng không dùng cho mail.

---

## 2.2. `app/Http/Controllers/Admin/AdminAuthController.php` (80 dòng)

Đăng nhập riêng cho khu vực admin tại `/admin/dang-nhap`.

Khác biệt so với `AuthController::login()`:

| | Client | Admin |
|---|---|---|
| Kiểm tra `status === 'ACTIVE'` | ✅ | ✅ |
| Kiểm tra `email_verified_at` | ✅ bắt buộc | ❌ **không kiểm tra** |
| Kiểm tra vai trò | ❌ | ✅ phải có `ADMIN` hoặc `STAFF` |
| Rehash mật khẩu cũ | ✅ | ✅ |
| Throttle | `auth` (5/phút) | `admin-auth` (5/phút) |
| Thông báo lỗi | tách riêng khóa/chưa xác thực | **gộp chung** "Tài khoản này không có quyền vào admin." |

Việc gộp thông báo lỗi ở admin là **cố ý và đúng** — không cho kẻ dò biết email nào là admin.

`canAccessAdmin()` truy vấn trực tiếp `user_roles JOIN roles` chứ không dùng quan hệ
`User::roles()` — nhất quán với `EnsureAdmin`, tránh N+1 nhưng lặp code ở 2 nơi.

> ⚠️ Admin không bị bắt xác thực email. Tài khoản do `CustomerAdminController` tạo ra
> **không bao giờ có `email_verified_at`** (xem §2.4) → tài khoản staff mới **vào được admin
> nhưng KHÔNG đăng nhập được ở phía client**. Bất đối xứng gây khó hiểu.

---

## 2.3. `app/Http/Middleware/EnsureAdmin.php` (64 dòng) — alias `admin`

Cổng phân quyền của toàn bộ `/admin/*`. Nhận tham số vai trò: `admin` (mặc định) hoặc
`admin:ADMIN`.

```php
private const ADMIN_AREA_ROLES = ['ADMIN', 'STAFF'];

handle($request, $next, string ...$roles)
```

Thứ tự kiểm tra:

1. Chưa đăng nhập → `redirect('admin.login')`
2. `status !== 'ACTIVE'` → **logout + hủy session** + về trang login kèm lỗi
3. Không có vai trò yêu cầu:
   - Nếu đang yêu cầu vai trò **hẹp hơn** (`admin:ADMIN`) mà user vẫn thuộc khu vực admin
     (là `STAFF`) → **giữ nguyên đăng nhập**, đá về dashboard với thông báo "không có quyền
     truy cập chức năng này" ✅ (xử lý đúng, không đăng xuất oan)
   - Ngược lại (không thuộc admin area) → **logout + hủy session**
4. Qua hết → `$next($request)`

### Mô hình 2 cấp quyền

| | `STAFF` | `ADMIN` |
|---|---|---|
| Dashboard, sản phẩm, danh mục, đơn hàng, hoàn đổi, kho, bài viết, bình luận | ✅ | ✅ |
| Báo cáo (5 màn hình) | ❌ | ✅ |
| Thành viên (`/admin/thanh-vien`) | ❌ | ✅ |
| Banner, Bố cục trang chủ, Nghiệp vụ | ❌ | ✅ |

Định nghĩa trong `routes/admin.php` bằng `Route::middleware('admin:ADMIN')->group(...)`.

### Cache vai trò (MỚI) — sửa được một vấn đề, tạo ra một vấn đề khác

Trước đây `hasAnyRole()` chạy 1 query JOIN **mỗi request**. Nay:

```php
private function hasAnyRole(int $userId, array $roles): bool
{
    $userRoles = Cache::remember("users.{$userId}.role_codes", now()->addMinutes(5),
        fn () => DB::table('user_roles')->join('roles', ...)->where('user_roles.user_id', $userId)
            ->pluck('roles.code')->all());

    return count(array_intersect($roles, $userRoles)) > 0;
}
```

Cùng một khối được áp dụng cho `AdminAuthController::canAccessAdmin()`. Lưu **toàn bộ mã vai
trò** thay vì kết quả boolean của một truy vấn cụ thể — đúng cách, dùng lại được cho mọi lần
kiểm tra ✅.

> 🔴 **Nhưng cache này không bao giờ bị vô hiệu hóa.** Rà soát toàn dự án: **không có một
> `Cache::forget("users.{id}.role_codes")` nào**. `CustomerAdminController::syncRole()` đổi vai
> trò trong CSDL nhưng không đụng tới cache.
>
> Hệ quả bảo mật: hạ cấp một `ADMIN` xuống `USER` → người đó **vẫn giữ toàn quyền admin trong
> tối đa 5 phút**. Thu hồi quyền nhân viên vừa nghỉ việc không có hiệu lực ngay.
>
> Điểm nhẹ nhõm: kiểm tra `status !== 'ACTIVE'` đọc từ `Auth::user()` (**không cache**) nên
> **khóa tài khoản vẫn có hiệu lực tức thì** ✅ — chỉ vai trò bị chậm.
>
> Xem [10](10-ket-qua-audit.md) mục **N-02**.

> ⚠️ **Phân quyền chỉ ở mức route, không ở mức bản ghi.** Bất kỳ `STAFF` nào cũng sửa được
> **mọi** sản phẩm, đơn hàng, bài viết. Không có khái niệm "đơn hàng của tôi phụ trách".
> Với dự án luận văn thì chấp nhận được, nhưng cần nêu rõ trong báo cáo.

> 🔴 **Không có Policy/Gate nào trong toàn dự án.** Kiểm tra sở hữu được viết tay rải rác
> bằng `abort_unless($x->user_id === Auth::id(), 403)` (có ở `AccountController::showOrder`,
> `ensureOwnAddress`, `ReturnRequestController` 3 chỗ). Cách này đúng nhưng dễ quên khi thêm
> chức năng mới.

---

## 2.4. `app/Http/Controllers/AccountController.php` (276 dòng)

Khu vực "Tài khoản của tôi" — 12 route dưới `/tai-khoan`.

| Method | Route | Ghi chú |
|---|---|---|
| `index()` | `GET /tai-khoan` | Hồ sơ + địa chỉ + 5 đơn gần nhất |
| `orders()` | `GET /tai-khoan/don-hang` | Lọc theo status, date_from, date_to; paginate 12 |
| `showOrder()` | `GET /tai-khoan/don-hang/{order}` | **`abort_unless($order->user_id === Auth::id(), 403)`** ✅ |
| **`invoice()`** | `GET /tai-khoan/don-hang/{order}/hoa-don` | **MỚI** — trang hóa đơn in được |
| **`emailInvoice()`** | `POST /tai-khoan/don-hang/{order}/hoa-don/gui-email` | **MỚI** — gửi hóa đơn về email, `throttle:user-actions` |
| ~~`editProfile()` / `updateProfile()`~~ | ~~`/tai-khoan/ho-so`~~ | 🔴 **Route đã gỡ, view đã xóa — nay là code chết** (N-06) |
| `editPassword()` / `updatePassword()` | `/tai-khoan/doi-mat-khau` | Yêu cầu mật khẩu hiện tại ✅ |
| `createAddress()` … `destroyAddress()` | `/tai-khoan/dia-chi/*` | CRUD địa chỉ, **tối đa 2** |

### Quy tắc validate (đáng chú ý)

- Điện thoại: `regex:/^(03|05|07|08|09)\d{8}$/` — đầu số di động VN, đúng.
- Tỉnh/thành: `in:` + danh sách **63 tỉnh hardcode** trong `cities()` (dòng 258–275).
  Whitelist chặt ✅ nhưng cứng nhắc — sau sáp nhập tỉnh 2025 danh sách này đã lỗi thời và
  phải sửa code để cập nhật.
- Ngày sinh: `before_or_equal:today`, `after_or_equal:1900-01-01` ✅
- Avatar: `image`, `mimes:jpg,jpeg,png,webp`, `max:5120` (5 MB)

### Quản lý địa chỉ mặc định (`is_default`)

Logic được xử lý cẩn thận trong transaction ở cả 3 thao tác:
- **Thêm:** nếu là địa chỉ đầu tiên hoặc user tick "mặc định" → bỏ cờ mọi địa chỉ khác trước.
- **Sửa:** tương tự, loại trừ chính nó (`whereKeyNot`).
- **Xóa:** nếu xóa địa chỉ mặc định → tự động phong địa chỉ còn lại mới nhất làm mặc định.

Đây là phần được viết chỉn chu nhất của controller.

### Upload avatar — ⚠️ nay là code chết

> **Cập nhật:** toàn bộ `editProfile()` / `updateProfile()` (74 dòng, gồm cả upload avatar) vẫn
> nằm trong controller nhưng **không còn route nào gọi tới**, và view `account/profile.blade.php`
> **đã bị xóa** — `editProfile()` sẽ ném `ViewNotFoundException` nếu ai khôi phục route.
> Phân tích dưới đây giữ lại để tham khảo nếu chức năng được phục hồi.

```php
$file = $request->file('avatar');
$fileName = 'avatar_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
$file->move(public_path('upload'), $fileName);
```

- Dùng **`getClientOriginalExtension()`** — phần mở rộng do **client gửi lên**, không phải suy
  từ nội dung file. Các chỗ khác trong dự án (`ProductAdminController`, `PostAdminController`)
  dùng `$file->extension()` (suy từ MIME) — an toàn hơn.
- Rule `image` + `mimes:` của Laravel đã kiểm tra nội dung thật qua fileinfo nên **hiện tại
  chưa khai thác được**, nhưng đây là phòng thủ một lớp; kết hợp với việc ghi thẳng vào
  `public_path()` (thư mục web servable) thì một sai sót nhỏ ở validate sẽ thành RCE.
- **Không xóa avatar cũ** → rác tích tụ vô hạn trong `public/upload/`.
- Ghi trực tiếp vào `public/` (không qua `Storage`) → **trên Railway, file upload mất sạch sau
  mỗi lần deploy** vì filesystem là ephemeral. Xem [10](10-ket-qua-audit.md) mục **H-03**.

Xem thêm mục "Ba chiến lược lưu ảnh mâu thuẫn" trong [tài liệu 08](08-module-noi-dung.md).

---

## 2.5. `app/Models/User.php` (86 dòng) & `Role.php`

```php
class User extends Authenticatable {
    use HasApiTokens, HasFactory, Notifiable;
    protected $hidden = ['password_hash'];
    public function getAuthPassword(): string { return (string) $this->password_hash; }
    public function getNameAttribute(): string { return (string) ($this->full_name ?: $this->email); }
}
```

- **`getAuthPassword()` override** để Laravel dùng cột `password_hash` thay vì `password` —
  đây là mấu chốt cho phép giữ nguyên schema của hệ thống cũ.
- **`getNameAttribute()`** là accessor ảo, giúp view Jetstream gọi `$user->name` không vỡ.
  Nhưng nó khiến `User::create(['name' => ...])` **im lặng bỏ qua** — chính là bug của
  `DatabaseSeeder` và `UserFactory`.
- **Không implement `MustVerifyEmail`** dù logic đăng nhập tự kiểm `email_verified_at`.
- Quan hệ: `addresses`, `orders`, `productReviews`, `returnRequests`, `roles` (belongsToMany
  qua bảng trung gian `user_roles`).

`Role`: `code`, `name`, `description`, `is_system` (bool). Các mã dùng trong code: `ADMIN`,
`STAFF`, `USER`.

> ⚠️ Quan hệ `User::roles()` **hầu như không được dùng** — mọi kiểm tra quyền đều dùng
> `DB::table('user_roles')->join('roles')` thủ công ở 4 nơi khác nhau
> (`EnsureAdmin`, `AdminAuthController`, `CustomerAdminController`, `DashboardController`).
> Nên gom về một `User::hasRole()` duy nhất.

---

## 2.6. Tổng kết bảo mật module này

| Điểm tốt | Điểm cần sửa |
|---|---|
| Token reset băm SHA-256, có `used_at`, hạn 60 phút | Cột khóa tài khoản là code chết (M-01) |
| Không tiết lộ email tồn tại (trừ 1 nhánh lỗi SMTP) | Log ghi token reset thô (M-02) |
| `session()->regenerate()` sau mọi lần đăng nhập | Đổi MK không hủy session khác |
| Rehash tự động hash cũ | Không có yêu cầu độ mạnh mật khẩu |
| Phân quyền 2 cấp rõ ràng, xử lý `admin:ADMIN` đúng | Không có Policy/Gate, kiểm sở hữu viết tay |
| CSRF bật toàn cục (trừ IPN, đúng) | 🆕 **Cache vai trò 5 phút không bao giờ bị xóa (N-02)** |
| Whitelist tỉnh/thành chặt (nay áp dụng cả cho đăng ký ✅) | 🆕 `cities()` bị sao chép ở 2 file |
| 🆕 Cache vai trò — bỏ được truy vấn lặp mỗi request | 🆕 Khách không sửa được hồ sơ nữa (N-06) |
| 🆕 Thông báo lỗi tiếng Việt đầy đủ cho form đăng ký | 🆕 `AuthController` vẫn gửi mail **đồng bộ**, chưa dùng `QueuedRawMail` |
| 🆕 Link ký hết hạn được xử lý mềm (`InvalidSignatureException`) | 🆕 Chuỗi thông báo đó bị **mojibake** (N-05) |
