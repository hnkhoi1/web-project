<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\LawController;
use App\Http\Controllers\NoteController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? to_route('notes.index') : to_route('login'))->name('home');

Route::inertia('gioi-thieu', 'about')->name('about');
Route::get('lien-he', [ContactController::class, 'create'])->name('contact.create');
Route::post('lien-he', [ContactController::class, 'store'])->name('contact.store');

Route::middleware(['auth'])->group(function () {
    Route::get('tra-cuu', [LawController::class, 'search'])->name('law.search');
    Route::get('dieu-luat/{id}', [LawController::class, 'show'])->whereNumber('id')->name('law.show');
    Route::get('tu-van', [ChatController::class, 'index'])->name('chat.index');
    Route::post('tu-van/hoi', [ChatController::class, 'ask'])->name('chat.ask');
    Route::get('ghi-chu', [NoteController::class, 'index'])->name('notes.index');
    Route::post('ghi-chu', [NoteController::class, 'store'])->name('notes.store');
    Route::patch('ghi-chu/{note}', [NoteController::class, 'update'])->name('notes.update');
    Route::patch('ghi-chu/{note}/ghim', [NoteController::class, 'pin'])->name('notes.pin');
    Route::delete('ghi-chu/{note}', [NoteController::class, 'destroy'])->name('notes.destroy');
});

require __DIR__.'/auth.php';
require __DIR__.'/settings.php';

// Unmatched URLs go through the web middleware group so the 404 page gets shared props and the session.
Route::fallback(fn () => abort(404));
