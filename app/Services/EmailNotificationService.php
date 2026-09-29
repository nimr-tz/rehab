<?php

namespace App\Services;

use App\Jobs\SendLoggedEmail;
use App\Models\AbstractSubmission;
use App\Models\User;
use App\Models\EmailLog;
use App\Mail\AbstractSubmissionConfirmation;
use App\Mail\ReviewerAssignmentNotification;
use App\Mail\AbstractStatusNotification;
use App\Mail\AbstractDecisionNotification;
use App\Mail\BulkReviewReminderNotification;
use App\Mail\ConferenceCodeAssignment;
use App\Mail\ReviewReminderNotification;
use App\Mail\ReviewCompletionNotification;
use App\Mail\BothReviewsCompleteNotification;
use App\Mail\ReviewerProgressUpdateNotification;
use App\Mail\SubthemeChangeRecommendationNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Mail\DraftSavedNotification;
use App\Mail\AbstractDeletedNotification;
use App\Mail\AbstractUpdatedNotification;
use App\Mail\AbstractSubthemeChangedNotification;
use App\Mail\AbstractWithdrawnNotification;
use App\Mail\RevisionRequestedNotification;
use App\Mail\RevisionReminderNotification;
use App\Mail\AbstractSubmitterUpdateNotification;
use App\Mail\PresentationUploadedNotification;
use App\Mail\SessionAssignmentNotification;
use App\Mail\PaymentVerifiedNotification;
use App\Mail\PaymentRejectedNotification;
use App\Mail\PaymentSubmittedNotification;
use App\Mail\PaymentSubmissionConfirmation;
use App\Mail\FeeWaivedNotification;
use App\Mail\GroupPaymentVerifiedNotification;
use App\Mail\GroupPaymentRejectedNotification;
use App\Mail\StudentIDVerifiedNotification;
use App\Mail\StudentIDRejectedNotification;
use App\Mail\AcceptedClarificationNotification;
use App\Mail\CpdReminderNotification;
use App\Mail\CertificateReadyNotification;
use App\Mail\PresenterCertificateNotification;
use Throwable;

class EmailNotificationService
{
    /**
     * Send abstract submission confirmation email
     */
    public function sendSubmissionConfirmation(AbstractSubmission $abstract)
    {
        return $this->sendEmailWithLogging(
            $abstract->user->email,
            function() use ($abstract) {
                return new AbstractSubmissionConfirmation($abstract);
            },
            EmailLog::TYPE_SUBMISSION_CONFIRMATION,
            $abstract->user->id,
            $abstract->id,
            "Abstract Submission Confirmation - " . config('app.name')
        );
    }

    /**
     * Send reviewer assignment notification
     */
    public function sendReviewerAssignment(AbstractSubmission $abstract, User $reviewer, string $reviewType = 'reviewer', array $options = [])
    {
        if (!isset($options['match_type'])) {
            $options['match_type'] = $this->getMatchTypeForReviewer($abstract, $reviewer);
        }
        if (!isset($options['assignment_type'])) {
            $options['assignment_type'] = 'assigned';
        }

        return $this->sendEmailWithLogging(
            $reviewer->email,
            function() use ($abstract, $reviewer, $options) {
                return new ReviewerAssignmentNotification($abstract, $reviewer, 1, $options);
            },
            EmailLog::TYPE_REVIEWER_ASSIGNMENT,
            $reviewer->id,
            $abstract->id,
            "New Reviewer Assignment - Abstract #{$abstract->id}",
            array_merge(['review_type' => $reviewType], $options)
        );
    }

    /**
     * Determine assignment match type for a reviewer.
     */
    private function getMatchTypeForReviewer(AbstractSubmission $abstract, User $reviewer): string
    {
        if (empty($abstract->subtheme)) {
            return 'fallback';
        }

        $interests = $reviewer->interests()->pluck('subtheme_name')->toArray();
        if (in_array($abstract->subtheme, $interests, true)) {
            return 'exact';
        }

        $relatedMap = config('conference.related_subthemes', []);
        $related = $relatedMap[$abstract->subtheme] ?? [];
        if (!empty($related) && count(array_intersect($related, $interests)) > 0) {
            return 'related';
        }

        return 'fallback';
    }

    /**
     * Send reviewer revocation notification (assignment removed/reassigned)
     */
    public function sendReviewerRevocation(AbstractSubmission $abstract, User $reviewer, string $reason = null)
    {
        return $this->sendEmailWithLogging(
            $reviewer->email,
            function() use ($abstract, $reviewer, $reason) {
                return new \App\Mail\ReviewerRevocationNotification($abstract, $reviewer, $reason);
            },
            EmailLog::TYPE_REVIEWER_REVOCATION,
            $reviewer->id,
            $abstract->id,
            "Review Assignment Reassigned - Abstract #{$abstract->id}",
            ['reason' => $reason]
        );
    }

    /**
     * Send abstract status notification (accepted/rejected)
     */
    public function sendStatusNotification(AbstractSubmission $abstract, string $status, string $message = null)
    {
        return $this->sendEmailWithLogging(
            $abstract->user->email,
            function() use ($abstract, $status, $message) {
                return new AbstractStatusNotification($abstract, $status, $message);
            },
            EmailLog::TYPE_STATUS_NOTIFICATION,
            $abstract->user->id,
            $abstract->id,
            "Abstract {$status} - " . config('app.name'),
            ['status' => $status, 'custom_message' => $message]
        );
    }

