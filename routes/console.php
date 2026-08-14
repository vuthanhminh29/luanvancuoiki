<?php

use App\Models\Appointment;
use App\Services\AppointmentNotificationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('appointments:send-reminders', function () {
    $notification = app(AppointmentNotificationService::class);
    $sentCount = 0;

    Appointment::query()
        ->where('status', Appointment::STATUS_CONFIRMED)
        ->whereNull('reminder_email_sent_at')
        ->whereBetween('appointment_date', [today(), today()->addDay()])
        ->orderBy('appointment_date')
        ->orderBy('appointment_time')
        ->chunkById(100, function ($appointments) use ($notification, &$sentCount) {
            foreach ($appointments as $appointment) {
                $scheduledAt = $appointment->scheduledAt();

                if (! $scheduledAt || ! $scheduledAt->between(now(), now()->addHours(24))) {
                    continue;
                }

                if ($notification->reminder($appointment)) {
                    $sentCount++;
                }
            }
        });

    $this->info("Sent {$sentCount} appointment reminder email(s).");
})->purpose('Send reminder emails for confirmed eye exam appointments');
