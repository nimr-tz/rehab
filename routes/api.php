<?php

use App\Http\Controllers\Api\V1\AttendeeController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\SessionController;
use App\Http\Middleware\EnsureScanningStaff;
use Illuminate\Support\Facades\Route;

/*
| The staff scanning app. Documented in docs/STAFF_APP_API.md.
*/

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login');

    Route::middleware(['auth:sanctum', EnsureScanningStaff::class, 'throttle:600,1'])->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->name('me');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('/sessions', [SessionController::class, 'index'])->name('sessions.index');
        Route::get('/sessions/{session}/attendance', [SessionController::class, 'attendance'])->name('sessions.attendance');
        Route::post('/sessions/{session}/scans', [SessionController::class, 'scan'])->name('sessions.scan');

        Route::get('/attendees/{code}', [AttendeeController::class, 'show'])->name('attendees.show');
    });
});