    /**
     * Send conference code assignment notification
     */
    public function sendConferenceCodeAssignment(AbstractSubmission $abstract)
    {
        return $this->sendEmailWithLogging(
            $abstract->user->email,
            function() use ($abstract) {
                return new ConferenceCodeAssignment($abstract);
            },
            EmailLog::TYPE_CODE_ASSIGNMENT,
            $abstract->user->id,
            $abstract->id,
            "Conference Code Assignment - " . config('app.name'),
            ['conference_code' => $abstract->conference_code]
        );
    }

    /**
     * Send review reminder to a specific reviewer
     */
    public function sendReviewReminder(User $reviewer, $pendingReviews, Carbon $deadline = null)
    {
        return $this->sendEmailWithLogging(
            $reviewer->email,
            function() use ($reviewer, $pendingReviews, $deadline) {
                return new BulkReviewReminderNotification($reviewer, collect($pendingReviews), $deadline);
            },
            EmailLog::TYPE_REVIEW_REMINDER,
            $reviewer->id,
            null,
            "Review Reminder - " . config('app.name'),
            [
                'pending_count' => $pendingReviews->count(),
                'deadline' => $deadline?->toDateString()
            ]
        );
    }

    /**
     * Send draft saved notification
     */
    public function sendDraftSavedNotification(AbstractSubmission $abstract)
    {
        return $this->sendEmailWithLogging(
            $abstract->user->email,
            function() use ($abstract) {
                return new DraftSavedNotification($abstract);
            },
            EmailLog::TYPE_DRAFT_SAVED,
            $abstract->user->id,
            $abstract->id,
            "Abstract Draft Saved - " . config('app.name')
        );
    }

    /**
     * Send abstract deleted notification
     */
    public function sendAbstractDeletedNotification(User $user, string $abstractTitle)
    {
        return $this->sendEmailWithLogging(
            $user->email,
            function() use ($user, $abstractTitle) {
                return new AbstractDeletedNotification($user, $abstractTitle);
            },
            EmailLog::TYPE_ABSTRACT_DELETED,
            $user->id,
            null,
            "Abstract Deleted - " . config('app.name'),
            ['abstract_title' => $abstractTitle]
        );
    }

    /**
     * Send abstract updated notification
     */
    public function sendAbstractUpdatedNotification(AbstractSubmission $abstract)
    {
        return $this->sendEmailWithLogging(
            $abstract->user->email,
            function() use ($abstract) {
                return new AbstractUpdatedNotification($abstract);
            },
            EmailLog::TYPE_ABSTRACT_UPDATED,
            $abstract->user->id,
            $abstract->id,
            "Abstract Updated - " . config('app.name')
        );
    }

    /**
     * Send abstract subtheme changed notification
     */
    public function sendAbstractSubthemeChangedNotification(AbstractSubmission $abstract, string $oldSubtheme, string $newSubtheme)
    {
        if (!$abstract->user || empty($abstract->user->email)) {
            return false;
        }

        return $this->sendEmailWithLogging(
            $abstract->user->email,
            function() use ($abstract, $oldSubtheme, $newSubtheme) {
                return new AbstractSubthemeChangedNotification($abstract, $oldSubtheme, $newSubtheme);
            },
            EmailLog::TYPE_SUBTHEME_CHANGED,
            $abstract->user->id,
            $abstract->id,
            "Abstract Subtheme Updated - " . config('app.name'),
            [
                'old_subtheme' => $oldSubtheme,
                'new_subtheme' => $newSubtheme,
            ]
        );
    }

    /**
     * Send abstract withdrawn notification
     */
    public function sendAbstractWithdrawnNotification(AbstractSubmission $abstract)
    {
        return $this->sendEmailWithLogging(
            $abstract->user->email,
            function() use ($abstract) {
                return new AbstractWithdrawnNotification($abstract);
            },
            EmailLog::TYPE_ABSTRACT_WITHDRAWN,
            $abstract->user->id,
            $abstract->id,
            "Abstract Withdrawn - " . config('app.name')
        );
    }

    /**
     * Send revision requested notification
     */
    public function sendRevisionRequestedNotification(AbstractSubmission $abstract, string $message = null)
    {
        return $this->sendEmailWithLogging(
            $abstract->user->email,
            function() use ($abstract, $message) {
                return new RevisionRequestedNotification($abstract, $message);
            },
            EmailLog::TYPE_REVISION_REQUESTED,
            $abstract->user->id,
            $abstract->id,
            "Revision Requested - " . config('app.name'),
            ['revision_message' => $message]
        );
    }

    /**
     * Send revision reminder notification
     */
    public function sendRevisionReminderNotification(AbstractSubmission $abstract, string $message = null)
    {
        return $this->sendEmailWithLogging(
            $abstract->user->email,
            function() use ($abstract, $message) {
                return new RevisionReminderNotification($abstract, $message);
            },
            EmailLog::TYPE_REVISION_REMINDER,
            $abstract->user->id,
            $abstract->id,
            "Reminder: Revision Submission Pending - " . config('app.name'),
            ['revision_message' => $message]
        );
    }

    /**
     * Send abstract accepted notification
     */
    public function sendAbstractAcceptedNotification(AbstractSubmission $abstract, string $message = null)
    {
        return $this->sendEmailWithLogging(
            $abstract->user->email,
            function() use ($abstract, $message) {
                $reviews = $abstract->reviews()->where('status', 'submitted')->get();
                return new AbstractDecisionNotification($abstract, 'accepted', $reviews, 'emails.abstract-accepted');
            },
            'abstract_accepted',
            $abstract->user->id,
            $abstract->id,
            "Abstract Accepted - " . config('app.name'),
            ['decision' => 'accepted', 'message' => $message]
        );
    }

