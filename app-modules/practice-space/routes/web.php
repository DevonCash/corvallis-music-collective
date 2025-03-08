<?php

use Illuminate\Support\Facades\Route;
use CorvMC\PracticeSpace\Filament\Pages\UserBookings;

/*
|--------------------------------------------------------------------------
| Practice Space Module Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your module. These
| routes are loaded by the PracticeSpaceServiceProvider within a group which
| contains the "web" middleware group.
|
*/

Route::middleware(['web', 'auth'])->prefix('practice-space')->group(function () {
    Route::get('/my-bookings', function () {
        return redirect()->to(UserBookings::getUrl());
    })->name('practice-space.my-bookings');
}); 