<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OurServiceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Auth routes
Route::post('/token', [AuthController::class, 'token']);

Route::middleware(['auth:sanctum'])->group(function () {
    // Auth routes that require authentication
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/logout-all', [AuthController::class, 'logoutAll']);

    // Service routes
    Route::get('/services', [OurServiceController::class, 'index']);
    Route::get('/services/{service}', [OurServiceController::class, 'show']);
});
