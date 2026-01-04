<?php

use App\Http\Controllers\ModerationWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/moderation/callback', [ModerationWebhookController::class, 'handle'])
    ->middleware('signed')
    ->name('moderation.callback');

