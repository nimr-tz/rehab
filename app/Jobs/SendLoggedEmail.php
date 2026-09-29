<?php

namespace App\Jobs;

use App\Models\EmailLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendLoggedEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 180;

    public function __construct(
        private readonly int $emailLogId,
        private readonly string $recipientEmail,
        private readonly Mailable $mailable
    ) {
    }

    public function handle(): void
    {
        $emailLog = EmailLog::find($this->emailLogId);

        if (!$emailLog) {
            Log::warning('Queued email log no longer exists.', [
                'log_id' => $this->emailLogId,
                'recipient' => $this->recipientEmail,
            ]);
            return;
        }

        $maxAttempts = 3;
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                Mail::to($this->recipientEmail)->sendNow($this->mailable);

                $emailLog->update([
                    'status' => EmailLog::STATUS_SENT,
                    'sent_at' => now(),
                    'error_message' => null,
                ]);

                Log::info('Queued email sent successfully.', [
                    'type' => $emailLog->email_type,
                    'recipient' => $this->recipientEmail,
                    'log_id' => $emailLog->id,
                    'attempt' => $attempt,
                ]);

                return;
            } catch (Throwable $e) {
                $lastException = $e;
                $errorMessage = $e->getMessage();
                $isRetryable = str_contains($errorMessage, 'Too many')
                    || str_contains($errorMessage, '550')
                    || str_contains($errorMessage, '421')
                    || str_contains(strtolower($errorMessage), 'rate');

                if ($isRetryable && $attempt < $maxAttempts) {
                    sleep($attempt * 3);
                    continue;
                }

                break;
            }
        }

        $emailLog->update([
            'status' => EmailLog::STATUS_FAILED,
            'error_message' => $lastException ? $lastException->getMessage() : 'Unknown error',
        ]);

        Log::error('Queued email failed.', [
            'type' => $emailLog->email_type,
            'recipient' => $this->recipientEmail,
            'log_id' => $emailLog->id,
            'error' => $lastException ? $lastException->getMessage() : 'Unknown error',
        ]);
    }
}