    /**
     * Send clarification email to accepted authors who were already notified.
     */
    public function sendAcceptedClarificationNotification(AbstractSubmission $abstract)
    {
        $feedbackItems = $abstract->getAuthorVisibleFeedbackItems();

        if ($feedbackItems->isEmpty()) {
            return true;
        }

        return $this->sendEmailWithLogging(
            $abstract->user->email,
            function() use ($abstract) {
                return new AcceptedClarificationNotification($abstract);
            },
            EmailLog::TYPE_ACCEPTED_CLARIFICATION,
            $abstract->user->id,
            $abstract->id,
            "Presentation Guidance for Your Accepted Abstract - " . config('app.name'),
            [
                'status' => 'accepted',
                'feedback_count' => $feedbackItems->count(),
            ]
        );
    }

    public function sendAbstractSubmitterUpdateNotification(User $user)
    {
        return $this->sendEmailWithLogging(
            $user->email,
            function() use ($user) {
                return new AbstractSubmitterUpdateNotification($user);
            },
            'abstract_submitter_update',
            $user->id,
            null,
            "Important Update for All " . config('conference.short_name') . " " . config('conference.year') . " Abstract Submitters - " . config('app.name')
        );
    }

    /**
     * Send abstract rejected notification
     */
    public function sendAbstractRejectedNotification(AbstractSubmission $abstract, string $message = null)
    {
        return $this->sendEmailWithLogging(
            $abstract->user->email,
            function() use ($abstract, $message) {
                $reviews = $abstract->reviews()->where('status', 'submitted')->get();
                return new AbstractDecisionNotification($abstract, 'rejected', $reviews, 'emails.abstract-rejected');
            },
            'abstract_rejected',
            $abstract->user->id,
            $abstract->id,
            "Abstract Decision - " . config('app.name'),
            ['decision' => 'rejected', 'message' => $message]
        );
    }

    /**
     * Send presentation uploaded notification
     */
    public function sendPresentationUploadedNotification(AbstractSubmission $abstract)
    {
        return $this->sendEmailWithLogging(
            $abstract->user->email,
            function() use ($abstract) {
                return new PresentationUploadedNotification($abstract);
            },
            EmailLog::TYPE_PRESENTATION_UPLOADED,
            $abstract->user->id,
            $abstract->id,
            "Presentation Uploaded - " . config('app.name')
        );
    }

    /**
     * Send session assignment notification to author
     */
    public function sendSessionAssignmentNotification(AbstractSubmission $abstract)
    {
        return $this->sendEmailWithLogging(
            $abstract->user->email,
            function() use ($abstract) {
                return new SessionAssignmentNotification($abstract);
            },
            EmailLog::TYPE_SESSION_ASSIGNMENT,
            $abstract->user->id,
            $abstract->id,
            "Session Assignment - " . config('app.name')
        );
    }

    /**
     * Send session chair assignment notification
     */
    public function sendChairAssignment(User $chair, \App\Models\ConferenceSession $session, $options = [])
    {
        $confirmationUrl = route('sessions.confirm-assignment', [
            'sessionId' => $session->id,
            'userId' => $chair->id,
            'role' => 'chair',
            'token' => $session->confirmation_token
        ]);

        return $this->sendEmailWithLogging(
            $chair->email,
            function() use ($chair, $session, $options, $confirmationUrl) {
                $mailable = new \App\Mail\ChairAssignmentNotification($session, $chair);
                $mailable->with('confirmationUrl', $confirmationUrl);
                if (isset($options['pdf_data']) && isset($options['pdf_name'])) {
                    $mailable->attachData($options['pdf_data'], $options['pdf_name'], [
                        'mime' => 'application/pdf',
                    ]);
                }
                return $mailable;
            },
            'chair_assignment',
            $chair->id,
            null,
            "Invitation: Session Chairperson for " . config('conference.short_name') . " " . config('conference.year') . " - {$session->name}",
            array_merge($options, ['confirmation_url' => $confirmationUrl])
        );
    }

    /**
     * Send session rapporteur assignment notification
     */
    public function sendRapporteurAssignment(User $rapporteur, \App\Models\ConferenceSession $session, $options = [])
    {
        $confirmationUrl = route('sessions.confirm-assignment', [
            'sessionId' => $session->id,
            'userId' => $rapporteur->id,
            'role' => 'rapporteur',
            'token' => $session->confirmation_token
        ]);

        return $this->sendEmailWithLogging(
            $rapporteur->email,
            function() use ($rapporteur, $session, $options, $confirmationUrl) {
                $mailable = new \App\Mail\RapporteurAssignmentNotification($session, $rapporteur);
                $mailable->with('confirmationUrl', $confirmationUrl);
                if (isset($options['pdf_data']) && isset($options['pdf_name'])) {
                    $mailable->attachData($options['pdf_data'], $options['pdf_name'], [
                        'mime' => 'application/pdf',
                    ]);
                }
                return $mailable;
            },
            'rapporteur_assignment',
            $rapporteur->id,
            null,
            "Scientific Session Rapporteur Assignment - {$session->name}"
        );
    }

