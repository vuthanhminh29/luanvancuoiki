# 09 — Mô hình dữ liệu

> ⚠️ **Cảnh báo quan trọng:** Sơ đồ dưới đây được **suy ngược từ code** (`$fillable`, `$casts`,
> quan hệ Eloquent, và các truy vấn SQL thô), **không phải từ migration**. Repo nay có 15
> migration nhưng chúng chỉ tạo 7 bảng hạ tầng + ALTER lên bảng có sẵn; ~20 bảng nghiệp vụ
> **vẫn không có định nghĩa nào trong mã nguồn**. Xem [10 — Audit](10-ket-qua-audit.md) mục **C-01**.
>
> Do đó: kiểu dữ liệu chính xác, khóa ngoại và ràng buộc UNIQUE **vẫn không thể xác minh**.
> **Chỉ mục thì nay đã xác minh được** — migration `2026_08_04_130000` khai báo tường minh
> ~40 index (xem §9.6).

---

## 9.1. Sơ đồ tổng thể

```
                    ┌─────────┐        ┌────────────┐
                    │  roles  │───n:n──│user_roles  │
                    └─────────┘        └─────┬──────┘
                                             │
┌──────────────┐                       ┌─────┴─────┐
│user_addresses│──────────n:1──────────│   users   │
└──────────────┘                       └─────┬─────┘
                                             │ 1:n
        ┌────────────────────────────────────┼──────────────────┐
        │                                    │                  │
   ┌────┴─────┐                     ┌────────┴───────┐   ┌──────┴────────┐
   │  orders  │                     │product_reviews │   │return_requests│
   └────┬─────┘                     └────────────────┘   └───────┬───────┘
        │ 1:n                                                    │ 1:n
   ┌────┴────────┐   ┌──────────┐                   ┌────────────┼──────────────┐
   │ order_items │   │ payments │                   │            │              │
   └────┬────────┘   └──────────┘        ┌──────────┴──┐ ┌───────┴────────┐ ┌───┴────────────────────┐
        │                                │return_request│ │return_request │ │return_damage           │
        │ n:1                            │   _items     │ │   _images     │ │  _assessments          │
        │                                └──────────────┘ └───────────────┘ └────────────────────────┘
┌───────┴──────────┐
│ product_variants │──n:1──┬── colors
└───────┬──────────┘       └── lens_sizes
        │ n:1
┌───────┴────┐──n:1──┬── categories
│  products  │       ├── brands
└───────┬────┘       ├── frame_shapes
        │ 1:n        └── frame_materials
┌───────┴────────┐
│ product_images │
└────────────────┘

┌────────────┐  1:n  ┌─────────────┐  n:1  ┌──────────────────┐
│ warehouses │───────│ inventories │───────│ product_variants │
└─────┬──────┘       └─────────────┘       └──────────────────┘
      │ (source/target)
┌─────┴─────────────┐  1:n  ┌────────────────────────┐
│stock_transactions │───────│stock_transaction_items │
└───────────────────┘       └────────────────────────┘

┌────────────┐        ┌───────┐        ┌───────────────┐        ┌──────────────┐
│ promotions │───1:n──│orders │        │post_categories│───1:n──│    posts     │
└────────────┘        └───────┘        └───────────────┘        └──────────────┘

┌─────────┐  ┌──────────────┐  ┌────────────────┐  ┌────────┐
│ banners │  │ home_layouts │  │ return_reasons │  │ stores │
└─────────┘  └──────────────┘  └────────────────┘  └────────┘
   (độc lập)     (không model)                      (không model)
```

---

## 9.2. Danh sách bảng

### Nhóm hạ tầng — **có migration** ✅

| Bảng | Migration | Ghi chú |
|---|---|---|
| `users` | `0001_01_01_000000` | Dùng `password_hash` + `full_name` (không phải `password`/`name`) |
| `password_reset_tokens` | `0001_01_01_000000` | Schema riêng: `token_hash`, `expires_at`, `used_at` |
| `sessions` | `0001_01_01_000000` | |
| `cache`, `cache_locks` | `0001_01_01_000001` | |
| `jobs`, `job_batches`, `failed_jobs` | `0001_01_01_000002` | Hàng đợi cấu hình nhưng chưa dùng |
| `personal_access_tokens` | `2026_07_10_013652` | Sanctum |
| (cột 2FA trên `users`) | `2026_07_10_013605` | Fortify 2FA — không dùng |

### Nhóm nghiệp vụ — **KHÔNG có migration** 🔴

