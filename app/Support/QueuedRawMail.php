<?php

declare(strict_types=1);

namespace App\Support;

use App\Jobs\SendRawMailJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail as LaravelMail;

class QueuedRawMail
{
    public static function raw(string $body, callable $callback): void
    {
        // Luong: Gan ket qua xu ly vao bien $message.
        $message = new QueuedRawMailMessage();
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        $callback($message);

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($message->to === null || $message->subject === null) {
            // Luong: Nem loi de dung luong khi dieu kien nghiep vu khong dat.
            throw new \RuntimeException('Queued raw mail requires both recipient and subject.');
        }

        // Luong: Bat dau khoi xu ly co the phat sinh loi.
        try {
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            SendRawMailJob::dispatch($message->to, $message->subject, $body);

            // Luong: Tra ve ket qua cuoi cung cua ham.
            return;
        // Luong: Bat va xu ly loi phat sinh trong khoi try.
        } catch (\Throwable $exception) {
            // Luong: Ghi log de theo doi va chan doan qua trinh xu ly.
            Log::warning('Queued raw mail dispatch failed; sending raw mail directly.', [
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'to' => $message->to,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'subject' => $message->subject,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'message' => $exception->getMessage(),
            ]);
        }

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        app()->terminating(function () use ($body, $message) {
            // Luong: Bat dau khoi xu ly co the phat sinh loi.
            try {
                // Luong: Gui email dang text theo noi dung da tao.
                LaravelMail::raw(
                    // Luong: Xu ly dong logic tiep theo trong ham public nay.
                    $body,
                    // Luong: Dinh nghia callback ngan gon cho thao tac hien tai.
                    fn ($mail) => $mail
                        // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                        ->to($message->to)
                        // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                        ->subject($message->subject)
                );
            // Luong: Bat va xu ly loi phat sinh trong khoi try.
            } catch (\Throwable $e) {
                // Luong: Ghi log de theo doi va chan doan qua trinh xu ly.
                Log::error('Failed sending raw mail in terminating callback', ['error' => $e->getMessage()]);
            }
        });
    }
}

class QueuedRawMailMessage
{
    public ?string $to = null;

    public ?string $subject = null;

    public function to(string $email): self
    {
        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->to = $email;

        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this;
    }

    public function subject(string $subject): self
    {
        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->subject = $subject;

        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $this;
    }
}
