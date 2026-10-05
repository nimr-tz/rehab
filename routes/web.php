<?php

use App\Http\Controllers\AbstractController;
use App\Http\Controllers\Admin\OverviewController;
use App\Http\Controllers\Admin\ParticipantController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeskController;
use App\Http\Controllers\Finance\PaymentController as FinancePaymentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgrammeController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\Scientific\AbstractController as ScientificAbstractController;
use App\Http\Controllers\Scientific\ReviewerController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');
Route::get('/programme', ProgrammeController::class)->name('programme');

// Sign-in, registration, password reset and email verification routes come from Fortify.

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/search', SearchController::class)->name('search');
    Route::get('/notifications/{id}', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('/notifications/read', [NotificationController::class, 'readAll'])->name('notifications.read');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'password'])->name('profile.password');

    // Participants
    Route::middleware('role:participant')->group(function () {
        Route::get('/registration', [RegistrationController::class, 'show'])->name('registration.show');
        Route::post('/registration', [RegistrationController::class, 'store'])->name('registration.store');
        Route::get('/registration/badge-and-check-in', [RegistrationController::class, 'badgePage'])->name('registration.badge.show');
        Route::get('/registration/badge', [RegistrationController::class, 'badge'])->name('registration.badge');
        Route::get('/registration/invitation-letter', [RegistrationController::class, 'letter'])->name('registration.letter');
        Route::post('/registration/payments', [PaymentController::class, 'store'])->name('registration.payments.store');

        Route::get('/abstracts', [AbstractController::class, 'index'])->name('abstracts.index');
        Route::get('/abstracts/new', [AbstractController::class, 'create'])->name('abstracts.create');
        Route::post('/abstracts', [AbstractController::class, 'store'])->name('abstracts.store');
        Route::get('/abstracts/{abstract}', [AbstractController::class, 'show'])->name('abstracts.show');
        Route::get('/abstracts/{abstract}/edit', [AbstractController::class, 'edit'])->name('abstracts.edit');
        Route::put('/abstracts/{abstract}', [AbstractController::class, 'update'])->name('abstracts.update');
        Route::post('/abstracts/{abstract}/withdraw', [AbstractController::class, 'withdraw'])->name('abstracts.withdraw');
    });

    // Reviewers: double-blind, they never see who wrote an abstract.
    Route::middleware('role:reviewer')->group(function () {
        Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');
        Route::get('/reviews/{assignment}', [ReviewController::class, 'edit'])->name('reviews.edit');
        Route::put('/reviews/{assignment}', [ReviewController::class, 'update'])->name('reviews.update');
    });

    // Scientific committee
    Route::middleware('role:scientific_admin|admin')->prefix('scientific')->name('scientific.')->group(function () {
        Route::get('/abstracts', [ScientificAbstractController::class, 'index'])->name('abstracts.index');
        Route::get('/abstracts/{abstract}', [ScientificAbstractController::class, 'show'])->name('abstracts.show');
        Route::post('/abstracts/{abstract}/reviewers', [ScientificAbstractController::class, 'assign'])->name('abstracts.assign');
        Route::delete('/abstracts/{abstract}/reviewers/{assignment}', [ScientificAbstractController::class, 'unassign'])->name('abstracts.unassign');
        Route::post('/abstracts/{abstract}/decision', [ScientificAbstractController::class, 'decide'])->name('abstracts.decide');
        Route::get('/reviewers', ReviewerController::class)->name('reviewers');
    });

    // Finance
    Route::middleware('role:finance_officer|admin')->prefix('finance')->name('finance.')->group(function () {
        Route::get('/payments', [FinancePaymentController::class, 'index'])->name('payments.index');
        Route::get('/payments/export', [FinancePaymentController::class, 'export'])->name('payments.export');
        Route::get('/payments/{payment}', [FinancePaymentController::class, 'show'])->name('payments.show');
        Route::get('/payments/{payment}/proof', [FinancePaymentController::class, 'proof'])->name('payments.proof');
        Route::post('/payments/{payment}/verify', [FinancePaymentController::class, 'verify'])->name('payments.verify');
        Route::post('/payments/{payment}/reject', [FinancePaymentController::class, 'reject'])->name('payments.reject');
    });

    // Registration desk
    Route::middleware('role:registration_officer|admin')->prefix('desk')->name('desk.')->group(function () {
        Route::get('/', [DeskController::class, 'index'])->name('index');
        Route::get('/print-queue', [DeskController::class, 'queue'])->name('queue');
        Route::post('/print-queue/batch', [DeskController::class, 'printBatch'])->name('print-batch');
        Route::post('/{registration}/check-in', [DeskController::class, 'checkIn'])->name('check-in');
        Route::get('/{registration}/badge', [DeskController::class, 'badge'])->name('badge');
    });

    // Administration
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', OverviewController::class)->name('overview');
        Route::get('/participants', [ParticipantController::class, 'index'])->name('participants.index');
        Route::get('/participants/{registration}', [ParticipantController::class, 'show'])->name('participants.show');
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::put('/users/{user}/roles', [UserController::class, 'roles'])->name('users.roles');
        Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    });
});