| # | Bảng | Model | Ghi chú |
|---|---|---|---|
| 1 | `roles` | `Role` | `code` ∈ {ADMIN, STAFF, USER}, `is_system` |
| 2 | `user_roles` | (pivot, không model) | Thao tác bằng `DB::table()` ở 4 nơi |
| 3 | `user_addresses` | `UserAddress` | Tối đa 2/user (ràng buộc ở tầng app) |
| 4 | `categories` | `Category` | Có `parent_id` nhưng **không có quan hệ cha-con nào trong model** |
| 5 | `brands` | `Brand` | |
| 6 | `frame_shapes` | `FrameShape` | `UPDATED_AT = null` |
| 7 | `frame_materials` | `FrameMaterial` | `UPDATED_AT = null` |
| 8 | `colors` | `Color` | `hex_code`, `UPDATED_AT = null` |
| 9 | `lens_sizes` | `LensSize` | `bridge_width`, `temple_length`, `lens_width`, `lens_height` |
| 10 | `products` | `Product` | |
| 11 | `product_variants` | `ProductVariant` | Đơn vị tồn kho & đặt hàng |
| 12 | `product_images` | `ProductImage` | `CREATED_AT` có, `UPDATED_AT = null` |
| 13 | `product_reviews` | `ProductReview` | |
| 14 | `promotions` | `Promotion` | |
| 15 | `orders` | `Order` | |
| 16 | `order_items` | `OrderItem` | `$timestamps = false`, lưu snapshot |
| 17 | `payments` | `Payment` | Chỉ VNPay ghi vào |
| 18 | `return_reasons` | `ReturnReason` | `$timestamps = false` |
| 19 | `return_requests` | `ReturnRequest` | `CREATED_AT = 'requested_at'`, `UPDATED_AT = null` |
| 20 | `return_request_items` | `ReturnRequestItem` | `$timestamps = false` |
| 21 | `return_request_images` | `ReturnRequestImage` | **Chưa có code nào ghi vào** |
| 22 | `return_damage_assessments` | `ReturnDamageAssessment` | Đặc thù kính mắt |
| 23 | `warehouses` | `Warehouse` | |
| 24 | `inventories` | `Inventory` | `CREATED_AT = null` |
| 25 | `stock_transactions` | `StockTransaction` | `UPDATED_AT = null` |
| 26 | `stock_transaction_items` | `StockTransactionItem` | `$timestamps = false` |
| 27 | `banners` | `Banner` | |
| 28 | `post_categories` | `PostCategory` | |
| 29 | `posts` | `Post` | |
| 30 | `home_layouts` | **không có model** | Chỉ `DB::table()` |
| 31 | `stores` | **không có model** | Chỉ đọc, không có chức năng tạo |
| 32 | **`try_on_snapshots`** | **`TryOnSnapshot`** | ✅ **CÓ migration** (`2026_08_04_120000`) — bảng nghiệp vụ **duy nhất** được định nghĩa trong repo |

**Tổng: 32 bảng nghiệp vụ, 27 model, ~20 bảng vẫn không có migration.**

### Cột mới thêm trong đợt cập nhật `ee3dfa5`

| Bảng | Cột | Migration | Mục đích |
|---|---|---|---|
| `orders` | `cancel_confirmation_token_hash`, `cancel_reason`, `cancel_requested_at`, `cancel_confirmed_at` | `2026_08_04_110000` | Luồng hủy đơn 2 bước |
| `orders` | `order_confirmation_email_sent_at` | `2026_08_04_113000` | Chống gửi trùng email xác nhận |
| `orders` | `delivered_at` | `2026_08_04_140000` | Thời điểm giao thành công |
| `inventories` | ~~`reserved_quantity`~~ **bị xóa** + `CHECK (quantity >= 0)` | `2026_08_04_152000` | Đơn giản hóa tồn kho |
| `try_on_snapshots` | bảng mới: `user_id`, `product_id`, `variant_id`, `user_name`, `user_email`, `product_name`, `model_sku`, `price`, `image_path`, `tryon_mode` | `2026_08_04_120000` | Lưu kết quả thử kính |

**Enum thay đổi:**
- `stock_transactions.type`: bỏ `TRANSFER`, thêm `SALE_OUT` và `DAMAGE` (migration `2026_08_04_153000`)
- `warehouses.type`: kho `RETURN` gộp về `NORMAL` (migration `2026_08_04_151000`)
- `orders.status`: `LOST_IN_TRANSIT` bị gỡ khỏi `VALID_STATUSES` của controller (nhưng báo cáo vẫn tham chiếu)

---

## 9.3. Bảng enum (tổng hợp từ rule validate & truy vấn)

