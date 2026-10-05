<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

// The view only; authentication arrives with Phase 0.
Route::view('/login', 'auth.login')->name('login');
