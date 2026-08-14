# Nghiệp vụ quản lý lịch đo mắt

Tài liệu này dùng để chốt nghiệp vụ trước khi triển khai code cho module quản lý lịch đo mắt. Mục tiêu là bổ sung phần còn thiếu sau flow đặt lịch hiện tại: admin xử lý lịch hẹn, khách tra cứu lịch bằng mã hẹn và khách đổi lịch trong giới hạn cho phép.

## 1. Hiện trạng trong hệ thống

Hệ thống đã có flow đặt lịch đo thị lực cho khách hàng tại route `/dat-lich-do-mat`.

Khách hiện có thể:

- Chọn dịch vụ đo mắt.
- Chọn ngày trong khoảng cho phép.
- Chọn khung giờ cố định.
- Nhập họ tên, số điện thoại, email và ghi chú.
- Gửi form để tạo lịch hẹn.
- Nhận mã lịch hẹn dạng `AO-YYYYMMDD-XXXX`.

Bảng `appointments` hiện đang lưu các thông tin chính:

| Nhóm dữ liệu | Trường hiện có |
| --- | --- |
| Liên kết tài khoản | `user_id` |
| Mã lịch hẹn | `code` |
| Dịch vụ | `service_code`, `service_name`, `price` |
| Thời gian | `appointment_date`, `appointment_time` |
| Thông tin khách | `customer_name`, `customer_phone`, `customer_email` |
| Ghi chú | `note` |
| Trạng thái | `status` |

Các phần còn thiếu so với yêu cầu mở rộng:

- Chưa có màn hình admin quản lý lịch hẹn.
- Chưa có lọc lịch theo ngày, trạng thái.
- Chưa có thao tác xác nhận, hủy, hoàn tất lịch.
- Chưa có kiểm tra trùng hoặc đầy khung giờ.
- Chưa có trang khách tra cứu lịch bằng mã.
- Chưa có chức năng khách tự đổi lịch.
- Chưa có email xác nhận, email đổi lịch và email nhắc lịch.
- Email trên form đặt lịch hiện đang không bắt buộc, chưa phù hợp nếu nghiệp vụ yêu cầu gửi xác nhận lịch qua email.

## 2. Đối chiếu với code hiện tại

Khi đối chiếu với code trong project, có một số điểm cần bám theo để nghiệp vụ hợp lý và dễ triển khai:

| Khu vực code | Hiện trạng | Ý nghĩa khi thiết kế nghiệp vụ |
| --- | --- | --- |
| `AppointmentController` | Đang khai báo cố định danh sách dịch vụ, khung giờ, tên cửa hàng, địa chỉ cửa hàng | Vì hệ thống chỉ có 1 cửa hàng, nên giữ địa điểm cố định là hợp lý |
| `appointments` | Đã có `user_id`, mã lịch, dịch vụ, ngày giờ, thông tin khách, email khách, trạng thái | Có thể mở rộng bằng migration, không cần tạo lại bảng |
| `routes/web.php` | Đặt lịch đang mở cho cả khách chưa đăng nhập | Tra cứu và đổi lịch cũng nên mở cho khách chưa đăng nhập, nhưng phải xác thực bằng mã lịch + số điện thoại/email |
| `routes/admin.php` | Admin route dùng middleware `admin`, mặc định cho `ADMIN` và `STAFF` | Lịch đo mắt nên cho cả nhân viên xử lý vì đây là nghiệp vụ vận hành hằng ngày |
| `EnsureAdmin` | Có thể giới hạn riêng bằng `admin:ADMIN` nếu cần | Không nên khóa module lịch đo mắt chỉ cho `ADMIN`, trừ khi thầy yêu cầu |
| `Warehouse` model | Có sẵn kho với trường `type`, trong đó có `STORE` | Không cần dùng cho lịch đo mắt nếu chỉ có một cửa hàng |
| `BusinessAdminController` | Có tab cửa hàng đọc bảng `stores`, nhưng chưa thấy `Store` model và migration tạo `stores` trong migration đang chạy | Không nên phụ thuộc vào bảng `stores` cho module lịch đo mắt |
| Email | Project đã có `App\Support\QueuedRawMail` và `SendRawMailJob` | Email lịch hẹn nên dùng lại cơ chế này thay vì viết kiểu gửi mới |

