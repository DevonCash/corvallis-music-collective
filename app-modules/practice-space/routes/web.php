<?php

use CorvMC\PracticeSpace\Http\Controllers\BookingConfirmationController;
use CorvMC\PracticeSpace\Notifications\BookingConfirmationRequestNotification;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Practice Space Module Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for the practice space module.
|
*/

// Booking confirmation routes
Route::get('/practice-space/bookings/{booking}/confirm', [BookingConfirmationController::class, 'confirm'])
    ->name('practice-space.bookings.confirm')
    ->middleware(['signed']);

Route::get('/practice-space/bookings/{booking}/cancel', [BookingConfirmationController::class, 'cancel'])
    ->name('practice-space.bookings.cancel')
    ->middleware(['signed']);
