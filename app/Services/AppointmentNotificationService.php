<?php

namespace App\Services;

use App\Models\Appointment;
use App\Support\QueuedRawMail as Mail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class AppointmentNotificationService
{
    private const STORE_NAME = 'Atelier Optique Studio';
    private const STORE_ADDRESS = '123 Nguyễn Trãi, P. Bến Thành, Q.1, TP.HCM';
    private const HOTLINE = '1900 6789';

    public function bookingReceived(Appointment $appointment): bool
    {
        return $this->send(
            $appointment,
            'Tiếp nhận lịch đo mắt ' . $appointment->code,
            array_merge($this->greetingLines($appointment), [
                'Hệ thống đã tiếp nhận lịch đo mắt của bạn.',
                'Lịch hẹn đang chờ nhân viên cửa hàng xác nhận.',
                '',
                ...$this->appointmentLines($appointment),
                '',
                'Bạn có thể tra cứu lịch hẹn bằng mã lịch và email hoặc số điện thoại đã đặt.',
                'Link tra cứu: ' . route('appointments.lookup', ['code' => $appointment->code]),
            ])
        );
    }

    public function confirmed(Appointment $appointment): bool
    {
        return $this->send(
            $appointment,
            'Xác nhận lịch đo mắt ' . $appointment->code,
            array_merge($this->greetingLines($appointment), [
                'Lịch đo mắt của bạn đã được cửa hàng xác nhận.',
                '',
                ...$this->appointmentLines($appointment),
                '',
                'Vui lòng có mặt trước giờ hẹn 10 phút.',
                'Cần hỗ trợ, vui lòng liên hệ hotline ' . self::HOTLINE . '.',
            ])
        );
    }

    public function cancelled(Appointment $appointment): bool
    {
        return $this->send(
            $appointment,
            'Thông báo hủy lịch đo mắt ' . $appointment->code,
            array_merge($this->greetingLines($appointment), [
                'Lịch đo mắt của bạn đã được hủy.',
                '',
                ...$this->appointmentLines($appointment),
                'Lý do hủy: ' . ($appointment->cancel_reason ?: '-'),
                '',
                'Bạn có thể đặt lịch mới trên website hoặc liên hệ hotline ' . self::HOTLINE . ' để được hỗ trợ.',
            ])
        );
    }

    public function rescheduled(Appointment $appointment): bool
    {
        return $this->send(
            $appointment,
            'Tiếp nhận đổi lịch đo mắt ' . $appointment->code,
            array_merge($this->greetingLines($appointment), [
                'Hệ thống đã tiếp nhận thông tin đổi lịch của bạn.',
                'Lịch hẹn mới đang chờ nhân viên cửa hàng xác nhận lại.',
                '',
                ...$this->appointmentLines($appointment),
                'Lý do đổi lịch: ' . ($appointment->reschedule_reason ?: '-'),
                '',
                'Link tra cứu: ' . route('appointments.lookup', ['code' => $appointment->code]),
            ])
        );
    }

    public function reminder(Appointment $appointment): bool
    {
        $sent = $this->send(
            $appointment,
            'Nhắc lịch đo mắt ' . $appointment->code,
            array_merge($this->greetingLines($appointment), [
                'Cửa hàng nhắc bạn về lịch đo mắt sắp tới.',
                '',
                ...$this->appointmentLines($appointment),
                '',
                'Vui lòng có mặt trước giờ hẹn 10 phút.',
                'Cần đổi lịch, bạn có thể tra cứu bằng mã lịch trên website hoặc liên hệ hotline ' . self::HOTLINE . '.',
            ])
        );

        if ($sent) {
            $appointment->forceFill(['reminder_email_sent_at' => now()])->save();
        }

        return $sent;
    }

    private function send(Appointment $appointment, string $subject, array $lines): bool
    {
        $email = trim((string) $appointment->customer_email);

        if ($email === '') {
            Log::warning('Appointment email skipped because customer email is missing.', [
                'appointment_id' => $appointment->id,
                'code' => $appointment->code,
            ]);

            return false;
        }

        try {
            Mail::raw(
                implode("\n", $lines),
                fn ($message) => $message
                    ->to($email)
                    ->subject($subject)
            );

            return true;
        } catch (\Throwable $exception) {
            Log::error('Appointment email could not be sent.', [
                'appointment_id' => $appointment->id,
                'code' => $appointment->code,
                'email' => $email,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function greetingLines(Appointment $appointment): array
    {
        return [
            'Xin chào ' . ($appointment->customer_name ?: 'quý khách') . ',',
            '',
        ];
    }

    private function appointmentLines(Appointment $appointment): array
    {
        return [
            'THÔNG TIN LỊCH HẸN',
            'Mã lịch hẹn: ' . $appointment->code,
            'Dịch vụ: ' . $appointment->service_name,
            'Thời gian: ' . $this->appointmentTime($appointment),
            'Địa điểm: ' . self::STORE_NAME . ' - ' . self::STORE_ADDRESS,
            'Số điện thoại: ' . $appointment->customer_phone,
            'Trạng thái: ' . $appointment->statusLabel(),
        ];
    }

    private function appointmentTime(Appointment $appointment): string
    {
        $date = $appointment->appointment_date
            ? Carbon::parse($appointment->appointment_date)->format('d/m/Y')
            : '-';

        return trim((string) $appointment->appointment_time) . ', ' . $date;
    }
}
