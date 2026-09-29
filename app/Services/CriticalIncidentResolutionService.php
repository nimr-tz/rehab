<?php

namespace App\Services;

use App\Mail\CriticalIncidentResolved;
use App\Models\EmailLog;
use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class CriticalIncidentResolutionService
{
    public function resolve(SystemLog $systemLog, array $data, ?User $resolver = null): array
    {
        $notifyUser = (bool) ($data['notify_user'] ?? false);
        $actionRequired = (bool) ($data['action_required'] ?? false);
        $resolutionNotes = trim((string) ($data['resolution_notes'] ?? ''));
        $resolutionSummary = trim((string) ($data['resolution_summary'] ?? ''));
        $actionDetails = trim((string) ($data['action_details'] ?? ''));

        $notificationEmail = $notifyUser
            ? $this->resolveNotificationEmail($systemLog, $data['notification_email'] ?? null)
            : null;

        $systemLog->update([
            'resolved' => true,
            'resolution_notes' => $resolutionNotes !== '' ? $resolutionNotes : 'Resolved by admin.',
            'resolved_by' => $resolver?->getAuthIdentifier(),
            'resolved_at' => now(),
            'resolution_summary' => $resolutionSummary !== '' ? $resolutionSummary : null,
            'user_notification_email' => $notificationEmail,
            'user_notification_status' => $notifyUser ? SystemLog::USER_NOTIFICATION_FAILED : SystemLog::USER_NOTIFICATION_NOT_REQUESTED,
            'user_notification_sent_at' => null,
            'user_notification_error' => null,
            'user_action_required' => $actionRequired,
            'user_action_details' => $actionDetails !== '' ? $actionDetails : null,
        ]);

        if (!$notifyUser) {
            return ['notified' => false, 'status' => SystemLog::USER_NOTIFICATION_NOT_REQUESTED];
        }

        try {
            Mail::mailer((string) config('mail.incident_resolutions.mailer', config('mail.default')))
                ->to($notificationEmail)
                ->send(new CriticalIncidentResolved(
                    $systemLog->fresh(),
                    $resolutionSummary !== '' ? $resolutionSummary : $systemLog->message,
                    $actionRequired,
                    $actionDetails !== '' ? $actionDetails : null,
                ));

            $this->recordEmailLog($systemLog, $notificationEmail, EmailLog::STATUS_SENT);

            $systemLog->update([
                'user_notification_status' => SystemLog::USER_NOTIFICATION_SENT,
                'user_notification_sent_at' => now(),
                'user_notification_error' => null,
            ]);

            return ['notified' => true, 'status' => SystemLog::USER_NOTIFICATION_SENT];
        } catch (Throwable $exception) {
            $this->recordEmailLog(
                $systemLog,
                $notificationEmail,
                EmailLog::STATUS_FAILED,
                $exception->getMessage(),
            );

            $systemLog->update([
                'user_notification_status' => SystemLog::USER_NOTIFICATION_FAILED,
                'user_notification_error' => Str::limit($exception->getMessage(), 2000),
            ]);

            Log::error('Critical incident resolution email failed', [
                'system_log_id' => $systemLog->id,
                'notification_email' => $notificationEmail,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return [
                'notified' => false,
                'status' => SystemLog::USER_NOTIFICATION_FAILED,
                'error' => $exception->getMessage(),
            ];
        }
    }

    public function resolveNotificationEmail(SystemLog $systemLog, ?string $override = null): ?string
    {
        $candidates = array_filter([
            $override,
            $systemLog->user?->email,
            data_get($systemLog->context, 'user.email'),
            data_get($systemLog->context, 'request.input.contact_email'),
            data_get($systemLog->context, 'request.input.notification_email'),
            data_get($systemLog->context, 'request.input.email'),
        ]);

        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate);

            if (filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                return Str::lower($candidate);
            }
        }

        return null;
    }

    private function recordEmailLog(SystemLog $systemLog, string $recipientEmail, string $status, ?string $errorMessage = null): void
    {
        EmailLog::create([
            'user_id' => $systemLog->user_id,
            'email_type' => EmailLog::TYPE_CRITICAL_INCIDENT_RESOLVED,
            'recipient_email' => $recipientEmail,
            'subject' => sprintf('[%s] Issue resolved', config('app.name', config('conference.short_name'))),
            'status' => $status,
            'error_message' => $errorMessage,
            'sent_at' => $status === EmailLog::STATUS_SENT ? now() : null,
            'metadata' => [
                'system_log_id' => $systemLog->id,
                'fingerprint' => $systemLog->fingerprint,
                'resolution_summary' => $systemLog->resolution_summary,
                'user_action_required' => $systemLog->user_action_required,
                'user_action_details' => $systemLog->user_action_details,
                'request' => Arr::only($systemLog->context ?? [], ['request', 'exception']),
            ],
        ]);
    }
}
