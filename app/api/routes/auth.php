<?php

use App\Http\Controllers\Auth\PasswordResetController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;

// Fortify's own routes are disabled (see FortifyServiceProvider): login, register,
// logout and the emailed password-reset link exist; no verification or confirmation.
Route::middleware('web')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
        Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
        Route::post('register', [RegisteredUserController::class, 'store'])->name('register.store');

        Route::get('quen-mat-khau', [PasswordResetController::class, 'create'])->name('password.request');
        Route::post('quen-mat-khau', [PasswordResetController::class, 'store'])
            ->middleware('throttle:5,1')
            ->name('password.email');
        Route::get('dat-lai-mat-khau/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
        Route::post('dat-lai-mat-khau', [PasswordResetController::class, 'update'])
            ->middleware('throttle:5,1')
            ->name('password.update');
    });

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->middleware('auth')
        ->name('logout');
});
