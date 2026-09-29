<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AbstractSubmission;
use App\Models\User;
use App\Models\EmailLog;
use App\Models\EmailCampaign;
use App\Services\EmailNotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use App\Services\NotificationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use App\Mail\ConferenceBroadcastNotification;
use App\Mail\RevisionReminderNotification;
use App\Mail\PresentationReminderNotification;
use App\Mail\PaymentReminderNotification;
use App\Mail\AbstractSubmitterUpdateNotification;
use App\Mail\CpdReminderNotification;
use App\Mail\WelcomeNotification;

class EmailManagementController extends Controller
{
    protected $emailService;
    protected $notificationService;

    public function __construct(EmailNotificationService $emailService, NotificationService $notificationService)
    {
        $this->emailService = $emailService;
        $this->notificationService = $notificationService;
    }

    /**
     * Email management dashboard
     */
    public function index()
    {
        $operations = $this->getEmailOperations();
        $recentCampaigns = $this->getRecentCampaigns();
        $emailStats = $this->getEmailStatistics();
        $cpdStats = $this->getCpdRequestStats();

        $stats = [
            'presentation_due' => $this->getPresentationReminderQuery()->count(),
            'emails_failed_today' => $emailStats['total_failed_today'] ?? 0,
            'emails_failed_week' => EmailLog::thisWeek()->failed()->count(),
            'campaigns_last_7_days' => $this->campaignsEnabled()
                ? EmailCampaign::where('sent_at', '>=', now()->subDays(7))->count()
                : 0,
        ];

        // Proactive Intelligence
        $actionableItems = $this->getActionableItems($operations);
        $queueStats = $this->getQueueStatistics();

        return view('admin.emails.index', compact('stats', 'queueStats', 'emailStats', 'actionableItems', 'operations', 'recentCampaigns', 'cpdStats'));
    }

