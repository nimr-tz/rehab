<?php

namespace App\Services;

use App\Models\AbstractSubmission;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\RevisionRequestedNotification;
use App\Mail\AbstractDecisionNotification;

class EmailService
{
    /**
     * Send notification to author when a revision is requested.
     *
     * @param AbstractSubmission $abstract
     * @param string|null $feedback
     * @return bool
     */
    public function sendRevisionRequestedNotification(AbstractSubmission $abstract, ?string $feedback): bool
    {
        try {
            Mail::to($abstract->user->email)->send(new RevisionRequestedNotification($abstract, $feedback));
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send Revision Requested email for abstract #{$abstract->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send notification to author when abstract is accepted.
     *
     * @param AbstractSubmission $abstract
     * @param string|null $message
     * @return bool
     */
    public function sendAbstractAcceptedNotification(AbstractSubmission $abstract, ?string $message): bool
    {
        try {
            $reviews = $abstract->reviews;

            Mail::to($abstract->user->email)->send(new AbstractDecisionNotification(
                $abstract,
                'accepted',
                $reviews,
                'emails.abstract-accepted'
            ));
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send Abstract Accepted email for abstract #{$abstract->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send notification to author when abstract is rejected.
     *
     * @param AbstractSubmission $abstract
     * @param string|null $message
     * @return bool
     */
    public function sendAbstractRejectedNotification(AbstractSubmission $abstract, ?string $message): bool
    {
        try {
             // Pass empty reviews array if not available, or fetch them if needed
            $reviews = $abstract->reviews;

            Mail::to($abstract->user->email)->send(new AbstractDecisionNotification(
                $abstract,
                'reject',
                $reviews,
                'emails.abstract-rejected'
            ));
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send Abstract Rejected email for abstract #{$abstract->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send payment verified notification
     */
    public function sendPaymentVerified($user, $notes = null)
    {
        Log::info("Email: Payment Verified for {$user->email}");
        // Implement actual mail sending if needed
        return true;
    }

    /**
     * Send payment rejected notification
     */
    public function sendPaymentRejected($user, $notes = null)
    {
        Log::info("Email: Payment Rejected for {$user->email}");
        return true;
    }

    /**
     * Send fee waived notification
     */
    public function sendFeeWaived($user, $notes = null)
    {
        Log::info("Email: Fee Waived for {$user->email}");
        return true;
    }

    /**
     * Send group payment verified notification
     */
    public function sendGroupPaymentVerified($group, $notes = null)
    {
        Log::info("Email: Group Payment Verified for leader {$group->leader->email}");
        return true;
    }

    /**
     * Send group payment rejected notification
     */
    public function sendGroupPaymentRejected($group, $notes = null)
    {
        Log::info("Email: Group Payment Rejected for leader {$group->leader->email}");
        return true;
    }

    /**
     * Send group fee waived notification
     */
    public function sendGroupFeeWaived($group, $notes = null)
    {
        Log::info("Email: Group Fee Waived for leader {$group->leader->email}");
        return true;
    }
}
