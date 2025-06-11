<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Filament Authentication Routes
Route::middleware(['web'])->group(function () {
    Route::get('/login', function () {
        return redirect()->route('filament.member.auth.login');
    })->name('login');
    
    Route::get('/register', function () {
        return redirect()->route('filament.member.auth.register');
    })->name('register');
    
    Route::get('/password/reset', function () {
        return redirect()->route('filament.member.auth.password-reset.request');
    })->name('password.request');
    
    Route::get('/password/reset/{token}', function () {
        return redirect()->route('filament.member.auth.password-reset.reset');
    })->name('password.reset');
    
    Route::get('/email/verify', function () {
        return redirect()->route('filament.member.auth.email-verification.prompt');
    })->name('verification.notice');
    
    Route::get('/email/verify/{id}/{hash}', function ($id, $hash) {
        return redirect()->route('filament.member.auth.email-verification.verify', ['id' => $id, 'hash' => $hash]);
    })->name('verification.verify');
});
