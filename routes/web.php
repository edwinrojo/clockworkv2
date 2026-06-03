<?php

use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EventAttendanceController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventLiveController;
use App\Http\Controllers\EventSessionController;
use App\Http\Controllers\QrDisplayController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VenueController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::get('display/{displaySecret}', [QrDisplayController::class, 'show'])->name('display.show');
Route::get('display/{displaySecret}/token', [QrDisplayController::class, 'token'])->name('display.token');

Route::middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::resource('departments', DepartmentController::class)->except(['show']);
    Route::resource('venues', VenueController::class)->except(['show']);
    Route::resource('events', EventController::class)->except(['show']);

    Route::get('events/{event}/live', [EventLiveController::class, 'show'])->name('events.live');
    Route::post('events/{event}/session/start', [EventSessionController::class, 'start'])->name('events.session.start');
    Route::post('events/{event}/session/pause', [EventSessionController::class, 'pause'])->name('events.session.pause');
    Route::post('events/{event}/session/resume', [EventSessionController::class, 'resume'])->name('events.session.resume');
    Route::post('events/{event}/session/end', [EventSessionController::class, 'end'])->name('events.session.end');
    Route::post('events/{event}/session/rotate', [EventSessionController::class, 'rotate'])->name('events.session.rotate');

    Route::get('events/{event}/attendances', [EventAttendanceController::class, 'index'])->name('events.attendances');
    Route::post('events/{event}/attendances', [EventAttendanceController::class, 'store'])->name('events.attendances.store');
    Route::get('events/{event}/attendances/export', [EventAttendanceController::class, 'export'])->name('events.attendances.export');

    Route::resource('users', UserController::class)->except(['show']);
});

require __DIR__.'/settings.php';
