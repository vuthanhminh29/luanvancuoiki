<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendRawMailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    private string $to;
    private string $subject;
    private string $body;

    public function __construct(string $to, string $subject, string $body)
    {
        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->to = $to;
        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->subject = $subject;
        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->body = $body;
    }

    public function handle(): void
    {
        // Luong: Gui email dang text theo noi dung da tao.
        Mail::raw(
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $this->body,
            // Luong: Dinh nghia callback ngan gon cho thao tac hien tai.
            fn ($message) => $message
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->to($this->to)
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->subject($this->subject)
        );
    }

    public function failed(\Throwable $exception): void
    {
        // Luong: Ghi log de theo doi va chan doan qua trinh xu ly.
        Log::error('Queued raw email could not be sent.', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'to' => $this->to,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'subject' => $this->subject,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'message' => $exception->getMessage(),
        ]);
    }
}
