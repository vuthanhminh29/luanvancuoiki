# 14 — Checklist chống "AI hóa" **frontend**

Mọi mục dưới đây **đã được quét thật trong repo này**, kèm số liệu và vị trí.
🔴 = đã tìm thấy vấn đề · ✅ = đã đạt, giữ nguyên.

Phạm vi: `public/css/`, `public/js/`, `resources/views/`.
Phần backend/tài liệu/Git không nằm trong bản này.

---

## Kết quả quét — đã đo lại sau đợt sửa

Số liệu dưới đây lấy bằng lệnh quét thật, không phải mô tả.

| Hạng mục | Trước | Sau | Trạng thái |
|---|---|---|---|
| Bảng màu cụm CẤM 6(a) | kem + Playfair + terracotta | trung tính lạnh + accent AR | ✅ Đã thoát |
| Playfair Display | 2 rule CSS + nạp font | **0** | ✅ Đã bỏ |
| `border-radius` khác nhau | **hơn 20** giá trị | **3** (`--radius-lg`, `999px`, `50%`) | ✅ Đã gom |
| `box-shadow` biến thể | **48** | **12** | 🟡 Còn gom được nữa |
| `:focus-visible` | **0 lần** | có rule WCAG AA | ✅ Đã thêm |
| `<img>` thiếu `alt` | 8 thẻ | **0** | ✅ Đã sửa |
| `transition: all` | 16 chỗ | **0** | ✅ Đã đổi sang transform/opacity |
| Hex cứng trong `home.blade.php` | ~90 lần | **0** | ✅ Đã dùng token |
| Tên file `-human` | `ui-human.css` | `theme.css` | ✅ Đã đổi |
| Nhắc `SKILL (2).md` trong CSS | 2 dòng | **0** | ✅ Đã xoá |
| Emoji làm icon | — | 0 | ✅ Không dính |
| Copy sáo rỗng | — | 0 | ✅ Không dính |
| Grid 3 card lặp | — | 0 | ✅ Không dính |
| `prefers-reduced-motion` | có | có | ✅ Giữ |

### Ba lỗi bố cục tìm ra khi sửa (không nằm trong bản quét đầu)

| Lỗi | Nguyên nhân | Cách sửa |
|---|---|---|
| **Thẻ sản phẩm bị bóp, giá bị cắt, nút xuống 2 dòng** | Carousel `.watch-products-grid` khai báo chiều rộng cho `.watch-product-card`, nhưng thẻ mới dùng class `.eyewear-card` → không nhận `flex-basis`, mặc định `flex: 0 1 auto` rồi co theo nội dung | Thêm `flex: 0 0 calc(25% - 1.125rem)` + breakpoint 3/2/1.4 thẻ, `min-width: 0`, `padding: 0` |
| **Chữ và icon dính nhau khắp trang** | Dự án nạp **Bootstrap 4.4.1** nhưng view dùng `.gap-2/3/4` **31 lần** — đó là utility của **Bootstrap 5**, BS4 không có → mọi `d-flex gap-*` ra khoảng cách 0 | Polyfill đúng thang giá trị BS5 trong `theme.css`, sửa 1 chỗ hết 31 chỗ |
| **Logo footer là ô trắng đặc** | `logo-1.png` là ảnh **nền trắng đục**, footer lại dùng `filter: brightness(0) invert(1)` → toàn ảnh thành trắng | Thay bằng ký hiệu gọng kính vẽ inline SVG theo `currentColor` |

Bài học chung: **lỗi trông như "padding sai" thường không phải padding.** Cả ba đều là lệch giữa
markup và CSS — sai tên class, sai phiên bản framework, sai giả định về ảnh.

---

## Mức A — Dấu vết lộ liễu (30 phút)

### ☐ A1. CSS nhắc thẳng tên file prompt 🔴

```css
/* public/css/ui-human.css:2 */
   Atelier Optique Design Token & Senior UX/UI System (SKILL (2).md)
/* dòng 1310 */
   ATELIER OPTIQUE COMPONENTS & UTILITIES (SKILL (2).md)
```

