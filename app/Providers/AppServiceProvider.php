<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use App\Models\AbstractSubmission;
use App\Models\SponsorPayment;
use App\Models\User;
use App\Models\GroupRegistration;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Set reasonable password requirements (not too strict)
        Password::defaults(function () {
            return Password::min(8)
                ->mixedCase()      // Requires uppercase and lowercase
                ->numbers();        // Requires at least one number
        });

        // Optimize Sidebar Data with View Composers
        View::composer('layouts.app', function ($view) {
            if (Auth::check()) {
                $user = Auth::user();
                $activeRole = session('active_role') ?? ($user->primary_role_name ?? ($user->role ?? 'author'));
                
                $sidebarData = [];

                // Shared: Unread Notifications Count
                $sidebarData['unreadNotificationsCount'] = $user->notifications()->unread()->count();

                // Role-specific data
                if (in_array($activeRole, ['author', 'user'])) {
                    $acceptedAbstracts = $user->abstractSubmissions()->where('status', 'accepted')->get();
                    $sidebarData['acceptedAbstracts'] = $acceptedAbstracts;
                    $sidebarData['pendingUploadsCount'] = $acceptedAbstracts->filter(fn($s) => !$s->hasActualPresentationFiles())->count();
                }

                if ($activeRole === 'admin') {
                    $sidebarData['pendingPaymentsCount'] = User::where('payment_status', 'submitted')->count();
                }

                if ($activeRole === 'finance_officer') {
                    $pendingParticipants = User::where('payment_status', 'submitted')->count();
                    $pendingGroups = GroupRegistration::where('payment_status', 'submitted')->count();
                    $pendingSponsors = Schema::hasTable('sponsor_payments')
                        ? SponsorPayment::where('payment_status', 'submitted')->count()
                        : 0;

                    $sidebarData['pendingFinanceCount'] = $pendingParticipants
                        + $pendingGroups
                        + $pendingSponsors;
                    $sidebarData['pendingGroupFinanceCount'] = $pendingGroups;
                    $sidebarData['pendingSponsorFinanceCount'] = $pendingSponsors;
                }

                if ($activeRole === 'reviewer') {
                    $allAssignments = AbstractSubmission::where('reviewer_id', $user->id)
                        ->orWhere('reviewer_2_id', $user->id)
                        ->get();
                    
                    $completedCount = $allAssignments->filter(function($abstract) use ($user) {
                        return $abstract->reviews()
                            ->where('reviewer_id', $user->id)
                            ->where('review_round', $abstract->revision_round ?? 0)
                            ->where('status', 'submitted')
                            ->exists();
                    })->count();
                    
                    $sidebarData['pendingReviewsCount'] = max(0, $allAssignments->count() - $completedCount);
                }

                $view->with('sidebarData', $sidebarData);
            }
        });
    }
}
