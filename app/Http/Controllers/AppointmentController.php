<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    // Danh sách dịch vụ đo thị lực cố định. Cửa hàng chỉ có 3 gói nên khai báo
    // tĩnh ở đây thay vì tạo hẳn một bảng service cho một danh sách không đổi.
    private const SERVICES = [
        'CO_BAN' => [
            'name' => 'Đo thị lực cơ bản',
            'price' => 0,
            'duration' => '20 phút',
            'description' => 'Kiểm tra thị lực nhanh, tư vấn độ cận/loạn cơ bản trước khi chọn gọng.',
        ],
        'CHUYEN_SAU' => [
            'name' => 'Đo thị lực chuyên sâu',
            'price' => 300000,
            'duration' => '45 phút',
            'description' => 'Đo bằng máy khúc xạ tự động, phân tích loạn thị, cận/viễn thị và tật khúc xạ đi kèm.',
        ],
        'TRE_EM' => [
            'name' => 'Đo thị lực trẻ em',
            'price' => 200000,
            'duration' => '30 phút',
            'description' => 'Quy trình đo riêng cho trẻ em, phát hiện sớm nhược thị và lác mắt.',
        ],
    ];

    // Khung giờ nhận khách trong ngày, mỗi khách cách nhau 1 tiếng.
    private const TIME_SLOTS = ['09:00', '10:00', '11:00', '14:00', '15:00', '16:00', '17:00', '18:00'];

    private const STORE_NAME = 'Atelier Optique Studio';
    private const STORE_ADDRESS = '123 Nguyễn Trãi, P. Bến Thành, Q.1, TP.HCM';

    // Hiển thị trang đặt lịch đo thị lực (chọn dịch vụ -> chọn giờ -> thông tin -> xác nhận).
    // Route: GET /dat-lich-do-mat.
    public function create(Request $request): View
    {
        $confirmedCode = $request->session()->get('appointment_code');
        $confirmed = $confirmedCode ? Appointment::where('code', $confirmedCode)->first() : null;

        return view('appointments.create', [
            'services' => self::SERVICES,
            'timeSlots' => self::TIME_SLOTS,
            'storeName' => self::STORE_NAME,
            'storeAddress' => self::STORE_ADDRESS,
            'confirmed' => $confirmed,
            'minDate' => now()->toDateString(),
            'maxDate' => now()->addDays(30)->toDateString(),
        ]);
    }

    // Lưu lịch đặt đo thị lực. Không bắt buộc đăng nhập để khách vãng lai vẫn đặt được.
    // Route: POST /dat-lich-do-mat.
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'service_code' => ['required', 'string', 'in:' . implode(',', array_keys(self::SERVICES))],
            'appointment_date' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:' . now()->addDays(30)->toDateString()],
            'appointment_time' => ['required', 'string', 'in:' . implode(',', self::TIME_SLOTS)],
            'customer_name' => ['required', 'string', 'max:100'],
            'customer_phone' => ['required', 'string', 'regex:/^[0-9+ ]{9,15}$/'],
            'customer_email' => ['nullable', 'email', 'max:190'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'appointment_date.after_or_equal' => 'Vui lòng chọn ngày từ hôm nay trở đi.',
            'customer_phone.regex' => 'Số điện thoại không hợp lệ.',
        ]);

        $service = self::SERVICES[$validated['service_code']];

        $appointment = Appointment::create([
            'user_id' => Auth::id(),
            'code' => $this->generateCode(),
            'service_code' => $validated['service_code'],
            'service_name' => $service['name'],
            'price' => $service['price'],
            'appointment_date' => $validated['appointment_date'],
            'appointment_time' => $validated['appointment_time'],
            'customer_name' => $validated['customer_name'],
            'customer_phone' => $validated['customer_phone'],
            'customer_email' => $validated['customer_email'] ?? null,
            'note' => $validated['note'] ?? null,
            'status' => 'PENDING',
        ]);

        return redirect()
            ->route('appointments.create')
            ->with('appointment_code', $appointment->code);
    }

    // Sinh mã lịch hẹn dạng AO-YYYYMMDD-XXXX, đảm bảo không trùng.
    private function generateCode(): string
    {
        do {
            $code = 'AO-' . now()->format('Ymd') . '-' . Str::upper(Str::random(4));
        } while (Appointment::where('code', $code)->exists());

        return $code;
    }
}
