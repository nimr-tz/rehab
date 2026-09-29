<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CheckInController;
use App\Http\Controllers\Api\ConfigController;
use App\Http\Controllers\Api\ProgramController;
use App\Http\Controllers\Api\SessionAttendanceController;
use App\Http\Controllers\Api\SessionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile API — staff attendance app
|--------------------------------------------------------------------------
|
| The mobile app is used by registration staff/ushers only: they sign in,
| pick the conference door or a session, and scan attendee badges.
|
*/

Route::get('/config', [ConfigController::class, 'index']);
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

// Programme data used by staff to choose which session door they are scanning.
Route::get('/program', [ProgramController::class, 'index']);
Route::get('/program/check-updates', [ProgramController::class, 'checkUpdates']);
Route::get('/sessions', [SessionController::class, 'index']);
Route::get('/sessions/{session}', [SessionController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

// Staff scanner (registration officers)
Route::middleware(['auth:sanctum', 'registration_officer'])->group(function () {
    Route::get('/staff/lookup-qr/{token}', [CheckInController::class, 'staffLookupQR']);
    Route::post('/staff/mark-attendance', [CheckInController::class, 'staffMarkAttendance']);

    // Session-door scanning (CPD evidence)
    Route::post('/staff/sessions/{session}/attendance', [SessionAttendanceController::class, 'store']);
    Route::get('/staff/sessions/{session}/attendance', [SessionAttendanceController::class, 'summary']);
});