Kết luận đối chiếu:

- Nên mở rộng trực tiếp bảng `appointments`.
- Không cần thêm `store_id` hoặc `warehouse_id` vì hệ thống chỉ có một cửa hàng.
- Địa điểm đo mắt cố định: `Atelier Optique Studio - 123 Nguyễn Trãi, P. Bến Thành, Q.1, TP.HCM`.
- Nên dùng lại `QueuedRawMail` cho email đặt lịch, xác nhận, hủy, đổi lịch và nhắc lịch.
- Nên đổi `customer_email` thành bắt buộc ở form đặt lịch vì email là kênh xác nhận chính.
- Nên đặt route admin lịch đo mắt trong nhóm `Route::middleware(['admin', 'throttle:admin'])`, không đặt trong nhóm `admin:ADMIN`.
- Nên thêm link menu admin trong sidebar giống các module vận hành khác.
- Nếu khách đặt lịch trước khi đăng ký tài khoản, lịch đó giữ `user_id = null` và không tự động tính vào quản lý cá nhân sau khi khách đăng ký.

## 3. Mục tiêu nghiệp vụ

Module mới cần phục vụ hai nhóm người dùng chính:

| Tác nhân | Mục tiêu |
| --- | --- |
| Khách hàng | Đặt lịch, tra cứu lịch, đổi lịch nếu còn trong giới hạn cho phép |
| Admin hoặc nhân viên | Xem danh sách lịch, lọc lịch, xác nhận lịch, hủy lịch, hoàn tất lịch, gửi nhắc lịch |

Phạm vi đề xuất cho đồ án:

- Có quản lý lịch hẹn phía admin.
- Có tra cứu lịch hẹn phía khách.
- Có đổi lịch phía khách.
- Có email xác nhận, email đổi lịch, email hủy lịch và email nhắc lịch.

## 4. Trạng thái lịch hẹn

Đề xuất dùng các trạng thái sau:

| Mã trạng thái | Ý nghĩa | Người thay đổi |
| --- | --- | --- |
| `PENDING` | Khách vừa đặt lịch, đang chờ cửa hàng xác nhận | Hệ thống tạo khi khách đặt lịch |
| `CONFIRMED` | Cửa hàng đã xác nhận lịch hẹn | Admin hoặc nhân viên |
| `COMPLETED` | Khách đã đến và hoàn tất đo mắt | Admin hoặc nhân viên |
| `CANCELLED` | Lịch đã bị hủy | Admin hoặc khách trong giới hạn cho phép |
| `NO_SHOW` | Khách không đến theo lịch đã xác nhận | Admin hoặc nhân viên |

Ghi chú:

- Không nên dùng `RESCHEDULED` làm trạng thái chính.
- Việc đổi lịch nên được hiểu là cập nhật ngày, giờ, địa điểm đo mắt và tăng số lần đổi lịch.
- Sau khi khách đổi lịch, trạng thái có thể quay về `PENDING` để admin xác nhận lại.

## 5. Luồng khách đặt lịch

### 5.1. Luồng chính

1. Khách truy cập trang đặt lịch đo mắt.
2. Hệ thống hiển thị địa điểm đo mắt cố định: `123 Nguyễn Trãi, P. Bến Thành, Q.1, TP.HCM`.
3. Khách chọn dịch vụ.
4. Khách chọn ngày hẹn.
5. Hệ thống hiển thị các khung giờ còn khả dụng.
6. Khách chọn khung giờ.
7. Khách nhập thông tin liên hệ, bắt buộc có email.
8. Hệ thống kiểm tra dữ liệu.
9. Hệ thống tạo lịch hẹn với trạng thái `PENDING`.
10. Hệ thống hiển thị mã lịch hẹn cho khách.
11. Hệ thống gửi email xác nhận đã tiếp nhận lịch cho khách.

