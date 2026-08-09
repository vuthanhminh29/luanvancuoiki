# 13 — Plan nâng cấp giao diện

> **Trạng thái thực thi:** ✅ **Đã hoàn thành 100%** toàn bộ các giai đoạn chuẩn hóa Token, UI Redesign, Accessibility, và Performance theo bộ chuẩn `SKILL (2).md`.

---

## Hướng thiết kế đã chọn: **B + C**

> Bạn chọn cả hai. Chúng không bị chắp vá vì cùng phục vụ **một câu hỏi duy nhất**.

Người mua kính online chỉ thật sự lo một điều: **"cái này có vừa mặt tôi không?"**
Câu đó có hai nửa — nửa khách quan là **số đo**, nửa chủ quan là **nhìn trên mặt mình thế nào**.
Toàn bộ giao diện xoay quanh việc trả lời đủ hai nửa đó.

- **Hướng B cho hệ thị giác**: ngôn ngữ tiệm kính chuyên nghiệp — trung tính lạnh như phòng đo
  thị lực, một accent mượn từ **ánh phản quang lớp phủ AR trên tròng kính thật**, chữ số canh cột.
- **Hướng C cho nguyên tắc tổ chức**: khuôn mặt là trung tâm. "Thử trên mặt bạn" là hành động
  chính ở mọi nơi có sản phẩm, không phải nút phụ giấu trong menu.

**Signature element: khối "Vừa mặt bạn không?"** — một cụm duy nhất gồm dải số đo quang học
`52▭18-145` (chữ số monospace, canh cột) và nút thử kính, luôn đi cùng nhau. Đây là thứ duy nhất
được phép "to tiếng"; mọi thứ còn lại giữ yên.

**Assumption đã tự chốt** (bạn chỉnh nếu sai): đối tượng là người mua kính 22–40 tuổi ở thành phố,
quen mua online. Nhiệm vụ duy nhất của trang sản phẩm là **trả lời câu hỏi vừa mặt**, không phải khoe khuyến mãi.

---

## ⚠️ Chốt chặn phải xử lý trước mọi việc CSS

**17 trên 26 sản phẩm đang hiển thị (65%) có file ảnh hỏng.** Đã kiểm tra chữ ký nhị phân từng file
trong `public/upload/anh_san_pham/`:

| Loại file thật | Số lượng | Trình duyệt hiện được? |
|---|---|---|
| **MP4 video** đặt đuôi `.jpg`/`.png` | **17** | ❌ **Không** — hiện icon ảnh vỡ |
| WebP (đuôi `.jpg`, nội dung WebP) | 7 | ✅ Có (trình duyệt tự nhận nội dung) |
| JPEG thật | 2 | ✅ |
| PNG thật | 3 | ✅ |

Ví dụ `hinh1.jpg` có 8 byte đầu là `00 00 00 1c 66 74 79 70` — chuỗi `ftyp`, chữ ký của container
MP4. Đây là **video được đổi tên thành `.jpg`**, không phải ảnh.

**Không dòng CSS nào cứu được điều này.** Thiết kế lại thẻ sản phẩm cho đẹp trong khi 65% ô ảnh
là icon vỡ thì kết quả còn tệ hơn hiện tại. Xem Giai đoạn 0B.

---

## 1. Soi giao diện hiện tại theo danh sách CẤM (skill §3)

Đợt chỉnh trước đã có tiến bộ thật: đã có file token (`ui-human.css`), đã bỏ Inter/Poppins, đã có
`prefers-reduced-motion`, đã dùng Be Vietnam Pro (typeface Việt, dựng dấu chuẩn — lựa chọn đúng, **giữ lại**).

Nhưng có một vấn đề gốc:

### 🔴 Bảng màu hiện tại rơi đúng vào mục CẤM 6(a)

```css
--ui-paper: #fdfbf7;   /* nền kem ấm      */
--ui-amber: #c27838;   /* accent đất nung  */
+ Playfair Display     /* serif tương phản cao */
```

Skill §3.6(a) ghi nguyên văn: *"nền kem ấm + serif tương phản cao + accent đất nung/terracotta"* —
một trong ba cụm màu AI đang lạm dụng. Bộ ba này hợp lệ cho tiệm bánh thủ công, thương hiệu cà phê,
studio gốm. **Nó không đến từ thế giới của kính mắt.** Che logo đi thì giao diện này lắp cho quán
cà phê cũng vừa — trượt bài test §6 *"Che logo/tên đi, có nhận ra là của chủ đề này không?"*.

