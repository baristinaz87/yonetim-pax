<?php

use App\Http\Controllers\Shopify\WebhookController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GoogleAuthController;

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

// Shopify yönetimi — partner hesapları ve uygulamalar
Route::prefix('shopify')
    ->middleware(['auth', 'verified'])
    ->name('shopify.')
    ->group(function () {
        Route::view('partner-accounts', 'shopify-partner-accounts')->name('partner-accounts');
        Route::view('partner-accounts/create', 'shopify-partner-account-form')->name('partner-accounts.create');
        Route::view('partner-accounts/{accountId}/edit', 'shopify-partner-account-form')->name('partner-accounts.edit');

        Route::view('apps', 'shopify-apps')->name('apps');
        Route::view('apps/create', 'shopify-app-form')->name('apps.create');
        Route::view('apps/{appId}', 'shopify-app-detail')->name('apps.show');
        Route::view('apps/{appId}/edit', 'shopify-app-form')->name('apps.edit');

        Route::view('stores', 'shopify-stores')->name('stores.index');
        Route::view('stores/{storeId}', 'shopify-store-detail')->name('stores.show');
    });

// Google OAuth callback
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
    ->middleware(['auth'])
    ->name('google.callback');

// Shopify webhook alıcısı — auth gerektirmez, imza middleware ile doğrulanır.
Route::post('/webhooks/shopify/{app:handle}', WebhookController::class)
    ->name('shopify.webhook');

require __DIR__.'/auth.php';