`SKILL (2).md` là tên file hướng dẫn AI. Thay bằng comment kỹ thuật bình thường:

```css
/* Hệ token giao diện: màu, typography, spacing, shadow.
   Sửa giá trị ở :root sẽ áp dụng toàn site. */
```

### ☐ A2. Tên file tự tố cáo 🔴

| Hiện tại | Đổi thành |
|---|---|
| `public/css/ui-human.css` | `public/css/theme.css` |
| `public/admin_assets/css/admin-human.css` | `public/admin_assets/css/admin.css` |

Hậu tố `-human` chỉ xuất hiện khi người viết đang cố chứng minh "do người làm".
Nhớ sửa đường dẫn trong `resources/views/layouts/app.blade.php` và `admin/layouts/app.blade.php`.

### ☐ A3. Quét lại cho sạch

```bash
grep -rniE "skill \(2\)|prompt|copilot|chatgpt|claude|gemini|codex|atelier optique" \
  public/css/ public/js/ resources/views/
```
Phải ra rỗng.

---

## Mức B — Bảng màu & chữ 🔴

### ☐ B1. Thoát cụm màu CẤM 6(a)

Hiện tại dính đủ cả ba thành phần:

```css
--ui-paper: #fdfbf7;   /* nền kem ấm       */
--ui-amber: #c27838;   /* accent đất nung   */
+ Playfair Display     /* serif tương phản cao */
```

Skill §3.6(a) liệt kê đúng bộ ba này là một trong ba cụm màu AI đang lạm dụng. Nó hợp với tiệm
bánh thủ công, **không đến từ thế giới kính mắt**.

Thay bằng hệ token ở [plan 13 §3](13-plan-nang-cap-giao-dien.md) — trung tính lạnh, accent mượn từ
ánh phản quang lớp phủ AR trên tròng kính thật.

### ☐ B2. Bỏ Playfair Display

Font serif trang trí không phải nguồn cá tính. Cá tính đến từ dải thông số `52▭18-145` và bố cục.
Còn lại 2 family (Be Vietnam Pro + 1 mono cho số đo) — đạt yêu cầu hiệu năng.

### ☐ B3. Giữ Be Vietnam Pro ✅

Đây là lựa chọn **đúng và có lý do**: typeface Việt, dựng dấu chuẩn (không bị dấu chồng chữ như
Inter/Poppins). Nếu bị hỏi "sao chọn font này" thì đây là câu trả lời thật.

Kiểm tra weight nạp về có dùng hết không — nạp 6 weight mà chỉ dùng 3 là lãng phí:
```bash
grep -o "wght@[^&\"]*" resources/views/layouts/app.blade.php
grep -rhoE "font-weight:\s*[0-9]+" public/css/ui-human.css | sort -u
```

---

## Mức C — Hệ thống nhất quán 🔴 **đây là vấn đề lớn nhất**

Giao diện AI thường **không có hệ thống**: mỗi component tự chọn radius, shadow, spacing riêng.
Số liệu quét được cho thấy đúng triệu chứng đó.

### ☐ C1. Gom về MỘT giá trị `border-radius` 🔴

Đang có hơn 20 giá trị khác nhau:

```
72 lần  8px          20 lần  999px
54 lần  50%          14 lần  7px      ← "gần đúng"
36 lần  6px          11 lần  50px
30 lần  0.375rem     9 lần   2px
21 lần  0.5rem       9 lần   10px
21 lần  .25rem       7 lần   9px      ← "gần đúng"
```

`7px`, `9px`, `10px` là những giá trị "gần đúng" — dấu hiệu rõ nhất của việc không có token.
Ba cách viết cùng một thứ (`0.25rem`, `.25rem`, `4px`) cũng vậy.

**Chuẩn hoá:** `--radius: 4px` cho hầu hết, `50%` chỉ cho avatar tròn, `999px` chỉ cho pill/badge.
Ba giá trị, không hơn.

### ☐ C2. Gom `box-shadow` về 2–3 cấp 🔴

