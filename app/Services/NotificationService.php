<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationService
{
    /**
     * Create a notification for a single user
     */
    public function createNotification(
        User $user,
        string $type,
        string $title,
        string $message,
        array $data = [],
        string $actionUrl = null,
        string $priority = 'medium',
        \DateTime $expiresAt = null
    ): Notification {
        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'action_url' => $actionUrl,
            'priority' => $priority,
            'expires_at' => $expiresAt,
        ]);

        return $notification;
    }

    /**
     * Create notifications for multiple users
     */
    public function createBulkNotifications(
        Collection $users,
        string $type,
        string $title,
        string $message,
        array $data = [],
        string $actionUrl = null,
        string $priority = 'medium',
        \DateTime $expiresAt = null
    ): Collection {
        $notifications = collect();
        
        foreach ($users as $user) {
            $notifications->push($this->createNotification(
                $user,
                $type,
                $title,
                $message,
                $data,
                $actionUrl,
                $priority,
                $expiresAt
            ));
        }
        
        return $notifications;
    }

    /**
     * Create a review assignment notification
     */
    public function createReviewAssignmentNotification(
        User $reviewer,
        string $abstractTitle,
        string $abstractId,
        \DateTime $deadline = null
    ): Notification {
        $deadlineText = $deadline ? " (Deadline: " . $deadline->format('M j, Y') . ")" : "";
        
        return $this->createNotification(
            $reviewer,
            'review_assignment',
            'New Review Assignment',
            "You have been assigned to review: \"{$abstractTitle}\"{$deadlineText}",
            [
                'abstract_id' => $abstractId,
                'abstract_title' => $abstractTitle,
                'deadline' => $deadline?->toISOString(),
            ],
            route('reviewer.review', $abstractId),
            'medium',
            $deadline
        );
    }

    /**
     * Create a status change notification
     */
    public function createStatusChangeNotification(
        User $user,
        string $abstractTitle,
        string $oldStatus,
        string $newStatus,
        string $abstractId
    ): Notification {
        $statusMessages = [
            'pending' => 'Your abstract is pending review',
            'under_review' => 'Your abstract is now under review',
            'ready_for_decision' => 'Your abstract is awaiting a final admin decision',
            'accepted' => 'Congratulations! Your abstract has been accepted',
            'rejected' => 'Your abstract was not accepted for this conference',
            'revision_requested' => 'Revisions have been requested for your abstract',
            'withdrawn' => 'Your abstract has been withdrawn'
        ];

        $message = $statusMessages[$newStatus] ?? "Your abstract status has changed from {$oldStatus} to {$newStatus}";

        return $this->createNotification(
            $user,
            'status_change',
            'Abstract Status Updated',
            $message,
            [
                'abstract_id' => $abstractId,
                'abstract_title' => $abstractTitle,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ],
            route('abstracts.show', $abstractId),
            $newStatus === 'accepted' ? 'high' : 'medium'
        );
    }

    /**
     * Create a deadline reminder notification
     */
    public function createDeadlineReminderNotification(
        User $user,
        string $title,
        string $message,
        \DateTime $deadline,
        string $actionUrl = null
    ): Notification {
        return $this->createNotification(
            $user,
            'deadline_reminder',
            $title,
            $message,
            [
                'deadline' => $deadline->toISOString(),
            ],
            $actionUrl,
            'high',
            $deadline
        );
    }

    /**
     * Create a quality flag notification
     */
    public function createQualityFlagNotification(
        User $admin,
        string $abstractTitle,
        string $abstractId,
        string $reason
    ): Notification {
        return $this->createNotification(
            $admin,
            'quality_flag',
            'Quality Flag Raised',
            "Quality flag raised for abstract: \"{$abstractTitle}\". Reason: {$reason}",
            [
                'abstract_id' => $abstractId,
                'abstract_title' => $abstractTitle,
                'reason' => $reason,
            ],
            route('admin.decisions.show', $abstractId),
            'high'
        );
    }

    /**
     * Create a notification for all users with a specific role
     */
    public function createRoleNotification(
        string $roleName,
        string $type,
        string $title,
        string $message,
        array $data = [],
        string $actionUrl = null,
        string $priority = 'medium'
    ): Collection {
        $users = User::whereHas('roles', function($query) use ($roleName) {
            $query->where('name', $roleName);
        })->get();

        return $this->createBulkNotifications(
            $users,
            $type,
            $title,
            $message,
            $data,
            $actionUrl,
            $priority
        );
    }

    /**
     * Create a notification for all administrators
     */
    public function createAdminNotification(
        string $type,
        string $title,
        string $message,
        array $data = [],
        string $actionUrl = null,
        string $priority = 'medium'
    ): Collection {
        return $this->createRoleNotification('admin', $type, $title, $message, $data, $actionUrl, $priority);
    }

    /**
     * Create an abstract action notification for the author
     */
    public function createAbstractActionNotification(
        User $user,
        string $title,
        string $message,
        string $abstractId,
        string $actionUrl = null
    ): Notification {
        return $this->createNotification(
            $user,
            'abstract_action',
            $title,
            $message,
            ['abstract_id' => $abstractId],
            $actionUrl ?: route('abstracts.show', $abstractId)
        );
    }

    /**
     * Create a payment notification for the user
     */
    public function createPaymentNotification(
        User $user,
        string $status,
        string $message,
        string $actionUrl = null
    ): Notification {
        return $this->createNotification(
            $user,
            'payment_update',
            'Payment Status Updated',
            $message,
            ['status' => $status],
            $actionUrl ?: route('dashboard'),
            $status === 'verified' ? 'medium' : 'high'
        );
    }

    /**
     * Create a presentation reminder notification
     */
    public function createPresentationReminderNotification(
        User $user,
        string $abstractTitle,
        string $abstractId
    ): Notification {
        return $this->createNotification(
            $user,
            'deadline_reminder',
            'Presentation Upload Required',
            "Please upload your presentation files for: \"{$abstractTitle}\"",
            ['abstract_id' => $abstractId],
            route('presentations.show', $abstractId),
            'high'
        );
    }

    /**
     * Create an official conference announcement notification.
     */
    public function createAnnouncementNotification(
        User $user,
        int|string $announcementId,
        string $announcementTitle,
        string $message,
        bool $isUrgent = false,
        string $actionUrl = null
    ): Notification {
        return $this->createNotification(
            $user,
            'announcement',
            $isUrgent ? 'Urgent Announcement' : 'Conference Announcement',
            $message,
            [
                'type' => 'announcement',
                'announcement_id' => $announcementId,
                'announcement_title' => $announcementTitle,
                'entity_type' => 'announcement',
                'entity_id' => $announcementId,
                'action' => 'open_announcement',
            ],
            $actionUrl,
            $isUrgent ? 'high' : 'medium'
        );
    }

    /**
     * Create a welcome notification for new users
     */
    public function createWelcomeNotification(User $user): Notification
    {
        return $this->createNotification(
            $user,
            'system_alert',
            'Welcome to ' . config('conference.short_name') . ' ' . config('conference.year'),
            'Thank you for joining the ' . config('conference.edition') . ' ' . config('conference.name') . ' ' . config('conference.year') . '. We are excited to have you!',
            [],
            route('dashboard'),
            'medium'
        );
    }

    /**
     * Create a system alert notification
     */
    public function createSystemAlertNotification(
        User $user,
        string $title,
        string $message,
        string $priority = 'medium',
        string $actionUrl = null
    ): Notification {
        return $this->createNotification(
            $user,
            'system_alert',
            $title,
            $message,
            [],
            $actionUrl,
            $priority
        );
    }

    /**
     * Get unread notifications for a user
     */
    public function getUnreadNotifications(User $user, int $limit = 10): Collection
    {
        return $user->notifications()
            ->unread()
            ->active()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get all notifications for a user
     */
    public function getAllNotifications(User $user, int $limit = 50): Collection
    {
        return $user->notifications()
            ->active()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Notification $notification): bool
    {
        return $notification->markAsRead();
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead(User $user): int
    {
        return $user->notifications()
            ->unread()
            ->update(['read_at' => now()]);
    }

    /**
     * Delete expired notifications
     */
    public function cleanupExpiredNotifications(): int
    {
        return Notification::where('expires_at', '<', now())->delete();
    }

    /**
     * Get notification count for a user
     */
    public function getUnreadCount(User $user): int
    {
        return $user->notifications()
            ->unread()
            ->active()
            ->count();
    }

    /**
     * Send review deadline reminders
     */
    public function sendReviewDeadlineReminders(): int
    {
        $reviewers = User::whereHas('roles', function($query) {
            $query->where('name', 'reviewer');
        })->get();

        $count = 0;
        $deadline = now()->addDays(3); // 3 days from now

        foreach ($reviewers as $reviewer) {
            $pendingReviews = $reviewer->reviews()
                ->whereNull('completed_at')
                ->where('created_at', '<', now()->subDays(7)) // Reviews older than 7 days
                ->count();

            if ($pendingReviews > 0) {
                $this->createDeadlineReminderNotification(
                    $reviewer,
                    'Review Deadline Reminder',
                    "You have {$pendingReviews} pending review(s) that need to be completed soon.",
                    $deadline,
                    route('reviewer.abstracts')
                );
                $count++;
            }
        }

        return $count;
    }
} 