    /**
     * Notify a rapporteur that the Chief Rapporteur approved their session report.
     */
    public function sendRapporteurReportApproved(\App\Models\RapporteurReport $report, ?string $note = null)
    {
        $author = $report->user;
        if (! $author || ! $author->email) {
            return false;
        }

        return $this->sendEmailWithLogging(
            $author->email,
            fn () => new \App\Mail\RapporteurReportApproved($report, $note),
            'rapporteur_report_approved',
            $author->id,
            null,
            'Your session report has been approved — ' . config('conference.short_name') . ' ' . config('conference.year')
        );
    }

    /**
     * Notify a rapporteur that the Chief Rapporteur returned their report for revision.
     */
    public function sendRapporteurReportReturned(\App\Models\RapporteurReport $report, string $feedback)
    {
        $author = $report->user;
        if (! $author || ! $author->email) {
            return false;
        }

        return $this->sendEmailWithLogging(
            $author->email,
            fn () => new \App\Mail\RapporteurReportReturned($report, $feedback),
            'rapporteur_report_returned',
            $author->id,
            null,
            'Action needed: revise your session report — ' . config('conference.short_name') . ' ' . config('conference.year')
        );
    }

    /**
     * Send review completion notification to reviewer
     */
    public function sendReviewCompletionNotification(AbstractSubmission $abstract, User $reviewer, $reviewerPosition = 1)
    {
        return $this->sendEmailWithLogging(
            $reviewer->email,
            function() use ($abstract, $reviewer, $reviewerPosition) {
                return new ReviewCompletionNotification($abstract, $reviewer, $reviewerPosition);
            },
            EmailLog::TYPE_REVIEW_COMPLETION,
            $reviewer->id,
            $abstract->id,
            "Review Completed - Abstract #{$abstract->id}",
            ['reviewer_position' => $reviewerPosition]
        );
    }

    /**
     * Send review submission notification to admin
     */
    public function sendReviewSubmissionNotification(AbstractSubmission $abstract, User $reviewer, $reviewerPosition = 1)
    {
        // Get admin users
        $adminUsers = User::whereHas('roles', function($query) {
            $query->where('name', 'admin');
        })->get();

        $successCount = 0;
        foreach ($adminUsers as $admin) {
            try {
                Mail::to($admin->email)->send(new \App\Mail\ReviewSubmissionNotification($abstract, $reviewer, $reviewerPosition));
                $successCount++;
            } catch (\Exception $e) {
                Log::error("Failed to send review submission notification to admin {$admin->email}: " . $e->getMessage());
            }
        }

        // Log the notification
        EmailLog::create([
            'recipient_email' => config('conference.contact_email'), // Generic admin email for logging
            'email_type' => EmailLog::TYPE_REVIEW_SUBMISSION,
            'user_id' => $reviewer->id,
            'abstract_submission_id' => $abstract->id,
            'subject' => "Review Submitted - Abstract #{$abstract->id}",
            'metadata' => [
                'reviewer_position' => $reviewerPosition,
                'admin_count' => $adminUsers->count(),
                'success_count' => $successCount
            ],
            'sent_at' => now(),
            'status' => $successCount > 0 ? 'sent' : 'failed'
        ]);

        return $successCount > 0;
    }

    /**
     * Send subtheme change recommendation notification to all scientific admins.
     */
    public function sendSubthemeChangeRecommendationNotification(AbstractSubmission $abstract, User $reviewer, int $reviewerPosition, string $suggestedSubtheme)
    {
        $scientificAdmins = User::whereHas('roles', function($query) {
            $query->where('name', 'scientific_admin');
        })->get();

        $successCount = 0;
        foreach ($scientificAdmins as $admin) {
            try {
                Mail::to($admin->email)->send(
                    new SubthemeChangeRecommendationNotification($abstract, $reviewer, $reviewerPosition, $suggestedSubtheme)
                );
                $successCount++;
            } catch (\Exception $e) {
                Log::error("Failed to send subtheme change recommendation to {$admin->email}: " . $e->getMessage());
            }
        }

        return $successCount > 0;
    }

    /**
     * Send student ID verified notification
     */
    public function sendStudentIDVerified(User $user)
    {
        return $this->sendEmailWithLogging(
            $user->email,
            function() use ($user) {
                return new StudentIDVerifiedNotification($user);
            },
            EmailLog::TYPE_STUDENT_ID_VERIFIED,
            $user->id,
            null,
            "Student Identity Verified - " . config('app.name')
        );
    }

    /**
     * Send student ID rejected notification
     */
    public function sendStudentIDRejected(User $user, $notes = null)
    {
        return $this->sendEmailWithLogging(
            $user->email,
            function() use ($user, $notes) {
                return new StudentIDRejectedNotification($user, $notes);
            },
            EmailLog::TYPE_STUDENT_ID_REJECTED,
            $user->id,
            null,
            "Action Required: Student Identity Rejected - " . config('app.name'),
            ['notes' => $notes]
        );
    }

    /**
     * Send notification when both reviews are complete
     */
    public function sendBothReviewsCompleteNotification(AbstractSubmission $abstract, User $reviewer1, User $reviewer2, $averageScore, $finalDecision = null)
    {
        // Send to admin/committee members
        $adminUsers = User::whereHas('roles', function($query) {
            $query->where('name', 'admin');
        })->get();

        foreach ($adminUsers as $admin) {
            $this->sendEmailWithLogging(
                $admin->email,
                function() use ($abstract, $reviewer1, $reviewer2, $averageScore, $finalDecision) {
                    return new BothReviewsCompleteNotification($abstract, $reviewer1, $reviewer2, $averageScore, $finalDecision);
                },
                EmailLog::TYPE_BOTH_REVIEWS_COMPLETE,
                $admin->id,
                $abstract->id,
                "Both Reviews Complete - Abstract #{$abstract->id}",
                [
                    'reviewer1_id' => $reviewer1->id,
                    'reviewer2_id' => $reviewer2->id,
                    'average_score' => $averageScore,
                    'final_decision' => $finalDecision
                ]
            );
        }
    }