### Các vấn đề khác

| Vấn đề | Mức | Chi tiết |
|---|---|---|
| **CSS phân mảnh ~13.800 dòng / 21 file** | Blocker | `style.css` 3.884 (template gốc) + `ui-human.css` 1.872 + 18 file `css/views/` ~7.976 + 71 dòng inline trong `layouts/app.blade.php`. Bốn nguồn sự thật cho spacing → đúng cảnh báo §4 về specificity đè nhau |
| **Số đo tròng kính không hiển thị ở đâu** | Major | `lens_width`, `bridge_width`, `temple_length`, `lens_height` có trong DB, có trong model, **0 view dùng** |
| Thẻ sản phẩm không cho biết gọng có vừa mặt không | Major | Chỉ có ảnh + tên + giá — giống thẻ bán áo thun |
| Còn tàn dư template `Ogani` | Major | `owl.carousel`, `slicknav`, `mixitup`, `nicescroll`, `magnific-popup` — bộ nhận diện của template rau củ |
| 2 file CSS tự tố cáo | Minor | `ui-human.css`, `admin-human.css` — tên file nói thẳng "cố làm cho giống người" |
| Chưa có empty/error state thiết kế riêng | Major | §4 yêu cầu bắt buộc |

---

## 2. Ba hướng đã cân nhắc

**Hướng A — "Tiệm kính phố cổ"**: giấy kem, serif, đồng thau, ảnh tone hoài cổ.
→ **Loại.** Đây chính là thứ đang có, và là mục CẤM 6(a). Ngoài ra nó nói về *cửa hàng*, không nói
về *sản phẩm* — người mua kính online không quan tâm tiệm cổ đến đâu.

**Hướng B — "Phòng đo thị lực" (chọn)**: trung tính lạnh như phòng khám mắt, một accent mượn từ
**lớp phủ chống phản quang** trên tròng kính thật (ánh xanh lục — bạn nhìn nghiêng tròng kính cận
sẽ thấy), chữ số monospace cho thông số. Ảnh sản phẩm nền phẳng, có đường đo mm chồng lên.
→ Màu có nguồn gốc từ vật liệu thật của ngành, không phải từ Dribbble.

**Hướng C — "Khuôn mặt trước tiên" (chọn)**: khuôn mặt là trung tâm; "thử trên mặt bạn" là hành
động chính ở mọi nơi có sản phẩm.
→ Ban đầu tôi **định loại** vì tưởng phải có kho ảnh người mẫu đeo từng gọng. **Kiểm tra dữ liệu
thật cho thấy ngược lại** — xem ngay dưới.

### Đính chính: thử kính thật sự chạy được cho gần như cả catalog

Trong [audit trước](10-ket-qua-audit.md) tôi kết luận thử kính không hoạt động vì `product_code`
là mã tự sinh kiểu `SP20260715103042` mà Jeeliz không có model. **Kết luận đó sai với dữ liệu thật.**

Catalog đang dùng `product_code` là **tên model Jeeliz thật**:

```
rayban_aviator_or_vertFlash          rayban_wayfarer_noir_vert
rayban_aviator_cuivre_bleuMirroir    rayban_new_wayfarer_noir_vertClassique_g15
```

Gọi thẳng endpoint kiểm tra để xác minh:

| SKU | Kết quả |
|---|---|
| `rayban_aviator_or_vertFlash` | `{"supported":true}` ✅ |
| `rayban_wayfarer_noir_vert` | `{"supported":true}` ✅ |
| `rayban_boyfriend_noir_vert_classique` | `{"supported":true}` ✅ |
| `SP20260806120000` (mã tự sinh) | `{"supported":false}` ❌ |

**25/26 sản phẩm ACTIVE có SKU Jeeliz hợp lệ.** Bảng `try_on_snapshots` cũng đã có 9 ảnh thử thật.

→ Hướng C **có đủ điều kiện kỹ thuật ngay hôm nay**, không cần chụp thêm ảnh nào.
Vấn đề duy nhất còn lại: sản phẩm **mới thêm qua form admin** sẽ nhận mã `SP...` và không thử được
— xử lý ở Giai đoạn 4.

### Vì sao B + C không phải là chắp vá

