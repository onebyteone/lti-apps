<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\LtiController;
use App\Http\Controllers\ChatbotController;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

// LTI Launch
Route::post('/lti/launch', [LtiController::class, 'launch']);

// Chatbot Routes
Route::prefix('chatbot')->group(function () {
    Route::post('/conversation', [ChatbotController::class, 'getOrCreateConversation']);
    Route::post('/message', [ChatbotController::class, 'sendMessage']);
    Route::get('/conversation/{conversationId}', [ChatbotController::class, 'getHistory']);
    Route::post('/context', [ChatbotController::class, 'uploadCourseContext']);
});