    /**
     * Send personalized progress update to reviewer
     */
    public function sendReviewerProgressUpdate(User $reviewer)
    {
        return $this->sendEmailWithLogging(
            $reviewer->email,
            function() use ($reviewer) {
                return new ReviewerProgressUpdateNotification($reviewer);
            },
            EmailLog::TYPE_PROGRESS_UPDATE,
            $reviewer->id,
            null,
            "Review Progress Update - " . config('app.name')
        );
    }

    /**
     * Send review reminder with 2-day delay
     */
    public function sendDelayedReviewReminder(AbstractSubmission $abstract, User $reviewer, $reviewerPosition = 1)
    {
        return $this->sendEmailWithLogging(
            $reviewer->email,
            function() use ($abstract, $reviewer, $reviewerPosition) {
                return new ReviewReminderNotification($abstract, $reviewer, $reviewerPosition);
            },
            EmailLog::TYPE_REVIEW_REMINDER,
            $reviewer->id,
            $abstract->id,
            "Review Reminder - Abstract #{$abstract->id}",
            ['reviewer_position' => $reviewerPosition, 'delayed' => true]
        );
    }

    /**
     * Send revision resubmitted notification to reviewer (24-hour deadline)
     */
    public function sendRevisionResubmittedNotification(AbstractSubmission $abstract, User $reviewer, $reviewerPosition = 1)
    {
        return $this->sendEmailWithLogging(
            $reviewer->email,
            function() use ($abstract, $reviewer, $reviewerPosition) {
                return new \App\Mail\RevisionReReviewNotification($abstract, $reviewer, $reviewerPosition);
            },
            'revision_rereview',
            $reviewer->id,
            $abstract->id,
            "⏰ URGENT: Revision Re-Review Required (24h) - Abstract #{$abstract->id}",
            ['review_type' => $reviewerPosition, 'is_revision' => true]
        );
    }

    /**
     * Send decision notification to reviewer when abstract is accepted
     */
    public function sendReviewerDecisionNotification(AbstractSubmission $abstract, User $reviewer, string $decision, string $message = null)
    {
        return $this->sendEmailWithLogging(
            $reviewer->email,
            function() use ($abstract, $reviewer, $decision, $message) {
                return new \App\Mail\ReviewerDecisionNotification($abstract, $reviewer, $decision, $message);
            },
            'reviewer_decision_notification',
            $reviewer->id,
            $abstract->id,
            "Decision Made - Abstract #{$abstract->id}",
            ['decision' => $decision, 'message' => $message]
        );
    }

    /**
     * Send decision notifications to all reviewers of an abstract
     */
    public function sendReviewerDecisionNotifications(AbstractSubmission $abstract, string $decision, string $message = null)
    {
        $reviewers = [];
        $successCount = 0;
        $failCount = 0;

        // Get reviewer 1
        if ($abstract->reviewer_id) {
            $reviewer1 = User::find($abstract->reviewer_id);
            if ($reviewer1) {
                $reviewers[] = $reviewer1;
            }
        }

        // Get reviewer 2
        if ($abstract->reviewer_2_id) {
            $reviewer2 = User::find($abstract->reviewer_2_id);
            if ($reviewer2) {
                $reviewers[] = $reviewer2;
            }
        }

        // Send notification to each reviewer
        foreach ($reviewers as $reviewer) {
            if ($this->sendReviewerDecisionNotification($abstract, $reviewer, $decision, $message)) {
                $successCount++;
            } else {
                $failCount++;
            }
        }

        Log::info("Reviewer decision notifications queued for abstract #{$abstract->id}: {$successCount} queued, {$failCount} failed to queue");

        return ['success' => $successCount, 'failed' => $failCount, 'total' => count($reviewers)];
    }

    /**
     * Log the email now, then send it from the queue so the browser does not wait on SMTP.
     */
    private function sendEmailWithLogging(
        string $recipientEmail,
        callable $mailableFactory,
        string $emailType,
        ?int $userId = null,
        ?int $abstractSubmissionId = null,
        string $subject = '',
        array $metadata = []
    ) {
        // Create email log entry
        $emailLog = EmailLog::create([
            'user_id' => $userId,
            'abstract_submission_id' => $abstractSubmissionId,
            'email_type' => $emailType,
            'recipient_email' => $recipientEmail,
            'subject' => $subject,
            'status' => EmailLog::STATUS_PENDING,
            'metadata' => $metadata
        ]);

        try {
            if ($abstractSubmissionId && !AbstractSubmission::whereKey($abstractSubmissionId)->exists()) {
                Log::warning("Abstract #{$abstractSubmissionId} not found for email type {$emailType}");
                $emailLog->update([
                    'status' => EmailLog::STATUS_FAILED,
                    'error_message' => 'Abstract not found'
                ]);
                return false;
            }

            if ($userId && !User::whereKey($userId)->exists()) {
                Log::warning("User #{$userId} not found for email type {$emailType}");
                $emailLog->update([
                    'status' => EmailLog::STATUS_FAILED,
                    'error_message' => 'User not found'
                ]);
                return false;
            }

            SendLoggedEmail::dispatch($emailLog->id, $recipientEmail, $mailableFactory())
                ->onConnection('database')
                ->onQueue($this->queueForEmailType($emailType));

            Log::info("Email queued for delivery", [
                'type' => $emailType,
                'recipient' => $recipientEmail,
                'log_id' => $emailLog->id,
            ]);

            return true;

        } catch (Throwable $e) {
            $emailLog->update([
                'status' => EmailLog::STATUS_FAILED,
                'error_message' => $e->getMessage()
            ]);

            Log::error("Unexpected error while queueing email", [
                'type' => $emailType,
                'recipient' => $recipientEmail,
                'error' => $e->getMessage(),
                'log_id' => $emailLog->id
            ]);

            return false;
        }
    }

