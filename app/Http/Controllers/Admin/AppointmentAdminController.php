<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\AppointmentNotificationService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AppointmentAdminController extends Controller
{
    public function index(Request $request): View
    {
        // Luong: Gan ket qua xu ly vao bien $filters.
        $filters = [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'date' => trim((string) $request->query('date', '')),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'status' => trim((string) $request->query('status', '')),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'keyword' => trim((string) $request->query('keyword', '')),
        ];

        // Luong: Gan ket qua xu ly vao bien $appointments.
        $appointments = Appointment::query()
            // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
            ->with('user')
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->when($filters['date'] !== '', fn ($query) => $query->whereDate('appointment_date', $filters['date']))
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->when($filters['keyword'] !== '', function ($query) use ($filters) {
                // Luong: Gan ket qua xu ly vao bien $keyword.
                $keyword = '%' . $filters['keyword'] . '%';

                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                $query->where(function ($inner) use ($keyword) {
                    // Luong: Bo sung dieu kien loc du lieu cho truy van.
                    $inner->where('code', 'like', $keyword)
                        // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                        ->orWhere('customer_name', 'like', $keyword)
                        // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                        ->orWhere('customer_phone', 'like', $keyword)
                        // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                        ->orWhere('customer_email', 'like', $keyword);
                });
            })
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            ->orderBy('appointment_date')
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            ->orderBy('appointment_time')
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            ->latest('id')
            // Luong: Thuc thi truy van va lay ket qua tu CSDL.
            ->paginate(15)
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->withQueryString();

        // Luong: Bat dau khoi xu ly co the phat sinh loi.
        try {
            // Luong: Gan ket qua xu ly vao bien $selectedDate.
            $selectedDate = $filters['date'] !== '' ? Carbon::parse($filters['date']) : today();
        // Luong: Bat va xu ly loi phat sinh trong khoi try.
        } catch (\Throwable) {
            // Luong: Gan ket qua xu ly vao bien $selectedDate.
            $selectedDate = today();
        }

        // Luong: Gan ket qua xu ly vao bien $weekStart.
        $weekStart = $selectedDate->copy()->startOfWeek(Carbon::MONDAY);
        // Luong: Gan ket qua xu ly vao bien $weekEnd.
        $weekEnd = $selectedDate->copy()->endOfWeek(Carbon::SUNDAY);

        // Luong: Gan ket qua xu ly vao bien $calendarAppointments.
        $calendarAppointments = Appointment::query()
            // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
            ->with('user')
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->whereBetween('appointment_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->when($filters['keyword'] !== '', function ($query) use ($filters) {
                // Luong: Gan ket qua xu ly vao bien $keyword.
                $keyword = '%' . $filters['keyword'] . '%';

                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                $query->where(function ($inner) use ($keyword) {
                    // Luong: Bo sung dieu kien loc du lieu cho truy van.
                    $inner->where('code', 'like', $keyword)
                        // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                        ->orWhere('customer_name', 'like', $keyword)
                        // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                        ->orWhere('customer_phone', 'like', $keyword)
                        // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                        ->orWhere('customer_email', 'like', $keyword);
                });
            })
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            ->orderBy('appointment_date')
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            ->orderBy('appointment_time')
            // Luong: Thuc thi truy van va lay ket qua tu CSDL.
            ->get();

        // Luong: Gan ket qua xu ly vao bien $summary.
        $summary = [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'total' => Appointment::count(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'pending' => Appointment::where('status', Appointment::STATUS_PENDING)->count(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'confirmed' => Appointment::where('status', Appointment::STATUS_CONFIRMED)->count(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'today' => Appointment::whereDate('appointment_date', today())->count(),
        ];

        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('admin.appointments.index', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'appointments' => $appointments,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'filters' => $filters,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'summary' => $summary,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'statuses' => $this->statuses(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'calendarAppointments' => $calendarAppointments,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'selectedDate' => $selectedDate,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'weekStart' => $weekStart,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'weekEnd' => $weekEnd,
        ]);
    }

    public function confirm(Appointment $appointment, AppointmentNotificationService $notification): RedirectResponse
    {
        // Luong: Bat dau khoi xu ly co the phat sinh loi.
        try {
            // Luong: Mo transaction de cac thao tac CSDL cung thanh cong hoac cung rollback.
            $result = DB::transaction(function () use ($appointment): true|string {
                // Luong: Gan ket qua xu ly vao bien $lockedAppointment.
                $lockedAppointment = Appointment::lockForUpdate()->find($appointment->id);

                // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
                if (! $lockedAppointment) {
                    // Luong: Tra ve ket qua cuoi cung cua ham.
                    return 'Không tìm thấy lịch hẹn.';
                }

                // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
                if (! $lockedAppointment->canConfirm()) {
                    // Luong: Tra ve ket qua cuoi cung cua ham.
                    return 'Chỉ lịch đang chờ xác nhận mới có thể xác nhận.';
                }

                // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
                $lockedAppointment->forceFill([
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'status' => Appointment::STATUS_CONFIRMED,
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'confirmed_at' => now(),
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'slot_lock_key' => $lockedAppointment->slot_lock_key
                        // Luong: Xu ly dong logic tiep theo trong ham public nay.
                        ?: Appointment::slotLockKeyFor(
                            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
                            $lockedAppointment->appointment_date->format('Y-m-d'),
                            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
                            $lockedAppointment->appointment_time
                        ),
                ])->save();

                // Luong: Tra ve ket qua cuoi cung cua ham.
                return true;
            });
        // Luong: Bat va xu ly loi phat sinh trong khoi try.
        } catch (QueryException $exception) {
            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
            if (str_contains($exception->getMessage(), 'slot_lock_key')) {
                // Luong: Quay lai trang truoc kem du lieu hoac thong bao can hien thi.
                return back()->with('error', 'Khung giờ này đang bị trùng với lịch khác. Vui lòng đổi hoặc hủy lịch trùng trước.');
            }

            // Luong: Nem loi de dung luong khi dieu kien nghiep vu khong dat.
            throw $exception;
        }

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($result !== true) {
            // Luong: Quay lai trang truoc kem du lieu hoac thong bao can hien thi.
            return back()->with('error', $result);
        }

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $notification->confirmed($appointment->fresh());

        // Luong: Quay lai trang truoc kem du lieu hoac thong bao can hien thi.
        return back()->with('success', 'Đã xác nhận lịch hẹn và gửi email cho khách.');
    }

    public function cancel(Request $request, Appointment $appointment, AppointmentNotificationService $notification): RedirectResponse
    {
        // Luong: Kiem tra va lay du lieu hop le tu request.
        $data = $request->validate([
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'cancel_reason' => ['required', 'string', 'max:500'],
        ], [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'cancel_reason.required' => 'Vui lòng nhập lý do hủy lịch.',
        ]);

        // Luong: Mo transaction de cac thao tac CSDL cung thanh cong hoac cung rollback.
        $result = DB::transaction(function () use ($appointment, $data): true|string {
            // Luong: Gan ket qua xu ly vao bien $lockedAppointment.
            $lockedAppointment = Appointment::lockForUpdate()->find($appointment->id);

            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
            if (! $lockedAppointment) {
                // Luong: Tra ve ket qua cuoi cung cua ham.
                return 'Không tìm thấy lịch hẹn.';
            }

            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
            if (! $lockedAppointment->canCancel()) {
                // Luong: Tra ve ket qua cuoi cung cua ham.
                return 'Lịch hẹn này không còn được phép hủy.';
            }

            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $lockedAppointment->forceFill([
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'status' => Appointment::STATUS_CANCELLED,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'cancelled_at' => now(),
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'cancel_reason' => $data['cancel_reason'],
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'slot_lock_key' => null,
            ])->save();

            // Luong: Tra ve ket qua cuoi cung cua ham.
            return true;
        });

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($result !== true) {
            // Luong: Quay lai trang truoc kem du lieu hoac thong bao can hien thi.
            return back()->with('error', $result);
        }

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $notification->cancelled($appointment->fresh());

        // Luong: Quay lai trang truoc kem du lieu hoac thong bao can hien thi.
        return back()->with('success', 'Đã hủy lịch hẹn và gửi email thông báo cho khách.');
    }

    public function complete(Appointment $appointment): RedirectResponse
    {
        // Luong: Mo transaction de cac thao tac CSDL cung thanh cong hoac cung rollback.
        $result = DB::transaction(function () use ($appointment): true|string {
            // Luong: Gan ket qua xu ly vao bien $lockedAppointment.
            $lockedAppointment = Appointment::lockForUpdate()->find($appointment->id);

            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
            if (! $lockedAppointment) {
                // Luong: Tra ve ket qua cuoi cung cua ham.
                return 'Không tìm thấy lịch hẹn.';
            }

            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
            if (! $lockedAppointment->canComplete()) {
                // Luong: Tra ve ket qua cuoi cung cua ham.
                return 'Chỉ lịch đã xác nhận mới có thể hoàn tất.';
            }

            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $lockedAppointment->forceFill([
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'status' => Appointment::STATUS_COMPLETED,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'completed_at' => now(),
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'slot_lock_key' => null,
            ])->save();

            // Luong: Tra ve ket qua cuoi cung cua ham.
            return true;
        });

        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $result === true
            // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
            ? back()->with('success', 'Đã đánh dấu lịch hẹn hoàn tất.')
            // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
            : back()->with('error', $result);
    }

    public function noShow(Appointment $appointment): RedirectResponse
    {
        // Luong: Mo transaction de cac thao tac CSDL cung thanh cong hoac cung rollback.
        $result = DB::transaction(function () use ($appointment): true|string {
            // Luong: Gan ket qua xu ly vao bien $lockedAppointment.
            $lockedAppointment = Appointment::lockForUpdate()->find($appointment->id);

            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
            if (! $lockedAppointment) {
                // Luong: Tra ve ket qua cuoi cung cua ham.
                return 'Không tìm thấy lịch hẹn.';
            }

            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
            if (! $lockedAppointment->canMarkNoShow()) {
                // Luong: Tra ve ket qua cuoi cung cua ham.
                return 'Chỉ lịch đã xác nhận và đã qua giờ hẹn mới có thể đánh dấu khách không đến.';
            }

            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $lockedAppointment->forceFill([
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'status' => Appointment::STATUS_NO_SHOW,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'no_show_at' => now(),
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'slot_lock_key' => null,
            ])->save();

            // Luong: Tra ve ket qua cuoi cung cua ham.
            return true;
        });

        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $result === true
            // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
            ? back()->with('success', 'Đã đánh dấu khách không đến.')
            // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
            : back()->with('error', $result);
    }

    private function statuses(): array
    {
        return [
            Appointment::STATUS_PENDING => 'Chờ xác nhận',
            Appointment::STATUS_CONFIRMED => 'Đã xác nhận',
            Appointment::STATUS_COMPLETED => 'Hoàn tất',
            Appointment::STATUS_CANCELLED => 'Đã hủy',
            Appointment::STATUS_NO_SHOW => 'Khách không đến',
        ];
    }
}
