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
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->send(
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            $appointment,
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            'Tiếp nhận lịch đo mắt ' . $appointment->code,
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            array_merge($this->greetingLines($appointment), [
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'Hệ thống đã tiếp nhận lịch đo mắt của bạn.',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'Lịch hẹn đang chờ nhân viên cửa hàng xác nhận.',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                '',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                ...$this->appointmentLines($appointment),
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                '',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'Bạn có thể tra cứu lịch hẹn bằng mã lịch và email hoặc số điện thoại đã đặt.',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'Link tra cứu: ' . route('appointments.lookup', ['code' => $appointment->code]),
            ])
        );
    }

    public function confirmed(Appointment $appointment): bool
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->send(
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            $appointment,
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            'Xác nhận lịch đo mắt ' . $appointment->code,
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            array_merge($this->greetingLines($appointment), [
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'Lịch đo mắt của bạn đã được cửa hàng xác nhận.',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                '',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                ...$this->appointmentLines($appointment),
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                '',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'Vui lòng có mặt trước giờ hẹn 10 phút.',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'Cần hỗ trợ, vui lòng liên hệ hotline ' . self::HOTLINE . '.',
            ])
        );
    }

    public function cancelled(Appointment $appointment): bool
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->send(
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            $appointment,
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            'Thông báo hủy lịch đo mắt ' . $appointment->code,
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            array_merge($this->greetingLines($appointment), [
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'Lịch đo mắt của bạn đã được hủy.',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                '',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                ...$this->appointmentLines($appointment),
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'Lý do hủy: ' . ($appointment->cancel_reason ?: '-'),
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                '',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'Bạn có thể đặt lịch mới trên website hoặc liên hệ hotline ' . self::HOTLINE . ' để được hỗ trợ.',
            ])
        );
    }

    public function rescheduled(Appointment $appointment): bool
    {
        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this->send(
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            $appointment,
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            'Tiếp nhận đổi lịch đo mắt ' . $appointment->code,
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            array_merge($this->greetingLines($appointment), [
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'Hệ thống đã tiếp nhận thông tin đổi lịch của bạn.',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'Lịch hẹn mới đang chờ nhân viên cửa hàng xác nhận lại.',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                '',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                ...$this->appointmentLines($appointment),
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'Lý do đổi lịch: ' . ($appointment->reschedule_reason ?: '-'),
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                '',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'Link tra cứu: ' . route('appointments.lookup', ['code' => $appointment->code]),
            ])
        );
    }

    public function reminder(Appointment $appointment): bool
    {
        // Luong: Gan ket qua xu ly vao bien $sent.
        $sent = $this->send(
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            $appointment,
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            'Nhắc lịch đo mắt ' . $appointment->code,
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            array_merge($this->greetingLines($appointment), [
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'Cửa hàng nhắc bạn về lịch đo mắt sắp tới.',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                '',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                ...$this->appointmentLines($appointment),
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                '',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'Vui lòng có mặt trước giờ hẹn 10 phút.',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'Cần đổi lịch, bạn có thể tra cứu bằng mã lịch trên website hoặc liên hệ hotline ' . self::HOTLINE . '.',
            ])
        );

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($sent) {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $appointment->forceFill(['reminder_email_sent_at' => now()])->save();
        }

        // Luong: Tra ve ket qua cuoi cung cua ham.
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