    private function queueForEmailType(string $emailType): string
    {
        return match ($emailType) {
            EmailLog::TYPE_STATUS_NOTIFICATION,
            EmailLog::TYPE_SUBMISSION_CONFIRMATION => 'emails-critical',
            EmailLog::TYPE_CODE_ASSIGNMENT,
            EmailLog::TYPE_PAYMENT_VERIFIED,
            EmailLog::TYPE_REVISION_REQUESTED,
            EmailLog::TYPE_REVISION_REMINDER,
            EmailLog::TYPE_SESSION_ASSIGNMENT => 'emails-high-priority',
            EmailLog::TYPE_DRAFT_SAVED => 'emails-low-priority',
            default => 'emails',
        };
    }

    /**
     * Send bulk review reminders to all reviewers with pending reviews
     */
    public function sendBulkReviewReminders(Carbon $deadline = null)
    {
        $successCount = 0;
        $failCount = 0;

        $pendingAbstracts = $this->pendingReviewAssignmentsQuery($deadline)
            ->with(['reviewer1', 'reviewer2'])
            ->orderByRaw('COALESCE(assigned_at, created_at) asc')
            ->get();

        $pendingReviewsByReviewer = collect();
        $reviewers = collect();

        foreach ($pendingAbstracts as $abstract) {
            if ($abstract->reviewer1 && is_null($abstract->reviewer_1_score)) {
                $this->addPendingReviewForReviewer($pendingReviewsByReviewer, $reviewers, $abstract->reviewer1, $abstract);
            }

            if ($abstract->reviewer2 && is_null($abstract->reviewer_2_score)) {
                $this->addPendingReviewForReviewer($pendingReviewsByReviewer, $reviewers, $abstract->reviewer2, $abstract);
            }
        }

        foreach ($pendingReviewsByReviewer as $reviewerId => $pendingReviews) {
            $reviewer = $reviewers->get($reviewerId);
            if (!$reviewer) {
                $failCount++;
                continue;
            }

            if ($this->sendReviewReminder($reviewer, $pendingReviews, $deadline)) {
                $successCount++;
            } else {
                $failCount++;
            }
        }

        Log::info("Bulk review reminders queued: {$successCount} queued, {$failCount} failed to queue");
        return ['success' => $successCount, 'failed' => $failCount];
    }

    /**
     * Send bulk status notifications for multiple abstracts
     */
    public function sendBulkStatusNotifications(array $abstractIds, string $status, string $message = null)
    {
        $successCount = 0;
        $failCount = 0;

        $abstracts = AbstractSubmission::with('user')->whereIn('id', $abstractIds)->get();

        foreach ($abstracts as $abstract) {
            if ($this->sendStatusNotification($abstract, $status, $message)) {
                $successCount++;
            } else {
                $failCount++;
            }
        }

        Log::info("Bulk status notifications ({$status}) queued: {$successCount} queued, {$failCount} failed to queue");
        return ['success' => $successCount, 'failed' => $failCount];
    }

    /**
     * Send bulk conference code assignment notifications
     */
    public function sendBulkConferenceCodeNotifications(array $abstractIds)
    {
        $successCount = 0;
        $failCount = 0;

        $abstracts = AbstractSubmission::with('user')
            ->whereIn('id', $abstractIds)
            ->whereNotNull('conference_code')
            ->get();

        foreach ($abstracts as $abstract) {
            if ($this->sendConferenceCodeAssignment($abstract)) {
                $successCount++;
            } else {
                $failCount++;
            }
        }

        Log::info("Bulk conference code notifications queued: {$successCount} queued, {$failCount} failed to queue");
        return ['success' => $successCount, 'failed' => $failCount];
    }

    /**
     * Get email statistics
     */
    public function getEmailStats()
    {
        // This would typically pull from a dedicated email tracking table
        // For now, return some basic stats
        return [
            'total_sent_today' => 0, // Would count from logs or email tracking table
            'pending_notifications' => $this->getPendingNotificationsCount(),
            'failed_emails_today' => 0, // Would count from logs
            'reviewers_notified_today' => 0, // Would count from logs
        ];
    }

    /**
     * Get count of pending notifications
     */
    private function getPendingNotificationsCount()
    {
        $count = 0;

        // Newly submitted abstracts without confirmation emails
        $count += AbstractSubmission::where('status', 'submitted')
            ->whereNull('email_sent_at') // Would need to add this column
            ->count();

        // Pending reviewer assignments
        $count += $this->pendingReviewAssignmentsQuery()->count();

        return $count;
    }