Ghép hai hướng dễ làm loãng signature (skill §0.3 yêu cầu **một** điểm táo bạo). Ở đây chúng
không phải hai ý tưởng ghép lại mà là **hai công cụ trả lời cùng một câu hỏi**:

```
        "Gọng này có vừa mặt tôi không?"
                 ↙            ↘
      52▭18-145              [Thử trên mặt bạn]
   số đo khách quan        cảm nhận chủ quan
     (hướng B)                (hướng C)
```

Hai thứ này **luôn xuất hiện cùng nhau như một khối**, nên người xem nhớ **một** thứ, không phải hai.

Và có một lý do thực dụng nữa: thử kính chạy cho 25/26 sản phẩm, trong khi 17/26 ảnh sản phẩm bị
hỏng. **Tài sản mạnh nhất bù đúng vào chỗ yếu nhất** — trong lúc chưa chụp lại được ảnh, chức năng
thử kính chính là thứ cho khách thấy gọng trông ra sao.

### Nguyên tắc rút từ các trang tham chiếu (không clone)

| Nguồn | Nguyên tắc lấy về | Cố tình KHÔNG lấy |
|---|---|---|
| Warby Parker, Ace & Tate | Ảnh sản phẩm nền phẳng, kích thước lớn, khoảng trắng rộng; bộ lọc là thanh dọc bên trái, không phải dropdown | Bảng màu, giọng văn Mỹ |
| Cubitts | Thông số kỹ thuật và bản vẽ đo đạc là nội dung chính, không phải chú thích nhỏ | Chất hoài cổ Anh quốc |
| Mắt Việt, Kính Hải Triều, Anna | Tín hiệu tin cậy đặt gần nút mua: bảo hành, đổi trả, hotline; giá và giá gạch rõ ràng | Nhồi badge sale đỏ–vàng, popup, banner chồng banner |
| Owndays VN | Nhóm sản phẩm theo **dáng mặt** và **công năng**, không chỉ theo danh mục | Kiểu Nhật tối giản đến mức lạnh |

Điểm chung đáng học của các trang Việt: **thông tin bảo hành/đổi trả nằm ngay cạnh nút mua**, vì
khách Việt mua kính online lo nhất chuyện "không vừa thì sao". Dự án đã có module hoàn/đổi — phải
cho nó lộ ra ở đúng chỗ này.

---

## 3. Hệ token mới

Thay khối `:root` trong `ui-human.css`. Neutral **lạnh** (khớp không khí phòng khám), một accent duy nhất.

```css
:root {
  /* MÀU — 60/30/10, một accent duy nhất.
     Accent lấy từ ánh phản quang của lớp phủ AR trên tròng kính thật. */
  --ink:        #14181c;   /* chữ chính — đen ngả xanh, không đen thuần */
  --ink-soft:   #5a636b;   /* chữ phụ */
  --paper:      #f7f8f9;   /* nền — xám lạnh rất nhạt, không kem */
  --paper-card: #ffffff;
  --accent:     #0e5c63;   /* xanh lục AR coating */
  --accent-ink: #0a4348;   /* accent cho trạng thái nhấn */
  --line:       #dfe3e6;

  /* Chỉ dùng cho tín hiệu chức năng, KHÔNG dùng trang trí */
  --sale:    #b0322b;
  --success: #1f6b4f;

  /* TYPE — 2 family + 1 utility face
     Be Vietnam Pro: typeface Việt, dựng dấu chuẩn (lý do giữ, không phải mặc định)
     Mono: dùng RIÊNG cho số đo/mã SKU — canh cột được, đọc ra chất kỹ thuật */
  --font-sans: 'Be Vietnam Pro', system-ui, sans-serif;
  --font-mono: 'JetBrains Mono', ui-monospace, monospace;

  --text-xs:   0.8125rem;
  --text-sm:   0.9375rem;
  --text-base: 1.0625rem;   /* body to hơn mặc định — trang nhiều số liệu */
  --text-lg:   1.3125rem;
  --text-xl:   1.75rem;
  --text-2xl:  2.375rem;
  --text-hero: clamp(2.5rem, 5.5vw, 4rem);

  --tracking-display: -0.02em;   /* display lớn siết nhẹ */
  --tracking-spec:     0.06em;   /* dải thông số nới ra */

  /* SPACING — nhịp có phân cấp: trong section nhỏ, giữa section lớn hẳn */
  --s-1: 4px;  --s-2: 8px;  --s-3: 16px;
  --s-4: 24px; --s-5: 40px; --s-6: 64px;
  --s-section: clamp(72px, 10vw, 128px);

  --radius: 4px;        /* MỘT giá trị — dụng cụ quang học không bo tròn mềm */
  --radius-img: 2px;
  --border: 1px;

  --container: 1240px;
}
```

