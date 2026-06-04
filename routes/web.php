<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DisplayUnlockController;
use App\Http\Controllers\EventAttendanceController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventDisplayPinController;
use App\Http\Controllers\EventLiveController;
use App\Http\Controllers\EventRosterController;
use App\Http\Controllers\EventSessionController;
use App\Http\Controllers\QrDisplayController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserImportController;
use App\Http\Controllers\VenueController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::get('display/{displaySecret}/unlock', [DisplayUnlockController::class, 'create'])->name('display.unlock');
Route::post('display/{displaySecret}/unlock', [DisplayUnlockController::class, 'store'])->name('display.unlock.store');
Route::get('display/{displaySecret}', [QrDisplayController::class, 'show'])->name('display.show');
Route::get('display/{displaySecret}/token', [QrDisplayController::class, 'token'])->name('display.token');

Route::middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('audit-log', [ActivityLogController::class, 'index'])->name('audit-log.index');

    Route::resource('departments', DepartmentController::class)->except(['show']);
    Route::resource('venues', VenueController::class)->except(['show']);
    Route::resource('events', EventController::class)->except(['show']);

    Route::get('events/{event}/live', [EventLiveController::class, 'show'])->name('events.live');
    Route::get('events/{event}/roster', [EventRosterController::class, 'edit'])->name('events.roster.edit');
    Route::put('events/{event}/roster', [EventRosterController::class, 'update'])->name('events.roster.update');
    Route::post('events/{event}/session/start', [EventSessionController::class, 'start'])->name('events.session.start');
    Route::post('events/{event}/session/pause', [EventSessionController::class, 'pause'])->name('events.session.pause');
    Route::post('events/{event}/session/resume', [EventSessionController::class, 'resume'])->name('events.session.resume');
    Route::post('events/{event}/session/end', [EventSessionController::class, 'end'])->name('events.session.end');
    Route::post('events/{event}/session/rotate', [EventSessionController::class, 'rotate'])->name('events.session.rotate');

    Route::get('events/{event}/attendances', [EventAttendanceController::class, 'index'])->name('events.attendances');
    Route::post('events/{event}/attendances', [EventAttendanceController::class, 'store'])->name('events.attendances.store');
    Route::get('events/{event}/attendances/export', [EventAttendanceController::class, 'export'])->name('events.attendances.export');

    Route::post('events/{event}/display-pin', [EventDisplayPinController::class, 'update'])->name('events.display-pin.update');

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');
    Route::get('reports/events/{event}', [ReportController::class, 'show'])->name('reports.show');

    Route::get('users/import', [UserImportController::class, 'create'])->name('users.import.create');
    Route::post('users/import', [UserImportController::class, 'store'])->name('users.import.store');
    Route::get('users/import/template', [UserImportController::class, 'template'])->name('users.import.template');
    Route::post('users/{user}/revoke-tokens', [UserController::class, 'revokeTokens'])->name('users.revoke-tokens');
    Route::post('users/{user}/send-password-reset', [UserController::class, 'sendPasswordReset'])->name('users.send-password-reset');
    Route::post('users/{user}/set-password', [UserController::class, 'setPassword'])->name('users.set-password');
    Route::resource('users', UserController::class)->except(['show']);
});

require __DIR__.'/settings.php';