    private function pendingReviewAssignmentsQuery(?Carbon $deadline = null)
    {
        $query = AbstractSubmission::query()
            ->whereIn('status', ['submitted', 'under_review'])
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->whereNotNull('reviewer_id')
                        ->whereNull('reviewer_1_score');
                })->orWhere(function ($q) {
                    $q->whereNotNull('reviewer_2_id')
                        ->whereNull('reviewer_2_score');
                });
            });

        if ($deadline) {
            $deadlineDate = $deadline->toDateString();

            $query->where(function ($query) use ($deadlineDate) {
                $query->whereDate('assigned_at', '<=', $deadlineDate)
                    ->orWhere(function ($q) use ($deadlineDate) {
                        $q->whereNull('assigned_at')
                            ->whereDate('created_at', '<=', $deadlineDate);
                    });
            });
        }

        return $query;
    }

    private function addPendingReviewForReviewer(
        Collection $pendingReviewsByReviewer,
        Collection $reviewers,
        ?User $reviewer,
        AbstractSubmission $abstract
    ): void {
        if (!$reviewer) {
            return;
        }

        $reviewers->put($reviewer->id, $reviewer);

        if (!$pendingReviewsByReviewer->has($reviewer->id)) {
            $pendingReviewsByReviewer->put($reviewer->id, collect());
        }

        $pendingReviewsByReviewer->get($reviewer->id)->push($abstract);
    }

    /**
     * Send payment verified notification
     */
    public function sendPaymentVerified(User $user, $notes = null)
    {
        return $this->sendEmailWithLogging(
            $user->email,
            function() use ($user, $notes) {
                return new PaymentVerifiedNotification($user, $notes);
            },
            'payment_verified',
            $user->id,
            null,
            "Payment Verified - " . config('app.name'),
            ['notes' => $notes]
        );
    }

    /**
     * Send payment rejected notification
     */
    public function sendPaymentRejected(User $user, string $notes)
    {
        return $this->sendEmailWithLogging(
            $user->email,
            function() use ($user, $notes) {
                return new PaymentRejectedNotification($user, $notes);
            },
            'payment_rejected',
            $user->id,
            null,
            "Action Required: Payment Evidence Rejected - " . config('app.name'),
            ['notes' => $notes]
        );
    }

    /**
     * Notify finance officers when a user submits payment details
     */
    public function notifyFinanceOfPaymentSubmission(User $user)
    {
        $financeOfficers = User::whereHas('roles', function($query) {
            $query->where('name', 'finance_officer');
        })->get();

        foreach ($financeOfficers as $admin) {
            $this->sendEmailWithLogging(
                $admin->email,
                function() use ($user) {
                    return new PaymentSubmittedNotification($user);
                },
                'payment_submitted_admin',
                $admin->id,
                null,
                "New Payment Submitted - " . $user->full_name,
                ['user_id' => $user->id]
            );
        }

        return true;
    }

    /**
     * Send fee waived notification
     */
    public function sendFeeWaived(User $user, $notes)
    {
        return $this->sendEmailWithLogging(
            $user->email,
            function() use ($user, $notes) {
                return new FeeWaivedNotification($user, $notes);
            },
            'fee_waived',
            $user->id,
            null,
            "Registration Fee Waived - " . config('app.name'),
            ['notes' => $notes]
        );
    }
    /**
     * Send fee waived notification to a group leader
     */
    public function sendGroupFeeWaived($groupRegistration, $notes)
    {
        $leader = $groupRegistration->leader;

        return $this->sendEmailWithLogging(
            $leader->email,
            function() use ($leader, $notes) {
                return new FeeWaivedNotification($leader, $notes);
            },
            'fee_waived',
            $leader->id,
            null,
            "Group Registration Fee Waived - " . config('app.name'),
            ['notes' => $notes, 'group_id' => $groupRegistration->id]
        );
    }

    /**
     * Send payment submission confirmation to user
     */
    public function sendPaymentSubmissionConfirmation(User $user)
    {
        return $this->sendEmailWithLogging(
            $user->email,
            function() use ($user) {
                return new PaymentSubmissionConfirmation($user);
            },
            'payment_submission_confirmation',
            $user->id,
            null,
            "Payment Proof Received - " . config('app.name'),
            ['user_id' => $user->id]
        );
    }

    /**
     * Send group payment verified notification
     */
    public function sendGroupPaymentVerified($groupRegistration, $notes = null)
    {
        return $this->sendEmailWithLogging(
            $groupRegistration->leader->email,
            function() use ($groupRegistration, $notes) {
                return new GroupPaymentVerifiedNotification($groupRegistration, $notes);
            },
            'group_payment_verified',
            $groupRegistration->leader_user_id,
            null,
            "Group Payment Verified - " . config('app.name'),
            ['notes' => $notes, 'group_id' => $groupRegistration->id]
        );
    }

    /**
     * Send group payment rejected notification
     */
    public function sendGroupPaymentRejected($groupRegistration, $notes = null)
    {
        return $this->sendEmailWithLogging(
            $groupRegistration->leader->email,
            function() use ($groupRegistration, $notes) {
                return new GroupPaymentRejectedNotification($groupRegistration, $notes);
            },
            'group_payment_rejected',
            $groupRegistration->leader_user_id,
            null,
            "Action Required: Group Payment Proof Rejected - " . config('app.name'),
            ['notes' => $notes, 'group_id' => $groupRegistration->id]
        );
    }

    /**
     * Notify Finance Officers when a user submits group payment proof
     */
    public function notifyFinanceOfGroupPaymentSubmission($groupRegistration)
    {
        $financeOfficers = User::whereHas('roles', function($query) {
            $query->where('name', 'finance_officer');
        })->get();

        foreach ($financeOfficers as $officer) {
            $this->sendEmailWithLogging(
                $officer->email,
                function() use ($groupRegistration) {
                    return new PaymentSubmittedNotification($groupRegistration->leader, $groupRegistration);
                },
                'group_payment_submitted_finance',
                $officer->id,
                null,
                "New Group Payment Proof Submitted - " . $groupRegistration->group_name,
                ['group_id' => $groupRegistration->id, 'leader_id' => $groupRegistration->leader_user_id]
            );
        }

        return true;
    }

    /**
     * Send committee decision notification to author
     * This is called when the scientific committee makes a final decision
     */
    public function sendCommitteeDecisionNotification(AbstractSubmission $abstract, string $decision, ?string $notes = null)
    {
        // Get committee notes from abstract if not provided
        $notesToSend = $notes ?? $abstract->committee_notes ?? $abstract->admin_comment;

        return $this->sendEmailWithLogging(
            $abstract->user->email,
            function() use ($abstract, $decision, $notesToSend) {
                return new \App\Mail\CommitteeDecisionNotification($abstract, $decision, $notesToSend);
            },
            'committee_decision',
            $abstract->user->id,
            $abstract->id,
            "Committee Decision - " . config('app.name'),
            ['decision' => $decision, 'notes' => $notesToSend]
        );
    }

    /**
     * Send welcome notification to new user after email verification
     */
    public function sendWelcomeNotification(User $user)
    {
        return $this->sendEmailWithLogging(
            $user->email,
            function() use ($user) {
                return new \App\Mail\WelcomeNotification($user);
            },
            'welcome_notification',
            $user->id,
            null,
            "Welcome to " . config('conference.short_name') . " " . config('conference.year') . " - " . config('app.name')
        );
    }

    /**
     * Send presentation upload reminder to author
     */
    public function sendPresentationReminder(AbstractSubmission $abstract)
    {
        return $this->sendEmailWithLogging(
            $abstract->user->email,
            function() use ($abstract) {
                return new \App\Mail\PresentationReminderNotification($abstract);
            },
            'presentation_reminder',
            $abstract->user->id,
            $abstract->id,
            "Reminder: Please Upload Your " . ucfirst($abstract->presentation_mode) . " Materials - " . config('conference.short_name') . " " . config('conference.year')
        );
    }

    /**
     * Send payment reminder to a user
     */
    public function sendPaymentReminder(User $user)
    {
        return $this->sendEmailWithLogging(
            $user->email,
            function() use ($user) {
                return new \App\Mail\PaymentReminderNotification($user);
            },
            'payment_reminder',
            $user->id,
            null,
            "Action Required: Conference Registration Payment Reminder - " . config('conference.short_name') . " " . config('conference.year')
        );
    }

    /**
     * Send a general conference broadcast email
     */
    public function sendConferenceBroadcast(User $user, string $subject, string $messageText, ?string $actionUrl = null, ?string $actionText = null, array $attachments = [])
    {
        return $this->sendEmailWithLogging(
            $user->email,
            function() use ($user, $subject, $messageText, $actionUrl, $actionText, $attachments) {
                return new \App\Mail\ConferenceBroadcastNotification($user, $subject, $messageText, $actionUrl, $actionText, $attachments);
            },
            'conference_broadcast',
            $user->id,
            null,
            $subject,
            [
                'attachments' => collect($attachments)
                    ->map(fn ($attachment) => [
                        'name' => $attachment['name'] ?? null,
                        'mime' => $attachment['mime'] ?? null,
                        'size' => $attachment['size'] ?? null,
                    ])
                    ->filter(fn ($attachment) => !empty($attachment['name']))
                    ->values()
                    ->all(),
            ]
        );
    }

    /**
     * Send CPD details reminder to a user.
     */
    public function sendCpdReminder(User $user)
    {
        return $this->sendEmailWithLogging(
            $user->email,
            function() use ($user) {
                return new CpdReminderNotification($user);
            },
            EmailLog::TYPE_CPD_REMINDER,
            $user->id,
            null,
            'Action Required: Update Your CPD Details — ' . config('conference.short_name') . ' ' . config('conference.year'),
            ['profile_url' => route('profile.edit')]
        );
    }

    /**
     * Notify a user that their attendance certificate is ready to download.
     */
    public function sendCertificateReady(\App\Models\User $user, \App\Models\Certificate $certificate)
    {
        return $this->sendEmailWithLogging(
            $user->email,
            function() use ($user, $certificate) {
                return new \App\Mail\CertificateReadyNotification($user, $certificate);
            },
            EmailLog::TYPE_CERTIFICATE_READY,
            $user->id,
            null,
            'Your Certificate of Attendance is Ready — ' . config('conference.short_name') . ' ' . config('conference.year')
        );
    }

    /**
     * Notify a presenter that their oral or poster certificate is ready to download.
     */
    public function sendPresenterCertificateReady(\App\Models\User $user, \App\Models\Certificate $certificate, \App\Models\AbstractSubmission $abstract)
    {
        return $this->sendEmailWithLogging(
            $user->email,
            function() use ($user, $certificate, $abstract) {
                return new \App\Mail\PresenterCertificateNotification($user, $certificate, $abstract);
            },
            EmailLog::TYPE_PRESENTER_CERTIFICATE,
            $user->id,
            $abstract->id,
            (strtolower($abstract->presentation_mode) === 'poster' ? 'Your Poster Presentation' : 'Your Oral Presentation')
                . ' Certificate is Ready — ' . config('conference.short_name') . ' ' . config('conference.year')
        );
    }
}