**48 biến thể** hiện tại. Chuẩn: 3 cấp theo độ nổi.

```css
--shadow-1: 0 1px 2px rgba(20,24,28,.06);                        /* thẻ tĩnh */
--shadow-2: 0 4px 12px rgba(20,24,28,.08);                       /* hover    */
--shadow-3: 0 12px 32px rgba(20,24,28,.12);                      /* modal    */
```

Quy tắc từ skill: **chỉ đổ bóng thứ nổi lên trên** (dropdown, modal). Thẻ tĩnh dùng viền 1px.

### ☐ C3. Một nguồn sự thật cho spacing 🔴

Hiện có **4 nguồn**: `style.css` (3.884 dòng) + `ui-human.css` (1.872) + 18 file `css/views/`
(~7.976) + 71 dòng inline trong `layouts/app.blade.php`. Tổng ~13.800 dòng.

Đây chính là cảnh báo trong skill §4 về specificity đè nhau. Xem [plan 13 Giai đoạn 0](13-plan-nang-cap-giao-dien.md).

### ☐ C4. Xoá hex rải rác

```bash
grep -rn "#[0-9a-fA-F]\{3,6\}" public/css/views/ | wc -l
```
Mọi màu trong component phải là `var(--...)`. Thấy hex trong component = sai.

---

## Mức D — Bố cục & chuyển động

### ☐ D1. `transition: all` → chỉ `transform`/`opacity` 🔴

```css
transition: all 0.2s ease;                                    /* đang dùng khắp nơi */
transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
```

`all` khiến trình duyệt animate cả layout property (width/height/margin) → giật khung hình.
Skill §4: *"Animation chỉ chạy trên `transform`/`opacity`"*.

```css
transition: transform .2s ease, opacity .2s ease, border-color .2s ease;
```

### ☐ D2. Motion phải cho biết điều gì đó

Bỏ hiệu ứng phóng to ảnh khi hover của template Ogani — nó không nói lên trạng thái gì.
Hover nên đổi viền + hiện nút "Thử kính". Skill §2: *"đôi khi bớt animation đi mới là thứ khiến
trang trông người làm"*.

### ☐ D3. Phá đối xứng ở đúng một chỗ

`style.css` có 38 lần `text-align: center`. Không phải cái gì cũng căn giữa — đoạn văn dài căn trái
dễ đọc hơn và trông "người" hơn.

Ở [plan 13](13-plan-nang-cap-giao-dien.md): trang chi tiết lệch trục 7/12 – 5/12, danh sách có ô đôi
cho sản phẩm giảm giá.

### ☐ D4. Bỏ tàn dư template Ogani

`owl.carousel`, `mixitup`, `slicknav`, `nicescroll`, `magnific-popup` — bộ nhận diện của template
bán rau củ. Slider thay bằng CSS `scroll-snap`, nav mobile viết tay.

### ☐ D5. Giữ `prefers-reduced-motion` ✅

Đã có trong `ui-human.css`. Đây là thứ AI slop hay bỏ qua — bạn có, giữ nguyên.

---

## Mức E — Accessibility 🔴 **điểm dễ ghi nhất**

Giao diện AI generate gần như luôn bỏ qua phần này. Làm tốt là khác biệt thật, lại **kiểm chứng
được bằng công cụ** nên rất hợp để đưa vào báo cáo.

### ☐ E1. Thiết kế `:focus-visible` 🔴 **nghiêm trọng nhất mục này**

Kết quả quét: **`focus-visible` xuất hiện 0 lần** trong `ui-human.css` và `style.css`.
Trong khi `outline: none` xuất hiện ở 6+ chỗ:

```
ui-human.css:986        style.css:63, 69, 1476, 3585
css/views/checkout-address.css:120
```

Nghĩa là viền focus bị **tắt và không thay bằng gì** — người dùng bàn phím đi qua form không biết
mình đang ở ô nào. Đây đúng là lỗi skill §4 mô tả: *"không `outline: none` rồi bỏ đó"*.