| Bảng.Cột | Giá trị | Nguồn xác định |
|---|---|---|
| `users.status` | `ACTIVE`, `LOCKED` | `CustomerAdminController::validateCustomer` |
| `users.gender` | `MALE`, `FEMALE`, `OTHER` | `AccountController::updateProfile` |
| `users.provider` | `LOCAL` (mặc định), (`google_id` có cột nhưng không có luồng OAuth) | migration |
| `roles.code` | `ADMIN`, `STAFF`, `USER` | `EnsureAdmin`, `AuthController::register` |
| `products.status` | `DRAFT`, `ACTIVE`, `INACTIVE`, `DISCONTINUED` | `ProductAdminController::validateProduct` |
| `products.uv_protection` | `UV380`, `UV400`, `NONE` | như trên |
| `product_variants.status` | `ACTIVE`, `OUT_OF_STOCK`, `DISCONTINUED` | như trên |
| `categories.status`, `brands.status`, `warehouses.status`, `post_categories.status`, `banners.status`, `return_reasons.status` | `ACTIVE`, `INACTIVE` | rải rác |
| `product_reviews.status` | `VISIBLE`, `PENDING`, `HIDDEN` | `ReviewAdminController`, `Product::visibleReviews` |
| `orders.status` | `PENDING`, `AWAITING_PAYMENT`, `CONFIRMED`, `DELIVERING`, `DELIVERED`, `CANCELLED`, `RETURN_PENDING`, `RETURNED`, `EXCHANGED`, `LOST_IN_TRANSIT` | `OrderAdminController::updateStatus` |
| `orders.payment_status` | `UNPAID`, `PAID` | `CheckoutController`, `VnPayController` |
| `orders.payment_method`, `payments.method` | `COD`, `VNPAY` | migration `2026_07_10_060000` |
| `payments.status` | `SUCCESS`, `FAILED` | `VnPayController` |
| `promotions.discount_type` | `PERCENT`, `FIXED_AMOUNT` | `BusinessAdminController::savePromotion` |
| `promotions.scope` | `ORDER` (giá trị duy nhất được dùng) | `CheckoutController::promotionFromCode` |
| `promotions.status` | `SCHEDULED`, `ACTIVE`, `INACTIVE`, `EXPIRED` | `savePromotion` |
| `return_requests.type` | `RETURN`, `EXCHANGE` | `ReturnRequestController::store` |
| `return_requests.status` | `PENDING`, `APPROVED`, `REJECTED`, `RECEIVED`, `COMPLETED`, `CANCELLED` | `ReturnAdminController::update` |
| `return_damage_assessments.part_code` | 8 giá trị (xem [05](05-module-don-hang-hoan-doi.md) §5.4) | `damagePartOptions()` |
| `return_damage_assessments.damage_level` | `NONE`, `LIGHT`, `MEDIUM`, `HEAVY`, `SEVERE` | `damageLevelFromPercent()` |
| `warehouses.type` | `NORMAL`, `RETURN`, `WARRANTY`, `STORE` | `BusinessAdminController::saveWarehouse` |
| `stock_transactions.type` | `IMPORT`, `EXPORT`, `TRANSFER` | `WarehouseAdminController::storeTransaction` |
| `stock_transactions.status` | `COMPLETED` (giá trị duy nhất từng được ghi) | như trên |
| `banners.position` | `HOME_SLIDER`, `HOME_BANNER_1`, `HOME_BANNER_2`, `CATEGORY_BANNER`, `PRODUCT_BANNER` | `BannerAdminController` |
| `banners.platform` | `DESKTOP`, `MOBILE`, `BOTH` | như trên |
| `posts.status` | `DRAFT`, `PUBLISHED`, `HIDDEN` | `PostAdminController::validatePost` |

> **Quy ước dự án:** mọi enum dùng `UPPER_SNAKE_CASE` tiếng Anh, lưu dạng chuỗi trong CSDL,
> validate bằng rule `in:` ở tầng ứng dụng. Nhất quán tốt ✅.

---

## 9.4. Cột được khai báo nhưng không bao giờ được dùng

Đây là chỉ dấu quan trọng cho biết chức năng nào **được thiết kế nhưng chưa cài đặt**:

