<?php

use App\Http\Controllers\AbstractController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES - No authentication required
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    $abstractsCount = \App\Models\AbstractSubmission::count();

    return view('welcome', compact('abstractsCount'));
})->name('home');


// Public Speakers Page
Route::get('/speakers', [App\Http\Controllers\SpeakerController::class, 'index'])->name('speakers.public');

// Public Conference Program View & Abstract Book Download
Route::get('/conference-program', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'programView'])->name('conference-program.view');
Route::get('/conference-program/download', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'exportProgramPDF'])->name('public.conference-program.download');
Route::get('/abstract-book/download', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'downloadAbstractBook'])->name('public.abstract-book.download');

// Public Certificate Verification (QR code scan)
Route::get('/verify/{code}', [App\Http\Controllers\CertificateController::class, 'verifyPage'])->middleware('throttle:30,1')->name('certificate.verify');
Route::get('/api/verify/{code}', [App\Http\Controllers\CertificateController::class, 'verify'])->middleware('throttle:30,1')->name('certificate.verify.api');
// Public certificate claim with a badge code (for attendees without an account)
Route::get('/certificate/claim', [App\Http\Controllers\CertificateController::class, 'claim'])->name('certificate.claim');
Route::post('/certificate/claim', [App\Http\Controllers\CertificateController::class, 'claimDownload'])
    ->middleware('throttle:15,1')
    ->name('certificate.claim.download');
// Public Badge Verification/Profile (QR code scan)
Route::get('/badge/{token}', [App\Http\Controllers\RegistrationOfficerController::class, 'publicBadge'])->middleware('throttle:60,1')->name('badge.public');