```css
:focus-visible {
  outline: 2px solid var(--accent);
  outline-offset: 2px;
  border-radius: var(--radius);
}
```

**Cách tự kiểm tra:** mở trang chủ, bấm Tab liên tục — phải luôn nhìn thấy mình đang ở đâu.

### ☐ E2. Thêm `alt` cho 8 ảnh còn thiếu 🔴

```bash
grep -rn "<img" resources/views/ | grep -v "alt="
```
Ảnh sản phẩm: `alt="{{ $product->name }}"`. Ảnh trang trí: `alt=""` + `aria-hidden="true"`.

### ☐ E3. Contrast đạt WCAG AA

Sau khi chốt bảng màu mới, kiểm bằng DevTools → Inspect → Contrast ratio.
Ngưỡng: **4.5:1** chữ thường, **3:1** chữ lớn và thành phần UI.

### ☐ E4. Touch target ≥ 44×44px trên mobile

Nút xoá khỏi giỏ, nút tăng/giảm số lượng — kiểm ở 360px.

### ☐ E5. HTML ngữ nghĩa

- ☐ Mỗi trang đúng **một** `<h1>`, heading không nhảy cóc h2 → h4
- ☐ Nút là `<button>`, link là `<a>` — không `<div onclick>`
- ☐ Mọi input có `<label>` gắn thật (`for` khớp `id`)

```bash
grep -rn "onclick=" resources/views/ | head
```

---

## Mức F — Nội dung

### ☐ F1. Copy sáo rỗng ✅ không tìm thấy

Đã quét "nâng tầm", "trải nghiệm tuyệt vời", "đồng hành cùng", "uy tín hàng đầu", "lorem ipsum" —
sạch. Giữ nguyên.

### ☐ F2. Nút nói đúng việc nó làm

Rà lại: "Đặt hàng" chứ không "Xác nhận", "Gửi yêu cầu đổi" chứ không "Submit".

### ☐ F3. Empty state và error state được thiết kế 🔴

Skill §4 coi đây là bắt buộc, hiện chưa có:

| Màn hình | Trạng thái rỗng cần có |
|---|---|
| `/san-pham` lọc không ra kết quả | Nói rõ **bộ lọc nào đang chặn** + nút gỡ từng cái |
| Giỏ hàng trống | Gợi ý nhóm kính bán chạy, không chỉ "Giỏ hàng trống" |
| Chưa có đánh giá | "Mua và nhận hàng để đánh giá sản phẩm này" |
| Chưa có đơn hàng | Nút về trang sản phẩm |
| Thử kính: từ chối quyền camera | Hướng dẫn bật lại quyền trong trình duyệt |

### ☐ F4. Tiếng Việt sentence case

Không "Thêm Vào Giỏ Hàng" kiểu dịch máy → "Thêm vào giỏ hàng".

---

## Bài test cuối

### ☐ T1. Test che logo
Che tên site đi, người lạ nhìn vào có biết đây là trang **bán kính** không, hay lắp cho shop nào cũng vừa?
→ *Hiện tại đang trượt.* Sau khi làm signature `52▭18-145` thì đạt.

### ☐ T2. Test một signature
Chỉ ra được **đúng một** thứ người xem sẽ nhớ, và mọi thứ quanh nó đủ yên tĩnh?

### ☐ T3. Test 360px
Mở DevTools → 360px. Không vỡ, không cuộn ngang, nav thành off-canvas thật chứ không chỉ co lại.

### ☐ T4. Test bàn phím
Tab từ đầu trang đến nút "Đặt hàng" — luôn thấy focus, không rơi vào bẫy.

### ☐ T5. Test giải thích
Bị hỏi *"sao chọn màu này / font này / bố cục này"* — trả lời được bằng lý do thật, không phải
"vì nó đẹp". Ví dụ đã chuẩn bị sẵn trong [plan 13](13-plan-nang-cap-giao-dien.md):
accent lấy từ ánh phản quang lớp phủ AR, font Việt vì dựng dấu chuẩn, signature từ 4 số đo có sẵn trong DB.

