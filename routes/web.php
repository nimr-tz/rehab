<?php

use App\Http\Controllers\AbstractController;
use App\Http\Controllers\Admin\OverviewController;
use App\Http\Controllers\Admin\ParticipantController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AwardController;
use App\Http\Controllers\Awards\CategoryController as AwardCategoryController;
use App\Http\Controllers\Awards\EntryController as AwardEntryController;
use App\Http\Controllers\Awards\JudgingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeskController;
use App\Http\Controllers\Finance\PaymentController as FinancePaymentController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\Media\AlbumController as MediaAlbumController;
use App\Http\Controllers\Media\PhotoController as MediaPhotoController;
use App\Http\Controllers\Media\RemovalRequestController;
use App\Http\Controllers\Media\UploadController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgrammeController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\Scientific\AbstractController as ScientificAbstractController;
use App\Http\Controllers\Scientific\ReviewerController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');
Route::get('/programme', ProgrammeController::class)->name('programme');

// Photo gallery
Route::get('/gallery', [GalleryController::class, 'index'])->name('gallery.index');
Route::get('/gallery/photos/{photo}/download', [GalleryController::class, 'download'])->name('gallery.photos.download');
Route::get('/gallery/photos/{photo}/{size}', [GalleryController::class, 'photo'])->whereIn('size', ['thumb', 'display'])->name('gallery.photos.show');
Route::post('/gallery/photos/{photo}/removal', [GalleryController::class, 'requestRemoval'])->middleware('throttle:5,1')->name('gallery.photos.removal');
Route::get('/gallery/{album:slug}', [GalleryController::class, 'album'])->name('gallery.album');
Route::get('/awards', [AwardController::class, 'index'])->name('awards.index');

// Sign-in, registration, password reset and email verification routes come from Fortify.

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/search', SearchController::class)->name('search');
    Route::post('/workspace', WorkspaceController::class)->name('workspace.switch');
    Route::get('/notifications/{id}', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('/notifications/read', [NotificationController::class, 'readAll'])->name('notifications.read');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'password'])->name('profile.password');

    // Awards: anyone with an account can nominate, and winners download their certificates.
    Route::get('/my-awards', [AwardController::class, 'mine'])->name('awards.mine');
    Route::post('/my-awards/nominations', [AwardController::class, 'nominate'])->middleware('throttle:10,1')->name('awards.nominate');
    Route::get('/my-awards/{entry}/certificate', [AwardController::class, 'certificate'])->name('awards.certificate');

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
        Route::get('/abstracts/{abstract}/revision', [AbstractController::class, 'revision'])->name('abstracts.revision.edit');
        Route::put('/abstracts/{abstract}/revision', [AbstractController::class, 'submitRevision'])->name('abstracts.revision.update');

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
        Route::put('/abstracts/{abstract}/revision-due', [ScientificAbstractController::class, 'extendRevision'])->name('abstracts.revision-due');
    });

    // Awards judges score the finalists of the awards they are assigned to.
    Route::middleware('role:judge')->prefix('judging')->name('judging.')->group(function () {
        Route::get('/', [JudgingController::class, 'index'])->name('index');
        Route::get('/entries/{entry}', [JudgingController::class, 'edit'])->name('edit');
        Route::put('/entries/{entry}', [JudgingController::class, 'update'])->name('update');
    });

    // Awards committee: the scientific committee and admins
    Route::middleware('role:scientific_admin|admin')->prefix('committee/awards')->name('committee.awards.')->group(function () {
        Route::get('/', [AwardCategoryController::class, 'index'])->name('index');
        Route::post('/suggested', [AwardCategoryController::class, 'suggested'])->name('suggested');
        Route::get('/new', [AwardCategoryController::class, 'create'])->name('create');
        Route::post('/', [AwardCategoryController::class, 'store'])->name('store');
        Route::get('/{category}', [AwardCategoryController::class, 'show'])->name('show');
        Route::get('/{category}/edit', [AwardCategoryController::class, 'edit'])->name('edit');
        Route::put('/{category}', [AwardCategoryController::class, 'update'])->name('update');
        Route::delete('/{category}', [AwardCategoryController::class, 'destroy'])->name('destroy');
        Route::put('/{category}/judges', [AwardCategoryController::class, 'judges'])->name('judges');
        Route::post('/{category}/entries', [AwardEntryController::class, 'store'])->name('entries.store');
        Route::delete('/{category}/entries/{entry}', [AwardEntryController::class, 'destroy'])->name('entries.destroy');
        Route::get('/{category}/entries/{entry}/certificate', [AwardEntryController::class, 'certificate'])->name('entries.certificate');
        Route::put('/{category}/places', [AwardEntryController::class, 'places'])->name('places');
        Route::post('/{category}/announcement', [AwardEntryController::class, 'announce'])->name('announce');
        Route::delete('/{category}/announcement', [AwardEntryController::class, 'withdraw'])->name('withdraw');
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

    // Photographers: albums, uploads and publishing
    Route::middleware('role:photographer|admin')->prefix('media')->name('media.')->group(function () {
        Route::get('/albums', [MediaAlbumController::class, 'index'])->name('albums.index');
        Route::get('/albums/new', [MediaAlbumController::class, 'create'])->name('albums.create');
        Route::post('/albums', [MediaAlbumController::class, 'store'])->name('albums.store');
        Route::get('/albums/{album}', [MediaAlbumController::class, 'show'])->name('albums.show');
        Route::get('/albums/{album}/edit', [MediaAlbumController::class, 'edit'])->name('albums.edit');
        Route::put('/albums/{album}', [MediaAlbumController::class, 'update'])->name('albums.update');
        Route::delete('/albums/{album}', [MediaAlbumController::class, 'destroy'])->name('albums.destroy');
        Route::post('/albums/{album}/uploads', UploadController::class)->name('albums.uploads');
        Route::post('/albums/{album}/photos', [MediaPhotoController::class, 'bulk'])->name('albums.photos');

        Route::middleware('role:admin')->group(function () {
            Route::get('/removal-requests', [RemovalRequestController::class, 'index'])->name('removal-requests.index');
            Route::put('/removal-requests/{removal}', [RemovalRequestController::class, 'update'])->name('removal-requests.update');
        });
    });

    // The executive summary: admins, and executives who see only this.
    Route::get('/admin', OverviewController::class)->middleware('role:admin|executive')->name('admin.overview');

    // Administration
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/participants', [ParticipantController::class, 'index'])->name('participants.index');
        Route::get('/participants/{registration}', [ParticipantController::class, 'show'])->name('participants.show');
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::put('/users/{user}/roles', [UserController::class, 'roles'])->name('users.roles');
        Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    });
});
