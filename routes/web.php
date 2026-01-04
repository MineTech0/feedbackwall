<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\FeedbackVoteController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\BoardController as AdminBoardController;
use App\Http\Controllers\Admin\FeedbackController as AdminFeedbackController;
use App\Http\Controllers\Admin\ModerationLogController as AdminModerationLogController;

Route::get('/', [BoardController::class, 'index'])->name('home');
Route::get('/b/{slug}', [BoardController::class, 'show'])->name('board.show');
Route::post('/b/{slug}/feedback', [FeedbackController::class, 'store'])
    ->name('feedback.store')
    ->middleware('throttle:feedback-submissions');

Route::post('/feedback/{feedback}/vote', [FeedbackVoteController::class, 'toggle'])
    ->name('feedback.vote')
    ->middleware('throttle:feedback-votes');

// Auth
Route::get('/login', [AuthController::class, 'redirectToGoogle'])->name('login'); // Named route 'login' for auth middleware
Route::get('/auth/google/redirect', [AuthController::class, 'redirectToGoogle'])->name('login.google');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Admin
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    
    // Board management
    Route::get('/boards', [AdminBoardController::class, 'index'])->name('boards.index');
    Route::get('/boards/create', [AdminBoardController::class, 'create'])->name('boards.create');
    Route::post('/boards', [AdminBoardController::class, 'store'])->name('boards.store');
    Route::get('/boards/{board}/edit', [AdminBoardController::class, 'edit'])->name('boards.edit');
    Route::put('/boards/{board}', [AdminBoardController::class, 'update'])->name('boards.update');
    Route::delete('/boards/{board}', [AdminBoardController::class, 'archive'])->name('boards.archive');

    // Feedback management per board
    Route::get('/boards/{board}/feedback', [AdminFeedbackController::class, 'index'])->name('boards.feedback.index');
    Route::post('/feedback/{feedback}/moderate', [AdminFeedbackController::class, 'moderate'])->name('feedback.moderate');

    // Moderation log
    Route::get('/moderation-logs', [AdminModerationLogController::class, 'index'])->name('moderation_logs.index');
});
