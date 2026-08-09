# Sơ Đồ Quy Trình & Luồng Xử Lý Dữ Liệu (Process Flow Diagrams)

Tài liệu này tổng hợp toàn bộ các **Sơ đồ quy trình xử lý (Process Flowcharts & Sequence Diagrams)** của dự án Website Bán Kính Mắt. Bạn có thể sử dụng các sơ đồ Mermaid này để chèn thẳng vào **Slide thuyết trình bảo vệ đồ án** trước hội đồng.

---

## 🔄 1. QUY TRÌNH THỦ KÍNH AI (VIRTUAL TRY-ON PROCESS)

Quy trình nhận diện khuôn mặt và lồng ghép gọng kính 2D/3D trực tiếp trên trình duyệt.

```mermaid
sequenceDiagram
    autonumber
    actor User as Khách hàng
    participant Browser as Trình duyệt (JS / Face API)
    participant Controller as ProductController
    participant Storage as Server Storage (public/upload)
    
    User->>Browser: Mở trang /thu-kinh & Bật Webcam/Tải ảnh
    Browser->>Browser: Detect Facial Landmarks (Mắt, Sống mũi)
    Browser->>Browser: Overlay ảnh gọng kính lên khuôn mặt
    User->>Browser: Nhấn "Lưu ảnh kết quả"
    Browser->>Controller: POST /thu-kinh/luu-ket-qua (Image base64 / Snapshot)
    Controller->>Storage: Lưu file ảnh vào public/upload/snapshots/
    Controller->>User: Trả về đường dẫn ảnh & thông báo thành công
```

### ✨ Giải thích quy trình với Hội đồng:
1. **Bước 1:** Trình duyệt gọi Camera qua WebRTC API hoặc nhận file ảnh từ client.
2. **Bước 2:** Thư viện JS quét 68 điểm mốc khuôn mặt (Facial Landmarks) để xác định tâm 2 mắt và sống mũi.
3. **Bước 3:** Tự động tính toán góc nghiêng và kích thước để đặt file PNG gọng kính trùng khớp vào khuôn mặt.
4. **Bước 4:** Khi khách nhấn lưu, hệ thống chụp lại canvas, gửi AJAX lên `ProductController@storeTryOnSnapshot` để lưu lại vết thử kính.

---

## 💳 2. QUY TRÌNH THANH TOÁN VNPAY & XÁC THỰC CHỮ KÝ SHA512

Quy trình thanh toán an toàn 3 bên: Khách hàng ↔ Server Website ↔ Cổng thanh toán VNPay.

```mermaid
sequenceDiagram
    autonumber
    actor Customer as Khách hàng
    participant Server as Server Website (VnPayService)
    participant VNPay as Cổng VNPay Sandbox
    participant Database as Cơ sở dữ liệu (MySQL)

    Customer->>Server: Đặt hàng & Chọn thanh toán VNPay (POST /thanh-toan)
    Server->>Server: Tạo Order (PENDING) & Gọi VnPayService::createPaymentUrl()
    Server->>Server: Sắp xếp tham số (ksort) & Hash SHA512 tạo vnp_SecureHash
    Server->>Customer: Redirection URL sang cổng VNPay
    Customer->>VNPay: Nhập thông tin thẻ ATM & OTP xác thực
    
    par Luồng 1: Tra cứu kết quả phía Client (Return URL)
        VNPay->>Customer: Redirect về /vnpay/return
        Customer->>Server: GET /vnpay/return?vnp_Amount=...&vnp_SecureHash=...
        Server->>Server: hash_equals() kiểm tra chữ ký Return
        Server->>Customer: Trả giao diện "Thanh toán thành công"
    and Luồng 2: Xử lý ngầm Server-to-Server (IPN URL)
        VNPay->>Server: GET/POST /vnpay/ipn?vnp_TxnRef=...
        Server->>Server: hash_equals() kiểm tra chữ ký IPN
        alt Chữ ký đúng & ResponseCode = '00'
            Server->>Database: Cập nhật Order status = 'PAID', Payment status = 'SUCCESS'
            Server->>VNPay: Trả về JSON {"RspCode":"00", "Message":"Confirm Success"}
        else Chữ ký sai hoặc lỗi
            Server->>VNPay: Trả về JSON {"RspCode":"97", "Message":"Invalid Signature"}
        end
    end
```