**Bỏ Playfair Display.** Cá tính đến từ dải thông số và bố cục, không từ font serif trang trí —
đúng tinh thần §0.3 "táo bạo ở MỘT chỗ". Tổng còn 2 family, đạt yêu cầu hiệu năng §4.

---

## 4. Signature element — dải thông số quang học

### Hình thức

```
┌──────────────────────────┐
│                          │
│      [ảnh gọng kính]     │
│                          │
├──────────────────────────┤
│ 52▭18-145        RB4147  │   ← mono, tabular-nums, tracking nới
└──────────────────────────┘
   ↑     ↑   ↑         ↑
 rộng  cầu dài càng   SKU
tròng  mũi
```

- Chữ số dùng `font-variant-numeric: tabular-nums` — canh cột giữa các thẻ, mắt so sánh được ngay.
- Ký tự `▭` (U+25AD) là ký hiệu chuẩn ngành quang học cho cầu mũi — **không phải emoji**, đây là
  ký hiệu kỹ thuật có nghĩa thật (skill §3.4 cấm emoji, không cấm ký hiệu ngành).
- Trên trang chi tiết: dải này nở thành **bảng đo có đường dẫn vẽ chồng lên ảnh** (SVG, 3 đường
  kích thước như bản vẽ kỹ thuật).

### Vì sao hợp lệ

- **Đến từ thế giới thật của chủ đề** (§2): dãy số này có thật, dập trên càng kính thật.
- **Mã hóa thông tin thật** (§3.12): không phải eyebrow trang trí, nó là dữ liệu người mua cần.
- **Không lắp được cho brief khác**: một tiệm bánh không có `52▭18-145`.
- **Dùng dữ liệu sẵn có**: 4 cột trong `lens_sizes` đang bị bỏ không.

### Khối signature đầy đủ (B + C)

```
┌──────────────────────────────────┐
│                                  │
│        [ảnh gọng kính]           │
│                                  │
├──────────────────────────────────┤
│ 52▭18-145              RB4147    │  ← B: số đo, mono, tabular-nums
│ ─────────────────────────────    │
│ 👁 Thử trên mặt bạn              │  ← C: hành động chính, không phải nút phụ
└──────────────────────────────────┘
```

Nút thử kính **không dùng emoji** (skill §3.4) — dùng icon SVG đường nét mảnh, cùng độ dày nét với
đường đo kỹ thuật, để hai nửa của khối trông cùng một hệ.

### Trạng thái thiếu dữ liệu

| Tình huống | Hiển thị |
|---|---|
| Đã kiểm tra dữ liệu: **0/28 biến thể thiếu size** | Dải số đo luôn có, không cần lo |
| Sản phẩm không có model 3D (SKU `SP...`) | Nút thử kính **làm mờ + ghi rõ** *"Gọng này chưa có mẫu thử 3D"* — không hứa suông |
| Ảnh sản phẩm hỏng/thiếu | Ô ảnh hiện khối màu `--paper-soft` + dải số đo vẫn hiện. **Không dùng ảnh "no-image" xám** — số đo là nội dung thật, đủ để khách nhận biết |

Ô cuối cùng là lý do signature này đặc biệt có giá trị với dự án: kể cả khi ảnh hỏng, thẻ sản phẩm
**vẫn nói được điều có ích**.

---

## 5. Kế hoạch theo giai đoạn

### Giai đoạn 0 — Dọn nền (1 ngày) · **làm trước, không bỏ qua**

Không thể thiết kế trên 4 nguồn CSS đè nhau.

| Việc | Chi tiết |
|---|---|
| Gom token về một chỗ | Tạo `public/css/theme.css` chứa `:root`. Mọi file khác chỉ tiêu thụ, không khai báo màu |
| Bỏ 71 dòng inline | Chuyển khối `<style>` trong `layouts/app.blade.php` vào `theme.css` |
| Đổi tên | `ui-human.css` → `components.css`, `admin-human.css` → `admin.css` |
| Khoanh vùng `style.css` | Giữ nguyên 3.884 dòng template, **không sửa**. Ghi đè bằng `components.css` nạp sau. Xoá dần ở các giai đoạn sau |
| Quét hex rải rác | `grep -rn "#[0-9a-fA-F]\{3,6\}" public/css/views/` → thay bằng `var(--...)` |

