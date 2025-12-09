<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\LtiController;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

// LTI 1.3 Endpoints
Route::get('/lti/config.json', [LtiController::class, 'config'])->name('lti.config');
Route::get('/.well-known/jwks.json', [LtiController::class, 'jwks'])->name('lti.jwks');
Route::post('/lti/login', [LtiController::class, 'login'])->name('lti.login');
Route::post('/lti/launch', [LtiController::class, 'launch'])->name('lti.launch');
