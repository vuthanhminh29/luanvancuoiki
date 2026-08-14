<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Services\AppointmentNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        $confirmedCode = $request->session()->get('appointment_code');
        $confirmed = $confirmedCode ? Appointment::where('code', $confirmedCode)->first() : null;

        return view('appointments.create', [
            'services' => self::SERVICES,
            'timeSlots' => self::TIME_SLOTS,
            'storeName' => self::STORE_NAME,
            'storeAddress' => self::STORE_ADDRESS,
            'confirmed' => $confirmed,
            'minDate' => now()->toDateString(),
            'maxDate' => now()->addDays(self::MAX_ADVANCE_DAYS)->toDateString(),
        ]);
    }

    public function store(Request $request, AppointmentNotificationService $notification): RedirectResponse
    {
        $validated = $request->validate([
            'service_code' => ['required', 'string', 'in:' . implode(',', array_keys(self::SERVICES))],
            'appointment_date' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:' . now()->addDays(self::MAX_ADVANCE_DAYS)->toDateString()],
            'appointment_time' => ['required', 'string', 'in:' . implode(',', self::TIME_SLOTS)],
            'customer_name' => ['required', 'string', 'max:100'],
            'customer_phone' => ['required', 'string', 'regex:/^[0-9+ ]{9,15}$/'],
            'customer_email' => ['required', 'email', 'max:190'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'appointment_date.after_or_equal' => 'Vui lòng chọn ngày từ hôm nay trở đi.',
            'appointment_date.before_or_equal' => 'Vui lòng chọn ngày trong 30 ngày tới.',
            'customer_phone.regex' => 'Số điện thoại không hợp lệ.',
            'customer_email.required' => 'Vui lòng nhập email để nhận xác nhận lịch hẹn.',
        ]);

        if ($this->slotIsFull($validated['appointment_date'], $validated['appointment_time'])) {
            return back()
                ->withInput()
                ->withErrors(['appointment_time' => 'Khung giờ này đã có lịch hẹn. Vui lòng chọn khung giờ khác.']);
        }

        if ($this->slotIsPast($validated['appointment_date'], $validated['appointment_time'])) {
            return back()
                ->withInput()
                ->withErrors(['appointment_time' => 'Khung giờ này đã qua. Vui lòng chọn khung giờ khác.']);
        }

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
            'customer_email' => $validated['customer_email'],
            'note' => $validated['note'] ?? null,
            'status' => Appointment::STATUS_PENDING,
        ]);

        $notification->bookingReceived($appointment);

        return redirect()
            ->route('appointments.create')
            ->with('appointment_code', $appointment->code);
    }

    public function lookup(Request $request): View
    {
        $appointment = null;
        $lookupAttempted = $request->filled('code') && $request->filled('contact');

        if ($lookupAttempted) {
            $data = $request->validate([
                'code' => ['required', 'string', 'max:20'],
                'contact' => ['required', 'string', 'max:190'],
            ], [
                'code.required' => 'Vui lòng nhập mã lịch hẹn.',
                'contact.required' => 'Vui lòng nhập email hoặc số điện thoại đã đặt lịch.',
            ]);

            $appointment = $this->findByCodeAndContact($data['code'], $data['contact']);
        }

        return view('appointments.lookup', [
            'appointment' => $appointment,
            'lookupAttempted' => $lookupAttempted,
            'timeSlots' => self::TIME_SLOTS,
            'minDate' => now()->toDateString(),
            'maxDate' => now()->addDays(self::MAX_ADVANCE_DAYS)->toDateString(),
            'storeName' => self::STORE_NAME,
            'storeAddress' => self::STORE_ADDRESS,
        ]);
    }

    public function reschedule(Request $request, Appointment $appointment, AppointmentNotificationService $notification): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20'],
            'contact' => ['required', 'string', 'max:190'],
            'appointment_date' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:' . now()->addDays(self::MAX_ADVANCE_DAYS)->toDateString()],
            'appointment_time' => ['required', 'string', 'in:' . implode(',', self::TIME_SLOTS)],
            'reschedule_reason' => ['nullable', 'string', 'max:500'],
        ]);

        if (! $this->matchesContact($appointment, $data['code'], $data['contact'])) {
            return back()
                ->withInput()
                ->withErrors(['code' => 'Không tìm thấy lịch hẹn phù hợp với thông tin đã nhập.']);
        }

        if (! $appointment->canReschedule()) {
            return back()
                ->withInput()
                ->withErrors(['appointment_date' => 'Lịch hẹn này không còn được phép đổi lịch.']);
        }

        if ($this->slotIsFull($data['appointment_date'], $data['appointment_time'], $appointment->id)) {
            return back()
                ->withInput()
                ->withErrors(['appointment_time' => 'Khung giờ mới đã có lịch hẹn. Vui lòng chọn khung giờ khác.']);
        }

        if ($this->slotIsPast($data['appointment_date'], $data['appointment_time'])) {
            return back()
                ->withInput()
                ->withErrors(['appointment_time' => 'Khung giờ mới đã qua. Vui lòng chọn khung giờ khác.']);
        }

        $result = DB::transaction(function () use ($appointment, $data): Appointment|string {
            $lockedAppointment = Appointment::lockForUpdate()->find($appointment->id);

            if (! $lockedAppointment) {
                return 'Không tìm thấy lịch hẹn.';
            }

            if (! $this->matchesContact($lockedAppointment, $data['code'], $data['contact'])) {
                return 'Không tìm thấy lịch hẹn phù hợp với thông tin đã nhập.';
            }

            if (! $lockedAppointment->canReschedule()) {
                return 'Lịch hẹn này không còn được phép đổi lịch.';
            }

            if ($this->slotIsFull($data['appointment_date'], $data['appointment_time'], $lockedAppointment->id)) {
                return 'Khung giờ mới đã có lịch hẹn. Vui lòng chọn khung giờ khác.';
            }

            if ($this->slotIsPast($data['appointment_date'], $data['appointment_time'])) {
                return 'Khung giờ mới đã qua. Vui lòng chọn khung giờ khác.';
            }

            $lockedAppointment->forceFill([
                'appointment_date' => $data['appointment_date'],
                'appointment_time' => $data['appointment_time'],
                'status' => Appointment::STATUS_PENDING,
                'confirmed_at' => null,
                'reschedule_count' => $lockedAppointment->reschedule_count + 1,
                'last_rescheduled_at' => now(),
                'reschedule_reason' => $data['reschedule_reason'] ?? null,
            ])->save();

            return $lockedAppointment->fresh();
        });

        if (is_string($result)) {
            return back()->withInput()->withErrors(['appointment_date' => $result]);
        }

        $notification->rescheduled($result);

        return redirect()
            ->route('appointments.lookup', [
                'code' => $result->code,
                'contact' => $data['contact'],
            ])
            ->with('success', 'Đã tiếp nhận yêu cầu đổi lịch và gửi email cho bạn.');
    }

    public function unavailableSlots(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:' . now()->addDays(self::MAX_ADVANCE_DAYS)->toDateString()],
            'exclude_appointment_id' => ['nullable', 'integer'],
        ]);

        $excludeAppointmentId = isset($data['exclude_appointment_id'])
            ? (int) $data['exclude_appointment_id']
            : null;

        $slots = collect(self::TIME_SLOTS)
            ->mapWithKeys(function (string $slot) use ($data, $excludeAppointmentId): array {
                $isPast = $this->slotIsPast($data['date'], $slot);
                $isFull = $this->slotIsFull($data['date'], $slot, $excludeAppointmentId);

                return [
                    $slot => [
                        'available' => ! $isPast && ! $isFull,
                        'reason' => $isPast ? 'past' : ($isFull ? 'full' : null),
                        'label' => $isPast ? 'Đã qua' : ($isFull ? 'Đã đầy' : null),
                    ],
                ];
            });

        return response()->json([
            'date' => $data['date'],
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
            ->whereDate('appointment_date', $date)
            ->where('appointment_time', $time)
            ->whereIn('status', Appointment::ACTIVE_SLOT_STATUSES)
            ->when($excludeAppointmentId !== null, fn ($query) => $query->whereKeyNot($excludeAppointmentId))
            ->count();

        return $count >= self::SLOT_CAPACITY;
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