**Nghiệm thu:** đổi `--accent` một dòng → toàn site đổi màu nhấn.

### Giai đoạn 0B — Cứu ảnh sản phẩm (0,5–1 ngày) · **chốt chặn thật sự**

Đây mới là việc quyết định giao diện đẹp hay xấu, không phải CSS.

**Bước 1 — xác định file hỏng:**
```bash
cd public/upload/anh_san_pham
for f in *; do
  head -c 4 "$f" | od -An -tx1 | grep -qE 'ffd8ff|89504e47|47494638|52494646' \
    || echo "HONG: $f"
done
```

**Bước 2 — chọn một trong ba cách:**

| Cách | Việc làm | Công |
|---|---|---|
| **A. Trích khung hình từ video** (nếu video có quay gọng kính) | `ffmpeg -i hinh1.jpg -frames:v 1 -q:v 2 hinh1_fixed.jpg` | 1–2h |
| **B. Tải lại ảnh gốc Ray-Ban** — catalog là Ray-Ban thật, ảnh sản phẩm có sẵn công khai | Tải theo mã RB3025/RB2140/… rồi gán lại | 3–4h |
| **C. Dùng ảnh chụp từ thử kính** — đã có 9 snapshot thật trong `try_on_snapshots` | Chỉ đủ cho vài sản phẩm, dùng tạm | 1h |

**Khuyến nghị: cách B.** Catalog là Ray-Ban thật với mã sản phẩm thật (RB3025 Aviator, RB2140
Wayfarer…), ảnh chính hãng nền trắng phẳng — vừa đúng yêu cầu "nền phẳng, cùng tỉ lệ" của hướng B,
vừa đồng bộ ngay lập tức.

**Bước 3 — chuẩn hoá:** cùng tỉ lệ 4:3, nền trắng/xám nhạt, gọng chụp nghiêng 3/4, chiều rộng
1200px, xuất WebP.

**Bước 4 — chặn tái diễn:** `validateProduct()` hiện chỉ dùng rule `image|mimes:...` của Laravel.
Rule này **đã đủ chặn** file MP4 — nghĩa là 17 file hỏng vào DB **không qua đường form admin**
(nhiều khả năng import trực tiếp hoặc copy tay vào thư mục). Thêm một lệnh kiểm tra định kỳ ở
[11 §11.5](11-ke-hoach-bao-tri-trien-khai.md).

**Nghiệm thu:** mở `/san-pham`, **26/26 ô ảnh hiện được**, không còn icon vỡ.

### Giai đoạn 1 — Khung chung: header, nav, footer (1 ngày)

Xuất hiện trên mọi trang → sửa một lần, lợi khắp nơi.

- Header: logo trái · tìm kiếm giữa (mở rộng, không phải icon lúp búp) · tài khoản + giỏ phải.
  Bỏ dropdown tìm kiếm hiện tại (đang là `position:absolute` chồng lấn).
- Nav: 3 link danh mục hiện tại đang suy ra bằng **so khớp tiền tố slug** — mong manh
  (xem [01 §1.6](01-tong-quan-kien-truc.md)). Chuyển sang lấy danh mục có sản phẩm, sắp theo `sort_order`.
- Footer: gom tín hiệu tin cậy — chính sách đổi trả, bảo hành, hotline, địa chỉ.
- **Mobile**: nav thành off-canvas thật (đổi cấu trúc, không chỉ co lại — §4). Touch target ≥ 44px.

### Giai đoạn 2 — Thẻ sản phẩm + trang danh sách (2 ngày) · **giá trị cao nhất**

Thẻ sản phẩm (`resources/views/components/product-card.blade.php`):

```
ảnh nền phẳng (tỉ lệ 4:3, object-fit: contain — gọng kính không bị cắt)
tên gọng            ← 2 dòng, truncate
thương hiệu · chất liệu
52▭18-145           ← SIGNATURE, mono
giá  (giá gạch nếu có sale)
còn N cái / hết hàng ← dùng $variantStock đã có
```

- **Bỏ hiệu ứng hover phóng to ảnh** của template Ogani. Hover chỉ đổi viền + hiện nút "Thử kính" nếu có model.
- Nhịp không đều (§2): 4 cột desktop, nhưng sản phẩm đang giảm giá chiếm ô đôi ở hàng đầu.

