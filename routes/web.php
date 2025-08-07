<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('merchants', 'merchants')
    ->middleware(['auth', 'verified'])
    ->name('merchants');

Route::view('merchant-detail/{id}', 'merchant-detail')
    ->middleware(['auth', 'verified'])
    ->name('merchant-detail');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
