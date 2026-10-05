<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

// Sign-in, registration, password reset and email verification routes come from Fortify.

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');
});