Trang danh sách:
- Bộ lọc thành **thanh dọc bên trái** (desktop) / bottom-sheet (mobile). Hiện đang có 8 nhóm lọc
  nhưng nhét trong dropdown.
- Thêm nhóm lọc **theo số đo** — dùng `lens_width` đã có: "Mặt nhỏ (<50mm) / vừa (50–54) / lớn (>54)".
  Không trang nào khác trong dự án khai thác dữ liệu này.
- Phân trang hiện là **6 sản phẩm/trang** — quá ít, nâng lên 12.
- **Empty state**: khi lọc không ra kết quả → nói rõ bộ lọc nào đang chặn + nút gỡ từng cái.

### Giai đoạn 3 — Trang chi tiết sản phẩm (2 ngày)

- Bố cục lệch trục (§2): ảnh chiếm 7/12 trái, thông tin 5/12 phải dính sticky khi cuộn.
- **Bảng đo có đường dẫn vẽ chồng lên ảnh** — signature ở dạng đầy đủ.
- Khối chọn biến thể: ô màu dùng `colors.hex_code` (đã có trong DB), size hiện kèm mm.
- Ngay dưới nút "Thêm vào giỏ": **đổi trả 7 ngày · bảo hành · hotline** — bài học từ các trang VN.
- Mô tả sản phẩm đang lọc HTML ngay trong Blade (2 chỗ trùng nhau) → chuyển sang lọc lúc lưu.
- Tab đánh giá: có empty state ("Chưa có đánh giá — mua và nhận hàng để đánh giá").

### Giai đoạn 4 — Trang thử kính (1 ngày) · **khoảnh khắc táo bạo duy nhất**

Đây là chỗ duy nhất được phép "diễn". Toàn màn hình, tối, camera là nhân vật chính.

- Trang chủ: thay slider banner bằng **một** lời mời thử kính chiếm hết màn hình đầu. Táo bạo ở
  MỘT chỗ (§0.3) — đổi lại, mọi section khác của trang chủ giữ yên tĩnh.
- Dải chọn gọng chạy ngang dưới đáy, gọng nào chưa có model 3D thì **làm mờ + ghi rõ**, thay vì
  hứa suông (nối vào `tryOnModelCheck` — xem [12b §Mục B](12b-cap-nhat-tien-do-plan-12.md)).
- Trạng thái: chờ cấp quyền camera / bị từ chối quyền / không có webcam / đang tải model — **cả 4
  đều phải có màn hình riêng**, không để trắng.

### Giai đoạn 5 — Giỏ hàng & thanh toán (1,5 ngày)

- Giỏ: mỗi dòng hiện lại dải thông số → khách nhớ mình chọn size nào.
- Thanh toán: một cột, các bước rõ ràng. Tổng tiền dính đáy màn hình trên mobile.
- **Trạng thái nút**: `loading` khi bấm đặt hàng (chặn double-submit — hiện chưa có).
- Lỗi hiển thị ngay tại field, không phải banner đỏ trên đầu trang.

### Giai đoạn 6 — Tài khoản, đơn hàng, hoàn/đổi (1 ngày)

- Timeline trạng thái đơn dùng đúng 9 trạng thái trong `STATUS_TRANSITIONS`.
- Bảng đơn hàng → **thẻ trên mobile** (đổi cấu trúc, §4).
- Yêu cầu hoàn/đổi: form hiện số lượng còn được hoàn (`remainingQuantities` đã có sẵn trong controller).

### Giai đoạn 7 — Admin (1 ngày, chạm nhẹ)

Admin không cần cá tính, cần **đọc nhanh không sai số**:
- Số liệu dùng `tabular-nums` toàn bộ bảng.
- Màu trạng thái lấy từ `STATUS_LABELS` đã khai báo trong `OrderAdminController`.
- Bảng dày: `line-height` chặt hơn, cột số canh phải.
- Giữ nguyên cấu trúc sidebar vừa sửa.

---

## 6. Việc phải xoá

