<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\RssSourceController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::view('/', 'dashboard')->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/articles', [ReviewController::class, 'index'])->name('articles.index');
    Route::get('/articles/{article}', [ReviewController::class, 'show'])->name('articles.show');
    Route::post('/articles/{article}/accept', [ReviewController::class, 'accept'])->name('articles.accept');
    Route::post('/articles/{article}/reject', [ReviewController::class, 'reject'])->name('articles.reject');
    Route::post('/articles/{article}/regenerate', [ReviewController::class, 'regenerate'])->name('articles.regenerate');

    Route::get('/rss', [RssSourceController::class, 'index'])->name('rss.index');
    Route::post('/rss', [RssSourceController::class, 'store'])->name('rss.store');
    Route::get('/rss/{rssSource}/edit', [RssSourceController::class, 'edit'])->name('rss.edit');
    Route::post('/rss/{rssSource}', [RssSourceController::class, 'update'])->name('rss.update');
    Route::post('/rss/{rssSource}/toggle', [RssSourceController::class, 'toggle'])->name('rss.toggle');
    Route::post('/rss/{rssSource}/delete', [RssSourceController::class, 'destroy'])->name('rss.destroy');
});
