<?php

use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\VenueController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::resource('departments', DepartmentController::class)->except(['show']);
    Route::resource('venues', VenueController::class)->except(['show']);
    Route::resource('events', EventController::class)->except(['show']);
});

require __DIR__.'/settings.php';
