<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Services\AppointmentNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AppointmentController extends Controller
{
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

    private const TIME_SLOTS = ['09:00', '10:00', '11:00', '14:00', '15:00', '16:00', '17:00', '18:00'];
    private const SLOT_CAPACITY = 1;
    private const MAX_ADVANCE_DAYS = 30;

    private const STORE_NAME = 'Atelier Optique Studio';
    private const STORE_ADDRESS = '123 Nguyễn Trãi, P. Bến Thành, Q.1, TP.HCM';

    public function create(Request $request): View
    {
        // Luong: Thuc thi truy van va lay ket qua tu CSDL.
        $confirmedCode = $request->session()->get('appointment_code');
        // Luong: Thuc thi truy van va lay ket qua tu CSDL.
        $confirmed = $confirmedCode ? Appointment::where('code', $confirmedCode)->first() : null;

        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('appointments.create', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'services' => self::SERVICES,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'timeSlots' => self::TIME_SLOTS,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'storeName' => self::STORE_NAME,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'storeAddress' => self::STORE_ADDRESS,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'confirmed' => $confirmed,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'minDate' => now()->toDateString(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'maxDate' => now()->addDays(self::MAX_ADVANCE_DAYS)->toDateString(),
        ]);
    }

    public function store(Request $request, AppointmentNotificationService $notification): RedirectResponse
    {
        // Luong: Kiem tra va lay du lieu hop le tu request.
        $validated = $request->validate([
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'service_code' => ['required', 'string', 'in:' . implode(',', array_keys(self::SERVICES))],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'appointment_date' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:' . now()->addDays(self::MAX_ADVANCE_DAYS)->toDateString()],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'appointment_time' => ['required', 'string', 'in:' . implode(',', self::TIME_SLOTS)],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'customer_name' => ['required', 'string', 'max:100'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'customer_phone' => ['required', 'string', 'regex:/^[0-9+ ]{9,15}$/'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'customer_email' => ['required', 'email', 'max:190'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'appointment_date.after_or_equal' => 'Vui lòng chọn ngày từ hôm nay trở đi.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'appointment_date.before_or_equal' => 'Vui lòng chọn ngày trong 30 ngày tới.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'customer_phone.regex' => 'Số điện thoại không hợp lệ.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'customer_email.required' => 'Vui lòng nhập email để nhận xác nhận lịch hẹn.',
        ]);

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($this->slotIsFull($validated['appointment_date'], $validated['appointment_time'])) {
            // Luong: Quay lai trang truoc kem du lieu hoac thong bao can hien thi.
            return back()
                // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
                ->withInput()
                // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
                ->withErrors(['appointment_time' => 'Khung giờ này đã có lịch hẹn. Vui lòng chọn khung giờ khác.']);
        }

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($this->slotIsPast($validated['appointment_date'], $validated['appointment_time'])) {
            // Luong: Quay lai trang truoc kem du lieu hoac thong bao can hien thi.
            return back()
                // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
                ->withInput()
                // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
                ->withErrors(['appointment_time' => 'Khung giờ này đã qua. Vui lòng chọn khung giờ khác.']);
        }

        // Luong: Gan ket qua xu ly vao bien $service.
        $service = self::SERVICES[$validated['service_code']];

        // Luong: Bat dau khoi xu ly co the phat sinh loi.
        try {
            // Luong: Tao ban ghi moi tu du lieu da chuan bi.
            $appointment = Appointment::create([
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'user_id' => Auth::id(),
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'code' => $this->generateCode(),
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'service_code' => $validated['service_code'],
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'service_name' => $service['name'],
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'price' => $service['price'],
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'appointment_date' => $validated['appointment_date'],
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'appointment_time' => $validated['appointment_time'],
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'slot_lock_key' => Appointment::slotLockKeyFor($validated['appointment_date'], $validated['appointment_time']),
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'customer_name' => $validated['customer_name'],
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'customer_phone' => $validated['customer_phone'],
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'customer_email' => $validated['customer_email'],
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'note' => $validated['note'] ?? null,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'status' => Appointment::STATUS_PENDING,
            ]);
        // Luong: Bat va xu ly loi phat sinh trong khoi try.
        } catch (QueryException $exception) {
            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
            if ($this->isSlotLockConflict($exception)) {
                // Luong: Quay lai trang truoc kem du lieu hoac thong bao can hien thi.
                return back()
                    // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
                    ->withInput()
                    // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
                    ->withErrors(['appointment_time' => 'Khung giờ này vừa có khách khác đặt. Vui lòng chọn khung giờ khác.']);
            }

            // Luong: Nem loi de dung luong khi dieu kien nghiep vu khong dat.
            throw $exception;
        }

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $notification->bookingReceived($appointment);

        // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
        return redirect()
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->route('appointments.create')
            // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
            ->with('appointment_code', $appointment->code);
    }

    public function lookup(Request $request): View
    {
        // Luong: Gan ket qua xu ly vao bien $appointment.
        $appointment = null;
        // Luong: Gan ket qua xu ly vao bien $lookupAttempted.
        $lookupAttempted = $request->filled('code') && $request->filled('contact');

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($lookupAttempted) {
            // Luong: Kiem tra va lay du lieu hop le tu request.
            $data = $request->validate([
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'code' => ['required', 'string', 'max:20'],
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'contact' => ['required', 'string', 'max:190'],
            ], [
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'code.required' => 'Vui lòng nhập mã lịch hẹn.',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'contact.required' => 'Vui lòng nhập email hoặc số điện thoại đã đặt lịch.',
            ]);

            // Luong: Gan ket qua xu ly vao bien $appointment.
            $appointment = $this->findByCodeAndContact($data['code'], $data['contact']);
        }

        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('appointments.lookup', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'appointment' => $appointment,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'lookupAttempted' => $lookupAttempted,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'timeSlots' => self::TIME_SLOTS,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'minDate' => now()->toDateString(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'maxDate' => now()->addDays(self::MAX_ADVANCE_DAYS)->toDateString(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'storeName' => self::STORE_NAME,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'storeAddress' => self::STORE_ADDRESS,
        ]);
    }

    public function reschedule(Request $request, Appointment $appointment, AppointmentNotificationService $notification): RedirectResponse
    {
        // Luong: Kiem tra va lay du lieu hop le tu request.
        $data = $request->validate([
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'code' => ['required', 'string', 'max:20'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'contact' => ['required', 'string', 'max:190'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'appointment_date' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:' . now()->addDays(self::MAX_ADVANCE_DAYS)->toDateString()],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'appointment_time' => ['required', 'string', 'in:' . implode(',', self::TIME_SLOTS)],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'reschedule_reason' => ['nullable', 'string', 'max:500'],
        ]);

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! $this->matchesContact($appointment, $data['code'], $data['contact'])) {
            // Luong: Quay lai trang truoc kem du lieu hoac thong bao can hien thi.
            return back()
                // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
                ->withInput()
                // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
                ->withErrors(['code' => 'Không tìm thấy lịch hẹn phù hợp với thông tin đã nhập.']);
        }

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! $appointment->canReschedule()) {
            // Luong: Quay lai trang truoc kem du lieu hoac thong bao can hien thi.
            return back()
                // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
                ->withInput()
                // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
                ->withErrors(['appointment_date' => 'Lịch hẹn này không còn được phép đổi lịch.']);
        }

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($this->slotIsFull($data['appointment_date'], $data['appointment_time'], $appointment->id)) {
            // Luong: Quay lai trang truoc kem du lieu hoac thong bao can hien thi.
            return back()
                // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
                ->withInput()
                // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
                ->withErrors(['appointment_time' => 'Khung giờ mới đã có lịch hẹn. Vui lòng chọn khung giờ khác.']);
        }

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($this->slotIsPast($data['appointment_date'], $data['appointment_time'])) {
            // Luong: Quay lai trang truoc kem du lieu hoac thong bao can hien thi.
            return back()
                // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
                ->withInput()
                // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
                ->withErrors(['appointment_time' => 'Khung giờ mới đã qua. Vui lòng chọn khung giờ khác.']);
        }

        // Luong: Bat dau khoi xu ly co the phat sinh loi.
        try {
            // Luong: Mo transaction de cac thao tac CSDL cung thanh cong hoac cung rollback.
            $result = DB::transaction(function () use ($appointment, $data): Appointment|string {
                // Luong: Gan ket qua xu ly vao bien $lockedAppointment.
                $lockedAppointment = Appointment::lockForUpdate()->find($appointment->id);

                // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
                if (! $lockedAppointment) {
                    // Luong: Tra ve ket qua cuoi cung cua ham.
                    return 'Không tìm thấy lịch hẹn.';
                }

                // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
                if (! $this->matchesContact($lockedAppointment, $data['code'], $data['contact'])) {
                    // Luong: Tra ve ket qua cuoi cung cua ham.
                    return 'Không tìm thấy lịch hẹn phù hợp với thông tin đã nhập.';
                }

                // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
                if (! $lockedAppointment->canReschedule()) {
                    // Luong: Tra ve ket qua cuoi cung cua ham.
                    return 'Lịch hẹn này không còn được phép đổi lịch.';
                }

                // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
                if ($this->slotIsFull($data['appointment_date'], $data['appointment_time'], $lockedAppointment->id)) {
                    // Luong: Tra ve ket qua cuoi cung cua ham.
                    return 'Khung giờ mới đã có lịch hẹn. Vui lòng chọn khung giờ khác.';
                }

                // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
                if ($this->slotIsPast($data['appointment_date'], $data['appointment_time'])) {
                    // Luong: Tra ve ket qua cuoi cung cua ham.
                    return 'Khung giờ mới đã qua. Vui lòng chọn khung giờ khác.';
                }

                // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
                $lockedAppointment->forceFill([
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'appointment_date' => $data['appointment_date'],
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'appointment_time' => $data['appointment_time'],
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'slot_lock_key' => Appointment::slotLockKeyFor($data['appointment_date'], $data['appointment_time']),
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'status' => Appointment::STATUS_PENDING,
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'confirmed_at' => null,
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'reschedule_count' => $lockedAppointment->reschedule_count + 1,
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'last_rescheduled_at' => now(),
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'reschedule_reason' => $data['reschedule_reason'] ?? null,
                ])->save();

                // Luong: Tra ve ket qua cuoi cung cua ham.
                return $lockedAppointment->fresh();
            });
        // Luong: Bat va xu ly loi phat sinh trong khoi try.
        } catch (QueryException $exception) {
            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
            if ($this->isSlotLockConflict($exception)) {
                // Luong: Quay lai trang truoc kem du lieu hoac thong bao can hien thi.
                return back()
                    // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
                    ->withInput()
                    // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
                    ->withErrors(['appointment_time' => 'Khung giờ mới vừa có khách khác đặt. Vui lòng chọn khung giờ khác.']);
            }

            // Luong: Nem loi de dung luong khi dieu kien nghiep vu khong dat.
            throw $exception;
        }

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (is_string($result)) {
            // Luong: Quay lai trang truoc kem du lieu hoac thong bao can hien thi.
            return back()->withInput()->withErrors(['appointment_date' => $result]);
        }

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $notification->rescheduled($result);

        // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
        return redirect()
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->route('appointments.lookup', [
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'code' => $result->code,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'contact' => $data['contact'],
            ])
            // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
            ->with('success', 'Đã tiếp nhận yêu cầu đổi lịch và gửi email cho bạn.');
    }

    public function unavailableSlots(Request $request): JsonResponse
    {
        // Luong: Kiem tra va lay du lieu hop le tu request.
        $data = $request->validate([
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'date' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:' . now()->addDays(self::MAX_ADVANCE_DAYS)->toDateString()],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'exclude_appointment_id' => ['nullable', 'integer'],
        ]);

        // Luong: Gan ket qua xu ly vao bien $excludeAppointmentId.
        $excludeAppointmentId = isset($data['exclude_appointment_id'])
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            ? (int) $data['exclude_appointment_id']
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            : null;

        // Luong: Gan ket qua xu ly vao bien $slots.
        $slots = collect(self::TIME_SLOTS)
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->mapWithKeys(function (string $slot) use ($data, $excludeAppointmentId): array {
                // Luong: Gan ket qua xu ly vao bien $isPast.
                $isPast = $this->slotIsPast($data['date'], $slot);
                // Luong: Gan ket qua xu ly vao bien $isFull.
                $isFull = $this->slotIsFull($data['date'], $slot, $excludeAppointmentId);

                // Luong: Tra ve ket qua cuoi cung cua ham.
                return [
                    // Luong: Gan ket qua xu ly vao bien $slot.
                    $slot => [
                        // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                        'available' => ! $isPast && ! $isFull,
                        // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                        'reason' => $isPast ? 'past' : ($isFull ? 'full' : null),
                        // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                        'label' => $isPast ? 'Đã qua' : ($isFull ? 'Đã đầy' : null),
                    ],
                ];
            });

        // Luong: Tra ve du lieu JSON cho client goi API.
        return response()->json([
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'date' => $data['date'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'slots' => $slots,
        ]);
    }

    private function generateCode(): string
    {
        do {
            $code = 'AO-' . now()->format('Ymd') . '-' . Str::upper(Str::random(4));
        } while (Appointment::where('code', $code)->exists());

        return $code;
    }

    private function slotIsFull(string $date, string $time, ?int $excludeAppointmentId = null): bool
    {
        $count = Appointment::query()
            ->where('appointment_date', Carbon::parse($date)->toDateString())
            ->where('appointment_time', $time)
            ->whereIn('status', Appointment::ACTIVE_SLOT_STATUSES)
            ->when($excludeAppointmentId !== null, fn ($query) => $query->whereKeyNot($excludeAppointmentId))
            ->count();

        return $count >= self::SLOT_CAPACITY;
    }

    private function isSlotLockConflict(QueryException $exception): bool
    {
        return str_contains($exception->getMessage(), 'appointments_slot_lock_key_unique')
            || str_contains($exception->getMessage(), 'slot_lock_key');
    }

    private function slotIsPast(string $date, string $time): bool
    {
        return Carbon::parse($date . ' ' . $time)->lte(now());
    }

    private function findByCodeAndContact(string $code, string $contact): ?Appointment
    {
        return Appointment::query()
            ->where('code', Str::upper(trim($code)))
            ->where(function ($query) use ($contact) {
                $normalizedContact = trim($contact);

                $query->where('customer_phone', $normalizedContact)
                    ->orWhereRaw('LOWER(customer_email) = ?', [Str::lower($normalizedContact)]);
            })
            ->first();
    }

    private function matchesContact(Appointment $appointment, string $code, string $contact): bool
    {
        $normalizedContact = trim($contact);

        return hash_equals($appointment->code, Str::upper(trim($code)))
            && (
                hash_equals((string) $appointment->customer_phone, $normalizedContact)
                || hash_equals(Str::lower((string) $appointment->customer_email), Str::lower($normalizedContact))
            );
    }
}
