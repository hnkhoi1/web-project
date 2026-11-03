<?php

use App\Http\Controllers\Settings\PreferencesController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('ho-so', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('doi-mat-khau', [SecurityController::class, 'edit'])->name('security.edit');
    Route::get('tuy-chinh', [PreferencesController::class, 'edit'])->name('preferences.edit');
    Route::put('tuy-chinh', [PreferencesController::class, 'update'])->name('preferences.update');

    // The shared demo account may read these pages but not change anything.
    Route::middleware('not-demo')->group(function () {
        Route::patch('ho-so', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('ho-so', [ProfileController::class, 'destroy'])->name('profile.destroy');
        Route::post('ho-so/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
        Route::delete('ho-so/avatar', [ProfileController::class, 'destroyAvatar'])->name('profile.avatar.destroy');

        Route::put('doi-mat-khau', [SecurityController::class, 'update'])
            ->middleware('throttle:6,1')
            ->name('user-password.update');
    });
});