### 5.2. Điều kiện hợp lệ

- Ngày hẹn không được nhỏ hơn ngày hiện tại.
- Ngày hẹn không vượt quá số ngày cho phép, đề xuất là 30 ngày.
- Khung giờ phải thuộc danh sách khung giờ cửa hàng hỗ trợ.
- Số điện thoại phải đúng định dạng cơ bản.
- Email là bắt buộc và phải đúng định dạng.
- Một khung giờ chỉ nhận số lượng lịch tối đa theo cấu hình.

### 5.3. Kiểm tra khung giờ

Đề xuất đơn giản:

- Mỗi ngày, mỗi khung giờ chỉ nhận tối đa 1 lịch đang hoạt động.
- Lịch đang hoạt động gồm `PENDING` và `CONFIRMED`.
- Lịch `CANCELLED`, `COMPLETED`, `NO_SHOW` không chiếm khung giờ.

Nếu muốn linh hoạt hơn:

- Thêm cấu hình `slot_capacity`, ví dụ mỗi khung giờ nhận tối đa 2 khách.
- Khi số lịch `PENDING` + `CONFIRMED` đạt giới hạn, khung giờ đó không còn được chọn.

## 6. Luồng admin quản lý lịch hẹn

### 6.1. Màn hình danh sách lịch hẹn

Admin truy cập màn hình quản lý lịch đo mắt trong khu vực quản trị.

Đường dẫn đề xuất:

```txt
/admin/lich-do-mat
```

Màn hình cần có các bộ lọc:

| Bộ lọc | Ý nghĩa |
| --- | --- |
| Ngày hẹn | Xem lịch theo một ngày cụ thể |
| Trạng thái | Xem lịch chờ xác nhận, đã xác nhận, đã hoàn tất, đã hủy |
| Từ khóa | Tìm theo mã lịch, tên khách, số điện thoại, email |

Thông tin hiển thị trong bảng:

- Mã lịch hẹn.
- Ngày giờ hẹn.
- Địa điểm đo mắt cố định.
- Dịch vụ.
- Tên khách.
- Số điện thoại.
- Email.
- Trạng thái.
- Số lần đổi lịch.
- Thời điểm tạo lịch.
- Các nút thao tác phù hợp với trạng thái.

### 6.2. Admin xác nhận lịch

Điều kiện:

- Lịch đang ở trạng thái `PENDING`.
- Khung giờ vẫn hợp lệ.

Kết quả:

- Trạng thái đổi từ `PENDING` sang `CONFIRMED`.
- Lưu thời điểm xác nhận.
- Lưu admin xác nhận nếu cần.
- Gửi email thông báo lịch đã được xác nhận.

### 6.3. Admin hủy lịch

Điều kiện:

- Lịch đang ở trạng thái `PENDING` hoặc `CONFIRMED`.
- Admin nhập lý do hủy nếu cần.

Kết quả:

- Trạng thái đổi sang `CANCELLED`.
- Lưu lý do hủy.
- Lưu thời điểm hủy.
- Gửi email thông báo hủy lịch.

### 6.4. Admin hoàn tất lịch

Điều kiện:

- Lịch đang ở trạng thái `CONFIRMED`.
- Khách đã đến cửa hàng và hoàn tất đo mắt.

Kết quả:

- Trạng thái đổi sang `COMPLETED`.
- Lưu thời điểm hoàn tất.
- Có thể lưu ghi chú kết quả đo hoặc ghi chú nội bộ nếu cần mở rộng.

### 6.5. Admin đánh dấu khách không đến

Điều kiện:

- Lịch đang ở trạng thái `CONFIRMED`.
- Đã qua giờ hẹn.
- Khách không đến.

