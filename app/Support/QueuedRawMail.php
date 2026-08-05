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
        $message = new QueuedRawMailMessage();
        $callback($message);

        if ($message->to === null || $message->subject === null) {
            throw new \RuntimeException('Queued raw mail requires both recipient and subject.');
        }

        try {
            SendRawMailJob::dispatch($message->to, $message->subject, $body);

            return;
        } catch (\Throwable $exception) {
            Log::warning('Queued raw mail dispatch failed; sending raw mail directly.', [
                'to' => $message->to,
                'subject' => $message->subject,
                'message' => $exception->getMessage(),
            ]);
        }

        app()->terminating(function () use ($body, $message) {
            try {
                LaravelMail::raw(
                    $body,
                    fn ($mail) => $mail
                        ->to($message->to)
                        ->subject($message->subject)
                );
            } catch (\Throwable $e) {
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
        $this->to = $email;

        return $this;
    }

    public function subject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }
}
