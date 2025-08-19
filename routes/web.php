<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::redirect('/dashboard', '/merchants')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('merchants', 'merchants')
    ->middleware(['auth', 'verified'])
    ->name('merchants');

Route::view('merchant-detail/{id}', 'merchant-detail')
    ->middleware(['auth', 'verified'])
    ->name('merchant-detail');

Route::view('our-services', 'our-services')
    ->middleware(['auth', 'verified'])
    ->name('our-services');

Route::view('our-services/create', 'our-service-form')
    ->middleware(['auth', 'verified'])
    ->name('our-services.create');

Route::view('our-services/{serviceId}/edit', 'our-service-form')
    ->middleware(['auth', 'verified'])
    ->name('our-services.edit');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