    /**
     * Full email activity log
     */
    public function activityLog(Request $request)
    {
        $query = EmailLog::with(['user', 'abstractSubmission'])
            ->orderByRaw('COALESCE(sent_at, created_at) desc')
            ->latest('created_at');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('recipient_email', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('email_type', 'like', "%{$search}%")
                    ->orWhere('error_message', 'like', "%{$search}%")
                    ->orWhere('abstract_submission_id', is_numeric($search) ? (int) $search : -1)
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('abstractSubmission', function ($abstractQuery) use ($search) {
                        if (is_numeric($search)) {
                            $abstractQuery->where('id', (int) $search);
                        } else {
                            $abstractQuery->where(function ($innerAbstractQuery) use ($search) {
                                $innerAbstractQuery->where('title', 'like', "%{$search}%")
                                    ->orWhere('conference_code', 'like', "%{$search}%")
                                    ->orWhere('author_name', 'like', "%{$search}%");
                            });
                        }
                    });
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('email_type') && $request->email_type !== 'all') {
            $query->where('email_type', $request->email_type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate(DB::raw('COALESCE(sent_at, created_at)'), '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate(DB::raw('COALESCE(sent_at, created_at)'), '<=', $request->date_to);
        }

        $logs = $query->paginate(25)->withQueryString();
        $logs->getCollection()->transform(function (EmailLog $log) {
            $log->preview_payload_data = $this->buildEmailLogPreviewPayload($log);
            return $log;
        });

        $typeOptions = EmailLog::query()
            ->select('email_type')
            ->distinct()
            ->orderBy('email_type')
            ->pluck('email_type');

        $summary = [
            'total' => (clone $query)->count(),
            'sent' => (clone $query)->whereIn('status', [EmailLog::STATUS_SENT, EmailLog::STATUS_OPENED, EmailLog::STATUS_CLICKED])->count(),
            'failed' => (clone $query)->whereIn('status', [EmailLog::STATUS_FAILED, EmailLog::STATUS_BOUNCED])->count(),
            'opened' => (clone $query)->whereIn('status', [EmailLog::STATUS_OPENED, EmailLog::STATUS_CLICKED])->count(),
        ];

        return view('admin.emails.activity-log', compact('logs', 'typeOptions', 'summary'));
    }

    private function buildEmailLogPreviewPayload(EmailLog $log): array
    {
        $metadata = is_array($log->metadata) ? $log->metadata : [];
        $abstract = $log->abstractSubmission;
        $user = $log->user;

        $lines = [];
        $html = $this->renderEmailLogPreviewHtml($log);

        switch ($log->email_type) {
            case 'abstract_accepted':
                $lines[] = 'This log represents an abstract acceptance message.';
                break;
            case EmailLog::TYPE_STATUS_NOTIFICATION:
                $status = $metadata['status'] ?? null;
                $lines[] = $status
                    ? "This status notification was sent when the abstract was marked `{$status}`."
                    : 'This is a status notification email.';
                if (!empty($metadata['custom_message'])) {
                    $lines[] = $metadata['custom_message'];
                }
                break;
            case EmailLog::TYPE_REVISION_REQUESTED:
                $lines[] = 'This email asked the author to revise and resubmit the abstract.';
                if (!empty($metadata['revision_message'])) {
                    $lines[] = $metadata['revision_message'];
                }
                break;
            case EmailLog::TYPE_REVISION_REMINDER:
                $lines[] = 'This was a reminder that the abstract revision was still pending.';
                if (!empty($metadata['revision_message'])) {
                    $lines[] = $metadata['revision_message'];
                }
                break;
            case 'presentation_reminder':
                $lines[] = 'This was a reminder to upload pending presentation materials.';
                break;
            case 'payment_reminder':
                $lines[] = 'This was a payment reminder for an unpaid registration.';
                break;
            case EmailLog::TYPE_REVIEW_REMINDER:
                $pendingCount = $metadata['pending_count'] ?? null;
                $lines[] = $pendingCount
                    ? "This review reminder covered {$pendingCount} pending review assignment(s)."
                    : 'This was a reviewer reminder email.';
                break;
            case EmailLog::TYPE_REVIEWER_ASSIGNMENT:
                $lines[] = 'This email notified a reviewer about a new assignment.';
                if (!empty($metadata['match_type'])) {
                    $lines[] = 'Assignment match type: ' . $metadata['match_type'] . '.';
                }
                break;
            case 'abstract_submitter_update':
                $lines[] = 'This was the general abstract submitter update broadcast.';
                break;
            case EmailLog::TYPE_ACCEPTED_CLARIFICATION:
                $lines[] = 'This was the presentation guidance message sent after acceptance.';
                break;
            case EmailLog::TYPE_WELCOME_NOTIFICATION:
                $lines[] = 'Welcome to the ' . config('conference.name') . ' (' . config('conference.short_name') . ' ' . config('conference.year') . ').';
                $lines[] = 'Your account has been successfully created and verified.';
                $lines[] = 'The email encourages abstract submission, explains payment can be completed later, and links the user to the dashboard.';
                break;
            case 'committee_decision':
                $decision = $metadata['decision'] ?? null;
                $lines[] = $decision
                    ? "This committee decision email communicated `{$decision}`."
                    : 'This was a committee decision email.';
                if (!empty($metadata['notes'])) {
                    $lines[] = $metadata['notes'];
                }
                break;
            default:
                $lines[] = 'Full rendered email HTML was not historically stored for this log entry.';
                $lines[] = 'This preview is reconstructed from the saved log metadata and related records.';
                break;
        }

        if ($abstract) {
            $lines[] = 'Abstract: ' . $abstract->title;
        }

        return [
            'subject' => $log->subject,
            'recipient_name' => $user?->full_name,
            'recipient_email' => $log->recipient_email,
            'email_type' => $log->email_type,
            'status' => $log->status,
            'sent_at' => optional($log->sent_at ?? $log->created_at)->toDateTimeString(),
            'error_message' => $log->error_message,
            'abstract_id' => $abstract?->id,
            'abstract_title' => $abstract?->title,
            'conference_code' => $abstract?->conference_code,
            'body_lines' => array_values(array_filter($lines)),
            'html' => $html,
            'metadata' => $metadata,
        ];
    }

    private function renderEmailLogPreviewHtml(EmailLog $log): ?string
    {
        try {
            $user = $log->user;
            $abstract = $log->abstractSubmission;
            $metadata = is_array($log->metadata) ? $log->metadata : [];

            return match ($log->email_type) {
                EmailLog::TYPE_WELCOME_NOTIFICATION => $user
                    ? (new WelcomeNotification($user))->render()
                    : null,
                'payment_reminder' => $user
                    ? (new PaymentReminderNotification($user))->render()
                    : null,
                'presentation_reminder' => $abstract
                    ? (new PresentationReminderNotification($abstract))->render()
                    : null,
                EmailLog::TYPE_ACCEPTED_CLARIFICATION => $abstract
                    ? (new \App\Mail\AcceptedClarificationNotification($abstract))->render()
                    : null,
                default => null,
            };
        } catch (\Throwable $e) {
            Log::warning("Unable to render email log preview for log #{$log->id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Strategic Intelligence: Detect actions needed
     */
    private function getActionableItems(?Collection $operations = null)
    {
        $operations ??= $this->getEmailOperations();

        return $operations
            ->filter(fn ($operation) => ($operation['count'] ?? 0) > 0 && !empty($operation['priority']))
            ->sortByDesc(function ($operation) {
                return match ($operation['priority']) {
                    'urgent' => 3,
                    'warning' => 2,
                    'info' => 1,
                    default => 0,
                };
            })
            ->take(4)
            ->map(function ($operation) {
                return [
                    'operation_key' => $operation['key'] ?? null,
                    'type' => $operation['priority'],
                    'title' => $operation['title'],
                    'message' => $operation['action_message'] ?? $operation['description'],
                    'action_label' => $operation['button_label'],
                    'action_route' => $operation['route'],
                    'confirm' => $operation['confirm'] ?? null,
                ];
            })
            ->values();
    }

    private function getEmailOperations(): Collection
    {
        $presentationReminderCount = $this->getPresentationReminderQuery()->count();
$operations = collect([
            [
                'key' => 'presentation_reminders',
                'title' => 'Presentation Reminders',
                'eyebrow' => 'In Program, Not Uploaded',
                'count' => $presentationReminderCount,
                'description' => 'abstracts in the conference program that are still missing the required presentation materials.',
                'detail' => 'Only targets authors placed in a session with a conference code — no one outside the program.',
                'route' => 'admin.emails.send-presentation-reminders',
                'button_label' => 'Send Presentation Reminders',
                'confirm' => 'Send presentation reminder emails to all program authors missing uploads?',
                'theme' => 'violet',
                'priority' => 'info',
                'action_message' => $presentationReminderCount . ' program abstracts are still missing presentation uploads.',
                'admin_only' => false,
            ],
        ]);

        return $operations;
    }

    private function getBroadcastRecipientQuery(string $targetGroup)
    {
        return match ($targetGroup) {
            'all' => User::query(),
            'attending' => $this->getAttendingQuery(),
            'participants' => User::whereIn('payment_status', ['verified', 'waived']),
            'paid' => User::where('payment_status', 'verified'),
            'authors' => User::whereHas('roles', fn($q) => $q->where('name', 'author')),
            'accepted_authors' => User::whereHas('abstractSubmissions', fn($q) => $q->where('status', 'accepted')),
            'revision_pending_authors' => User::whereHas('abstractSubmissions', function ($q) {
                $q->whereIn('status', [
                    'revision_requested',
                    'revision_required',
                    'minor_revision_required',
                    'major_revision_required',
                ]);
            }),
            'reviewers' => User::whereHas('roles', fn($q) => $q->where('name', 'reviewer')),
            'unpaid' => User::where(function ($query) {
                $query->whereNull('payment_status')
                    ->orWhereNotIn('payment_status', ['verified', 'waived']);
            }),
            default => User::query()->whereRaw('1 = 0'),
        };
    }

    private function getBroadcastGroupKeys(): array
    {
        return [
            'all',
            'attending',
            'participants',
            'paid',
            'authors',
            'accepted_authors',
            'revision_pending_authors',
            'reviewers',
            'unpaid',
        ];
    }

    private function getBroadcastAudienceLabel(string $targetGroup): string
    {
        return match ($targetGroup) {
            'all' => 'All Delegates',
            'attending' => 'Attending Participants (Paid)',
            'participants' => 'Participants',
            'paid' => 'Paid Registrations',
            'authors' => 'Authors',
            'accepted_authors' => 'Accepted Authors',
            'revision_pending_authors' => 'Authors Awaiting Revision Resubmission',
            'reviewers' => 'Reviewers',
            'unpaid' => 'Unpaid Registrations',
            default => 'Selected Group',
        };
    }

    private function getRecentCampaignWarning(string $targetGroup, string $subject): ?array
    {
        if (!$this->campaignsEnabled()) {
            return null;
        }

        if (blank(trim($subject))) {
            return null;
        }

        $campaign = EmailCampaign::where('target_group', $targetGroup)
            ->where('subject', trim($subject))
            ->where('sent_at', '>=', now()->subDays(7))
            ->latest('sent_at')
            ->first();

        if (!$campaign) {
            return null;
        }

        return [
            'message' => "A similar campaign with this subject was already sent to {$campaign->audience_label} {$campaign->sent_at->diffForHumans()}.",
            'sent_at' => optional($campaign->sent_at)->toDateTimeString(),
            'recipient_count' => $campaign->recipient_count,
        ];
    }

    private function campaignsEnabled(): bool
    {
        try {
            return Schema::hasTable('email_campaigns');
        } catch (\Throwable $e) {
            Log::warning('Email campaign table check failed: ' . $e->getMessage());
            return false;
        }
    }

    private function getRecentCampaigns(): Collection
    {
        if (!$this->campaignsEnabled()) {
            return collect();
        }

        try {
            return EmailCampaign::with('user')
                ->latest('sent_at')
                ->latest()
                ->limit(8)
                ->get();
        } catch (\Throwable $e) {
            Log::warning('Unable to load email campaigns: ' . $e->getMessage());
            return collect();
        }
    }

    private function getSubmissionConfirmationQuery()
    {
        return AbstractSubmission::where('status', 'submitted')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('email_logs')
                    ->whereColumn('email_logs.abstract_submission_id', 'abstract_submissions.id')
                    ->where('email_type', EmailLog::TYPE_SUBMISSION_CONFIRMATION);
            });
    }

    private function getDecisionNotificationQuery()
    {
        return AbstractSubmission::whereIn('status', ['accepted', 'rejected'])
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('email_logs')
                    ->whereColumn('email_logs.abstract_submission_id', 'abstract_submissions.id')
                    ->whereIn('email_type', ['status_notification', 'abstract_accepted', 'abstract_rejected']);
            });
    }

    private function getConferenceCodeNotificationQuery()
    {
        return AbstractSubmission::whereNotNull('conference_code')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('email_logs')
                    ->whereColumn('email_logs.abstract_submission_id', 'abstract_submissions.id')
                    ->where('email_type', 'code_assignment');
            });
    }

    private function getRevisionReminderQuery()
    {
        return AbstractSubmission::whereIn('status', [
                'revision_requested',
                'revision_required',
                'minor_revision_required',
                'major_revision_required',
            ]);
    }

    private function getPresentationReminderQuery()
    {
        return AbstractSubmission::where('status', 'accepted')
            ->whereNotNull('session_id')
            ->whereNotNull('conference_code')
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->whereNull('presentation_mode')
                        ->whereNull('oral_presentation_file')
                        ->whereNull('poster_presentation_file')
                        ->whereNull('audio_poster_file')
                        ->whereNull('audio_poster_poster_file');
                })->orWhere(function ($q) {
                    $q->whereRaw('LOWER(presentation_mode) = ?', ['oral'])
                        ->whereNull('oral_presentation_file');
                })->orWhere(function ($q) {
                    $q->whereRaw('LOWER(presentation_mode) = ?', ['poster'])
                        ->whereNull('poster_presentation_file');
                })->orWhere(function ($q) {
                    $q->whereRaw('LOWER(presentation_mode) = ?', ['audio_poster'])
                        ->where(function ($audioQuery) {
                            $audioQuery->whereNull('audio_poster_file')
                                ->orWhereNull('audio_poster_poster_file');
                        });
                });
            });
    }

    private function getSessionAssignmentNotificationQuery()
    {
        return AbstractSubmission::where('status', 'accepted')
            ->whereNotNull('session_id')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('email_logs')
                    ->whereColumn('email_logs.abstract_submission_id', 'abstract_submissions.id')
                    ->where('email_type', EmailLog::TYPE_SESSION_ASSIGNMENT);
            });
    }

    private function getPaymentReminderRecipients(): Collection
    {
        return User::with(['groupMember.groupRegistration', 'groupRegistrations'])
            ->where(function ($query) {
                $query->whereNotNull('registration_category')
                    ->orWhereNotNull('payment_reference')
                    ->orWhereHas('groupMember')
                    ->orWhereHas('groupRegistrations')
                    ->orWhere('intent_attendee', true)
                    ->orWhere('intent_presenter', true);
            })
            ->get()
            ->filter(fn (User $user) => !$user->isPaid())
            ->values();
    }

    private function getCpdReminderRecipientQuery()
    {
        return User::query()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->where(function ($q) {
                $q->whereNull('professional_board')
                  ->orWhere('professional_board', '')
                  ->orWhereNull('registration_number')
                  ->orWhere('registration_number', '');
            });
    }

    private function getCpdRequestQuery()
    {
        return User::query()
            ->where(function ($query) {
                $query->whereRaw("TRIM(COALESCE(professional_board, '')) <> ''")
                    ->orWhereRaw("TRIM(COALESCE(registration_number, '')) <> ''");
            });
    }

    private function getCpdRequestStats(): array
    {
        $requests = $this->getCpdRequestQuery();
        $complete = (clone $requests)
            ->whereRaw("TRIM(COALESCE(professional_board, '')) <> ''")
            ->whereRaw("TRIM(COALESCE(registration_number, '')) <> ''")
            ->count();
        $total = (clone $requests)->count();

        return [
            'total' => $total,
            'complete' => $complete,
            'incomplete' => $total - $complete,
        ];
    }

    /**
     * Get email delivery statistics
     */
    private function getEmailStatistics()
    {
        try {
            $totalSentToday = EmailLog::today()->successful()->count();
            $deliveryRateToday = EmailLog::getDeliveryRate('today');

            // Fallback to weekly if today is dry, for better UI experience
            $displaySent = $totalSentToday > 0 ? $totalSentToday : EmailLog::thisWeek()->successful()->count();
            $displayRate = $totalSentToday > 0 ? $deliveryRateToday : EmailLog::getDeliveryRate('week');

            $emailStats = [
                'total_sent_today' => $totalSentToday,
                'display_sent' => $displaySent,
                'delivery_rate_today' => $deliveryRateToday,
                'display_rate' => $displayRate,
                'total_failed_today' => EmailLog::today()->failed()->count(),
                'open_rate_today' => EmailLog::getOpenRate('today'),
                'total_sent_week' => EmailLog::thisWeek()->successful()->count(),
                'delivery_rate_week' => EmailLog::getDeliveryRate('week'),
                'recent_emails' => EmailLog::with(['user', 'abstractSubmission'])
                    ->orderByRaw('COALESCE(sent_at, created_at) desc')
                    ->latest('created_at')
                    ->limit(10)
                    ->get(),
                'by_type_today' => EmailLog::today()
                    ->select('email_type', DB::raw('count(*) as count'))
                    ->groupBy('email_type')
                    ->pluck('count', 'email_type')
                    ->toArray(),
            ];

            return $emailStats;
        } catch (\Exception $e) {
            return [
                'total_sent_today' => 0,
                'display_sent' => 0,
                'delivery_rate_today' => 0,
                'display_rate' => 0,
                'total_failed_today' => 0,
                'open_rate_today' => 0,
                'total_sent_week' => 0,
                'delivery_rate_week' => 0,
                'recent_emails' => collect(),
                'by_type_today' => [],
                'error' => 'Unable to fetch email stats'
            ];
        }
    }

    /**
     * Get queue statistics
     */
    private function getQueueStatistics()
    {
        try {
            $totalFailed = DB::table('failed_jobs')->count();
            $topFailures = DB::table('failed_jobs')
                ->select(DB::raw('SUBSTRING(exception, 1, 100) as reason'), DB::raw('count(*) as count'))
                ->groupBy('reason')
                ->orderBy('count', 'desc')
                ->limit(3)
                ->get();

            $queueStats = [
                'total_pending' => DB::table('jobs')->count(),
                'total_failed' => $totalFailed,
                'health_score' => $totalFailed > 10 ? 'POOR' : ($totalFailed > 0 ? 'NEEDS ATTENTION' : 'EXCELLENT'),
                'top_failures' => $topFailures,
                'by_queue' => DB::table('jobs')
                    ->select('queue', DB::raw('count(*) as count'))
                    ->groupBy('queue')
                    ->pluck('count', 'queue')
                    ->toArray(),
                'recent_processed' => DB::table('jobs') // Assuming jobs stay in table briefly or using a better metric
                    ->where('created_at', '>=', now()->subHour())
                    ->count(),
            ];

            return $queueStats;
        } catch (\Exception $e) {
            return [
                'total_pending' => 0,
                'total_failed' => 0,
                'health_score' => 'UNKNOWN',
                'top_failures' => collect(),
                'by_queue' => [],
                'recent_processed' => 0,
                'error' => 'Unable to fetch queue stats'
            ];
        }
    }

    /**
     * Send bulk status notifications
     */
    public function sendBulkStatusNotifications(Request $request)
    {
        $request->validate([
            'abstract_ids' => 'required|array',
            'abstract_ids.*' => 'exists:abstract_submissions,id',
            'status' => 'required|in:accepted,rejected',
            'message' => 'nullable|string|max:1000'
        ]);

        $result = $this->emailService->sendBulkStatusNotifications(
            $request->abstract_ids,
            $request->status,
            $request->message
        );

        if ($result['success'] > 0) {
            $this->startQueueWorkerInBackground();
        }

        return redirect()->back()->with('success',
            "Status notifications queued: {$result['success']} queued, {$result['failed']} failed to queue");
    }

    public function sendPendingSubmissionConfirmations()
    {
        $abstracts = $this->getSubmissionConfirmationQuery()
            ->with('user')
            ->get();

        if ($abstracts->isEmpty()) {
            return redirect()->back()->with('info', 'No submitted abstracts are missing submission confirmation emails right now.');
        }

        $successCount = 0;
        $failedCount = 0;

        foreach ($abstracts as $abstract) {
            try {
                if ($this->emailService->sendSubmissionConfirmation($abstract)) {
                    $successCount++;
                } else {
                    $failedCount++;
                }
            } catch (\Exception $e) {
                Log::error("Failed to queue submission confirmation for abstract #{$abstract->id}: " . $e->getMessage());
                $failedCount++;
            }
        }

        if ($successCount > 0) {
            $this->startQueueWorkerInBackground();
        }

        return redirect()->back()->with(
            $failedCount > 0 ? 'warning' : 'success',
            "Submission confirmations queued: {$successCount} queued, {$failedCount} failed to queue."
        );
    }

    /**
     * Send bulk review reminders
     */
    public function sendBulkReviewReminders(Request $request)
    {
        $validated = $request->validate([
            'deadline' => 'nullable|date',
        ]);

        $deadline = filled($validated['deadline'] ?? null)
            ? Carbon::parse($validated['deadline'])->startOfDay()
            : null;

        $result = $this->emailService->sendBulkReviewReminders($deadline);

        if ($result['success'] > 0) {
            $this->startQueueWorkerInBackground();
        }

        return redirect()->back()->with('success',
            "Review reminders queued: {$result['success']} queued, {$result['failed']} failed to queue");
    }

    /**
     * Send bulk conference code notifications
     */
    public function sendBulkConferenceCodeNotifications(Request $request)
    {
        $request->validate([
            'abstract_ids' => 'required|array',
            'abstract_ids.*' => 'exists:abstract_submissions,id'
        ]);

        $result = $this->emailService->sendBulkConferenceCodeNotifications($request->abstract_ids);

        if ($result['success'] > 0) {
            $this->startQueueWorkerInBackground();
        }

        return redirect()->back()->with('success',
            "Conference code notifications queued: {$result['success']} queued, {$result['failed']} failed to queue");
    }

    /**
     * Send bulk presentation reminders to authors of accepted abstracts
     */
    public function sendBulkPresentationReminders(Request $request)
    {
        $abstracts = $this->getPresentationReminderQuery()
            ->with('user')
            ->get();

        if ($abstracts->isEmpty()) {
            return redirect()->back()->with('info', 'No accepted abstracts are currently eligible for presentation reminder emails.');
        }

        $successCount = 0;
        $failedCount = 0;

        foreach ($abstracts as $abstract) {
            try {
                // Send email
                $this->emailService->sendPresentationReminder($abstract);

                // Send in-app notification
                $this->notificationService->createPresentationReminderNotification(
                    $abstract->user,
                    $abstract->title,
                    $abstract->id
                );

                $successCount++;
            } catch (\Exception $e) {
                Log::error("Failed to queue presentation reminder for abstract #{$abstract->id}: " . $e->getMessage());
                $failedCount++;
            }
        }

        if ($successCount > 0) {
            $this->startQueueWorkerInBackground();
        }

        return redirect()->back()->with('success',
            "Presentation reminders queued: {$successCount} queued, {$failedCount} failed to queue");
    }

    public function sendCpdReminderEmails()
    {
        $users = $this->getCpdReminderRecipientQuery()
            ->orderBy('id')
            ->get();

        if ($users->isEmpty()) {
            return redirect()->back()->with('info', 'All registered participants have already filled in their CPD information.');
        }

        $subject = 'Action Required: Update Your CPD Details — ' . config('conference.short_name') . ' ' . config('conference.year');
        $campaign = null;

        if ($this->campaignsEnabled()) {
            $campaign = EmailCampaign::create([
                'user_id' => auth()->id(),
                'target_group' => 'cpd_missing',
                'audience_label' => 'Missing CPD Info',
                'subject' => $subject,
                'message' => 'Reminder to participants to fill in their professional board and registration number for CPD points.',
                'action_text' => 'Update My Profile',
                'action_url' => route('profile.edit'),
                'recipient_count' => $users->count(),
                'status' => 'sending',
                'metadata' => ['operation' => 'cpd_reminders'],
            ]);
        }

        $successCount = 0;
        $failedCount = 0;

        foreach ($users as $user) {
            try {
                if ($this->emailService->sendCpdReminder($user)) {
                    $successCount++;
                } else {
                    $failedCount++;
                }
            } catch (\Exception $e) {
                Log::error("Failed to queue CPD reminder to user #{$user->id}: " . $e->getMessage());
                $failedCount++;
            }
        }

        if ($campaign) {
            $campaign->update([
                'status' => $failedCount > 0 ? 'partial' : 'queued',
                'sent_at' => null,
                'recipient_count' => $successCount,
                'metadata' => array_merge($campaign->metadata ?? [], [
                    'attempted_count' => $users->count(),
                    'success_count' => $successCount,
                    'failed_count' => $failedCount,
                ]),
            ]);
        }

        if ($successCount > 0) {
            $this->startQueueWorkerInBackground();
        }

        return redirect()->back()->with(
            $failedCount > 0 ? 'warning' : 'success',
            "CPD reminders queued: {$successCount} queued, {$failedCount} failed to queue."
        );
    }

    public function exportCpdRequests()
    {
        $users = $this->getCpdRequestQuery()
            ->with([
                'attendances' => fn ($query) => $query
                    ->where('day', '>=', 1)
                    ->orderBy('day'),
            ])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $filename = config('conference.file_prefix').'-cpd-requests-'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($users) {
            $file = fopen('php://output', 'w');

            // UTF-8 BOM keeps names and institutions readable in Microsoft Excel.
            fwrite($file, "\xEF\xBB\xBF");
            fputcsv($file, [
                'Name',
                'Email',
                'Phone',
                'Professional Board / Council',
                'Registration Number',
                'CPD Details Status',
                'Affiliation / Institution',
                'Country',
                'Registration Category',
                'Payment Status',
                'Attendance Days',
                'Days Attended',
                'Profile Last Updated',
            ]);

            foreach ($users as $user) {
                $board = trim((string) $user->professional_board);
                $registrationNumber = trim((string) $user->registration_number);
                $attendanceDays = $user->attendances
                    ->pluck('day')
                    ->unique()
                    ->sort()
                    ->values();

                $status = match (true) {
                    $board !== '' && $registrationNumber !== '' => 'Complete',
                    $board === '' => 'Incomplete - missing professional board',
                    default => 'Incomplete - missing registration number',
                };

                fputcsv($file, [
                    $user->full_name,
                    $user->email,
                    $user->phone ?? '',
                    $board,
                    $registrationNumber,
                    $status,
                    $user->affiliation ?: ($user->institute ?? ''),
                    $user->country ?? '',
                    $user->registration_category ?? '',
                    $user->payment_status ?? '',
                    $attendanceDays->map(fn ($day) => 'Day '.$day)->implode(', '),
                    $attendanceDays->count(),
                    $user->updated_at?->format('Y-m-d H:i:s') ?? '',
                ]);
            }

            fclose($file);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function sendRevisionReminderEmails()
    {
        $abstracts = $this->getRevisionReminderQuery()
            ->with('user')
            ->get();

        if ($abstracts->isEmpty()) {
            return redirect()->back()->with('info', 'No abstracts are currently eligible for revision reminder emails.');
        }

        $successCount = 0;
        $failedCount = 0;

        foreach ($abstracts as $abstract) {
            try {
                if ($this->emailService->sendRevisionReminderNotification($abstract, $abstract->admin_comment)) {
                    $successCount++;
                } else {
                    $failedCount++;
                }
            } catch (\Exception $e) {
                Log::error("Failed to queue revision reminder for abstract #{$abstract->id}: " . $e->getMessage());
                $failedCount++;
            }
        }

        if ($successCount > 0) {
            $this->startQueueWorkerInBackground();
        }

        return redirect()->back()->with(
            $failedCount > 0 ? 'warning' : 'success',
            "Revision reminders queued: {$successCount} queued, {$failedCount} failed to queue."
        );
    }

    public function sendPendingSessionNotifications()
    {
        $abstracts = $this->getSessionAssignmentNotificationQuery()
            ->with('user', 'session')
            ->get();

        if ($abstracts->isEmpty()) {
            return redirect()->back()->with('info', 'No session assignment notifications are pending right now.');
        }

        $successCount = 0;
        $failedCount = 0;

        foreach ($abstracts as $abstract) {
            try {
                if ($this->emailService->sendSessionAssignmentNotification($abstract)) {
                    $successCount++;
                } else {
                    $failedCount++;
                }
            } catch (\Exception $e) {
                Log::error("Failed to queue session assignment notification for abstract #{$abstract->id}: " . $e->getMessage());
                $failedCount++;
            }
        }

        if ($successCount > 0) {
            $this->startQueueWorkerInBackground();
        }

        return redirect()->back()->with(
            $failedCount > 0 ? 'warning' : 'success',
            "Session assignment notifications queued: {$successCount} queued, {$failedCount} failed to queue."
        );
    }

    /**
     * Preview email template
     */
    public function previewEmail(Request $request)
    {
        $type = $request->get('type');
        $abstractId = $request->get('abstract_id');

        $abstract = AbstractSubmission::with('user')->find($abstractId);

        if (!$abstract) {
            return response()->json(['error' => 'Abstract not found'], 404);
        }

        $view = match($type) {
            'submission' => 'emails.abstract-submission-confirmation',
            'status' => 'emails.abstract-status-notification',
            'code' => 'emails.conference-code-assignment',
            'reviewer' => 'emails.reviewer-assignment-notification',
            default => null
        };

        if (!$view) {
            return response()->json(['error' => 'Invalid email type'], 400);
        }

        $data = ['abstract' => $abstract];

        if ($type === 'status') {
            $data['status'] = $request->get('status', 'accepted');
            $data['message'] = $request->get('message');
        }

        if ($type === 'reviewer') {
            $data['reviewer'] = $abstract->reviewer ?? User::first();
            $data['reviewType'] = 'primary';
        }

        return view($view, $data);
    }

    public function broadcastAudienceInfo(Request $request)
    {
        $validated = $request->validate([
            'target_group' => 'required|in:' . implode(',', $this->getBroadcastGroupKeys()),
            'subject' => 'nullable|string|max:255',
        ]);

        $query = $this->getBroadcastRecipientQuery($validated['target_group']);
        $count = (clone $query)->count();

        return response()->json([
            'count' => $count,
            'audience_label' => $this->getBroadcastAudienceLabel($validated['target_group']),
            'warning' => $this->getRecentCampaignWarning($validated['target_group'], $validated['subject'] ?? ''),
        ]);
    }

    public function previewBroadcast(Request $request)
    {
        $validated = $request->validate([
            'target_group' => 'required|in:' . implode(',', $this->getBroadcastGroupKeys()),
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'action_text' => 'nullable|string|max:50',
            'action_url' => 'nullable|url',
        ]);

        $user = $this->getBroadcastRecipientQuery($validated['target_group'])->first();

        if (!$user) {
            $user = new User([
                'first_name' => config('conference.short_name'),
                'last_name' => 'Participant',
                'email' => config('conference.contact_email'),
            ]);
        }

        $html = (new ConferenceBroadcastNotification(
            $user,
            $validated['subject'],
            $validated['message'],
            $validated['action_url'] ?? null,
            $validated['action_text'] ?? null
        ))->render();

        return response()->json([
            'html' => $html,
            'count' => $this->getBroadcastRecipientQuery($validated['target_group'])->count(),
            'audience_label' => $this->getBroadcastAudienceLabel($validated['target_group']),
            'warning' => $this->getRecentCampaignWarning($validated['target_group'], $validated['subject']),
        ]);
    }

    public function previewOperation(Request $request)
    {
        $validated = $request->validate([
            'operation' => 'required|in:presentation_reminders',
        ]);

        $operation = $this->getEmailOperations()->firstWhere('key', $validated['operation']);

        if (!$operation) {
            return response()->json(['error' => 'Operation not found'], 404);
        }

        [$html, $subject] = match ($validated['operation']) {
            'presentation_reminders' => $this->buildPresentationReminderPreview(),
        };

        return response()->json([
            'title' => $operation['title'],
            'eyebrow' => $operation['eyebrow'],
            'count' => $operation['count'],
            'description' => $operation['description'],
            'detail' => $operation['detail'],
            'confirm' => $operation['confirm'] ?? null,
            'button_label' => $operation['button_label'],
            'route' => route($operation['route']),
            'subject' => $subject,
            'html' => $html,
        ]);
    }

    /**
     * Get pending email notifications
     */
    public function getPendingNotifications()
    {
        $pending = [
            'new_submissions' => AbstractSubmission::where('status', 'submitted')
                ->whereDate('created_at', today())
                ->with('user')
                ->get(),

            'pending_reviewers' => User::where(function($query) {
                $query->whereHas('reviewedAbstracts1', function($subquery) {
                    $subquery->whereNull('reviewer_1_score')
                            ->whereIn('status', ['submitted', 'under_review']);
                })->orWhereHas('reviewedAbstracts2', function($subquery) {
                    $subquery->whereNull('reviewer_2_score')
                            ->whereIn('status', ['submitted', 'under_review']);
                });
            })->with(['reviewedAbstracts1' => function($query) {
                $query->whereNull('reviewer_1_score')
                      ->whereIn('status', ['submitted', 'under_review']);
            }, 'reviewedAbstracts2' => function($query) {
                $query->whereNull('reviewer_2_score')
                      ->whereIn('status', ['submitted', 'under_review']);
            }])->get(),

            'new_codes' => AbstractSubmission::whereNotNull('conference_code')
                ->whereDate('code_assigned_at', today())
                ->with('user')
                ->get(),
        ];

        return response()->json($pending);
    }

    /**
     * Test email configuration
     */
    public function testEmail(Request $request)
    {
        $request->validate([
            'test_email' => 'required|email'
        ]);

        try {
            $testAbstract = AbstractSubmission::latest('id')->first();

            if (!$testAbstract) {
                return response()->json(['success' => false, 'message' => 'No abstract record is available for the test email.']);
            }

            $testAbstract->forceFill([
                'title' => 'Test Abstract for Email Configuration',
                'author_name' => 'Test Author',
                'author_institute' => 'Test Institution',
                'subtheme' => 'Test Subtheme',
                'status' => 'submitted',
            ]);

            $testUser = new User([
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => $request->test_email,
            ]);

            $testAbstract->setRelation('user', $testUser);

            $success = $this->emailService->sendSubmissionConfirmation($testAbstract);

            if ($success) {
                $this->startQueueWorkerInBackground();
                return response()->json(['success' => true, 'message' => 'Test email queued successfully!']);
            } else {
                return response()->json(['success' => false, 'message' => 'Failed to queue test email. Check logs for details.']);
            }
        } catch (\Exception $e) {
            Log::error('Email test failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Email test failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Export email delivery report
     */
    public function exportReport(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'format' => 'required|in:csv,json'
        ]);

        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();

        $emails = EmailLog::with(['user', 'abstractSubmission'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();

        if ($request->format === 'csv') {
            return $this->exportCsv($emails, $startDate, $endDate);
        } else {
            return response()->json([
                'period' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d')
                ],
                'summary' => [
                    'total_emails' => $emails->count(),
                    'successful' => $emails->where('status', EmailLog::STATUS_SENT)->count(),
                    'failed' => $emails->whereIn('status', [EmailLog::STATUS_FAILED, EmailLog::STATUS_BOUNCED])->count(),
                    'delivery_rate' => $emails->count() > 0 ? round(($emails->where('status', EmailLog::STATUS_SENT)->count() / $emails->count()) * 100, 2) : 0
                ],
                'emails' => $emails->map(function($email) {
                    return [
                        'id' => $email->id,
                        'type' => $email->email_type,
                        'recipient' => $email->recipient_email,
                        'status' => $email->status,
                        'sent_at' => $email->sent_at?->format('Y-m-d H:i:s'),
                        'user_name' => $email->user ? $email->user->first_name . ' ' . $email->user->last_name : null,
                        'abstract_id' => $email->abstract_submission_id,
                        'error_message' => $email->error_message
                    ];
                })
            ]);
        }
    }

    /**
     * Export email data as CSV
     */
    private function exportCsv($emails, $startDate, $endDate)
    {
        $filename = 'email_delivery_report_' . $startDate->format('Y-m-d') . '_to_' . $endDate->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($emails) {
            $file = fopen('php://output', 'w');

            // CSV headers
            fputcsv($file, [
                'ID',
                'Email Type',
                'Recipient Email',
                'Recipient Name',
                'Status',
                'Sent At',
                'Abstract ID',
                'Error Message',
                'Created At'
            ]);

            // CSV data
            foreach ($emails as $email) {
                fputcsv($file, [
                    $email->id,
                    ucwords(str_replace('_', ' ', $email->email_type)),
                    $email->recipient_email,
                    $email->user ? $email->user->first_name . ' ' . $email->user->last_name : '',
                    ucfirst($email->status),
                    $email->sent_at ? $email->sent_at->format('Y-m-d H:i:s') : '',
                    $email->abstract_submission_id ?? '',
                    $email->error_message ?? '',
                    $email->created_at->format('Y-m-d H:i:s')
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
    /**
     * Send bulk payment reminders to users who haven't paid
     */
    public function sendBulkPaymentReminders(Request $request)
    {
        $unpaidUsers = $this->getPaymentReminderRecipients();

        if ($unpaidUsers->isEmpty()) {
            return redirect()->back()->with('info', 'No delegates are currently eligible for a payment reminder.');
        }

        $successCount = 0;
        $failedCount = 0;

        foreach ($unpaidUsers as $user) {
            try {
                $this->emailService->sendPaymentReminder($user);
                $successCount++;
            } catch (\Exception $e) {
                Log::error("Failed to queue payment reminder to user #{$user->id}: " . $e->getMessage());
                $failedCount++;
            }
        }

        if ($successCount > 0) {
            $this->startQueueWorkerInBackground();
        }

        return redirect()->back()->with('success',
            "Payment reminders queued: {$successCount} queued, {$failedCount} failed to queue");
    }

    /**
     * Send general broadcast email to a target group
     */
    public function sendBulkConferenceBroadcast(Request $request)
    {
        $validated = $request->validate([
            'target_group' => 'required|in:' . implode(',', $this->getBroadcastGroupKeys()),
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'action_text' => 'nullable|string|max:50',
            'action_url' => 'nullable|url',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,csv,txt,jpg,jpeg,png'
        ]);

        $users = $this->getBroadcastRecipientQuery($validated['target_group'])->get();

        if ($users->isEmpty()) {
            return redirect()->back()->with('error', 'No users found for the selected target group.');
        }

        $campaign = null;
        $attachments = [];

        if ($this->campaignsEnabled()) {
            $campaign = EmailCampaign::create([
                'user_id' => auth()->id(),
                'target_group' => $validated['target_group'],
                'audience_label' => $this->getBroadcastAudienceLabel($validated['target_group']),
                'subject' => $validated['subject'],
                'message' => $validated['message'],
                'action_text' => $validated['action_text'] ?? null,
                'action_url' => $validated['action_url'] ?? null,
                'recipient_count' => $users->count(),
                'status' => 'sending',
                'metadata' => [
                    'warning' => $this->getRecentCampaignWarning($validated['target_group'], $validated['subject']),
                    'attachments' => [],
                ],
            ]);
        }

        foreach ($request->file('attachments', []) as $file) {
            $originalName = $file->getClientOriginalName();
            $storedPath = $file->storeAs(
                'email-broadcast-attachments/' . Str::uuid(),
                Str::limit(pathinfo($originalName, PATHINFO_FILENAME), 80, '') . '.' . $file->getClientOriginalExtension(),
                'local'
            );

            $attachments[] = [
                'path' => Storage::disk('local')->path($storedPath),
                'storage_path' => $storedPath,
                'name' => $originalName,
                'mime' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ];
        }

        if ($campaign && !empty($attachments)) {
            $metadata = $campaign->metadata ?? [];
            $metadata['attachments'] = collect($attachments)
                ->map(fn ($attachment) => [
                    'name' => $attachment['name'],
                    'mime' => $attachment['mime'],
                    'size' => $attachment['size'],
                ])
                ->values()
                ->all();
            $campaign->update(['metadata' => $metadata]);
        }

        $successCount = 0;
        $failedCount = 0;

        foreach ($users as $user) {
            try {
                $queued = $this->emailService->sendConferenceBroadcast(
                    $user,
                    $validated['subject'],
                    $validated['message'],
                    $validated['action_url'] ?? null,
                    $validated['action_text'] ?? null,
                    $attachments
                );

                if ($queued) {
                    $successCount++;
                } else {
                    $failedCount++;
                }
            } catch (\Exception $e) {
                Log::error("Failed to queue broadcast to user #{$user->id}: " . $e->getMessage());
                $failedCount++;
            }
        }

        if ($campaign) {
            $campaign->update([
                'status' => $failedCount > 0 ? 'partial' : 'queued',
                'sent_at' => null,
                'recipient_count' => $successCount,
                'metadata' => array_merge($campaign->metadata ?? [], [
                    'attempted_count' => $users->count(),
                    'success_count' => $successCount,
                    'failed_count' => $failedCount,
                ]),
            ]);
        }

        if ($successCount > 0) {
            $this->startQueueWorkerInBackground();
        }

        return redirect()->back()->with('success',
            "Broadcast queued for {$successCount} users. {$failedCount} failed to queue.");
    }

    /**
     * Start queue workers (Background trigger)
     */
    public function startQueueWorkers()
    {
        try {
            $pending = DB::table('jobs')->count();

            if ($pending === 0) {
                return response()->json(['success' => true, 'message' => 'No pending jobs in the queue.']);
            }

            $this->startQueueWorkerInBackground();

            return response()->json(['success' => true, 'message' => "{$pending} pending job(s) are now being processed in the background."]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to process queue: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Restart queue workers
     */
    public function restartQueueWorkers()
    {
        try {
            \Illuminate\Support\Facades\Artisan::call('queue:restart');

            $pending = DB::table('jobs')->count();
            if ($pending > 0) {
                $this->startQueueWorkerInBackground();
                return response()->json(['success' => true, 'message' => "Restart signal sent. {$pending} pending job(s) are now being processed in the background."]);
            }

            return response()->json(['success' => true, 'message' => 'Restart signal sent. There are no pending jobs to process right now.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to restart workers: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Retry all failed jobs
     */
    public function retryFailedJobs()
    {
        try {
            $count = DB::table('failed_jobs')->count();

            if ($count === 0) {
                return response()->json(['success' => true, 'message' => 'No failed jobs to retry.']);
            }

            \Illuminate\Support\Facades\Artisan::call('queue:retry', ['id' => ['all']]);

            $this->startQueueWorkerInBackground();

            return response()->json(['success' => true, 'message' => "{$count} failed job(s) have been re-queued and are now being processed."]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to retry jobs: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Clear failed jobs
     */
    public function clearFailedJobs()
    {
        try {
            \Illuminate\Support\Facades\Artisan::call('queue:flush');
            return response()->json(['success' => true, 'message' => 'All failed jobs have been purged.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to clear jobs: ' . $e->getMessage()], 500);
        }
    }

    private function startQueueWorkerInBackground(): void
    {
        $php = PHP_BINARY ?: 'php';
        $artisan = base_path('artisan');
        $logFile = storage_path('logs/queue-worker.log');
        $command = implode(' ', [
            escapeshellarg($php),
            escapeshellarg($artisan),
            'queue:work',
            'database',
            '--queue=emails-critical,emails-high-priority,emails,emails-low-priority,default',
            '--stop-when-empty',
            '--tries=3',
            '--timeout=240',
        ]);

        if (PHP_OS_FAMILY === 'Windows') {
            pclose(popen('start "" /B ' . $command . ' >> ' . escapeshellarg($logFile) . ' 2>&1', 'r'));
            return;
        }

        exec($command . ' >> ' . escapeshellarg($logFile) . ' 2>&1 &');
    }

    /**
     * Dispatch all pending status notifications (accepted/rejected)
     */
    public function sendPendingNotifications()
    {
        $abstracts = $this->getDecisionNotificationQuery()->get();

        if ($abstracts->isEmpty()) {
            return redirect()->back()->with('info', 'No pending status notifications found.');
        }

        $successCount = 0;
        $failedCount = 0;

        foreach ($abstracts as $abstract) {
            try {
                if ($abstract->status === 'accepted') {
                    $this->emailService->sendAbstractAcceptedNotification($abstract);
                } else {
                    $this->emailService->sendAbstractRejectedNotification($abstract);
                }
                $successCount++;
            } catch (\Exception $e) {
                Log::error("Bulk recovery: Failed to notify abstract #{$abstract->id}: " . $e->getMessage());
                $failedCount++;
            }
        }

        if ($successCount > 0) {
            $this->startQueueWorkerInBackground();
        }

        return redirect()->back()->with('success', "Queued {$successCount} notifications. {$failedCount} failed to queue.");
    }

    /**
     * Dispatch all pending code assignment emails
     */
    public function sendPendingCodes()
    {
        $abstracts = $this->getConferenceCodeNotificationQuery()->get();

        if ($abstracts->isEmpty()) {
            return redirect()->back()->with('info', 'No pending code notifications found.');
        }

        $successCount = 0;
        $failedCount = 0;

        foreach ($abstracts as $abstract) {
            try {
                $this->emailService->sendConferenceCodeAssignment($abstract);
                $successCount++;
            } catch (\Exception $e) {
                Log::error("Bulk recovery: Failed to queue code for abstract #{$abstract->id}: " . $e->getMessage());
                $failedCount++;
            }
        }

        if ($successCount > 0) {
            $this->startQueueWorkerInBackground();
        }

        return redirect()->back()->with('success', "Queued {$successCount} codes. {$failedCount} failed to queue.");
    }

    private function buildPresentationReminderPreview(): array
    {
        $abstract = $this->getPresentationReminderQuery()->with('user')->first()
            ?? $this->makeSampleAbstract([
                'status' => 'accepted',
                'title' => 'Presentation reminder preview abstract',
                'presentation_mode' => 'oral',
            ]);

        $mailable = new PresentationReminderNotification($abstract);

        return [$mailable->render(), $mailable->envelope()->subject];
    }

    private function makeSampleAbstract(array $attributes = []): AbstractSubmission
    {
        $user = $this->makeSampleUser();

        $abstract = new AbstractSubmission(array_merge([
            'id' => 9999,
            'title' => 'Sample conference abstract',
            'subtheme' => \App\Support\ConferenceTopics::names()[0] ?? 'Sample topic',
            'status' => 'accepted',
            'author_name' => trim(($user->first_name ?? 'Sample') . ' ' . ($user->last_name ?? 'Author')),
            'presentation_mode' => 'oral',
            'admin_comment' => null,
            'created_at' => now(),
        ], $attributes));

        $abstract->setRelation('user', $user);

        return $abstract;
    }

    private function getAttendingQuery()
    {
        // Paid directly or fee waived
        return User::whereNotNull('email')
            ->whereIn('payment_status', ['verified', 'waived']);
    }

    private function makeSampleUser(): User
    {
        return new User([
            'first_name' => 'Sample',
            'last_name' => 'Participant',
            'email' => config('conference.contact_email'),
            'payment_status' => 'pending',
        ]);
    }
}