Kết quả:

- Trạng thái đổi sang `NO_SHOW`.
- Lưu ghi chú nếu cần.

## 7. Luồng khách tra cứu lịch hẹn

### 7.1. Màn hình tra cứu

Đường dẫn đề xuất:

```txt
/tra-cuu-lich-hen
```

Khách nhập:

- Mã lịch hẹn.
- Số điện thoại hoặc email.

Không nên cho tra cứu chỉ bằng mã lịch hẹn, vì người khác biết mã có thể xem thông tin cá nhân của khách.

### 7.2. Kết quả tra cứu

Nếu thông tin hợp lệ, hệ thống hiển thị:

- Mã lịch hẹn.
- Dịch vụ.
- Ngày giờ hẹn.
- Địa điểm đo mắt.
- Tên khách.
- Số điện thoại đã ẩn một phần.
- Email đã ẩn một phần nếu có.
- Trạng thái hiện tại.
- Ghi chú cần thiết cho khách.
- Nút đổi lịch nếu lịch còn được phép đổi.

Nếu thông tin không hợp lệ:

- Hiển thị thông báo không tìm thấy lịch hẹn.
- Không nói rõ sai mã hay sai số điện thoại/email để tránh dò thông tin.

## 8. Luồng khách đổi lịch

### 8.1. Điều kiện được đổi lịch

Đề xuất quy định:

- Chỉ được đổi lịch khi trạng thái là `PENDING` hoặc `CONFIRMED`.
- Phải đổi trước giờ hẹn ít nhất 24 giờ.
- Mỗi lịch được đổi tối đa 2 lần.
- Ngày mới không được nhỏ hơn ngày hiện tại.
- Ngày mới không vượt quá 30 ngày tính từ hôm nay.
- Khung giờ mới phải còn trống.

### 8.2. Cách xử lý sau khi đổi lịch

Khi khách đổi lịch thành công:

- Cập nhật `appointment_date`.
- Cập nhật `appointment_time`.
- Tăng `reschedule_count`.
- Lưu thời điểm đổi lịch gần nhất.
- Lưu lý do đổi lịch nếu khách nhập.
- Đổi trạng thái về `PENDING` để admin xác nhận lại.
- Gửi email thông báo đã tiếp nhận yêu cầu đổi lịch.

### 8.3. Lưu lịch sử đổi lịch

Có hai hướng:

| Hướng | Ưu điểm | Nhược điểm |
| --- | --- | --- |
| Chỉ lưu `reschedule_count`, `last_rescheduled_at`, `reschedule_reason` | Dễ làm, đủ cho demo |
| Tạo bảng `appointment_reschedules` | Theo dõi lịch sử đầy đủ, nghiệp vụ đẹp hơn |

Đề xuất cho đồ án:

- Giai đoạn đầu chỉ lưu số lần đổi và thời điểm đổi gần nhất.
- Nếu thầy yêu cầu chi tiết, bổ sung bảng lịch sử đổi lịch sau.

## 9. Thông báo lịch hẹn qua email

Email là kênh thông báo chính của module lịch đo mắt. Vì vậy khách bắt buộc nhập email khi đặt lịch.

### 9.1. Các loại email cần gửi

| Loại email | Thời điểm gửi | Ý nghĩa |
| --- | --- | --- |
| Tiếp nhận lịch | Sau khi khách đặt lịch thành công | Gửi mã lịch hẹn và thông tin lịch đang chờ xác nhận |
| Xác nhận lịch | Khi admin chuyển lịch sang `CONFIRMED` | Báo cho khách biết cửa hàng đã xác nhận lịch |
| Hủy lịch | Khi admin hoặc khách hủy lịch | Báo lịch đã bị hủy và lý do nếu có |
| Đổi lịch | Khi khách đổi lịch thành công | Báo thông tin lịch mới và trạng thái chờ xác nhận lại |
| Nhắc lịch | Trước giờ hẹn, ví dụ trước 24 giờ | Nhắc khách đến đúng lịch |

