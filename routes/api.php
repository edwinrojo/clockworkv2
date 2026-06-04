<?php

use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CheckInController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\ProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:api-login');

    Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword'])
        ->middleware('throttle:api-password-reset');

    Route::post('auth/reset-password', [AuthController::class, 'resetPassword'])
        ->middleware('throttle:api-password-reset');

    Route::middleware(['auth:sanctum', 'mobile.employee', 'throttle:api-mobile'])->group(function (): void {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('profile', [ProfileController::class, 'show']);
        Route::get('events', [EventController::class, 'index']);
        Route::post('check-in', [CheckInController::class, 'store'])
            ->middleware('throttle:api-check-in');
        Route::get('attendances', [AttendanceController::class, 'index']);
    });
});