| Bảng.Cột | Tình trạng |
|---|---|
| `users.failed_login_count` | Chỉ reset ở `resetPassword`, **không bao giờ tăng** |
| `users.last_failed_login_at` | **Không bao giờ được ghi** |
| `users.locked_until` | Chỉ reset, **không bao giờ được kiểm tra** |
| `users.last_login_at` | **Không bao giờ được ghi** |
| `users.google_id`, `users.provider` | Không có luồng đăng nhập Google nào |
| ~~`inventories.reserved_quantity`~~ | ✅ **ĐÃ XÓA KHỎI CSDL** (migration `2026_08_04_152000` cộng dồn vào `quantity` rồi drop cột) |
| `orders.address_id` | Có trong `$fillable`, **vẫn không bao giờ được set** — kể cả khi `register` nay tự tạo `UserAddress` |
| `orders.delivered_at` | ✅ **Nay đã được lưu thật** qua `forceFill()` (migration `2026_08_04_140000`) — nhưng vẫn không có trong `$fillable` |
| `order_items.discount_amount` | Luôn ghi `0` |
| `promotions.usage_per_user` | **Không bao giờ được đặt, không bao giờ được kiểm tra** |
| `promotions.stackable` | Được ghi nhưng **không bao giờ được đọc** |
| `stock_transactions.related_order_id` | ✅ **Nay đã được ghi** bởi `createSaleOutTransaction()` — nhưng phiếu đó không trừ kho (C-02) |
| `stock_transaction_items.ordered_quantity` vs `actual_quantity` | **Luôn bằng nhau** |
| `return_requests.reviewed_by` | **Không bao giờ được ghi** (nhưng `reviewed_at` thì có) |
| `return_request_items.exchange_variant_id` | **Không bao giờ được ghi** → chức năng đổi hàng dở dang |
| `return_request_images.*` | Toàn bộ bảng **không có code nào ghi vào** |
| `product_images.is_thumbnail` | Luôn ghi `false` |
| `categories.parent_id` | Có cột, **không có quan hệ cha-con trong model** → không có danh mục đa cấp |
| `payments.expired_at` | Không bao giờ được ghi |
| `warehouses.capacity` | Được validate khi tạo, **không bao giờ được kiểm tra khi nhập hàng** |
| `banners.platform` | Được lưu và lọc trong admin, **không ảnh hưởng hiển thị** |

---

## 9.5. Quy ước timestamp (không đồng nhất)

| Model | Quy ước |
|---|---|
| `Product`, `Order`, `User`, `Category`, `Brand`, `Warehouse`, `Post`, `Banner`, `Promotion`, `ProductReview`, `ProductVariant` | Đầy đủ `created_at` + `updated_at` |
| `Inventory` | `CREATED_AT = null` — chỉ có `updated_at` |
| `ProductImage`, `StockTransaction`, `ReturnRequestImage`, `Color`, `LensSize`, `FrameShape`, `FrameMaterial` | `UPDATED_AT = null` — chỉ có `created_at` |
| `ReturnRequest` | `CREATED_AT = 'requested_at'`, `UPDATED_AT = null` |
| `OrderItem`, `ReturnRequestItem`, `ReturnDamageAssessment`, `StockTransactionItem`, `ReturnReason` | `$timestamps = false` |

Đây là dấu hiệu rõ ràng rằng schema được kế thừa từ hệ thống cũ và model Laravel được viết
để **khớp với CSDL có sẵn**, không phải ngược lại.

---

## 9.6. Chỉ mục — ✅ phần lớn đã được bổ sung

Migration `2026_08_04_130000_add_performance_indexes.php` khai báo **~40 chỉ mục trên 18 bảng**,
với cơ chế phòng thủ tốt: bỏ qua nếu bảng/cột/index đã tồn tại
(`Schema::hasTable`, `hasColumn`, `hasIndex`), và `down()` gỡ theo thứ tự ngược ✅.

Đã có (đối chiếu với khuyến nghị bên dưới):

| Khuyến nghị cũ | Đã có? | Tên index thực tế |
|---|---|---|
| `order_items (product_id)` | ✅ | `idx_order_items_product` |
| `order_items (order_id)` | ✅ | `idx_order_items_order` |
| `orders (status, created_at)` | ✅ | `idx_orders_status_created` |
| `orders (user_id, ...)` | ✅ | `idx_orders_user_created` |
| `orders (order_code)` | ✅ | `idx_orders_order_code` (⚠️ **không UNIQUE**) |
| `inventories (variant_id)` | ✅ | `idx_inventories_variant` |
| `inventories (warehouse_id, variant_id)` | ✅ | `idx_inventories_variant_warehouse` (⚠️ **không UNIQUE**) |
| `product_variants (product_id, ...)` | ✅ | `idx_product_variants_product_status` |
| `products (status, category_id)` | ✅ | `idx_products_status_category` |
| `products (slug)` | ✅ | `idx_products_slug` (⚠️ **không UNIQUE**) |
| `product_reviews (product_id, status)` | ✅ | `idx_product_reviews_product_status_created` |
| `user_roles (user_id)` | ✅ | `idx_user_roles_user_role` + `idx_user_roles_role_user` |
| `banners (position, status, start_at)` | ✅ | `idx_banners_status_position_priority` + `idx_banners_dates` |
| `posts (slug)` | ✅ | `idx_posts_slug` (⚠️ **không UNIQUE**) |

