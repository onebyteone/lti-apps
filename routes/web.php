<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\LtiController;
use App\Http\Controllers\ChatController;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

// LTI 1.1 Endpoints
Route::get('/lti/config.xml', [LtiController::class, 'config'])->name('lti.config');
Route::post('/lti/launch', [LtiController::class, 'launch'])->name('lti.launch');

// Chat API
Route::post('/api/chat', [ChatController::class, 'chat'])->name('api.chat');