### ✨ Giải thích quy trình với Hội đồng:
1. **Tạo chữ ký:** Server gọi `VnPayService` để tạo ra chuỗi hash `vnp_SecureHash` bằng thuật toán **HMAC-SHA512** bảo mật cao.
2. **Đảm bảo tính toàn vẹn:** Khách hàng không thể tự sửa số tiền trên URL vì VNPay sẽ tính lại Hash với `Secret Key` và chặn ngay nếu không khớp.
3. **Cập nhật IPN:** Sử dụng cơ chế IPN (Instant Payment Notification) đảm bảo dù khách có tắt trình duyệt giữa chừng thì Server VNPay vẫn chủ động báo cho Server website để chốt đơn thành công.

---

## 📦 3. QUY TRÌNH QUẢN LÝ TỒN KHO & BIẾN ĐỘNG KHO (STOCK TRANSACTION)

Quy trình kiểm soát tồn kho chặt chẽ qua bảng lịch sử `stock_transactions`.

```mermaid
flowchart TD
    A[Bắt đầu thao tác] --> B{Loại giao dịch?}
    
    B -->|Nhập hàng mới| C[Admin tạo phiếu Nhập kho]
    C --> D[Thêm StockTransaction: type = IMPORT, qty > 0]
    D --> E[Cập nhật inventories: quantity = quantity + qty]
    
    B -->|Khách đặt mua| F[Khách Checkout thành công]
    F --> G[Thêm StockTransaction: type = EXPORT, qty < 0]
    G --> H[Cập nhật inventories: quantity = quantity - qty]
    
    B -->|Hủy đơn hàng| I[Hệ thống/Admin Hủy đơn]
    I --> J[Thêm StockTransaction: type = RESTORE, qty > 0]
    J --> K[Cập nhật inventories: quantity = quantity + qty]
    
    B -->|Khách Đổi trả| L[Admin duyệt Đổi trả]
    L --> M[Thêm StockTransaction: type = RETURN, qty > 0]
    M --> N[Cập nhật inventories: quantity = quantity + qty]
    
    E --> O[Kiểm tra Low Stock Warning: qty <= safety_stock]
    H --> O
    K --> O
    N --> O
    O -->|Đúng| P[Hiển thị cảnh báo Đỏ trên Admin Dashboard]
    O -->|Sai| Q[Trạng thái kho bình thường]
```

### ✨ Giải thích quy trình với Hội đồng:
- Hệ thống không chỉ lưu số tồn kho hiện tại mà còn lưu **lịch sử mọi biến động** (`StockTransaction`).
- Mọi hành động Xuất, Nhập, Hủy đơn hay Đổi trả đều được ghi vết minh bạch để đối soát kế toán cuối tháng.

---

## 🔄 4. QUY TRÌNH XỬ LÝ ĐỔI TRẢ HÀNG (RETURN REQUEST PROCESS)

Quy trình quản lý yêu cầu hoàn tiền / đổi sản phẩm từ khách hàng.

```mermaid
stateDiagram-v2
    [*] --> PENDING: Khách tạo Yêu cầu Đổi trả (kèm ảnh minh chứng)
    
    PENDING --> REJECTED: Admin Từ chối (Ghi rõ lý do)
    PENDING --> APPROVED: Admin Chấp nhận Yêu cầu
    
    APPROVED --> SHIPPING_BACK: Khách gửi hàng về Showroom
    SHIPPING_BACK --> RECEIVED: Admin xác nhận Đã nhận hàng
    
    RECEIVED --> COMPLETED: Nhập kho lại + Hoàn tiền / Đổi sản phẩm
    
    REJECTED --> [*]
    COMPLETED --> [*]
```

### ✨ Giải thích quy trình với Hội đồng:
- Khách hàng tạo yêu cầu trực tiếp trên website kèm lý do và ảnh chụp sản phẩm bị lỗi.
- Admin kiểm duyệt trên trang Quản trị -> Duyệt -> Khách gửi hàng -> Xác nhận -> Tự động nhập kho lại kính và hoàn tất quy trình.