### 9.2. Email nhắc lịch

Hệ thống gửi email nhắc lịch cho khách khi:

- Lịch đã được xác nhận.
- Lịch chưa hoàn tất, chưa hủy.
- Còn khoảng 24 giờ trước giờ hẹn.
- Lịch chưa từng được gửi nhắc trước đó.

Nội dung email nên có:

- Mã lịch hẹn.
- Dịch vụ.
- Ngày giờ.
- Địa điểm đo mắt.
- Địa chỉ cửa hàng.
- Số điện thoại cửa hàng.
- Lưu ý có mặt trước giờ hẹn 10 phút.
- Link tra cứu hoặc đổi lịch nếu còn cho phép.

Về mặt code, nên tạo một service riêng, ví dụ `AppointmentNotificationService`, và bên trong dùng lại `App\Support\QueuedRawMail` giống các service email đơn hàng hiện tại.

## 10. Dữ liệu cần bổ sung

Đề xuất bổ sung vào bảng `appointments`:

| Trường | Kiểu dữ liệu đề xuất | Ý nghĩa |
| --- | --- | --- |
| `confirmed_at` | timestamp nullable | Thời điểm admin xác nhận |
| `cancelled_at` | timestamp nullable | Thời điểm hủy |
| `completed_at` | timestamp nullable | Thời điểm hoàn tất |
| `no_show_at` | timestamp nullable | Thời điểm đánh dấu khách không đến |
| `cancel_reason` | text nullable | Lý do hủy |
| `admin_note` | text nullable | Ghi chú nội bộ của admin |
| `reschedule_count` | unsigned tiny integer default 0 | Số lần khách đổi lịch |
| `last_rescheduled_at` | timestamp nullable | Lần đổi lịch gần nhất |
| `reschedule_reason` | text nullable | Lý do đổi lịch gần nhất |
| `reminder_email_sent_at` | timestamp nullable | Đã gửi email nhắc lịch lúc nào |

Lý do không thêm `store_id` hoặc `warehouse_id` ở phiên bản đầu:

- Website chỉ có một cửa hàng.
- `AppointmentController` hiện đã dùng địa chỉ cố định.
- Yêu cầu lọc theo chi nhánh không cần thiết với phạm vi một cửa hàng.
- Giảm số lượng file và bảng phải sửa khi triển khai.
- Nếu sau này mở nhiều chi nhánh, có thể bổ sung `warehouse_id` sau bằng migration riêng.

Với `customer_email`, bảng hiện tại đang để nullable. Có hai cách xử lý:

| Cách | Ý nghĩa |
| --- | --- |
| Chỉ sửa validation form thành bắt buộc | Nhanh, ít rủi ro, dữ liệu mới luôn có email |
| Thêm migration đổi `customer_email` sang NOT NULL | Chặt chẽ hơn ở tầng database, nhưng cần xử lý dữ liệu lịch cũ đang thiếu email |

Đề xuất cho phiên bản đầu:

- Sửa validation để `customer_email` bắt buộc.
- Sửa label trên form thành `Email *`.
- Chưa vội đổi cột database sang NOT NULL nếu đã có dữ liệu cũ.

Nếu muốn quản lý lịch sử gửi email tốt hơn, có thể tạo thêm bảng `appointment_email_logs`.

Trường đề xuất cho bảng log:

| Trường | Ý nghĩa |
| --- | --- |
| `appointment_id` | Lịch hẹn liên quan |
| `type` | `BOOKING_RECEIVED`, `CONFIRMED`, `CANCELLED`, `REMINDER`, `RESCHEDULED` |
| `recipient_email` | Email nhận |
| `subject` | Tiêu đề email |
| `body` | Nội dung email |
| `status` | `SENT` hoặc `FAILED` |
| `sent_at` | Thời điểm gửi |
| `error_message` | Lỗi nếu gửi thất bại |

