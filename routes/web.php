<?php

use App\Http\Controllers\NoteController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? to_route('notes.index') : to_route('login'))->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('ghi-chu', [NoteController::class, 'index'])->name('notes.index');
    Route::post('ghi-chu', [NoteController::class, 'store'])->name('notes.store');
    Route::patch('ghi-chu/{note}', [NoteController::class, 'update'])->name('notes.update');
    Route::patch('ghi-chu/{note}/ghim', [NoteController::class, 'pin'])->name('notes.pin');
    Route::delete('ghi-chu/{note}', [NoteController::class, 'destroy'])->name('notes.destroy');
});

require __DIR__.'/auth.php';
require __DIR__.'/settings.php';
