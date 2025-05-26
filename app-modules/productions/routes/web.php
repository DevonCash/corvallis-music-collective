<?php

use Carbon\CarbonImmutable;
use CorvMC\Productions\Models\Production;
use Illuminate\Support\Facades\Route;

// Public routes
Route::middleware(['web'])->group(function () {
    Route::get('/show-tonight', function () {
        $activeProduction = Production::where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->first();

        if (!$activeProduction || !$activeProduction->ticket_link) {
            return redirect()->route('productions.index');
        }

        return redirect($activeProduction->ticket_link);
    })->name('productions.show-tonight');

    Route::get('/events', function () {
        return view('productions::livewire-index');
    })->name('productions.index');

    Route::get('/events/{production}', function (Production $production) {
        return view('productions::show', [
            'production' => $production->load('venue')
        ]);
    })->name('productions.show');
});

// Protected routes
Route::middleware(['web', 'auth'])->group(function () {
    // Add any protected production routes here
});
