<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get user's notifications
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        if ($request->wantsJson()) {
            $notifications = $this->notificationService->getAllNotifications($user, 50);
            return response()->json([
                'notifications' => $notifications,
                'unread_count' => $this->notificationService->getUnreadCount($user)
            ]);
        }

        $notifications = $user->notifications()
            ->active()
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('notifications.index', compact('notifications'));
    }

    /**
     * Get unread notifications
     */
    public function unread(Request $request): JsonResponse
    {
        $user = $request->user();
        $notifications = $this->notificationService->getUnreadNotifications($user, 10);

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $notifications->count()
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request, Notification $notification)
    {
        $user = $request->user();
        
        // Ensure the notification belongs to the user
        if ($notification->user_id !== $user->id) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            abort(403);
        }

        $this->notificationService->markAsRead($notification);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'unread_count' => $this->notificationService->getUnreadCount($user)
            ]);
        }

        return redirect()->back()->with('success', 'Notification marked as read');
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request)
    {
        $user = $request->user();
        $count = $this->notificationService->markAllAsRead($user);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'marked_read' => $count,
                'unread_count' => 0
            ]);
        }

        return redirect()->back()->with('success', 'All notifications marked as read');
    }

    /**
     * Delete a notification
     */
    public function destroy(Request $request, Notification $notification)
    {
        $user = $request->user();
        
        // Ensure the notification belongs to the user
        if ($notification->user_id !== $user->id) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            abort(403);
        }

        $notification->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'unread_count' => $this->notificationService->getUnreadCount($user)
            ]);
        }

        return redirect()->back()->with('success', 'Notification deleted');
    }

    /**
     * Get notification count for badge
     */
    public function count(Request $request): JsonResponse
    {
        $user = $request->user();
        $count = $this->notificationService->getUnreadCount($user);

        return response()->json([
            'count' => $count
        ]);
    }
}