Bổ sung ngoài khuyến nghị: `users(status)`, `users(provider)`, `roles(code)`,
`password_reset_tokens(user_id, used_at, expires_at)`, `orders(payment_method, created_at)`,
`orders(recipient_phone)`, `order_items(variant_id)`, `products(status, view_count)`,
`products(product_code)`, `product_variants(color_id/lens_size_id/sku)`, `categories(status/slug)`,
`brands(status)`, `user_addresses(...)`, `try_on_snapshots(...)`,
`stock_transactions(type, related_order_id)`, `stock_transaction_items(variant_id)`.

### ⚠️ Vẫn còn thiếu: các ràng buộc UNIQUE

Migration chỉ tạo **index thường**. Những cột sau cần **UNIQUE** để chặn dữ liệu sai ở tầng CSDL:

```sql
ALTER TABLE orders            ADD UNIQUE INDEX ux_orders_order_code (order_code);
ALTER TABLE payments          ADD UNIQUE INDEX ux_payments_code (payment_code);
ALTER TABLE products          ADD UNIQUE INDEX ux_products_slug (slug);
ALTER TABLE posts             ADD UNIQUE INDEX ux_posts_slug (slug);        -- xem M-24
ALTER TABLE inventories       ADD UNIQUE INDEX ux_inventories_wh_var (warehouse_id, variant_id);  -- xem L-08
ALTER TABLE product_reviews   ADD UNIQUE INDEX ux_reviews_order_item (order_item_id);             -- xem H-01
```

Đặc biệt `product_reviews (order_item_id)`: index thường đã có, nhưng **UNIQUE** mới là thứ
chặn được đánh giá trùng (H-01).

<details>
<summary>Danh sách khuyến nghị gốc (giữ lại để đối chiếu)</summary>

```sql
-- Tra cứu nóng
CREATE UNIQUE INDEX ux_products_slug        ON products (slug);
CREATE UNIQUE INDEX ux_posts_slug           ON posts (slug);          -- hiện KHÔNG unique, xem M-24
CREATE UNIQUE INDEX ux_orders_order_code    ON orders (order_code);   -- VNPay tra theo cột này
CREATE UNIQUE INDEX ux_payments_code        ON payments (payment_code);
CREATE UNIQUE INDEX ux_inventories_wh_var   ON inventories (warehouse_id, variant_id);  -- xem L-08

-- Báo cáo & danh sách (đang gây full scan)
CREATE INDEX ix_order_items_product   ON order_items (product_id);
CREATE INDEX ix_order_items_order     ON order_items (order_id);
CREATE INDEX ix_orders_status_created ON orders (status, created_at);
CREATE INDEX ix_orders_user           ON orders (user_id);
CREATE INDEX ix_inventories_variant   ON inventories (variant_id);
CREATE INDEX ix_variants_product      ON product_variants (product_id);
CREATE INDEX ix_products_status_cat   ON products (status, category_id);
CREATE INDEX ix_reviews_product       ON product_reviews (product_id, status);
CREATE INDEX ix_user_roles_user       ON user_roles (user_id);   -- kiểm tra mỗi request admin
CREATE INDEX ix_banners_pos_status    ON banners (position, status, start_at);
```

</details>

---

## 9.7. Việc cần làm đầu tiên

> **Vẫn đúng nguyên vẹn sau đợt cập nhật.** 9 migration mới đều là `ALTER`/index trên bảng có
> sẵn (trừ `try_on_snapshots`), nên vấn đề gốc chưa được chạm tới.

**Sinh migration từ CSDL hiện có.** Không có bước này thì dự án không thể:
- dựng lại môi trường từ đầu,
- chạy test tự động,
- deploy lên môi trường mới,
- hay đảm bảo CSDL dev khớp CSDL production.

Cách làm nhanh nhất:
```bash
mysqldump --no-data --skip-comments ten_db > schema.sql
```
rồi chuyển thành các file migration, **hoặc** tối thiểu commit `schema.sql` vào repo và ghi
rõ trong `README.md` rằng phải import file này trước khi chạy `php artisan migrate`.

Chi tiết ở [10 — Audit](10-ket-qua-audit.md) mục **C-01**.