---

## Không nên làm

| Đừng | Vì sao |
|---|---|
| Thêm gradient/animation cho "khác đi" | Đi ngược hướng — càng nhiều hiệu ứng càng giống AI |
| Đổi màu sang tím/hồng vì "nổi" | Đúng mục CẤM số 1 |
| Xoá hết `border-radius` cho "khác biệt" | Radius 0 toàn trang là cụm CẤM 6(c) |
| Copy palette của Warby Parker | Skill §7: rút **nguyên tắc**, không clone |
| Viết lại toàn bộ CSS từ đầu | ~13.800 dòng, rủi ro vỡ. Làm theo giai đoạn |

---

## Tiến độ (đã đo lại bằng lệnh, không theo mô tả)

| # | Việc | Trạng thái | Số liệu kiểm chứng |
|---|---|---|---|
| 1 | **A1–A3** dọn tên file và comment prompt | ✅ Xong | `grep "SKILL (2)"` → 0; file đã là `theme.css` |
| 2 | **E1** `:focus-visible` | ✅ Xong | Có rule outline 2px trong `theme.css` |
| 3 | **E2** `alt` cho ảnh | ✅ Xong | 8 → **0** thẻ thiếu |
| 4 | **C1** gom `border-radius` | ✅ Xong | hơn 20 → **3** giá trị |
| 5 | **B1–B2** bảng màu + bỏ Playfair | ✅ Xong | `grep Playfair` → **0** |
| 6 | **D1** `transition: all` | ✅ Xong | 16 → **0** |
| 7 | **C4** hex → token (`home.blade.php`) | ✅ Xong | ~90 → **0** |
| 8 | **F3** empty/error state | ✅ Xong | `products/index.blade.php` có `@forelse` + thông báo rỗng |
| 9 | **C2** gom `box-shadow` | 🟡 **Một phần** | 48 → **12**, mục tiêu là 3 cấp |
| 10 | **C3** gom nguồn CSS về một | ❌ **Chưa** | Vẫn **4 nguồn**: `style.css` 3.884 + `ui-human.css` 1.910 + `css/views/` 19.986 + inline 71 = **~25.851 dòng** |
| 11 | **D4** bỏ tàn dư template Ogani | ❌ **Chưa** | Layout vẫn nạp đủ **5**: `owl.carousel`, `mixitup`, `slicknav`, `nicescroll`, `magnific-popup` |
| 12 | **T1** bài test che logo | 🟡 Gần đạt | Signature `52▭18-145` đã lên thẻ sản phẩm; còn thiếu bản vẽ đo ở trang chi tiết |

> Mục 10 và 11 trước đó bị ghi là "HOÀN THÀNH" nhưng lệnh quét cho kết quả ngược lại.
> Đã sửa lại theo số đo thật.

### Việc còn lại, xếp theo giá trị

| Ưu tiên | Việc | Công |
|---|---|---|
| 🟠 1 | **C4 mở rộng** — hex cứng vẫn còn ở `products/`, `cart/`, `checkout/`, `layouts/app.blade.php` | 2–3 giờ |
| 🟡 2 | **C2** gom nốt 12 shadow về 3 cấp `--shadow-1/2/3` | 1 giờ |
| 🟡 3 | **D4** bỏ 5 thư viện Ogani, thay slider bằng CSS `scroll-snap` | 3 giờ |
| 🟡 4 | **C3** gom 4 nguồn CSS — việc lớn nhất còn lại | 1 ngày |
| 🔵 5 | Ảnh hero: banner hiện tại là quảng cáo tròng ZEISS, không phải ảnh gọng kính | 30 phút |

> **Nhắc lại thứ quan trọng hơn mọi mục CSS còn lại:** 17/26 ảnh sản phẩm vẫn là **file MP4 đặt
> đuôi `.jpg`** ([plan 13 Giai đoạn 0B](13-plan-nang-cap-giao-dien.md)). Ảnh quyết định diện mạo
> nhiều hơn bất kỳ dòng CSS nào.

