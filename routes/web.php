<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? to_route('notes.index') : to_route('login'))->name('home');

Route::middleware(['auth'])->group(function () {
    // Temporary stub; replaced by the notes feature.
    Route::inertia('ghi-chu', 'notes/index')->name('notes.index');
});

require __DIR__.'/auth.php';
require __DIR__.'/settings.php';