## 11. Quy tắc phân quyền

Đề xuất:

- Khách chưa đăng nhập vẫn đặt lịch và tra cứu lịch bằng mã + số điện thoại/email.
- Khách đã đăng nhập khi đặt lịch thì lịch gắn với `user_id`.
- Khách bắt buộc nhập email khi đặt lịch để nhận mã lịch, xác nhận lịch, hủy lịch, đổi lịch và nhắc lịch.
- Admin hoặc nhân viên được xem và xử lý lịch hẹn.
- Nếu muốn chặt hơn, chỉ `ADMIN` được hủy lịch, còn nhân viên chỉ xác nhận và hoàn tất.

Giai đoạn đầu nên cho cả `ADMIN` và `STAFF` xử lý lịch hẹn. Điều này khớp với middleware `admin` hiện tại và hợp lý về nghiệp vụ, vì nhân viên cửa hàng thường là người xác nhận khách đến đo mắt.

## 12. Quy tắc khách vãng lai và tài khoản

Hệ thống cho phép cả khách chưa đăng nhập đặt lịch đo mắt. Khi khách vãng lai đặt lịch:

- `user_id` của lịch hẹn để trống.
- Hệ thống vẫn lưu họ tên, số điện thoại và email khách nhập tại thời điểm đặt lịch.
- Khách nhận mã lịch hẹn qua email.
- Khách tra cứu hoặc đổi lịch bằng mã lịch hẹn kết hợp với số điện thoại hoặc email.

Nếu sau khi đặt lịch khách mới đăng ký tài khoản:

- Lịch đã đặt trước đó không tự động gắn vào tài khoản mới.
- Lịch đó không hiển thị trong khu vực quản lý cá nhân của khách.
- Khách vẫn có thể tra cứu bằng mã lịch hẹn và số điện thoại hoặc email.

Lý do chọn quy tắc này:

- Tránh tự động ghép nhầm lịch của người khác chỉ vì trùng email hoặc số điện thoại.
- Giữ đúng dữ liệu tại thời điểm khách đặt lịch.
- Dễ triển khai và phù hợp với flow hiện tại, vì `appointments.user_id` đã cho phép nullable.

Nếu sau này muốn nâng cấp, có thể thêm chức năng "liên kết lịch hẹn vào tài khoản" bằng cách yêu cầu khách đăng nhập, nhập mã lịch hẹn và xác nhận qua email.

## 13. Các màn hình cần có

| Màn hình | Đường dẫn đề xuất | Mục đích |
| --- | --- | --- |
| Đặt lịch đo mắt | `/dat-lich-do-mat` | Khách tạo lịch mới |
| Tra cứu lịch hẹn | `/tra-cuu-lich-hen` | Khách xem trạng thái lịch |
| Đổi lịch | Có thể nằm trong trang tra cứu | Khách chọn ngày giờ mới |
| Admin danh sách lịch | `/admin/lich-do-mat` | Admin xem, lọc, xử lý lịch |

## 14. Thứ tự triển khai đề xuất

Triển khai từ từ theo từng file:

1. Tạo migration bổ sung cột cho `appointments`.
2. Cập nhật `App\Models\Appointment`.
3. Cập nhật flow đặt lịch để kiểm tra khung giờ trống.
4. Tạo `AdminAppointmentController`.
5. Thêm route admin cho lịch đo mắt.
6. Tạo view admin danh sách lịch.
7. Thêm xử lý xác nhận, hủy, hoàn tất, không đến.
8. Tạo trang khách tra cứu lịch.
9. Thêm xử lý khách đổi lịch.
10. Tạo service gửi email lịch hẹn.
11. Tùy chọn tạo bảng log email lịch hẹn.
12. Viết test hoặc kiểm thử thủ công các luồng chính.

## 15. Phạm vi nên chốt trước khi code

Các câu hỏi cần chốt:

