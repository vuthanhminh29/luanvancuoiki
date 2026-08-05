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
        $this->to = $to;
        $this->subject = $subject;
        $this->body = $body;
    }

    public function handle(): void
    {
        Mail::raw(
            $this->body,
            fn ($message) => $message
                ->to($this->to)
                ->subject($this->subject)
        );
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Queued raw email could not be sent.', [
            'to' => $this->to,
            'subject' => $this->subject,
            'message' => $exception->getMessage(),
        ]);
    }
}
