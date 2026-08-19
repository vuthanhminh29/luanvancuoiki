# Tài liệu hệ thống — Website bán kính mắt (Laravel)

Bộ tài liệu này mô tả **toàn bộ mã nguồn** của dự án: kiến trúc, từng module nghiệp vụ, từng file source trong `app/`, mô hình dữ liệu, kết quả audit, **giáo trình giảng dạy 7 buổi**, **bộ 100 câu hỏi phản biện chuyên sâu** và **sơ đồ quy trình xử lý dữ liệu (Process Flowcharts)**.

---

## 🚀 TÀI LIỆU ĐÀO TẠO & PHẢN BIỆN CẤP TỐC (MỚI BỔ SUNG)

| STT | Tài liệu | Nội dung chính |
|---|---|---|
| 🎓 | [Giáo trình Giảng dạy 7 Buổi](00-GIAO-TRINH-GIANG-DAY-7-BUOI.md) | **Lộ trình giảng dạy đứng lớp 7 buổi** (Mục tiêu, Lý thuyết, Dẫn chứng code, Bài tập thực hành). |
| ❓ | [Cẩm nang 100 Câu hỏi Phản biện](00-100-CAU-HOI-PHAN-BIEN-CHUYEN-SAU.md) | **100 câu hỏi nảy lửa hội đồng hay hỏi** + Đáp án chuẩn IT + Đường dẫn mở dòng code trong 5s. |
| 🔄 | [Sơ đồ Quy trình & Luồng xử lý](00-QUY-TRINH-LUONG-XU-LY-PROCESS.md) | **Sơ đồ Mermaid quy trình:** Thử kính AI, Thanh toán VNPay SHA512, Biến động kho & Đổi trả (Chèn Slide). |
| 🗺️ | [Bản đồ Tra cứu Code 5s](00-BANG-TRA-CUU-CODE-NHANH.md) | **Bảng tra cứu nhanh:** Tính năng -> Route -> Controller / Dòng code -> Model -> View. |
| 🎭 | [Bí kíp Phản biện cho AI Users](00-KIPHAP-PHAN-BIEN-FOR-AI-USERS.md) | **Tư duy đọc code 4 bước**, giải mã thuật ngữ AI code, Kịch bản Demo 10p & Chiến thuật trả lời lỗi AI. |

---

## 📚 TÀI LIỆU PHÂN TÍCH HỆ THỐNG CHI TIẾT

| # | Tài liệu | Nội dung |
|---|----------|----------|
| 01 | [Tổng quan & Kiến trúc](01-tong-quan-kien-truc.md) | Stack, luồng request, bootstrap, middleware, cấu hình, deploy |
| 02 | [Module Xác thực & Phân quyền](02-module-xac-thuc-phan-quyen.md) | `AuthController`, `AdminAuthController`, `EnsureAdmin`, Fortify/Jetstream |
| 03 | [Module Sản phẩm & Danh mục](03-module-san-pham.md) | `ProductController`, `ProductAdminController`, `CategoryAdminController`, models |
| 04 | [Module Giỏ hàng, Thanh toán & VNPay](04-module-gio-hang-thanh-toan.md) | `CartController`, `CheckoutController`, `VnPayController`, `VnPayService` |
| 05 | [Module Đơn hàng & Hoàn/Đổi](05-module-don-hang-hoan-doi.md) | `OrderAdminController`, `ReturnRequestController`, `ReturnAdminController` |
| 06 | [Module Kho hàng & Tồn kho](06-module-kho-hang.md) | `WarehouseAdminController`, `Inventory`, `StockTransaction` |
| 07 | [Module Quản trị & Báo cáo](07-module-quan-tri-bao-cao.md) | `DashboardController`, `ReportAdminController`, `CustomerAdminController`, `BusinessAdminController` |
| 08 | [Module Nội dung & Trải nghiệm](08-module-noi-dung.md) | `BlogController`, `PostAdminController`, `BannerAdminController`, `HomeLayoutAdminController`, thử kính AI |
| 09 | [Mô hình dữ liệu](09-mo-hinh-du-lieu.md) | 26 bảng, quan hệ, enum, quy ước |
| 10 | [Kết quả Audit](10-ket-qua-audit.md) | **Danh sách lỗi & rủi ro theo mức độ ưu tiên** |
| 11 | [Kế hoạch Bảo trì & Triển khai](11-ke-hoach-bao-tri-trien-khai.md) | **Checklist gỡ chặn deploy, runbook, lộ trình sprint, bảo trì định kỳ** |
| 11b | [Cập nhật tiến độ Plan 11](11b-cap-nhat-tien-do-plan-11.md) | **Báo cáo cập nhật tiến độ thực tế các mục trong Plan 11** |
| 12 | [Plan xử lý góp ý của thầy](12-plan-xu-ly-gop-y-cua-thay.md) | **6 việc thầy yêu cầu + 2 lỗi đang gặp — kèm câu trả lời để nói với thầy** |
| 12b | [Cập nhật tiến độ Plan 12](12b-cap-nhat-tien-do-plan-12.md) | **Báo cáo cập nhật tiến độ thực tế 100% các mục góp ý của thầy (Plan 12)** |
| 13 | [Plan nâng cấp giao diện](13-plan-nang-cap-giao-dien.md) | **Hướng thiết kế, hệ token, signature element, lộ trình 7 giai đoạn** |
| 14 | [Checklist chống "AI hóa" frontend](14-checklist-chong-ai-hoa.md) | **Kết quả quét giao diện thật: màu, radius, shadow, focus, alt, motion — kèm cách sửa** |
| 15 | [Nghiệp vụ quản lý lịch đo mắt](15-nghiep-vu-quan-ly-lich-do-mat.md) | `AppointmentController`, `AppointmentAdminController`, tra cứu & đổi lịch |
| 16 | [Module Chatbot AI](16-module-chatbot-ai.md) | `ChatbotController`, `ProductContextBuilder` (RAG), `ChatCompletionAiService`, widget tư vấn |

---

## ⚡ Đọc Nhanh Cho Người Chuẩn Bị Phản Biện / Giảng Dạy

- **Người học chưa biết code AI:** Đọc ngay [00 — Bí kíp Phản biện cho AI Users](00-KIPHAP-PHAN-BIEN-FOR-AI-USERS.md) và học thuộc [00 — 100 Câu hỏi Phản biện](00-100-CAU-HOI-PHAN-BIEN-CHUYEN-SAU.md).
- **Người dạy / Tutor:** Dùng [00 — Giáo trình Giảng dạy 7 Buổi](00-GIAO-TRINH-GIANG-DAY-7-BUOI.md) để đứng lớp giảng dạy từng buổi.
- **Khi làm Slide thuyết trình:** Copy các sơ đồ chuẩn tại [00 — Sơ đồ Quy trình & Luồng xử lý](00-QUY-TRINH-LUONG-XU-LY-PROCESS.md).
- **Khi bị thầy cô hỏi mở code bất ngờ:** Tra cứu nhanh tại [00 — Bản đồ Tra cứu Code 5s](00-BANG-TRA-CUU-CODE-NHANH.md).