/*
|--------------------------------------------------------------------------
| AUTHENTICATED ROUTES - Require login AND email verification
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | DASHBOARDS - Role-based dashboards
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard', function (Request $request) {
        // The author/user dashboard is the single shared landing page for every role.
        // Staff (admin, finance, registration, reviewer, etc.) reach their role-specific
        // tools through the navigation menu, not via the post-login redirect.
        return redirect()->route('user.dashboard');
    })->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | USER/AUTHOR DASHBOARD
    |--------------------------------------------------------------------------
    */
    Route::get('/user/dashboard', [App\Http\Controllers\UserController::class, 'dashboard'])->name('user.dashboard');

    /*
    |--------------------------------------------------------------------------
    | RAPPORTEUR REPORTS
    |--------------------------------------------------------------------------
    */
    Route::get('rapporteur-reports/{rapporteurReport}/download', [App\Http\Controllers\RapporteurReportController::class, 'download'])->name('rapporteur-reports.download');
    Route::resource('rapporteur-reports', App\Http\Controllers\RapporteurReportController::class)
        ->only(['index', 'create', 'store', 'show', 'edit', 'update']);

    /*
    |--------------------------------------------------------------------------
    | CHIEF RAPPORTEUR — review, edit, approve & compile session reports
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:chief_rapporteur')->prefix('chief-rapporteur')->name('chief-rapporteur.')->group(function () {
        Route::get('/', [App\Http\Controllers\ChiefRapporteurController::class, 'index'])->name('index');
        Route::get('/compiled', [App\Http\Controllers\ChiefRapporteurController::class, 'compiled'])->name('compiled');
        Route::get('/export', [App\Http\Controllers\ChiefRapporteurController::class, 'export'])->name('export');
        Route::get('/{rapporteurReport}', [App\Http\Controllers\ChiefRapporteurController::class, 'show'])->name('show');
        Route::get('/{rapporteurReport}/download', [App\Http\Controllers\ChiefRapporteurController::class, 'download'])->name('download');
        Route::get('/{rapporteurReport}/edit', [App\Http\Controllers\ChiefRapporteurController::class, 'edit'])->name('edit');
        Route::put('/{rapporteurReport}', [App\Http\Controllers\ChiefRapporteurController::class, 'update'])->name('update');
        Route::post('/{rapporteurReport}/approve', [App\Http\Controllers\ChiefRapporteurController::class, 'approve'])->name('approve');
        Route::post('/{rapporteurReport}/return', [App\Http\Controllers\ChiefRapporteurController::class, 'returnForRevision'])->name('return');
    });
    Route::get('/user/abstracts/{abstract}/revision', [App\Http\Controllers\UserController::class, 'showRevisionInterface'])->name('user.abstract.revision');
    Route::post('/user/abstracts/{abstract}/resubmit', [App\Http\Controllers\UserController::class, 'resubmitAbstract'])->name('user.abstract.resubmit');
    Route::get('/user/print-badge', [App\Http\Controllers\RegistrationOfficerController::class, 'printMyBadge'])->name('user.print-badge');
    Route::get('/session-role-application', [App\Http\Controllers\SessionRoleApplicationController::class, 'create'])->name('session-role-applications.create');
    Route::post('/session-role-application', [App\Http\Controllers\SessionRoleApplicationController::class, 'store'])->name('session-role-applications.store');

    /*
    |--------------------------------------------------------------------------
    | INVITATION LETTERS & VISAS
    |--------------------------------------------------------------------------
    */
    Route::get('/invitation', [App\Http\Controllers\InvitationController::class, 'index'])->name('invitation.index');
    Route::post('/invitation', [App\Http\Controllers\InvitationController::class, 'store'])->name('invitation.store');
    Route::get('/invitation/download', [App\Http\Controllers\InvitationController::class, 'download'])->name('invitation.download');

    /*
    |--------------------------------------------------------------------------
    | CERTIFICATE ROUTES - Tiered Attendance & Presenter Certificates
    |--------------------------------------------------------------------------
    */
    Route::get('/certificate', [App\Http\Controllers\CertificateController::class, 'index'])->name('certificate.index');
    Route::get('/certificate/download/attendance', [App\Http\Controllers\CertificateController::class, 'downloadAttendance'])->name('certificate.download.attendance');
    Route::get('/certificate/download/presentation/{abstract}', [App\Http\Controllers\CertificateController::class, 'downloadPresentation'])->name('certificate.download.presentation');
    Route::get('/certificate/preview/{certificate?}', [App\Http\Controllers\CertificateController::class, 'preview'])->name('certificate.preview');

    /*
    |--------------------------------------------------------------------------
    | REGISTRATION & PAYMENT
    |--------------------------------------------------------------------------
    */
    Route::get('/payment', [App\Http\Controllers\RegistrationPaymentController::class, 'show'])->name('payment.show');
    Route::post('/payment', [App\Http\Controllers\RegistrationPaymentController::class, 'store'])->name('payment.store');
    Route::get('/payment/transactions/{transaction}/proof', [App\Http\Controllers\RegistrationPaymentController::class, 'proof'])->name('payment.proof');

    /*
    |--------------------------------------------------------------------------
    | GROUP REGISTRATION - Register multiple attendees at once
    |--------------------------------------------------------------------------
    */
    Route::prefix('group-registration')->name('group-registration.')->group(function () {
        Route::get('/', [App\Http\Controllers\GroupRegistrationController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\GroupRegistrationController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\GroupRegistrationController::class, 'store'])->name('store');
        Route::get('/{groupRegistration}', [App\Http\Controllers\GroupRegistrationController::class, 'show'])->name('show');
        Route::get('/{groupRegistration}/edit', [App\Http\Controllers\GroupRegistrationController::class, 'edit'])->name('edit');
        Route::put('/{groupRegistration}', [App\Http\Controllers\GroupRegistrationController::class, 'update'])->name('update');
        Route::delete('/{groupRegistration}', [App\Http\Controllers\GroupRegistrationController::class, 'destroy'])->name('destroy');

        // Member management
        Route::post('/{groupRegistration}/members', [App\Http\Controllers\GroupRegistrationController::class, 'addMember'])->name('add-member');
        Route::patch('/{groupRegistration}/members/{member}', [App\Http\Controllers\GroupRegistrationController::class, 'updateMember'])->name('update-member');
        Route::delete('/{groupRegistration}/members/{member}', [App\Http\Controllers\GroupRegistrationController::class, 'removeMember'])->name('remove-member');

        // Badge Printing for Groups
        Route::get('/{groupRegistration}/members/{member}/print-badge', [App\Http\Controllers\GroupRegistrationController::class, 'printMemberBadge'])->name('print-badge');
        Route::get('/{groupRegistration}/bulk-print-badges', [App\Http\Controllers\GroupRegistrationController::class, 'bulkPrintBadges'])->name('bulk-print');

        // Invitation Letters
        Route::get('/{groupRegistration}/members/{member}/invitation', [App\Http\Controllers\GroupRegistrationController::class, 'downloadMemberInvitation'])->name('member-invitation');
    });

    /*
    |--------------------------------------------------------------------------
    | PROFILE MANAGEMENT
    |--------------------------------------------------------------------------
    */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/journey', [ProfileController::class, 'updateJourney'])->name('profile.update-journey');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
    |--------------------------------------------------------------------------
    | ABSTRACT MANAGEMENT
    |--------------------------------------------------------------------------
    */
    Route::get('/abstracts/create', [AbstractController::class, 'create'])->name('abstracts.create');
    Route::post('/abstracts', [AbstractController::class, 'store'])->name('abstracts.store');
    Route::get('/my-abstracts', [AbstractController::class, 'my'])->name('abstracts.my');
    Route::get('/abstracts/{abstract}', [AbstractController::class, 'show'])->name('abstracts.show');
    Route::get('/abstracts/{abstract}/edit', [AbstractController::class, 'edit'])->name('abstracts.edit');
    Route::put('/abstracts/{abstract}', [AbstractController::class, 'update'])->name('abstracts.update');
    Route::post('/abstracts/{abstract}/resubmit-revision', [AbstractController::class, 'resubmitRevision'])->name('abstracts.resubmitRevision');
    Route::post('/abstracts/{abstract}/submit', [AbstractController::class, 'submit'])->name('abstracts.submit');
    Route::patch('/abstracts/{abstract}/withdraw', [AbstractController::class, 'withdraw'])->name('abstracts.withdraw');
    Route::delete('/abstracts/{abstract}', [AbstractController::class, 'destroy'])->name('abstracts.destroy');

    /*
    |--------------------------------------------------------------------------
    | PROCEEDINGS CORRECTIONS - camera-ready edits for accepted abstracts
    |--------------------------------------------------------------------------
    */
    Route::get('/abstracts/{abstract}/proceedings', [App\Http\Controllers\ProceedingsCorrectionController::class, 'edit'])->name('abstracts.proceedings.edit');
    Route::put('/abstracts/{abstract}/proceedings', [App\Http\Controllers\ProceedingsCorrectionController::class, 'update'])->name('abstracts.proceedings.update');
    Route::post('/abstracts/{abstract}/proceedings/preview', [App\Http\Controllers\ProceedingsCorrectionController::class, 'preview'])->name('abstracts.proceedings.preview');

    // Revision workflow for authors
    Route::get('/abstracts/{id}/revision', [App\Http\Controllers\UserController::class, 'showRevisionForm'])->name('abstracts.revision.form');
    Route::post('/abstracts/{id}/revision', [App\Http\Controllers\UserController::class, 'submitRevision'])->name('abstracts.revision.submit');

    /*
    |--------------------------------------------------------------------------
    | ROLE SWITCHING
    |--------------------------------------------------------------------------
    */
    Route::post('/switch-role', function (Request $request) {
        $user = $request->user();
        $requestedRole = $request->input('role');

        // Verify user has the requested role
        if ($user->hasRole($requestedRole)) {
            // Update the user's primary role or session role
            session(['active_role' => $requestedRole]);

            // Determine the correct dashboard URL for the new role
            $dashboardUrl = '/dashboard';
            if ($requestedRole === 'admin' || $requestedRole === 'scientific_admin') {
                $dashboardUrl = route('admin.dashboard');
            } elseif ($requestedRole === 'finance_officer') {
                $dashboardUrl = route('finance.dashboard');
            } elseif ($requestedRole === 'registration_officer') {
                $dashboardUrl = route('registration.dashboard');
            } elseif ($requestedRole === 'reviewer') {
                $dashboardUrl = route('reviewer.dashboard');
            } elseif ($requestedRole === 'chair' || $requestedRole === 'rapporteur') {
                $dashboardUrl = route('sessions.my');
            }

            // Check if this is an AJAX request
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Role switched successfully',
                    'new_role' => $requestedRole,
                    'redirect_url' => $dashboardUrl,
                ]);
            }

            // For regular form submissions, redirect directly
            return redirect($dashboardUrl);
        }

        // Handle error case
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to switch to that role',
            ], 403);
        }

        return redirect()->back()->with('error', 'You do not have permission to switch to that role');
    })->name('switch-role');

    /*
    |--------------------------------------------------------------------------
    | ADMIN ROUTES - For conference organizers
    |--------------------------------------------------------------------------
    */
    Route::middleware(['scientific_admin'])->prefix('admin')->name('admin.')->group(function () {
        // Dashboard
        Route::get('/dashboard', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/realtime-stats', [App\Http\Controllers\Admin\DashboardController::class, 'getRealtimeStats'])->name('dashboard.realtime');
        Route::get('/dashboard/participants', [App\Http\Controllers\Admin\DashboardController::class, 'participants'])->name('dashboard.participants');
        Route::get('/dashboard/participants/export', [App\Http\Controllers\Admin\DashboardController::class, 'exportParticipants'])->name('dashboard.participants.export');
        Route::get('/submission-window', [App\Http\Controllers\Admin\SubmissionWindowController::class, 'show'])->name('submission-window.show');
        Route::post('/submission-window/open', [App\Http\Controllers\Admin\SubmissionWindowController::class, 'open'])->name('submission-window.open');
        Route::post('/submission-window/close', [App\Http\Controllers\Admin\SubmissionWindowController::class, 'close'])->name('submission-window.close');

        // AI Assistant Route
        Route::post('/ai-chat', [App\Http\Controllers\AdminController::class, 'aiChat'])->name('ai.chat');

        // USER MANAGEMENT (Admin & Scientific Admin)
        Route::get('/users', [App\Http\Controllers\Admin\UserManagementController::class, 'index'])->name('users');
        Route::get('/users/{user}/edit', [App\Http\Controllers\Admin\UserManagementController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [App\Http\Controllers\Admin\UserManagementController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [App\Http\Controllers\Admin\UserManagementController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/{user}/toggle-role/{role}', [App\Http\Controllers\Admin\UserManagementController::class, 'toggleRole'])->name('users.toggle-role');
        Route::post('/users/{user}/set-primary-role/{role}', [App\Http\Controllers\Admin\UserManagementController::class, 'setPrimaryRole'])->name('users.set-primary-role');
        Route::post('/users/{user}/set-payment-status', [App\Http\Controllers\Admin\UserManagementController::class, 'setPaymentStatus'])->name('users.set-payment-status');
        Route::get('/users/stats', [App\Http\Controllers\Admin\UserManagementController::class, 'getStats'])->name('users.stats');
        Route::get('/users/search', [App\Http\Controllers\Admin\UserManagementController::class, 'search'])->name('users.search');
        Route::get('/users/by-role/{role}', [App\Http\Controllers\Admin\UserManagementController::class, 'getByRole'])->name('users.by-role');
        Route::get('/users/export', [App\Http\Controllers\Admin\UserManagementController::class, 'export'])->name('users.export');
        Route::get('/duplicates', [App\Http\Controllers\Admin\DuplicateWatchlistController::class, 'index'])->name('duplicates.index');
        Route::get('/diagnostics/workflow', [App\Http\Controllers\Admin\WorkflowDiagnosticsController::class, 'index'])->name('diagnostics.workflow');
        Route::post('/diagnostics/workflow/{abstract}/repair', [App\Http\Controllers\Admin\WorkflowDiagnosticsController::class, 'repair'])->name('diagnostics.workflow.repair');
        Route::post('/diagnostics/workflow/bulk-repair', [App\Http\Controllers\Admin\WorkflowDiagnosticsController::class, 'bulkRepair'])->name('diagnostics.workflow.bulk-repair');
        Route::post('/diagnostics/workflow/stale/bulk-accept', [App\Http\Controllers\Admin\WorkflowDiagnosticsController::class, 'bulkAcceptStaleEligible'])->name('diagnostics.workflow.stale.bulk-accept');
        Route::post('/diagnostics/workflow/{abstract}/stale/accept', [App\Http\Controllers\Admin\WorkflowDiagnosticsController::class, 'acceptFromStaleQueue'])->name('diagnostics.workflow.stale.accept');
        Route::post('/diagnostics/workflow/email-drift/bulk-accept', [App\Http\Controllers\Admin\WorkflowDiagnosticsController::class, 'bulkAcceptEmailDrift'])->name('diagnostics.workflow.email-drift.bulk-accept');
        Route::post('/diagnostics/workflow/{abstract}/email-drift/accept', [App\Http\Controllers\Admin\WorkflowDiagnosticsController::class, 'acceptFromEmailDrift'])->name('diagnostics.workflow.email-drift.accept');
        Route::post('/diagnostics/workflow/{abstract}/repair-revision-path', [App\Http\Controllers\Admin\WorkflowDiagnosticsController::class, 'repairRevisionPath'])->name('diagnostics.workflow.repair-revision-path');
        Route::post('/diagnostics/workflow/bulk-repair-revision-path', [App\Http\Controllers\Admin\WorkflowDiagnosticsController::class, 'bulkRepairRevisionPath'])->name('diagnostics.workflow.bulk-repair-revision-path');
        Route::post('/diagnostics/workflow/{abstract}/repair-assignment-status', [App\Http\Controllers\Admin\WorkflowDiagnosticsController::class, 'repairAssignmentStatus'])->name('diagnostics.workflow.repair-assignment-status');
        Route::post('/diagnostics/workflow/bulk-repair-assignment-status', [App\Http\Controllers\Admin\WorkflowDiagnosticsController::class, 'bulkRepairAssignmentStatus'])->name('diagnostics.workflow.bulk-repair-assignment-status');

        // Student Verification Routes (Admin & Scientific Admin)
        Route::prefix('students')->name('students.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\StudentVerificationController::class, 'index'])->name('index');
            Route::post('/{user}/verify', [App\Http\Controllers\Admin\StudentVerificationController::class, 'verify'])->name('verify');
            Route::post('/{user}/reject', [App\Http\Controllers\Admin\StudentVerificationController::class, 'reject'])->name('reject');
        });

        // SYSTEM ADMINISTRATION (Admin Only)
        Route::middleware(['admin'])->group(function () {
            // System Logs
            Route::get('/system-logs', [App\Http\Controllers\Admin\SystemLogController::class, 'index'])->name('system-logs.index');
            Route::get('/system-logs/{systemLog}', [App\Http\Controllers\Admin\SystemLogController::class, 'show'])->name('system-logs.show');
            Route::patch('/system-logs/{systemLog}/resolve', [App\Http\Controllers\Admin\SystemLogController::class, 'resolve'])->name('system-logs.resolve');
            Route::post('/system-logs/bulk-resolve', [App\Http\Controllers\Admin\SystemLogController::class, 'bulkResolve'])->name('system-logs.bulk-resolve');
            Route::delete('/system-logs/clear-resolved', [App\Http\Controllers\Admin\SystemLogController::class, 'clearResolved'])->name('system-logs.clear-resolved');

            // Group Registration Management Routes
            Route::get('/group-registrations', [App\Http\Controllers\Admin\GroupRegistrationController::class, 'index'])->name('group-registrations.index');
            Route::get('/group-registrations/export', [App\Http\Controllers\Admin\GroupRegistrationController::class, 'export'])->name('group-registrations.export');
            Route::get('/group-registrations/export-excel', [App\Http\Controllers\Admin\GroupRegistrationController::class, 'exportExcel'])->name('group-registrations.export-excel');
            Route::get('/group-registrations/export-pdf', [App\Http\Controllers\Admin\GroupRegistrationController::class, 'exportPdf'])->name('group-registrations.export-pdf');
            Route::get('/group-registrations/{groupRegistration}', [App\Http\Controllers\Admin\GroupRegistrationController::class, 'show'])->name('group-registrations.show');
            Route::post('/group-registrations/member/{member}/check-in', [App\Http\Controllers\Admin\GroupRegistrationController::class, 'checkInMember'])->name('group-registrations.check-in-member');
            Route::post('/group-registrations/lookup-qr', [App\Http\Controllers\Admin\GroupRegistrationController::class, 'lookupByQr'])->name('group-registrations.lookup-qr');

            // Attendance Management Routes
            Route::get('/attendance/scanner', [App\Http\Controllers\Admin\AttendanceController::class, 'scanner'])->name('attendance.scanner');
            Route::post('/attendance/scan', [App\Http\Controllers\Admin\AttendanceController::class, 'scan'])->name('attendance.scan');
            Route::get('/attendance/report', [App\Http\Controllers\Admin\AttendanceController::class, 'report'])->name('attendance.report');
            Route::get('/attendance/export', [App\Http\Controllers\RegistrationOfficerController::class, 'exportReport'])->name('attendance.export');

            // Queue Management Routes
            Route::post('/queue/start', [App\Http\Controllers\Admin\EmailManagementController::class, 'startQueueWorkers'])->name('queue.start');
            Route::post('/queue/restart', [App\Http\Controllers\Admin\EmailManagementController::class, 'restartQueueWorkers'])->name('queue.restart');
            Route::post('/queue/retry-failed', [App\Http\Controllers\Admin\EmailManagementController::class, 'retryFailedJobs'])->name('queue.retry-failed');
            Route::post('/queue/clear-failed', [App\Http\Controllers\Admin\EmailManagementController::class, 'clearFailedJobs'])->name('queue.clear-failed');
        });

        // SCIENTIFIC PROGRAM MANAGEMENT (Scientific Admin & System Admin)

        // Assign Reviewers Route (Optimized Controller)
        Route::get('/abstracts/assign-reviewers', [App\Http\Controllers\Admin\ReviewerAssignmentController::class, 'index'])->name('abstracts.assign-reviewers-page');

        // Abstract Book Routes - MUST be before parameterized {abstract} routes
        Route::get('/abstracts/book', [App\Http\Controllers\Admin\AbstractBookController::class, 'index'])->name('abstracts.book');
        Route::get('/abstracts/book/index', [App\Http\Controllers\Admin\AbstractBookController::class, 'index'])->name('abstracts.book.index');
        Route::get('/abstracts/book/stream', [App\Http\Controllers\Admin\AbstractBookController::class, 'stream'])->name('abstracts.book.stream');
        Route::post('/abstracts/book/generate', [App\Http\Controllers\Admin\AbstractBookController::class, 'generate'])->name('abstracts.book.generate');

        // Abstract Management Routes
        Route::get('/abstracts', [App\Http\Controllers\Admin\AbstractManagementController::class, 'index'])->name('abstracts.index');
        Route::get('/presentations', [App\Http\Controllers\Admin\AbstractManagementController::class, 'presentations'])->name('abstracts.presentations');
        Route::get('/abstracts/{abstract}/preview-presentation/{type}', [App\Http\Controllers\Admin\AbstractManagementController::class, 'previewPresentation'])->name('abstracts.preview-presentation');
        Route::get('/abstracts/{abstract}/download-presentation/{type}', [App\Http\Controllers\Admin\AbstractManagementController::class, 'downloadPresentation'])->name('abstracts.download-presentation');

        // Legacy decision queue routes (redirect to new unified system)
        Route::get('/abstracts/decision-queue', function () {
            return redirect()->route('admin.decisions.index', ['filter' => 'conflicts']);
        })->name('abstracts.decision-queue');
        Route::get('/abstracts/decision-queue/{abstract}', function ($abstract) {
            return redirect()->route('admin.decisions.show', $abstract);
        })->name('decision-queue.show');
        Route::post('/abstracts/bulk-export', [App\Http\Controllers\Admin\AbstractManagementController::class, 'bulkExport'])->name('abstracts.bulk-export');
        Route::post('/abstracts/bulk-action', [App\Http\Controllers\Admin\AbstractManagementController::class, 'bulkAction'])->name('abstracts.bulk-action');
        Route::get('/abstracts/pending', [App\Http\Controllers\Admin\AbstractManagementController::class, 'pendingAbstracts'])->name('abstracts.pending');
        Route::get('/abstracts/under-review', [App\Http\Controllers\Admin\AbstractManagementController::class, 'underReviewAbstracts'])->name('abstracts.under-review');
        Route::get('/abstracts/completed', [App\Http\Controllers\Admin\AbstractManagementController::class, 'completedAbstracts'])->name('abstracts.completed');
        // Show dedicated abstract view page
        Route::get('/abstracts/{abstract}', [App\Http\Controllers\Admin\AbstractManagementController::class, 'show'])->name('abstracts.view');
        Route::get('/abstracts/{abstract}/edit', [App\Http\Controllers\Admin\AbstractManagementController::class, 'edit'])->name('abstracts.edit');
        Route::get('/abstracts/{abstract}/history', [App\Http\Controllers\Admin\AbstractManagementController::class, 'history'])->name('abstracts.history');
        Route::get('/abstracts/export/excel', [App\Http\Controllers\Admin\AbstractManagementController::class, 'exportAllExcel'])->name('abstracts.export.excel');
        Route::get('/abstracts/{abstract}/export', [App\Http\Controllers\Admin\AbstractManagementController::class, 'export'])->name('abstracts.export');
        Route::put('/abstracts/{abstract}', [App\Http\Controllers\Admin\AbstractManagementController::class, 'update'])->name('abstracts.update');
        Route::delete('/abstracts/{abstract}', [App\Http\Controllers\Admin\AbstractManagementController::class, 'destroy'])->name('abstracts.destroy'); // Admin delete override
        Route::patch('/abstracts/{abstract}/status', [App\Http\Controllers\Admin\AbstractManagementController::class, 'changeStatus'])->name('abstracts.change-status');
        Route::patch('/abstracts/{abstract}/assign-code', [App\Http\Controllers\Admin\AbstractManagementController::class, 'assignConferenceCode'])->name('abstracts.assign-code');
        Route::post('/abstracts/{abstract}/toggle-blind-review', [App\Http\Controllers\Admin\AbstractManagementController::class, 'toggleBlindReview'])->name('abstracts.toggle-blind-review');
        Route::post('/abstracts/{abstract}/clear-reviewers', [App\Http\Controllers\Admin\AbstractManagementController::class, 'clearReviewers'])->name('abstracts.clear-reviewers');
        Route::post('/abstracts/{abstract}/eligible-reviewers', [App\Http\Controllers\Admin\AbstractManagementController::class, 'getEligibleReviewers'])->name('abstracts.eligible-reviewers');
        Route::post('/abstracts/{abstract}/request-revision', [App\Http\Controllers\Admin\AbstractManagementController::class, 'requestRevision'])->name('abstracts.request-revision');

        // Reviewer Assignment Routes
        Route::get('/reviewers/assign', [App\Http\Controllers\Admin\ReviewerAssignmentController::class, 'index'])->name('reviewers.assign');
        Route::get('/reviewers/preferences', [App\Http\Controllers\Admin\ReviewerAssignmentController::class, 'reviewerPreferences'])->name('reviewers.preferences');
        Route::post('/abstracts/{abstract}/assign-reviewer', [App\Http\Controllers\Admin\ReviewerAssignmentController::class, 'assignReviewer'])->name('abstracts.assign-reviewer');
        Route::post('/abstracts/{abstract}/assign-both-reviewers', [App\Http\Controllers\Admin\ReviewerAssignmentController::class, 'assignBothReviewers'])->name('abstracts.assign-both-reviewers');
        Route::post('/abstracts/{abstract}/assign-reviewers', [App\Http\Controllers\Admin\ReviewerAssignmentController::class, 'storeReviewerAssignment'])->name('abstracts.assign-reviewers');
        Route::post('/abstracts/{abstract}/reassign-reviewers', [App\Http\Controllers\Admin\ReviewerAssignmentController::class, 'reassignReviewers'])->name('abstracts.reassign-reviewers');
        Route::post('/abstracts/auto-assign-all', [App\Http\Controllers\Admin\ReviewerAssignmentController::class, 'autoAssignAll'])->name('abstracts.auto-assign-all');
        Route::get('/reviewers/assignment-stats', [App\Http\Controllers\Admin\ReviewerAssignmentController::class, 'getAssignmentStats'])->name('reviewers.assignment-stats');

        // Invitation Management Routes
        Route::get('/invitations', [App\Http\Controllers\Admin\InvitationManagementController::class, 'index'])->name('invitations.index');

        // Conference Program Management Routes
        Route::get('/conference-program', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'index'])->name('conference-program.index');
        Route::get('/conference-program/assigned', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'assignedCodes'])->name('conference-program.assigned');
        Route::post('/conference-program/update-codes', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'updateCodes'])->name('conference-program.update-codes');
        Route::post('/conference-program/detect-topics', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'detectSessionTopics'])->name('conference-program.detect-topics');
        Route::get('/conference-program/pending', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'pendingCodes'])->name('conference-program.pending');
        Route::get('/conference-program/selected', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'selectedAbstracts'])->name('conference-program.selected');
        Route::get('/conference-program/builder', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'programBuilder'])->name('conference-program.builder');
        Route::post('/conference-program/create-session', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'createSession'])->name('conference-program.create-session');
        Route::post('/conference-program/add-to-session', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'addToSession'])->name('conference-program.add-to-session');
        Route::post('/conference-program/remove-from-session', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'removeFromSession'])->name('conference-program.remove-from-session');
        Route::post('/conference-program/reorder-session', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'reorderSession'])->name('conference-program.reorder-session');
        Route::post('/conference-program/check-conflicts', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'checkConflicts'])->name('conference-program.check-conflicts');
        Route::get('/conference-program/assign-codes', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'assignCodesPage'])->name('conference-program.assign-codes');
        Route::post('/conference-program/assign-codes', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'saveAssignedCodes'])->name('conference-program.assign-codes.save');
        Route::post('/conference-program/bulk-save-codes', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'bulkSaveCodes'])->name('conference-program.bulk-save-codes');
        Route::post('/conference-program/finalize-codes', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'finalizeCodesFromSessionOrder'])->name('conference-program.finalize-codes');
        Route::post('/conference-program/toggle-code-lock/{abstract}', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'toggleCodeLock'])->name('conference-program.toggle-code-lock');
        Route::get('/conference-program/export', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'exportProgram'])->name('conference-program.export');
        Route::get('/conference-program/export-pdf', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'exportProgramPDF'])->name('conference-program.export-pdf');
        Route::get('/conference-program/generate-abstract-book', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'generateAbstractBook'])->name('conference-program.generate-abstract-book');
        Route::get('/conference-program/abstract-book/status', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'abstractBookStatus'])->name('conference-program.abstract-book.status');
        Route::post('/conference-program/abstract-book/reset', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'resetAbstractBookStatus'])->name('conference-program.abstract-book.reset');
        Route::get('/conference-program/abstract-book/diagnostics', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'abstractBookDiagnostics'])->name('conference-program.abstract-book.diagnostics');
        Route::get('/conference-program/abstract-book/download', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'downloadAbstractBook'])->name('conference-program.abstract-book.download');
        Route::post('/conference-program/proceedings-corrections', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'toggleProceedingsCorrections'])->name('conference-program.proceedings-corrections.toggle');
        Route::get('/conference-program/proceedings/download', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'downloadProceedings'])->name('conference-program.proceedings.download');
        Route::get('/proceedings/entries', [App\Http\Controllers\Admin\ProceedingsEntryController::class, 'index'])->name('proceedings.entries');
        Route::get('/conference-program/view-pdf', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'viewProgramPDF'])->name('conference-program.view-pdf');
        Route::get('/conference-program/view', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'programView'])->name('conference-program.view');
        Route::get('/conference-program/stats', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'getProgramStats'])->name('conference-program.stats');
        Route::get('/conference-program/search', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'searchAbstracts'])->name('conference-program.search');

        // Session Management Routes
        Route::get('/conference-program/session/{id}', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'getSession'])->name('conference-program.get-session');
        Route::put('/conference-program/session/{id}', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'updateSession'])->name('conference-program.update-session');
        Route::delete('/conference-program/session/{id}', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'deleteSession'])->name('conference-program.delete-session');
        Route::post('/conference-program/bulk-assign', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'bulkAssignToSession'])->name('conference-program.bulk-assign');
        Route::post('/conference-program/fix-capacities', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'fixSessionCapacities'])->name('conference-program.fix-capacities');
        Route::post('/conference-program/quick-add-guest', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'quickAddGuestPresentation'])->name('conference-program.quick-add-guest');
        Route::post('/conference-program/send-notifications', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'sendBulkSessionNotifications'])->name('conference-program.send-notifications');
        Route::post('/conference-program/session/{id}/notify-leads', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'notifySessionLeads'])->name('conference-program.notify-leads');
        Route::post('/conference-program/notify-all-leads', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'notifyAllLeads'])->name('conference-program.notify-all-leads');

        // Presentation Download Routes (queued background ZIP builds)
        Route::get('/conference-program/session/{session}/download-presentations', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'downloadSessionPresentations'])->name('conference-program.download-session-presentations');
        Route::get('/conference-program/day/{day}/download-presentations', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'downloadDayPresentations'])->name('conference-program.download-day-presentations');
        Route::get('/presentations/download-all', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'downloadAllPresentations'])->name('presentations.download-all');
        Route::get('/presentations/download-jobs', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'presentationDownloadJobs'])->name('presentations.download-jobs');
        Route::get('/presentations/download-jobs/{jobId}/file', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'presentationDownloadFile'])->name('presentations.download-file');
        Route::delete('/presentations/download-jobs/{jobId}', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'cancelPresentationDownload'])->name('presentations.cancel-job');

        // Analytics Routes
        Route::get('/analytics', [App\Http\Controllers\Admin\AnalyticsController::class, 'dashboard'])->name('analytics.dashboard');
        Route::get('/analytics/realtime-stats', [App\Http\Controllers\Admin\AnalyticsController::class, 'getRealtimeStats'])->name('analytics.realtime');

        // Subtheme Management Routes
        Route::get('/subthemes', [App\Http\Controllers\Admin\SubthemeController::class, 'index'])->name('subthemes.index');
        Route::post('/subthemes/bulk-update', [App\Http\Controllers\Admin\SubthemeController::class, 'bulkUpdate'])->name('subthemes.bulk-update');
        Route::post('/subthemes/generate-codes', [App\Http\Controllers\Admin\SubthemeController::class, 'generateCodes'])->name('subthemes.generate-codes');

        // Email Management Routes
        Route::get('/emails', [App\Http\Controllers\Admin\EmailManagementController::class, 'index'])->name('emails.index');
        Route::get('/emails/activity-log', [App\Http\Controllers\Admin\EmailManagementController::class, 'activityLog'])->name('emails.activity-log');
        Route::post('/emails/send-review-reminders', [App\Http\Controllers\Admin\EmailManagementController::class, 'sendBulkReviewReminders'])->name('emails.send-review-reminders');
        Route::post('/emails/send-status-notifications', [App\Http\Controllers\Admin\EmailManagementController::class, 'sendBulkStatusNotifications'])->name('emails.send-status-notifications');
        Route::post('/emails/send-code-notifications', [App\Http\Controllers\Admin\EmailManagementController::class, 'sendBulkConferenceCodeNotifications'])->name('emails.send-code-notifications');
        Route::post('/emails/send-pending-submission-confirmations', [App\Http\Controllers\Admin\EmailManagementController::class, 'sendPendingSubmissionConfirmations'])->name('emails.send-pending-submission-confirmations');
        Route::post('/emails/test-email', [App\Http\Controllers\Admin\EmailManagementController::class, 'testEmail'])->name('emails.test-email');
        Route::get('/emails/pending-notifications', [App\Http\Controllers\Admin\EmailManagementController::class, 'getPendingNotifications'])->name('emails.pending-notifications');
        Route::post('/emails/send-pending-notifications', [App\Http\Controllers\Admin\EmailManagementController::class, 'sendPendingNotifications'])->name('emails.send-pending-notifications');
        Route::post('/emails/send-pending-codes', [App\Http\Controllers\Admin\EmailManagementController::class, 'sendPendingCodes'])->name('emails.send-pending-codes');
        Route::get('/emails/preview', [App\Http\Controllers\Admin\EmailManagementController::class, 'previewEmail'])->name('emails.preview');
        Route::post('/emails/export-report', [App\Http\Controllers\Admin\EmailManagementController::class, 'exportReport'])->name('emails.export-report');
        Route::post('/emails/send-payment-reminders', [App\Http\Controllers\Admin\EmailManagementController::class, 'sendBulkPaymentReminders'])->name('emails.send-payment-reminders');
        Route::post('/emails/send-cpd-reminders', [App\Http\Controllers\Admin\EmailManagementController::class, 'sendCpdReminderEmails'])->name('emails.send-cpd-reminders');
        Route::get('/emails/export-cpd-requests', [App\Http\Controllers\Admin\EmailManagementController::class, 'exportCpdRequests'])->name('emails.export-cpd-requests');
        Route::post('/emails/send-broadcast', [App\Http\Controllers\Admin\EmailManagementController::class, 'sendBulkConferenceBroadcast'])->name('emails.send-broadcast');
        Route::post('/emails/broadcast-audience-info', [App\Http\Controllers\Admin\EmailManagementController::class, 'broadcastAudienceInfo'])->name('emails.broadcast-audience-info');
        Route::post('/emails/preview-broadcast', [App\Http\Controllers\Admin\EmailManagementController::class, 'previewBroadcast'])->name('emails.preview-broadcast');
        Route::post('/emails/preview-operation', [App\Http\Controllers\Admin\EmailManagementController::class, 'previewOperation'])->name('emails.preview-operation');
        Route::post('/emails/send-presentation-reminders', [App\Http\Controllers\Admin\EmailManagementController::class, 'sendBulkPresentationReminders'])->name('emails.send-presentation-reminders');
        Route::post('/emails/send-revision-reminders', [App\Http\Controllers\Admin\EmailManagementController::class, 'sendRevisionReminderEmails'])->name('emails.send-revision-reminders');
        Route::post('/emails/send-session-notifications', [App\Http\Controllers\Admin\EmailManagementController::class, 'sendPendingSessionNotifications'])->name('emails.send-session-notifications');

        // Review Quality Management Routes
        Route::get('/review-quality', [App\Http\Controllers\Admin\ReviewQualityController::class, 'dashboard'])->name('review-quality.dashboard');
        Route::get('/review-quality/reviewers/{reviewer}/performance', [App\Http\Controllers\Admin\ReviewQualityController::class, 'reviewerPerformance'])->name('review-quality.reviewer-performance');
        Route::get('/review-quality/reviewers/performances', [App\Http\Controllers\Admin\ReviewQualityController::class, 'allReviewerPerformances'])->name('review-quality.all-performances');
        Route::post('/review-quality/abstracts/{abstract}/flag', [App\Http\Controllers\Admin\ReviewQualityController::class, 'flagForQualityReview'])->name('review-quality.flag');
        Route::post('/review-quality/abstracts/{abstract}/resolve-flag', [App\Http\Controllers\Admin\ReviewQualityController::class, 'resolveQualityFlag'])->name('review-quality.resolve-flag');
        Route::get('/review-quality/stats', [App\Http\Controllers\Admin\ReviewQualityController::class, 'getQualityStats'])->name('review-quality.stats');
        Route::get('/review-quality/reviewers/{reviewer}/performance-data', [App\Http\Controllers\Admin\ReviewQualityController::class, 'getReviewerPerformance'])->name('review-quality.reviewer-performance-data');
        Route::get('/review-quality/export-quality-report', [App\Http\Controllers\Admin\ReviewQualityController::class, 'exportQualityReport'])->name('review-quality.export-quality-report');
        Route::get('/review-quality/export-performance-report', [App\Http\Controllers\Admin\ReviewQualityController::class, 'exportPerformanceReport'])->name('review-quality.export-performance-report');
        Route::get('/review-quality/settings', [App\Http\Controllers\Admin\ReviewQualityController::class, 'qualitySettings'])->name('review-quality.settings');
        Route::post('/review-quality/settings', [App\Http\Controllers\Admin\ReviewQualityController::class, 'updateQualitySettings'])->name('review-quality.update-settings');

        // Advanced Analytics Routes
        Route::get('/advanced-analytics', [App\Http\Controllers\Admin\AdvancedAnalyticsController::class, 'dashboard'])->name('advanced-analytics.dashboard');
        Route::get('/advanced-analytics/stats', [App\Http\Controllers\Admin\AdvancedAnalyticsController::class, 'getDashboardStats'])->name('advanced-analytics.stats');
        Route::get('/advanced-analytics/trends', [App\Http\Controllers\Admin\AdvancedAnalyticsController::class, 'getSubmissionTrends'])->name('advanced-analytics.trends');
        Route::get('/advanced-analytics/reviewers', [App\Http\Controllers\Admin\AdvancedAnalyticsController::class, 'getReviewerAnalytics'])->name('advanced-analytics.reviewers');
        Route::get('/advanced-analytics/categories', [App\Http\Controllers\Admin\AdvancedAnalyticsController::class, 'getCategoryDistribution'])->name('advanced-analytics.categories');
        Route::get('/advanced-analytics/sessions', [App\Http\Controllers\Admin\AdvancedAnalyticsController::class, 'getSessionAnalytics'])->name('advanced-analytics.sessions');
        Route::get('/advanced-analytics/quality', [App\Http\Controllers\Admin\AdvancedAnalyticsController::class, 'getQualityMetrics'])->name('advanced-analytics.quality');
        Route::get('/advanced-analytics/timeline', [App\Http\Controllers\Admin\AdvancedAnalyticsController::class, 'getTimelineAnalytics'])->name('advanced-analytics.timeline');
        Route::get('/advanced-analytics/report', [App\Http\Controllers\Admin\AdvancedAnalyticsController::class, 'generateReport'])->name('advanced-analytics.report');
        Route::get('/advanced-analytics/export', [App\Http\Controllers\Admin\AdvancedAnalyticsController::class, 'exportReport'])->name('advanced-analytics.export');

        // Reports Routes
        Route::get('/reports/builder', [App\Http\Controllers\Admin\ReportController::class, 'index'])->name('reports.builder');
        Route::get('/reports/summary', [App\Http\Controllers\Admin\ReportController::class, 'summary'])->name('reports.summary');
        Route::post('/reports/generate', [App\Http\Controllers\Admin\ReportController::class, 'generate'])->name('reports.generate');
        Route::get('/reports/export', [App\Http\Controllers\AdminController::class, 'exportReports'])->name('reports.export');
        Route::get('/reports/unpaid-contacts', [App\Http\Controllers\Admin\ReportController::class, 'exportUnpaidContacts'])->name('reports.unpaid-contacts');

        // Revision Management Routes (Legacy - redirect to unified system)
        Route::get('/revisions', function () {
            return redirect()->route('admin.decisions.index', ['filter' => 'revisions']);
        })->name('revisions.index');
        Route::get('/revisions/{abstract}', function ($abstract) {
            return redirect()->route('admin.decisions.show', $abstract);
        })->name('revisions.show');

        // Keep these for backward compatibility
        Route::get('/revisions/export', [App\Http\Controllers\Admin\RevisionManagementController::class, 'exportRevisions'])->name('revisions.export');
        Route::get('/revisions/stats', [App\Http\Controllers\Admin\RevisionManagementController::class, 'getStats'])->name('revisions.stats');

        // Legacy revision review routes (for backward compatibility)
        Route::get('/revisions/{abstract}/review', [App\Http\Controllers\AdminController::class, 'reviewRevision'])->name('revisions.review');
        Route::post('/revisions/{abstract}/process-legacy', [App\Http\Controllers\AdminController::class, 'processRevision'])->name('processRevision');

        // Speaker Management Routes (for mobile app)
        Route::get('/speakers', [App\Http\Controllers\Admin\SpeakerController::class, 'index'])->name('speakers.index');
        Route::get('/speakers/create', [App\Http\Controllers\Admin\SpeakerController::class, 'create'])->name('speakers.create');
        Route::post('/speakers', [App\Http\Controllers\Admin\SpeakerController::class, 'store'])->name('speakers.store');
        Route::get('/speakers/{speaker}/edit', [App\Http\Controllers\Admin\SpeakerController::class, 'edit'])->name('speakers.edit');
        Route::put('/speakers/{speaker}', [App\Http\Controllers\Admin\SpeakerController::class, 'update'])->name('speakers.update');
        Route::delete('/speakers/{speaker}', [App\Http\Controllers\Admin\SpeakerController::class, 'destroy'])->name('speakers.destroy');
        Route::post('/speakers/update-order', [App\Http\Controllers\Admin\SpeakerController::class, 'updateOrder'])->name('speakers.update-order');

        // Announcement Management Routes (for mobile app)
        Route::resource('announcements', App\Http\Controllers\Admin\AnnouncementController::class);
        Route::get('/session-role-applications', [App\Http\Controllers\Admin\SessionRoleApplicationController::class, 'index'])->name('session-role-applications.index');
        Route::get('/session-role-applications/export', [App\Http\Controllers\Admin\SessionRoleApplicationController::class, 'exportCsv'])->name('session-role-applications.export');
        Route::get('/session-role-applications/import-pool', [App\Http\Controllers\Admin\SessionRoleApplicationController::class, 'importPool'])->name('session-role-applications.import-pool');
        Route::get('/session-role-applications/import-pool/preview', fn () => redirect()->route('admin.session-role-applications.import-pool'));
        Route::post('/session-role-applications/import-pool/preview', [App\Http\Controllers\Admin\SessionRoleApplicationController::class, 'previewPool'])->name('session-role-applications.import-pool.preview');
        Route::post('/session-role-applications/import-pool/assign', [App\Http\Controllers\Admin\SessionRoleApplicationController::class, 'assignImportedLead'])->name('session-role-applications.import-pool.assign');
        Route::post('/session-role-applications/import-pool/approve', [App\Http\Controllers\Admin\SessionRoleApplicationController::class, 'approveGeneratedAssignments'])->name('session-role-applications.import-pool.approve');
        Route::post('/session-role-applications/import-pool/auto-assign', [App\Http\Controllers\Admin\SessionRoleApplicationController::class, 'autoAssignImportedPool'])->name('session-role-applications.import-pool.auto-assign');
        Route::post('/session-role-applications/import-pool/verify', [App\Http\Controllers\Admin\SessionRoleApplicationController::class, 'verifyImportedLead'])->name('session-role-applications.import-pool.verify');
        Route::patch('/session-role-applications/{application}/status', [App\Http\Controllers\Admin\SessionRoleApplicationController::class, 'updateStatus'])->name('session-role-applications.update-status');

        // Certificate Management
        Route::prefix('certificates')->name('certificates.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\CertificateManagementController::class, 'index'])->name('index');
            Route::get('/export', [App\Http\Controllers\Admin\CertificateManagementController::class, 'export'])->name('export');
            Route::post('/bulk-issue', [App\Http\Controllers\Admin\CertificateManagementController::class, 'bulkIssue'])->name('bulk-issue');
            Route::post('/{certificate}/revoke', [App\Http\Controllers\Admin\CertificateManagementController::class, 'revoke'])->name('revoke');
            Route::post('/{certificate}/reinstate', [App\Http\Controllers\Admin\CertificateManagementController::class, 'reinstate'])->name('reinstate');
            Route::get('/preview/{user}', [App\Http\Controllers\Admin\CertificateManagementController::class, 'previewUser'])->name('preview-user');
        });

        /*
        |--------------------------------------------------------------------------
        | CONFERENCE PROGRAM MANAGEMENT
        |--------------------------------------------------------------------------
        */
        Route::prefix('conference-program')->name('conference-program.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'index'])->name('index');
            Route::get('/assigned', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'assignedCodes'])->name('assigned');
            Route::post('/assigned/update', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'updateCodes'])->name('assigned.update');

            // Code Assignment Routes
            Route::get('/assign-codes', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'assignCodesPage'])->name('assign-codes.page');
            Route::post('/assign-codes', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'saveAssignedCodes'])->name('assign-codes.save');

            // Program Builder & Sessions
            Route::get('/builder', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'programBuilder'])->name('builder');
            Route::post('/sessions', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'createSession'])->name('sessions.create');

            // Session Content Management
            Route::post('/sessions/add-abstract', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'addToSession'])->name('sessions.add-abstract');
            Route::post('/sessions/remove-abstract', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'removeFromSession'])->name('sessions.remove-abstract');
            Route::post('/sessions/reorder', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'reorderSession'])->name('sessions.reorder');
            Route::post('/sessions/check-conflicts', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'checkConflicts'])->name('sessions.check-conflicts');
            Route::post('/fix-capacities', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'fixCapacities'])->name('fix-capacities');

            // Export
            Route::get('/export', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'exportProgram'])->name('export');

            // PDF Exports & Views
            Route::get('/export-pdf', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'exportProgramPDF'])->name('export-pdf');
            Route::get('/view-pdf', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'viewProgramPDF'])->name('view-pdf');
            Route::get('/view', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'programView'])->name('view');
            Route::get('/generate-abstract-book', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'generateAbstractBook'])->name('generate-abstract-book');
            Route::get('/abstract-book/status', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'abstractBookStatus'])->name('abstract-book.status');
            Route::post('/abstract-book/reset', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'resetAbstractBookStatus'])->name('abstract-book.reset');
            Route::get('/abstract-book/download', [App\Http\Controllers\Admin\ConferenceProgramController::class, 'downloadAbstractBook'])->name('abstract-book.download');
        });

    });

    /*
    |--------------------------------------------------------------------------
    | EXECUTIVE COMMAND CENTER - Strategic overview for leadership
    |--------------------------------------------------------------------------
    */
    Route::middleware(['scientific_admin'])->prefix('executive')->name('executive.')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\ExecutiveDashboardController::class, 'index'])->name('dashboard');
        Route::get('/refresh', [App\Http\Controllers\ExecutiveDashboardController::class, 'refreshMetrics'])->name('refresh');
    });

    /*
    |--------------------------------------------------------------------------
    | REVIEWER ROUTES - For abstract reviewers
    |--------------------------------------------------------------------------
    */
    // Note: the 'reviewer_onboarding' gate was removed — the preferences flow is stubbed
    // out (PreferenceController@index/@store just redirect to the dashboard and never set
    // reviewer_preferences_set), so any reviewer with that flag unset was bounced in an
    // infinite redirect loop (dashboard <-> preferences) → ERR_TOO_MANY_REDIRECTS.
    Route::middleware(['reviewer'])->prefix('reviewer')->name('reviewer.')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\ReviewerController::class, 'dashboard'])->name('dashboard');
        Route::get('/abstracts', [App\Http\Controllers\ReviewerController::class, 'abstracts'])->name('abstracts');
        Route::get('/abstracts/{abstract}/review', [App\Http\Controllers\ReviewerController::class, 'review'])->name('review');
        Route::get('/abstracts/{abstract}/comparison', [App\Http\Controllers\ReviewerController::class, 'comparison'])->name('comparison');
        Route::post('/abstracts/{abstract}/submit-review', [App\Http\Controllers\ReviewerController::class, 'submitReview'])->name('submit-review');
        Route::post('/abstracts/{abstract}/save-draft', [App\Http\Controllers\ReviewerController::class, 'saveDraft'])->name('save-draft');
        Route::post('/abstracts/{abstract}/decline', [App\Http\Controllers\ReviewerController::class, 'declineAssignment'])->name('decline-assignment');
        Route::get('/progress', [App\Http\Controllers\ReviewerController::class, 'getReviewProgress'])->name('progress');

        // Abstract submission for reviewers (they can also be authors)
        Route::get('/my-submissions', [App\Http\Controllers\ReviewerController::class, 'mySubmissions'])->name('my-submissions');

        // Reviewing Preferences
        Route::get('/preferences', [App\Http\Controllers\Reviewer\PreferenceController::class, 'index'])->name('preferences.index');
        Route::post('/preferences', [App\Http\Controllers\Reviewer\PreferenceController::class, 'store'])->name('preferences.store');
    });

    /*
    |--------------------------------------------------------------------------
    | FINANCE OFFICER ROUTES - For payment management
    |--------------------------------------------------------------------------
    */
    Route::middleware(['finance_officer'])->prefix('finance')->name('finance.')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\FinanceOfficerController::class, 'dashboard'])->name('dashboard');
        Route::get('/payments', [App\Http\Controllers\FinanceOfficerController::class, 'payments'])->name('payments');
        Route::get('/payments/{user}', [App\Http\Controllers\FinanceOfficerController::class, 'show'])->name('show');
        Route::post('/payments/{user}/verify', [App\Http\Controllers\FinanceOfficerController::class, 'verify'])->name('verify');
        Route::post('/payments/{user}/reject', [App\Http\Controllers\FinanceOfficerController::class, 'reject'])->name('reject');
        Route::post('/payments/{user}/waive', [App\Http\Controllers\FinanceOfficerController::class, 'waive'])->name('waive');
        Route::post('/payments/{user}/return-to-payment', [App\Http\Controllers\FinanceOfficerController::class, 'returnWaiverToPayment'])->name('return-to-payment');

        // Group Registration Routes for Finance
        Route::get('/groups', [App\Http\Controllers\FinanceOfficerController::class, 'groups'])->name('groups');
        Route::get('/groups/{groupRegistration}', [App\Http\Controllers\FinanceOfficerController::class, 'showGroup'])->name('group.show');
        Route::post('/groups/{groupRegistration}/verify', [App\Http\Controllers\FinanceOfficerController::class, 'verifyGroup'])->name('group.verify');
        Route::post('/groups/{groupRegistration}/reject', [App\Http\Controllers\FinanceOfficerController::class, 'rejectGroup'])->name('group.reject');
        Route::post('/groups/{groupRegistration}/waive', [App\Http\Controllers\FinanceOfficerController::class, 'waiveGroup'])->name('group.waive');
        Route::post('/groups/{groupRegistration}/return-to-payment', [App\Http\Controllers\FinanceOfficerController::class, 'returnGroupWaiverToPayment'])->name('group.return-to-payment');

        Route::get('/export', [App\Http\Controllers\FinanceOfficerController::class, 'exportReport'])->name('export');


        // Sponsor Payment Routes
        Route::get('/sponsors', [App\Http\Controllers\FinanceOfficerController::class, 'sponsors'])->name('sponsors');
        Route::get('/sponsors/create', [App\Http\Controllers\FinanceOfficerController::class, 'createSponsorPayment'])->name('sponsors.create');
        Route::post('/sponsors', [App\Http\Controllers\FinanceOfficerController::class, 'storeSponsorPayment'])->name('sponsors.store');
        Route::post('/sponsors/{sponsorPayment}/verify', [App\Http\Controllers\FinanceOfficerController::class, 'verifySponsor'])->name('sponsors.verify');
        Route::post('/sponsors/{sponsorPayment}/reject', [App\Http\Controllers\FinanceOfficerController::class, 'rejectSponsor'])->name('sponsors.reject');

        // Proof-of-payment files (private disk)
        Route::get('/transactions/{transaction}/proof', [App\Http\Controllers\FinanceOfficerController::class, 'transactionProof'])->name('transactions.proof');
    });

    /*
    |--------------------------------------------------------------------------
    | REGISTRATION OFFICER ROUTES - For conference check-in
    |--------------------------------------------------------------------------
    */
    Route::middleware(['registration_officer'])->prefix('registration')->name('registration.')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\RegistrationOfficerController::class, 'dashboard'])->name('dashboard');
        Route::get('/attendees', [App\Http\Controllers\RegistrationOfficerController::class, 'attendees'])->name('attendees');
        Route::get('/attendees/print-all-paid', [App\Http\Controllers\RegistrationOfficerController::class, 'printAllPaidBadges'])->name('print-all-paid');
        Route::get('/search', [App\Http\Controllers\RegistrationOfficerController::class, 'search'])->name('search');
        Route::get('/attendees/{user}', [App\Http\Controllers\RegistrationOfficerController::class, 'attendeeDetails'])->name('attendee-details');
        Route::patch('/attendees/{user}', [App\Http\Controllers\RegistrationOfficerController::class, 'updateAttendee'])->name('attendee-details.update');
        Route::get('/group-members/{member}', [App\Http\Controllers\RegistrationOfficerController::class, 'groupMemberDetails'])->name('group-member-details');
        Route::post('/attendees/{user}/check-in', [App\Http\Controllers\RegistrationOfficerController::class, 'checkIn'])->name('check-in');
        Route::post('/group-members/{member}/check-in', [App\Http\Controllers\RegistrationOfficerController::class, 'groupMemberCheckIn'])->name('group-member-check-in');
        Route::get('/stats', [App\Http\Controllers\RegistrationOfficerController::class, 'getStats'])->name('stats');
        Route::get('/export', [App\Http\Controllers\RegistrationOfficerController::class, 'exportReport'])->name('export');
        Route::post('/abstracts/{abstract}/send-reminder', [App\Http\Controllers\RegistrationOfficerController::class, 'sendPresentationReminder'])->name('send-presentation-reminder');
        Route::post('/attendees/{user}/attendance', [App\Http\Controllers\RegistrationOfficerController::class, 'recordAttendance'])->name('record-attendance');

        Route::get('/attendees/{user}/print-badge', [App\Http\Controllers\RegistrationOfficerController::class, 'printBadge'])->name('print-badge');
        Route::get('/group-members/{member}/print-badge', [App\Http\Controllers\RegistrationOfficerController::class, 'printGroupMemberBadge'])->name('group-member.print-badge');
        Route::post('/attendees/bulk-print', [App\Http\Controllers\RegistrationOfficerController::class, 'bulkPrintBadges'])->name('bulk-print-badges');

        // Attendance Tracking Routes
        Route::get('/attendance/scanner', [App\Http\Controllers\Admin\AttendanceController::class, 'scanner'])->name('attendance.scanner');
        Route::post('/attendance/scan', [App\Http\Controllers\Admin\AttendanceController::class, 'scan'])->name('attendance.scan');
        Route::post('/group-members/{member}/attendance', [App\Http\Controllers\RegistrationOfficerController::class, 'recordGroupMemberAttendance'])->name('group-member.record-attendance');
        Route::get('/attendance/report', [App\Http\Controllers\Admin\AttendanceController::class, 'report'])->name('attendance.report');

        // Onsite / Walk-In Visitors
        Route::get('/onsite', [App\Http\Controllers\RegistrationOfficerController::class, 'onsiteVisitors'])->name('onsite.index');
        Route::post('/onsite', [App\Http\Controllers\RegistrationOfficerController::class, 'onsiteCreate'])->name('onsite.create');
        Route::patch('/onsite/{visitor}', [App\Http\Controllers\RegistrationOfficerController::class, 'onsiteUpdate'])->name('onsite.update');
        Route::get('/onsite/{visitor}/print-badge', [App\Http\Controllers\RegistrationOfficerController::class, 'onsitePrint'])->name('onsite.print-badge');
        Route::post('/onsite/{visitor}/check-in', [App\Http\Controllers\RegistrationOfficerController::class, 'onsiteCheckIn'])->name('onsite.check-in');

        // Registry name overrides (badge print name without changing admin records)
        Route::post('/registry/name-override', [App\Http\Controllers\RegistrationOfficerController::class, 'registryNameOverride'])->name('registry.name-override');

        // Data Quality (scan endpoint kept for pre-print gate)
        Route::post('/quality/scan', [App\Http\Controllers\RegistrationOfficerController::class, 'qualityScan'])->name('quality.scan');
    });

    /*
    |--------------------------------------------------------------------------
    | PRESENTATION MANAGEMENT - For accepted abstracts
    |--------------------------------------------------------------------------
    */
    Route::get('/presentations', [App\Http\Controllers\PresentationController::class, 'index'])->name('presentations.index');
    Route::get('/presentations/{submission}', [App\Http\Controllers\PresentationController::class, 'show'])->name('presentations.show');
    Route::post('/presentations/{submission}/upload', [App\Http\Controllers\PresentationController::class, 'upload'])->name('presentations.upload');
    Route::post('/presentations/{submission}/delete', [App\Http\Controllers\PresentationController::class, 'delete'])->name('presentations.delete');
    Route::get('/presentations/{submission}/download/{fileType}/{fileName?}', [App\Http\Controllers\PresentationController::class, 'download'])->name('presentations.download');

    /*
    |--------------------------------------------------------------------------
    | NOTIFICATION ROUTES - For in-app notifications
    |--------------------------------------------------------------------------
    */
    Route::get('/notifications', [App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread', [App\Http\Controllers\NotificationController::class, 'unread'])->name('notifications.unread');
    Route::patch('/notifications/{notification}/read', [App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::patch('/notifications/mark-all-read', [App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::delete('/notifications/{notification}', [App\Http\Controllers\NotificationController::class, 'destroy'])->name('notifications.destroy');

    /*
    |--------------------------------------------------------------------------
    | HELP DOCUMENTATION ROUTES - For help and support
    |--------------------------------------------------------------------------
    */
    Route::get('/help', [App\Http\Controllers\HelpController::class, 'index'])->name('help.index');
    Route::get('/help/article/{slug}', [App\Http\Controllers\HelpController::class, 'article'])->name('help.article');
    Route::get('/help/faq', [App\Http\Controllers\HelpController::class, 'faq'])->name('help.faq');
    Route::get('/help/contact', [App\Http\Controllers\HelpController::class, 'contact'])->name('help.contact');
    Route::post('/help/contact', [App\Http\Controllers\HelpController::class, 'submitContact'])->middleware('throttle:5,1')->name('help.contact.submit');
    Route::get('/help/search', [App\Http\Controllers\HelpController::class, 'search'])->name('help.search');
    Route::get('/notifications/count', [App\Http\Controllers\NotificationController::class, 'count'])->name('notifications.count');

    /*
    |--------------------------------------------------------------------------
    | CHAIR & RAPPORTEUR ROUTES - For scientific session management
    |--------------------------------------------------------------------------
    */
    // Public Session Assignment Confirmation (Token protected)
    Route::get('/sessions/confirm/{sessionId}/{userId}/{role}/{token}', [App\Http\Controllers\SessionManagementController::class, 'confirmAssignment'])->name('sessions.confirm-assignment');

    Route::middleware(['auth'])->group(function () {
        Route::get('/sessions/my', [App\Http\Controllers\SessionManagementController::class, 'mySessions'])->name('sessions.my');
        Route::get('/sessions/{session}', [App\Http\Controllers\SessionManagementController::class, 'show'])->name('sessions.show');
        Route::post('/sessions/{session}/report', [App\Http\Controllers\SessionManagementController::class, 'saveReport'])->name('sessions.save-report');
    });

    /*
    |--------------------------------------------------------------------------
    | HELP ROUTES - Documentation and support
    |--------------------------------------------------------------------------
    */
    Route::get('/help', [App\Http\Controllers\HelpController::class, 'index'])->name('help.index');
    Route::get('/help/{category}/{article}', [App\Http\Controllers\HelpController::class, 'show'])->name('help.show');
    Route::get('/help/faq', [App\Http\Controllers\HelpController::class, 'faq'])->name('help.faq');
    Route::get('/help/contact', [App\Http\Controllers\HelpController::class, 'contact'])->name('help.contact');
});

/*
|--------------------------------------------------------------------------
| PUBLIC FEEDBACK ROUTES - For participants (no auth required)
|--------------------------------------------------------------------------
*/
Route::get('/feedback', [FeedbackController::class, 'create'])->name('feedback.create');
Route::post('/feedback', [FeedbackController::class, 'store'])->middleware('throttle:10,1')->name('feedback.store');
Route::get('/feedback/thank-you', [FeedbackController::class, 'thankyou'])->name('feedback.thankyou');

/*
|--------------------------------------------------------------------------
| AUTHENTICATION ROUTES - Laravel Breeze (includes email verification)
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| ADMIN FEEDBACK ROUTES - For admin users
|--------------------------------------------------------------------------
*/
Route::middleware(['scientific_admin'])->prefix('admin')->group(function () {
    Route::get('/feedback', [FeedbackController::class, 'index'])->name('admin.feedback.index');
    Route::get('/feedback/export', [FeedbackController::class, 'export'])->name('admin.feedback.export');
    Route::get('/feedback/report', [FeedbackController::class, 'report'])->name('admin.feedback.report');
});

/*
|--------------------------------------------------------------------------
| ENHANCED DECISION & REVISION WORKFLOW - Consolidated Admin Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'scientific_admin'])->prefix('admin')->name('admin.')->group(function () {
    // Main decision dashboard - consolidates decision queue and revision management
    Route::get('/decisions', [App\Http\Controllers\Admin\DecisionManagementController::class, 'index'])
        ->name('decisions.index');

    // View abstract for decision (replaces both decision-queue/show and revisions/show)
    Route::get('/decisions/{abstract}', [App\Http\Controllers\Admin\DecisionManagementController::class, 'show'])
        ->name('decisions.show');

    // Process admin decision (unified endpoint for all decision types)
    Route::post('/decisions/{abstract}/process', [App\Http\Controllers\Admin\DecisionManagementController::class, 'processDecision'])
        ->name('decisions.process');

    // Request revision (with enhanced feedback system)
    Route::post('/decisions/{abstract}/request-revision', [App\Http\Controllers\Admin\DecisionManagementController::class, 'requestRevision'])
        ->name('decisions.request-revision');

    // Get decision analysis data (AJAX endpoint)
    Route::get('/decisions/{abstract}/analysis', [App\Http\Controllers\Admin\DecisionManagementController::class, 'getDecisionAnalysis'])
        ->name('decisions.analysis');

    // Bulk decision actions
    Route::post('/decisions/bulk-process', [App\Http\Controllers\Admin\DecisionManagementController::class, 'bulkProcess'])
        ->name('decisions.bulk-process');

    Route::get('/diagnostics/workflow-trace', [App\Http\Controllers\Admin\WorkflowTraceController::class, 'index'])
        ->name('diagnostics.trace.index');
    Route::get('/diagnostics/workflow-trace/{abstract}', [App\Http\Controllers\Admin\WorkflowTraceController::class, 'show'])
        ->name('diagnostics.trace.show');
});

/*
|--------------------------------------------------------------------------
| NOTIFICATION ROUTES - Authenticated Users
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/notifications', [App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/mark-all-read', [App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
    Route::post('/notifications/{notification}/read', [App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::delete('/notifications/{notification}', [App\Http\Controllers\NotificationController::class, 'destroy'])->name('notifications.destroy');
});