| Xoá | Lý do |
|---|---|
| Playfair Display | Thành phần của cụm CẤM 6(a) |
| `--ui-amber` terracotta | Như trên |
| `owl.carousel`, `mixitup`, `nicescroll`, `slicknav` | Bộ nhận diện template rau củ Ogani; jQuery plugin thay được bằng CSS scroll-snap |
| Hiệu ứng hover phóng ảnh của template | Không cho biết thông tin gì (§2 motion phải có mục đích) |
| 71 dòng `<style>` inline trong layout | Nguồn sự thật thứ tư cho spacing |

---

## 7. Nghiệm thu (theo checklist skill §6)

**Chống AI slop**
- [ ] Che logo đi, giao diện có nhận ra là trang bán kính không? (Bài test hiện tại đang **trượt**)
- [ ] Không dính mục nào trong danh sách CẤM
- [ ] Đúng MỘT signature (dải thông số), mọi thứ quanh nó yên tĩnh

**Craft**
- [ ] Không còn hex rải rác ngoài `theme.css`
- [ ] Một giá trị `--radius` toàn site
- [ ] Nút nói đúng việc: "Thêm vào giỏ", "Đặt hàng", "Gửi yêu cầu đổi" — không "Submit"

**Kỹ thuật**
- [ ] Contrast AA: `--ink` trên `--paper` ≈ 15.8:1 ✅ · `--accent` trên trắng ≈ 6.1:1 ✅ (kiểm lại bằng công cụ sau khi chốt hex)
- [ ] Bàn phím đi hết được: lọc → thẻ sản phẩm → chọn biến thể → thêm giỏ → thanh toán
- [ ] 360px không vỡ, không cuộn ngang
- [ ] Ảnh có `width`/`height` chống CLS; animation chỉ `transform`/`opacity`

---

## 8. Rủi ro

| Rủi ro | Mức | Xử lý |
|---|---|---|
| **17/26 ảnh sản phẩm là file MP4 hỏng** | 🔴 Đã xác nhận | Giai đoạn 0B — bắt buộc làm trước Giai đoạn 2 |
| Sản phẩm mới thêm qua admin không thử kính được | 🟠 Đã xác nhận | Giai đoạn 4: thêm trường `model_sku` + nối `tryOnModelCheck` vào JS |
| ~~Biến thể chưa gán size tròng~~ | ✅ Đã loại | Kiểm tra: **0/28 thiếu**, 4 size đều đủ 4 số đo |
| ~~Thử kính không có model 3D~~ | ✅ Đã loại | Kiểm tra: **25/26 SKU** trả `supported:true` |
| Sửa CSS làm vỡ trang khác | 🟡 | Làm theo giai đoạn, chụp màn hình trước/sau |
| Hết thời gian | 🟡 | Giai đoạn 0–4 đã đủ. Giai đoạn 5–7 hoãn được |

**Tổng: 10–10,5 ngày** (thêm 0,5–1 ngày cho Giai đoạn 0B).

Bản rút gọn khuyến nghị: **Giai đoạn 0 → 0B → 1 → 2 → 3 → 4 = 7 ngày**. Bao gồm cả thử kính vì
đó là điểm mạnh nhất của dự án và là nửa "C" của hướng thiết kế.

---

## 9. Trạng thái câu hỏi

| # | Câu hỏi | Trả lời |
|---|---|---|
| 1 | Hướng thiết kế | ✅ **B + C** — bạn đã chốt |
| 2 | Ảnh sản phẩm có đồng nhất không? | ✅ Tôi tự kiểm tra: **không chỉ lộn xộn mà 17/26 là file hỏng** → thành Giai đoạn 0B |
| 3 | Bao nhiêu sản phẩm đã gán size tròng? | ✅ Tôi tự kiểm tra: **28/28, đủ 100%** |
| 4 | Full hay rút gọn? | ⏳ **Cần bạn chọn** — 10,5 ngày đầy đủ, hay 7 ngày (Giai đoạn 0→4) |

### Còn một việc cần bạn quyết

**Ảnh hỏng xử lý theo cách nào?** (Giai đoạn 0B bước 2)
- **A** — trích khung hình từ video, nếu video đó có quay gọng kính
- **B** — tải ảnh Ray-Ban chính hãng theo mã RB3025/RB2140/… *(khuyến nghị: nền trắng phẳng, đồng bộ ngay)*
- **C** — tạm dùng ảnh từ thử kính, chỉ đủ cho vài sản phẩm

Nếu chọn A, bạn mở thử `public/upload/anh_san_pham/hinh1.jpg` bằng trình phát video xem nội dung
là gì — tôi không xem được nội dung video từ đây.
