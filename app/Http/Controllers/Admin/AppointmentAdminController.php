<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\AppointmentNotificationService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AppointmentAdminController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'date' => trim((string) $request->query('date', '')),
            'status' => trim((string) $request->query('status', '')),
            'keyword' => trim((string) $request->query('keyword', '')),
        ];

        $appointments = Appointment::query()
            ->with('user')
            ->when($filters['date'] !== '', fn ($query) => $query->whereDate('appointment_date', $filters['date']))
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['keyword'] !== '', function ($query) use ($filters) {
                $keyword = '%' . $filters['keyword'] . '%';

                $query->where(function ($inner) use ($keyword) {
                    $inner->where('code', 'like', $keyword)
                        ->orWhere('customer_name', 'like', $keyword)
                        ->orWhere('customer_phone', 'like', $keyword)
                        ->orWhere('customer_email', 'like', $keyword);
                });
            })
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $summary = [
            'total' => Appointment::count(),
            'pending' => Appointment::where('status', Appointment::STATUS_PENDING)->count(),
            'confirmed' => Appointment::where('status', Appointment::STATUS_CONFIRMED)->count(),
            'today' => Appointment::whereDate('appointment_date', today())->count(),
        ];

        return view('admin.appointments.index', [
            'appointments' => $appointments,
            'filters' => $filters,
            'summary' => $summary,
            'statuses' => $this->statuses(),
        ]);
    }

    public function confirm(Appointment $appointment, AppointmentNotificationService $notification): RedirectResponse
    {
        try {
            $result = DB::transaction(function () use ($appointment): true|string {
                $lockedAppointment = Appointment::lockForUpdate()->find($appointment->id);

                if (! $lockedAppointment) {
                    return 'Không tìm thấy lịch hẹn.';
                }

                if (! $lockedAppointment->canConfirm()) {
                    return 'Chỉ lịch đang chờ xác nhận mới có thể xác nhận.';
                }

                $lockedAppointment->forceFill([
                    'status' => Appointment::STATUS_CONFIRMED,
                    'confirmed_at' => now(),
                    'slot_lock_key' => $lockedAppointment->slot_lock_key
                        ?: Appointment::slotLockKeyFor(
                            $lockedAppointment->appointment_date->format('Y-m-d'),
                            $lockedAppointment->appointment_time
                        ),
                ])->save();

                return true;
            });
        } catch (QueryException $exception) {
            if (str_contains($exception->getMessage(), 'slot_lock_key')) {
                return back()->with('error', 'Khung giờ này đang bị trùng với lịch khác. Vui lòng đổi hoặc hủy lịch trùng trước.');
            }

            throw $exception;
        }

        if ($result !== true) {
            return back()->with('error', $result);
        }

        $notification->confirmed($appointment->fresh());

        return back()->with('success', 'Đã xác nhận lịch hẹn và gửi email cho khách.');
    }

    public function cancel(Request $request, Appointment $appointment, AppointmentNotificationService $notification): RedirectResponse
    {
        $data = $request->validate([
            'cancel_reason' => ['required', 'string', 'max:500'],
        ], [
            'cancel_reason.required' => 'Vui lòng nhập lý do hủy lịch.',
        ]);

        $result = DB::transaction(function () use ($appointment, $data): true|string {
            $lockedAppointment = Appointment::lockForUpdate()->find($appointment->id);

            if (! $lockedAppointment) {
                return 'Không tìm thấy lịch hẹn.';
            }

            if (! $lockedAppointment->canCancel()) {
                return 'Lịch hẹn này không còn được phép hủy.';
            }

            $lockedAppointment->forceFill([
                'status' => Appointment::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancel_reason' => $data['cancel_reason'],
                'slot_lock_key' => null,
            ])->save();

            return true;
        });

        if ($result !== true) {
            return back()->with('error', $result);
        }

        $notification->cancelled($appointment->fresh());

        return back()->with('success', 'Đã hủy lịch hẹn và gửi email thông báo cho khách.');
    }

    public function complete(Appointment $appointment): RedirectResponse
    {
        $result = DB::transaction(function () use ($appointment): true|string {
            $lockedAppointment = Appointment::lockForUpdate()->find($appointment->id);

            if (! $lockedAppointment) {
                return 'Không tìm thấy lịch hẹn.';
            }

            if (! $lockedAppointment->canComplete()) {
                return 'Chỉ lịch đã xác nhận mới có thể hoàn tất.';
            }

            $lockedAppointment->forceFill([
                'status' => Appointment::STATUS_COMPLETED,
                'completed_at' => now(),
                'slot_lock_key' => null,
            ])->save();

            return true;
        });

        return $result === true
            ? back()->with('success', 'Đã đánh dấu lịch hẹn hoàn tất.')
            : back()->with('error', $result);
    }

    public function noShow(Appointment $appointment): RedirectResponse
    {
        $result = DB::transaction(function () use ($appointment): true|string {
            $lockedAppointment = Appointment::lockForUpdate()->find($appointment->id);

            if (! $lockedAppointment) {
                return 'Không tìm thấy lịch hẹn.';
            }

            if (! $lockedAppointment->canMarkNoShow()) {
                return 'Chỉ lịch đã xác nhận và đã qua giờ hẹn mới có thể đánh dấu khách không đến.';
            }

            $lockedAppointment->forceFill([
                'status' => Appointment::STATUS_NO_SHOW,
                'no_show_at' => now(),
                'slot_lock_key' => null,
            ])->save();

            return true;
        });

        return $result === true
            ? back()->with('success', 'Đã đánh dấu khách không đến.')
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
