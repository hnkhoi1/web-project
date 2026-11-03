<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    // Temporary stub; replaced by the notes feature.
    Route::inertia('ghi-chu', 'notes/index')->name('notes.index');
});

require __DIR__.'/auth.php';
require __DIR__.'/settings.php';