1. Có giữ cố định một cửa hàng như code hiện tại không?
2. Mỗi khung giờ nhận tối đa bao nhiêu khách?
3. Khách được đổi lịch tối đa mấy lần?
4. Khách phải đổi trước giờ hẹn bao lâu?
5. Sau khi khách đổi lịch, trạng thái quay về `PENDING` hay giữ `CONFIRMED`?
6. Có cần admin nhập lý do khi hủy lịch không?
7. Có cần lưu lịch sử đổi lịch đầy đủ không?
8. Có đổi cột `customer_email` trong database sang NOT NULL không, hay chỉ bắt buộc ở validation?
9. Lịch vãng lai có cần liên kết thủ công vào tài khoản sau này không?
10. Có cần lưu log từng email đã gửi không?

## 16. Đề xuất chốt cho phiên bản đầu

Để vừa đủ đẹp cho đồ án nhưng không quá nặng, đề xuất chốt phiên bản đầu như sau:

- Chỉ có một cửa hàng cố định: `123 Nguyễn Trãi, P. Bến Thành, Q.1, TP.HCM`.
- Email khách hàng là bắt buộc khi đặt lịch.
- Mỗi ngày, mỗi khung giờ nhận tối đa 1 lịch đang hoạt động.
- Khách được đổi lịch tối đa 2 lần.
- Khách phải đổi trước giờ hẹn ít nhất 24 giờ.
- Sau khi khách đổi lịch, trạng thái quay về `PENDING`.
- Admin cần nhập lý do khi hủy lịch.
- Email gửi thật nếu SMTP hoạt động.
- Chưa cần bảng lịch sử đổi lịch riêng ở giai đoạn đầu.
- Chưa cần bảng log email riêng ở giai đoạn đầu nếu muốn code gọn.
- Lịch khách đặt trước khi đăng ký tài khoản không tự động hiển thị trong quản lý cá nhân.

## 17. Luồng code hợp lý theo project

Nếu chốt phiên bản đầu, luồng code nên đi như sau:

1. Trang đặt lịch tiếp tục dùng địa chỉ cố định trong `AppointmentController`.
2. Form đặt lịch bắt buộc nhập `customer_email`.
3. Khi khách chọn ngày và giờ, backend kiểm tra slot có bị chiếm bởi lịch `PENDING` hoặc `CONFIRMED` không.
4. Khi tạo lịch, lưu `status = PENDING`.
5. Sau khi tạo lịch, gửi email tiếp nhận lịch cho khách.
6. Admin vào `/admin/lich-do-mat`, controller query `Appointment::with('user')`.
7. Admin lọc theo `appointment_date`, `status`, `keyword`.
8. Admin bấm xác nhận, hủy, hoàn tất hoặc không đến. Mỗi action chỉ cho phép khi trạng thái hiện tại hợp lệ.
9. Khách tra cứu bằng `code` và `customer_phone` hoặc `customer_email`.
10. Khách đổi lịch thì backend kiểm tra giới hạn 24 giờ, số lần đổi và slot mới.
11. Email dùng `AppointmentNotificationService` gọi `QueuedRawMail::raw`.

Luồng này ít đụng vào kiến trúc cũ, tận dụng được model và middleware sẵn có, đồng thời vẫn đáp ứng đúng góp ý của thầy.

## 18. Kết luận

Module quản lý lịch đo mắt nên được xây theo hướng mở rộng từ flow đặt lịch hiện tại, không thay toàn bộ chức năng cũ. Điểm quan trọng nhất là bổ sung vòng đời lịch hẹn rõ ràng: khách đặt lịch, admin xác nhận, hệ thống nhắc lịch, khách có thể tra cứu hoặc đổi lịch trong giới hạn, và admin hoàn tất hoặc hủy lịch khi cần.

Sau khi nghiệp vụ này được chốt, có thể bắt đầu code từng file theo thứ tự triển khai ở mục 14.
